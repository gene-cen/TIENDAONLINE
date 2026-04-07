<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    // Credenciales
    private $host     = 'smtp.gmail.com';
    private $username = 'tiendaonlinecencocal@gmail.com';
    private $password = 'upue bdci ngpc tnum';
    private $port     = 587;
    private $fromName = 'Cencocal Tienda Online';

    // Imágenes Corporativas
    private $urlLogo   = 'https://www.cencocal.cl/to/logo.png';
    private $urlBanner = 'https://www.cencocal.cl/to/email_footer.png';

    // --- CONFIGURACIÓN TÉCNICA ---
    private function getConfiguredMailer()
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $this->host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->username;
        $mail->Password   = $this->password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $this->port;
        $mail->CharSet    = 'UTF-8';

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom($this->username, $this->fromName);
        return $mail;
    }

    // --- PLANTILLA HTML (Diseño Cencocal) ---
    private function getTemplate($titulo, $mensaje, $textoBoton, $linkBoton)
    {
        $colorIndigo = '#1a237e';
        $colorRojo   = '#d32f2f';
        $colorVerde  = '#2e7d32';
        $colorFondo  = '#f4f6f8';

        $bloqueBoton = '';
        if ($linkBoton) {
            $bloqueBoton = "
                <br>
                <a href='$linkBoton' class='btn'>$textoBoton</a>
                <p style='margin-top: 30px; font-size: 12px; color: #999;'>
                    Si el botón no funciona, copia este enlace: <a href='$linkBoton' style='color: $colorIndigo;'>$linkBoton</a>
                </p>";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background-color: $colorFondo; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
                .header { background-color: #ffffff; padding: 30px; text-align: center; border-bottom: 4px solid $colorVerde; }
                .content { padding: 40px 30px; text-align: center; color: #444; }
                .btn { display: inline-block; background-color: $colorRojo; color: #ffffff !important; padding: 12px 35px; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 16px; margin-top: 25px; box-shadow: 0 4px 6px rgba(211, 47, 47, 0.3); }
                .legal { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 11px; color: #888; border-top: 1px solid #eee; }
                img { max-width: 100%; height: auto; display: block; }
                strong { color: $colorIndigo; }
            </style>
        </head>
        <body>
            <div style='background-color: $colorFondo; padding: 40px 0;'>
                <div class='container'>
                    <div class='header'>
                        <img src='" . $this->urlLogo . "' alt='Cencocal' style='height: 60px; margin: 0 auto; display: block;'>
                    </div>
                    <div class='content'>
                        <h2 style='color: $colorIndigo; margin-top: 0; font-size: 24px;'>$titulo</h2>
                        <div style='font-size: 16px; line-height: 1.6;'>$mensaje</div>
                        $bloqueBoton
                    </div>
                    <div>
                        <img src='" . $this->urlBanner . "' alt='Contacto' style='width: 100%; display: block;'>
                    </div>
                    <div class='legal'>
                        &copy; " . date('Y') . " Cencocal S.A. - Todos los derechos reservados.<br>
                        Este es un correo automático, por favor no responder.
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    // ==============================================================================
    // MÉTODOS PÚBLICOS
    // ==============================================================================

    // 1. ACTIVACIÓN DE CUENTA
    public function enviarVerificacion($email, $nombre, $token)
    {
        try {
            $mail = $this->getConfiguredMailer();
            $mail->addAddress($email, $nombre);
            $mail->Subject = "Bienvenido a Cencocal - Activa tu cuenta";

            $link = BASE_URL . "auth/verificar?token=" . $token;
            $titulo = "¡Bienvenido a la familia!";
            $mensaje = "Hola <strong>$nombre</strong>,<br><br>Gracias por registrarte en nuestra Tienda Online. Estamos felices de tenerte aquí.<br>Para comenzar a comprar con los mejores precios, confirma tu correo haciendo clic abajo:";

            $mail->isHTML(true);
            $mail->Body = $this->getTemplate($titulo, $mensaje, "ACTIVAR MI CUENTA", $link);
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Error mail: " . $mail->ErrorInfo);
            return false;
        }
    }

    // 2. RECUPERACIÓN DE CONTRASEÑA
    public function enviarRecuperacion($email, $token)
    {
        try {
            $mail = $this->getConfiguredMailer();
            $mail->addAddress($email);
            $mail->Subject = "Restablecer Contraseña - Cencocal";

            $link = BASE_URL . "auth/reset?token=" . $token;
            $titulo = "¿Olvidaste tu contraseña?";
            $mensaje = "No te preocupes. Hemos recibido una solicitud para restablecer tu clave.<br>Si fuiste tú, haz clic en el botón para crear una nueva.";

            $mail->isHTML(true);
            $mail->Body = $this->getTemplate($titulo, $mensaje, "CAMBIAR CONTRASEÑA", $link);
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Error mail: " . $mail->ErrorInfo);
            return false;
        }
    }

    // 3. CONFIRMACIÓN DE COMPRA
    public function enviarConfirmacionCompra($email, $nombre, $pedidoId, $montoTotal, $tracking)
    {
        try {
            $mail = $this->getConfiguredMailer();
            $mail->addAddress($email, $nombre);
            $mail->Subject = "Confirmación de Pedido #$pedidoId - Cencocal";

            $montoFmt = number_format($montoTotal, 0, ',', '.');
            $link = BASE_URL . "perfil?tab=pedidos";

            $titulo = "¡Gracias por tu compra!";
            $mensaje = "
                Hola <strong>$nombre</strong>,<br><br>
                Tu pedido <strong>#$pedidoId</strong> ha sido recibido exitosamente.<br><br>
                
                <div style='background-color:#f8f9fa; padding:15px; border-radius:10px; margin: 20px 0;'>
                    <p style='margin:5px 0;'><strong>Monto Total:</strong> $$montoFmt</p>
                    <p style='margin:5px 0;'><strong>Código de Seguimiento:</strong> $tracking</p>
                </div>

                Estamos preparando tus productos para el despacho. Te avisaremos cuando vayan en camino.
            ";

            $mail->isHTML(true);
            $mail->Body = $this->getTemplate($titulo, $mensaje, "VER MIS PEDIDOS", $link);
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Error mail compra: " . $mail->ErrorInfo);
            return false;
        }
    }

    // 4. ACTUALIZACIÓN DE ESTADO (AHORA USA GETTEMPLATE)
    public function enviarActualizacionEstado($email, $nombreCliente, $idPedido, $nuevoEstado, $tracking)
    {
        try {
            $mail = $this->getConfiguredMailer();
            $mail->addAddress($email, $nombreCliente);
            $mail->Subject = "Actualización de tu Pedido #$idPedido - $nuevoEstado";

            // Enlace directo a la pestaña de pedidos
            $link = BASE_URL . "perfil?tab=pedidos";

            $titulo = "¡Tu pedido se mueve!";

            // Construimos el mensaje interno para pasarle a getTemplate
            $mensaje = "
                Hola <strong>$nombreCliente</strong>,<br><br>
                Queremos contarte que el estado de tu pedido <strong>#$idPedido</strong> se ha actualizado.<br><br>
                
                <div style='background-color:#f8f9fa; padding:20px; border-radius:10px; margin: 20px 0;'>
                    <p style='margin:0; font-size:12px; color:#666; text-transform:uppercase; letter-spacing:1px;'>Nuevo Estado</p>
                    <h2 style='color:#2e7d32; margin:10px 0 15px 0;'>$nuevoEstado</h2>
                    
                    <div style='border-top:1px dashed #ccc; padding-top:15px; margin-top:15px;'>
                        <p style='margin:0; font-size:14px;'><strong>Código de Seguimiento:</strong> $tracking</p>
                    </div>
                </div>

                Recuerda que puedes ver la fecha estimada de entrega detallada ingresando a tu cuenta.
            ";

            $mail->isHTML(true);
            // ¡AQUÍ ESTÁ LA CLAVE! Usamos getTemplate para mantener el diseño idéntico
            $mail->Body = $this->getTemplate($titulo, $mensaje, "VER DETALLE DEL PEDIDO", $link);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Error enviando actualización de estado: " . $mail->ErrorInfo);
            return false;
        }
    }

    // 5. CONFIRMACIÓN DE CAPTURA CON AJUSTE DE STOCK
    public function enviarAjusteStockCaptura($email, $nombre, $pedidoId, $montoOriginal, $montoFinal)
    {
        try {
            $mail = $this->getConfiguredMailer();
            $mail->addAddress($email, $nombre);
            $mail->Subject = "Ajuste de cobro por disponibilidad de stock - Pedido #$pedidoId";

            $montoOrigFmt = number_format($montoOriginal, 0, ',', '.');
            $montoFinalFmt = number_format($montoFinal, 0, ',', '.');
            $diferenciaFmt = number_format($montoOriginal - $montoFinal, 0, ',', '.');

            $link = BASE_URL . "perfil?tab=pedidos";

            $titulo = "Ajuste en tu cobro final";
            $mensaje = "
                Hola <strong>$nombre</strong>,<br><br>
                Al preparar tu pedido <strong>#$pedidoId</strong>, notamos que algunos productos ya no se encontraban disponibles en bodega.<br><br>
                Por este motivo, hemos ajustado a tu favor el cobro en tu método de pago:<br>
                
                <div style='background-color:#f8f9fa; padding:15px; border-radius:10px; margin: 20px 0; text-align:left; display:inline-block; border-left: 4px solid #2e7d32;'>
                    <p style='margin:5px 0;'><strong>Monto Original Autorizado:</strong> $$montoOrigFmt</p>
                    <p style='margin:5px 0; color:#d32f2f;'><strong>Ajuste por Productos Faltantes:</strong> -$$diferenciaFmt</p>
                    <p style='margin:10px 0 5px 0; font-size:18px; color:#2e7d32;'><strong>Monto Final Cobrado:</strong> $$montoFinalFmt</p>
                </div>
                <br><br>
                <strong>Nota Importante:</strong> En la aplicación de tu banco podrías ver una retención temporal por el monto original ($$montoOrigFmt), pero Cencocal S.A. solo ha cobrado definitivamente los <strong>$$montoFinalFmt</strong>. La diferencia será liberada de forma automática por tu institución financiera en los próximos días.
            ";

            $mail->isHTML(true);
            $mail->Body = $this->getTemplate($titulo, $mensaje, "VER DETALLE DEL PEDIDO", $link);
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Error enviando ajuste de captura: " . $mail->ErrorInfo);
            return false;
        }
    }
    // 6. CONFIRMACIÓN DE VENTA ASISTIDA EN TIENDA
    public function enviarConfirmacion($email, $pedidoId)
    {
        try {
            $mail = $this->getConfiguredMailer();
            $mail->addAddress($email);
            $mail->Subject = "Comprobante de Venta Asistida #$pedidoId - Cencocal";

            $link = BASE_URL;

            $titulo = "¡Venta Registrada en Tienda!";
            $mensaje = "
                Hola,<br><br>
                Se ha registrado exitosamente la Venta Asistida <strong>#$pedidoId</strong> en sucursal.<br><br>
                
                <div style='background-color:#f8f9fa; padding:15px; border-radius:10px; margin: 20px 0;'>
                    <p style='margin:5px 0;'><strong>N° de Orden:</strong> #$pedidoId</p>
                    <p style='margin:5px 0;'><strong>Modalidad:</strong> Pago Presencial en Tienda (Efectivo/Tarjeta)</p>
                </div>

                Los productos están listos para ser entregados al cliente o preparados para su despacho según lo acordado.
            ";

            $mail->isHTML(true);
            $mail->Body = $this->getTemplate($titulo, $mensaje, "IR A LA TIENDA", $link);
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Error enviando confirmación asistida: " . $mail->ErrorInfo);
            return false;
        }
    }
}
