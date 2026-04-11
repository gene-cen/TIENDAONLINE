<?php

namespace App\Controllers;

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
    /**
     * Paso 1: Generar el token y redirigir a Transbank
     */
    public function iniciar($idPedido = null)
    {
        // 1. Soportamos que el ID venga por segmento MVC o por parámetro $_GET
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
            // 2. USO SEGURO DE CREDENCIALES (Igual que en tu método de captura diferida)
            $commerceCode = getenv('WEBPAY_COMMERCE_CODE') ?: '597055555540';
            $apiKey       = getenv('WEBPAY_API_KEY') ?: '579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C';
            $envMode      = getenv('WEBPAY_ENVIRONMENT') ?: 'integration';

            $environment = ($envMode === 'production')
                ? \Transbank\Webpay\Options::ENVIRONMENT_PRODUCTION
                : \Transbank\Webpay\Options::ENVIRONMENT_INTEGRATION;

            $options = new \Transbank\Webpay\Options($apiKey, $commerceCode, $environment);
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
            // 4. SI ALGO FALLA, FRENAMOS TODO Y MOSTRAMOS EL ERROR REAL
            die("<div style='padding: 20px; border: 2px solid red; font-family: sans-serif;'>
                    <h3 style='color:red;'>Falló la conexión con Transbank o la BD</h3>
                    <p><strong>Detalle Técnico:</strong> " . $e->getMessage() . "</p>
                 </div>");
        }
    }

    public function capturarMontoFinal($pedidoId, $monto)
    {
        try {
            // 1. Buscamos el token y la orden de compra en tu DB
            $stmt = $this->db->prepare("SELECT token, buy_order FROM transacciones_webpay WHERE pedido_id = ? AND status = 'authorized' LIMIT 1");
            $stmt->execute([$pedidoId]);
            $tx = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$tx) {
                return ['status' => false, 'msg' => 'No se encontró una transacción autorizada para este pedido.'];
            }

            // =================================================================================
            // --- CONFIGURACIÓN DINÁMICA DESDE .ENV ---
            // =================================================================================

            // Leemos las variables de tu archivo .env
            $commerceCode = getenv('WEBPAY_COMMERCE_CODE') ?: '597055555540';
            $apiKey       = getenv('WEBPAY_API_KEY') ?: '579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C';
            $envMode      = getenv('WEBPAY_ENVIRONMENT') ?: 'integration';

            /** * MODO PRODUCCIÓN (Referencia rápida):
             * Cuando pases a real, solo debes asegurar que en tu .env diga:
             * WEBPAY_ENVIRONMENT=production
             */
            $environment = ($envMode === 'production')
                ? \Transbank\Webpay\Options::ENVIRONMENT_PRODUCTION
                : \Transbank\Webpay\Options::ENVIRONMENT_INTEGRATION;

            // Creamos el objeto de opciones con tus credenciales
            $options = new \Transbank\Webpay\Options($apiKey, $commerceCode, $environment);

            // Instanciamos la transacción con las opciones configuradas
            $transaction = new \Transbank\Webpay\WebpayPlus\Transaction($options);
            // =================================================================================

            // 2. Obtenemos el authorization_code (requerido para la captura)
            $statusResponse = $transaction->status($tx['token']);
            $authCode = $statusResponse->getAuthorizationCode();

            // 3. Ejecutamos la captura real en los servidores de Transbank
            $response = $transaction->capture(
                $tx['token'],
                $tx['buy_order'],
                $authCode,
                $monto
            );

            // 4. Validamos el éxito (responseCode 0 = Aprobado por Transbank)
            if ($response->getResponseCode() === 0) {
                $up = $this->db->prepare("UPDATE transacciones_webpay SET status = 'captured', amount = ? WHERE token = ?");
                $up->execute([$monto, $tx['token']]);

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

            // SELECT ADAPTADO A TU TABLA ACTUAL (token_ws)
            $stmt = $this->db->prepare("SELECT pedido_id FROM transacciones_webpay WHERE token_ws = ? LIMIT 1");
            $stmt->execute([$token]);
            $transaccion = $stmt->fetch(\PDO::FETCH_ASSOC);
            $idPedido = $transaccion['pedido_id'] ?? null;

            if ($result->isApproved()) {
                if ($idPedido) {
                    $this->db->beginTransaction();

                    // 1. Estado de la orden (Logístico)
                    $this->pedidoModel->actualizarEstado($idPedido, 2);

                    // 2. CAMBIO CLAVE: Estado del pago (2 = Pagado)
                    // Asegúrate que en tu tabla 'estados_pago' el ID 2 sea 'Pagado'
                    $this->pedidoModel->actualizarEstadoPago($idPedido, 2);

                    $this->pedidoModel->registrarHistorial($idPedido, 2, 'Pago aprobado vía Webpay Plus.');

                    // --- MAGIA: RESERVAMOS EL STOCK PARA EVITAR SOBREVENTAS ---
                    $this->pedidoModel->reservarStock($idPedido);
                    // -----------------------------------------------------------

                    // UPDATE ADAPTADO A TU TABLA ACTUAL
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

                // Vaciamos el carrito porque la compra fue un éxito
                if (isset($_SESSION['carrito'])) {
                    unset($_SESSION['carrito']);
                }

                // Redirigimos a la hermosa vista de éxito
                header("Location: " . BASE_URL . "pedido/exito?id=" . $idPedido);
                exit();
            } else {
                // ... (el resto del código de pago rechazado sigue igual) ...
                // Pago rechazado
                if ($idPedido) {
                    // UPDATE ADAPTADO A TU TABLA ACTUAL
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
    public function capturarPagoAdmin($idPedido, $montoFinalCobrar)
    {
        // Obtenemos la transacción autorizada
        $stmt = $this->db->prepare("SELECT * FROM transacciones_webpay WHERE pedido_id = ? AND status = 'autorizado' LIMIT 1");
        $stmt->execute([$idPedido]);
        $transaccion = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$transaccion) {
            return false; // Transacción no encontrada o no está en estado autorizado
        }

        $token = $transaccion['token_ws'];
        $buy_order = "ORD-" . $idPedido;
        $authorization_code = $transaccion['authorization_code'];

        // $montoOriginal es lo que el banco retuvo al inicio (Ej: $5.060)
        $montoOriginal = (int)$transaccion['amount'];

        // $montoDeseado es lo que pedimos capturar (Ej: $5.060)
        $montoDeseado = (int)round($montoFinalCobrar);

        // Webpay NO puede capturar más de lo autorizado originalmente, topamos el valor por seguridad
        $montoCaptura = min($montoOriginal, $montoDeseado);

        try {
            $options = new Options(
                $_ENV['WEBPAY_API_KEY'],
                $_ENV['WEBPAY_COMMERCE_CODE'],
                $_ENV['WEBPAY_ENVIRONMENT']
            );
            $tx = new Transaction($options);

            // Ejecutamos la captura en Transbank
            $response = $tx->capture($token, $buy_order, $authorization_code, $montoCaptura);

            if ($response->isApproved()) {

                // Iniciamos transacción para asegurar consistencia en BD
                $this->db->beginTransaction();

                // 1. Actualizamos la transacción en la BD local indicando lo que REALMENTE se cobró en el banco
                $updateWs = "UPDATE transacciones_webpay SET status = 'capturado', amount = ? WHERE token_ws = ?";
                $this->db->prepare($updateWs)->execute([$montoCaptura, $token]);

                // --- NUEVO: ACTUALIZAMOS EL PAGO Y AVANZAMOS LA LOGÍSTICA ---
                $estadoPagoCapturado = 3; // 3 = Capturado
                $estadoLogisticoPreparacion = 3; // 3 = En Preparación

                $updatePedido = "UPDATE pedidos SET estado_pago_id = ?, estado_pedido_id = ? WHERE id = ?";
                $this->db->prepare($updatePedido)->execute([$estadoPagoCapturado, $estadoLogisticoPreparacion, $idPedido]);

                // Dejamos el rastro en el historial para que se vea en la línea de tiempo
                $this->pedidoModel->registrarHistorial($idPedido, $estadoLogisticoPreparacion, "Pago Webpay CAPTURADO ($" . number_format($montoCaptura, 0, ',', '.') . "). Avanza a preparación automáticamente.");
                // ------------------------------------------------------------

                $this->db->commit();

                // 2. Notificamos al cliente SOLO si hubo una rebaja a su favor
                if ($montoCaptura < $montoOriginal) {
                    $pedido = $this->pedidoModel->obtenerPorId($idPedido);
                    $mailService = new \App\Services\MailService();
                    $mailService->enviarAjusteStockCaptura(
                        $pedido['email_cliente'],
                        $pedido['nombre_cliente'],
                        $idPedido,
                        $montoOriginal,
                        $montoCaptura
                    );
                }

                return true;
            }
            return false;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en Captura Diferida Webpay: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Paso 4: Reembolso Total (Anulación de Venta)
     */
    public function reembolsarPagoAdmin($idPedido)
    {
        // Buscamos la transacción (puede estar autorizada o ya capturada)
        $stmt = $this->db->prepare("SELECT * FROM transacciones_webpay WHERE pedido_id = ? AND status IN ('autorizado', 'capturado') LIMIT 1");
        $stmt->execute([$idPedido]);
        $transaccion = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$transaccion) {
            return ['status' => false, 'msg' => 'No se encontró una transacción válida para reembolsar.'];
        }

        $token = $transaccion['token_ws'];
        $montoAReembolsar = (int)$transaccion['amount']; // Se devuelve exactamente lo que se cobró

        try {
            $options = new Options(
                $_ENV['WEBPAY_API_KEY'],
                $_ENV['WEBPAY_COMMERCE_CODE'],
                $_ENV['WEBPAY_ENVIRONMENT']
            );
            $tx = new Transaction($options);

            // Ejecutamos el reembolso en Transbank
            $response = $tx->refund($token, $montoAReembolsar);

            // Transbank responde con 'NULLIFIED' (Anulado) o 'REVERSED' (Reversado)
            if ($response->getType() === 'NULLIFIED' || $response->getType() === 'REVERSED') {
                // Actualizamos la base de datos local
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
