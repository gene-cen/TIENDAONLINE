<?php

namespace App\Controllers;

// 1. VOLVEMOS A LA CLASE CORRECTA: Transaction
use Transbank\Webpay\WebpayPlus\Transaction;
use Transbank\Webpay\Options;
use App\Models\Pedido;

class WebpayController
{
    private $db;
    private $pedidoModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->pedidoModel = new Pedido($db);
    }

    /**
     * Paso 1: Generar el token y redirigir a Transbank
     */
    public function iniciar($idPedido = null)
    {
        $idPedido = $idPedido ?? $_GET['id'] ?? null;

        if (!$idPedido) {
            die("<h3 style='color:red;'>Error Backend: No se recibió el ID del pedido para ir a Webpay.</h3>");
        }

        $pedido = $this->pedidoModel->obtenerPorId($idPedido);

        if (!$pedido) {
            die("<h3 style='color:red;'>Error Backend: El pedido #$idPedido no existe en la BD.</h3>");
        }

        $session_id = session_id();
        $buy_order = "ORD-" . $idPedido;
        $amount = (int)round($pedido['monto_total']);
        $return_url = BASE_URL . "webpay/confirmar";

        try {
            $commerceCode = getenv('WEBPAY_COMMERCE_CODE') ?: '597055555540';
            $apiKey       = getenv('WEBPAY_API_KEY') ?: '579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C';
            $envMode      = getenv('WEBPAY_ENVIRONMENT') ?: 'integration';

            $environment = ($envMode === 'production')
                ? \Transbank\Webpay\Options::ENVIRONMENT_PRODUCTION
                : \Transbank\Webpay\Options::ENVIRONMENT_INTEGRATION;

            $options = new \Transbank\Webpay\Options($apiKey, $commerceCode, $environment);
            
            // Usamos Transaction normalmente
            $tx = new \Transbank\Webpay\WebpayPlus\Transaction($options);

            $response = $tx->create($buy_order, $session_id, $amount, $return_url);

            // 3. REGISTRO EN LA BASE DE DATOS
            $sql = "INSERT INTO transacciones_webpay (pedido_id, token_ws, amount, status) 
                    VALUES (?, ?, ?, 'inicializada')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idPedido, $response->getToken(), $amount]);

            // Formulario auto-ejecutable para ir a Webpay
            echo '<form action="' . $response->getUrl() . '" method="POST" id="webpay-form" style="display:none;">
                    <input type="hidden" name="token_ws" value="' . $response->getToken() . '" />
                  </form>
                  <div style="text-align:center;margin-top:100px;font-family:sans-serif;">
                    <h3 style="color:#283593;">Conectando con Webpay...</h3>
                    <p>Por favor no cierres esta ventana.</p>
                  </div>
                  <script>document.getElementById("webpay-form").submit();</script>';
            exit();
        } catch (\Exception $e) {
            die("<div style='padding: 20px; border: 2px solid red; font-family: sans-serif;'>
                    <h3 style='color:red;'>Falló la conexión con Transbank o la BD</h3>
                    <p><strong>Detalle Técnico:</strong> " . $e->getMessage() . "</p>
                 </div>");
        }
    }

    /**
     * Paso 2: Recibir la respuesta de Transbank y confirmar el pago
     */
    public function confirmar()
    {
        $token = $_POST['token_ws'] ?? $_GET['token_ws'] ?? null;

        if (!$token) {
            header("Location: " . BASE_URL . "checkout?msg=pago_anulado_usuario");
            exit();
        }

        try {
            $options = new Options(
                $_ENV['WEBPAY_API_KEY'],
                $_ENV['WEBPAY_COMMERCE_CODE'],
                $_ENV['WEBPAY_ENVIRONMENT']
            );
            
            $tx = new Transaction($options);

            // Confirmamos la transacción con Transbank
            $result = $tx->commit($token);

            $stmt = $this->db->prepare("SELECT pedido_id FROM transacciones_webpay WHERE token_ws = ? LIMIT 1");
            $stmt->execute([$token]);
            $transaccion = $stmt->fetch(\PDO::FETCH_ASSOC);
            $idPedido = $transaccion['pedido_id'] ?? null;

            if ($result->isApproved()) {
                if ($idPedido) {
                    $this->db->beginTransaction();

                    $this->pedidoModel->actualizarEstado($idPedido, 2);
                    $this->pedidoModel->actualizarEstadoPago($idPedido, 2);
                    $this->pedidoModel->registrarHistorial($idPedido, 2, 'Pago retenido vía Webpay Plus Diferido (Autorizado).');

                    // RESERVAMOS EL STOCK
                    $this->pedidoModel->reservarStock($idPedido);

                    $update = "UPDATE transacciones_webpay 
                               SET status = 'autorizado', 
                                   authorization_code = ?, 
                                   response_code = ? 
                               WHERE token_ws = ?";
                    $this->db->prepare($update)->execute([
                        $result->getAuthorizationCode(),
                        $result->getResponseCode(),
                        $token
                    ]);

                    $this->db->commit();
                }

                if (isset($_SESSION['carrito'])) {
                    unset($_SESSION['carrito']);
                }

                header("Location: " . BASE_URL . "pedido/exito?id=" . $idPedido);
                exit();
            } else {
                if ($idPedido) {
                    $update = "UPDATE transacciones_webpay 
                               SET status = 'rechazado', 
                                   response_code = ? 
                               WHERE token_ws = ?";
                    $this->db->prepare($update)->execute([$result->getResponseCode(), $token]);

                    $this->pedidoModel->registrarHistorial($idPedido, 1, 'Intento de pago rechazado por el banco.');
                }

                header("Location: " . BASE_URL . "checkout?msg=pago_rechazado_banco");
                exit();
            }
        } catch (\Exception $e) {
            error_log("Error en Confirmación Webpay: " . $e->getMessage());
            header("Location: " . BASE_URL . "checkout?msg=error_tecnico_webpay");
            exit();
        }
    }

    /**
     * Paso 3: Captura Diferida (Llamado por el Administrador al confirmar stock o ediciones)
     */
    public function capturarMontoFinal($pedidoId, $monto)
    {
        try {
            $stmt = $this->db->prepare("SELECT token_ws, authorization_code, amount FROM transacciones_webpay WHERE pedido_id = ? AND status = 'autorizado' LIMIT 1");
            $stmt->execute([$pedidoId]);
            $tx = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$tx) {
                return ['status' => false, 'msg' => 'No se encontró una transacción autorizada en la BD.'];
            }

            $commerceCode = getenv('WEBPAY_COMMERCE_CODE') ?: '597055555540';
            $apiKey       = getenv('WEBPAY_API_KEY') ?: '579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C';
            $envMode      = getenv('WEBPAY_ENVIRONMENT') ?: 'integration';

            $environment = ($envMode === 'production')
                ? \Transbank\Webpay\Options::ENVIRONMENT_PRODUCTION
                : \Transbank\Webpay\Options::ENVIRONMENT_INTEGRATION;

            $options = new \Transbank\Webpay\Options($apiKey, $commerceCode, $environment);
            
            $transaction = new \Transbank\Webpay\WebpayPlus\Transaction($options);

            // 🔥 EL ARREGLO ESTÁ AQUÍ: Reconstruimos el buy_order manualmente
            // Ya que en iniciar() no lo insertas en la tabla.
            $buy_order = "ORD-" . $pedidoId;

            // Topamos el monto por seguridad para evitar rechazos del banco
            $montoOriginal = (int)$tx['amount'];
            $montoDeseado = (int)round($monto);
            $montoCaptura = min($montoOriginal, $montoDeseado);

            // Ejecutamos la captura
            $response = $transaction->capture(
                $tx['token_ws'],
                $buy_order,
                $tx['authorization_code'],
                $montoCaptura
            );

            // Validamos que ResponseCode sea 0 (aprobado para capturas)
            if ($response->getResponseCode() == 0) {
                $up = $this->db->prepare("UPDATE transacciones_webpay SET status = 'capturado', amount = ? WHERE token_ws = ?");
                $up->execute([$montoCaptura, $tx['token_ws']]);

                return ['status' => true, 'msg' => 'Captura exitosa'];
            } else {
                return [
                    'status' => false,
                    'msg' => 'Transbank rechazó la captura. Código: ' . $response->getResponseCode()
                ];
            }
        } catch (\Exception $e) {
            error_log("Error Crítico Captura Webpay: " . $e->getMessage());
            return ['status' => false, 'msg' => 'Error en Webpay: ' . $e->getMessage()];
        }
    }

    /**
     * Paso 3.1: Método secundario de captura admin (Mapeado a capturarMontoFinal para evitar duplicidad)
     */
    public function capturarPagoAdmin($idPedido, $montoFinalCobrar)
    {
        $res = $this->capturarMontoFinal($idPedido, $montoFinalCobrar);
        
        if ($res['status'] === true) {
            // Actualizamos la logística local si se usa este método directamente
            $estadoPagoCapturado = 3; 
            $estadoLogisticoPreparacion = 3;

            $this->db->beginTransaction();
            $updatePedido = "UPDATE pedidos SET estado_pago_id = ?, estado_pedido_id = ? WHERE id = ?";
            $this->db->prepare($updatePedido)->execute([$estadoPagoCapturado, $estadoLogisticoPreparacion, $idPedido]);
            
            $this->pedidoModel->registrarHistorial($idPedido, $estadoLogisticoPreparacion, "Pago Webpay CAPTURADO. Avanza a preparación automáticamente.");
            $this->db->commit();
            return true;
        }
        return false;
    }

    /**
     * Paso 4: Reembolso Total (Anulación de Venta)
     */
    public function reembolsarPagoAdmin($idPedido)
    {
        $stmt = $this->db->prepare("SELECT * FROM transacciones_webpay WHERE pedido_id = ? AND status IN ('autorizado', 'capturado') LIMIT 1");
        $stmt->execute([$idPedido]);
        $transaccion = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$transaccion) {
            return ['status' => false, 'msg' => 'No se encontró una transacción válida para reembolsar.'];
        }

        $token = $transaccion['token_ws'];
        $montoAReembolsar = (int)$transaccion['amount']; 

        try {
            $options = new Options(
                $_ENV['WEBPAY_API_KEY'],
                $_ENV['WEBPAY_COMMERCE_CODE'],
                $_ENV['WEBPAY_ENVIRONMENT']
            );
            
            $tx = new Transaction($options);

            $response = $tx->refund($token, $montoAReembolsar);

            if ($response->getType() === 'NULLIFIED' || $response->getType() === 'REVERSED') {
                $update = "UPDATE transacciones_webpay SET status = 'reembolsado' WHERE token_ws = ?";
                $this->db->prepare($update)->execute([$token]);

                return ['status' => true];
            }

            return ['status' => false, 'msg' => 'El banco rechazó la anulación en línea.'];
        } catch (\Exception $e) {
            error_log("Error en Reembolso Webpay: " . $e->getMessage());
            return ['status' => false, 'msg' => 'Error de conexión con Transbank: ' . $e->getMessage()];
        }
    }
}