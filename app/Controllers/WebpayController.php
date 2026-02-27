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
    public function iniciar($idPedido)
    {
        $pedido = $this->pedidoModel->obtenerPorId($idPedido);

        if (!$pedido) {
            header("Location: " . BASE_URL . "checkout?error=pedido_no_encontrado");
            exit();
        }

        $session_id = session_id();
        $buy_order = "ORD-" . $idPedido;
        // Transbank requiere que el monto sea entero
        $amount = (int)round($pedido['monto_total']);
        $return_url = BASE_URL . "webpay/confirmar";

        try {
            // Configuración con credenciales del .env
            $options = new Options(
                $_ENV['WEBPAY_API_KEY'],
                $_ENV['WEBPAY_COMMERCE_CODE'],
                $_ENV['WEBPAY_ENVIRONMENT']
            );
            $tx = new Transaction($options);

            $response = $tx->create($buy_order, $session_id, $amount, $return_url);

            // INSERT ADAPTADO A TU TABLA ACTUAL
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
            error_log("Error inicializando Webpay: " . $e->getMessage());
            header("Location: " . BASE_URL . "checkout?msg=error_webpay_init");
            exit();
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

                    $this->pedidoModel->actualizarEstado($idPedido, 2); // 2 = Pagado
                    $this->pedidoModel->registrarHistorial($idPedido, 2, 'Pago aprobado vía Webpay Plus. Orden: ' . $result->getBuyOrder());

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

                header("Location: " . BASE_URL . "perfil?msg=pago_exitoso&orden=" . $result->getBuyOrder());
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
}
