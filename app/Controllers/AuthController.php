<?php

namespace App\Controllers;

use App\Models\Usuario;
use App\Services\MailService;
use Google\Client as GoogleClient;
use PDOException;
use Exception;

/**
 * ARCHIVO: AuthController.php
 * Descripción: Maneja el ciclo de vida de la identidad del usuario con la base de datos normalizada.
 */
class AuthController
{
    private $userModel;
    private $googleClient;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new Usuario($db);
        $this->initGoogleClient();
    }

    private function initGoogleClient()
    {
        $this->googleClient = new GoogleClient();
        $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID');
        $clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET');

        $this->googleClient->setClientId($clientId);
        $this->googleClient->setClientSecret($clientSecret);
        $this->googleClient->setRedirectUri(BASE_URL . "auth/google-callback");
        $this->googleClient->addScope("email");
        $this->googleClient->addScope("profile");
    }

    // =========================================================
    // 🚪 SECCIÓN 1: LOGIN
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $redirect = $_GET['redirect'] ?? 'home';

            $user = $this->userModel->login($email, $password);

            // Detectamos si es AJAX por el campo oculto o por las cabeceras
            $isAjax = isset($_POST['is_ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
            if ($user) {
                $this->setSession($user);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    // Verificamos si la ruta ya tiene un '?' para no romper la URL
                    $separador = (strpos($redirect, '?') !== false) ? '&' : '?';
                    echo json_encode([
                        'status' => 'success',
                        'redirect' => BASE_URL . $redirect . $separador . 'msg=login_exito'
                    ]);
                    exit();
                }
                header("Location: " . BASE_URL . $redirect . "?msg=login_exito");
            } else {
                if ($isAjax) {
                    header('Content-Type: application/json'); // Obligamos a responder en JSON
                    echo json_encode(['status' => 'error']);
                    exit();
                }
                header("Location: " . BASE_URL . "home?msg=login_error");
            }
            exit();
        }
    }
    public function googleLogin()
    {
        header("Location: " . $this->googleClient->createAuthUrl());
        exit();
    }

    public function googleCallback()
    {
        if (isset($_GET['code'])) {
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
            $this->googleClient->setAccessToken($token);

            $googleService = new \Google\Service\Oauth2($this->googleClient);
            $data = $googleService->userinfo->get();

            $user = $this->userModel->getByEmail($data->email);

            if (!$user) {
                // Registro automático Google (Limpio de campos borrados)
                $userData = [
                    'nombre'    => $data->name,
                    'email'     => $data->email,
                    'password'  => password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT),
                    'rol_id'    => 6, // Cliente por defecto
                    'google_id' => $data->id
                ];
                $res = $this->userModel->crear($userData);
                $user = $this->userModel->getById($res['id']);
            }

            $this->setSession($user);
            header("Location: " . BASE_URL . "home");
            exit();
        }
    }

    public function logout()
    {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        header("Location: " . BASE_URL . "home?msg=logout_exito");
        exit();
    }

    // =========================================================
    // 📝 SECCIÓN 2: REGISTRO
    // =========================================================

public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'] ?? '';
            $pass  = $_POST['password'] ?? '';
            $rut   = $_POST['rut'] ?? '';

            // 🛡️ CANDADO 1: Validar longitud de la contraseña
            if (strlen($pass) < 6) {
                header("Location: " . BASE_URL . "home?msg=error_password_corta");
                exit();
            }

            // 🛡️ CANDADO 2: Validar correo duplicado
            if ($this->userModel->getByEmail($email)) {
                header("Location: " . BASE_URL . "home?msg=error_email_duplicado");
                exit();
            }

            // 🛡️ CANDADO 3: Validar RUT duplicado (Nuevo)
            // Asumiendo que tienes un método getByRut en tu modelo
            if (method_exists($this->userModel, 'getByRut') && $this->userModel->getByRut($rut)) {
                header("Location: " . BASE_URL . "home?msg=error_rut_duplicado");
                exit();
            }

            // Limpieza de teléfono (quitamos prefijos y espacios)
            $telRaw = str_replace(['+569', ' ', '+'], '', $_POST['telefono'] ?? '');

            // Preparar datos básicos del usuario
            $datos = [
                'nombre'       => $_POST['nombre'] ?? '',
                'apellido'     => $_POST['apellido'] ?? '', // No olvides el apellido si lo tienes en el form
                'rut'          => $rut,
                'email'        => $email,
                'telefono'     => "+569" . $telRaw,
                'password'     => password_hash($pass, PASSWORD_DEFAULT),
                'giro'         => $_POST['giro'] ?? '',
                'rol_id'       => 6 // Cliente por defecto
            ];

            // 1. Crear el usuario
            $resultado = $this->userModel->crear($datos);

            if (isset($resultado['id']) && $resultado['id']) {
                $userId = $resultado['id'];

                // 📍 LÓGICA DE DIRECCIÓN OPCIONAL
                // Verificamos si el switch 'quiere_direccion' fue activado
                if (isset($_POST['quiere_direccion']) && !empty($_POST['comuna'])) {
                    $direccion = [
                        'usuario_id'   => $userId,
                        'region'       => 'Valparaíso', // Fija como pediste
                        'comuna'       => $_POST['comuna'] ?? '',
                        'calle'        => $_POST['calle'] ?? '',
                        'numero'       => $_POST['numero'] ?? '',
                        'latitud'      => $_POST['latitud'] ?? null,
                        'longitud'     => $_POST['longitud'] ?? null,
                        'es_principal' => 1
                    ];
                    
                    // Guardamos la dirección (Asegúrate de tener este método en tu modelo)
                    if (method_exists($this->userModel, 'guardarDireccion')) {
                        $this->userModel->guardarDireccion($direccion);
                    }
                }

                // 📧 Envío de correo de verificación
                $mailService = new \App\Services\MailService();
                $enviado = $mailService->enviarVerificacion($email, $datos['nombre'], $resultado['token']);

                $msg = $enviado ? "registro_exito" : "registro_exito_sin_correo";
                header("Location: " . BASE_URL . "home?msg=" . $msg);
                
            } else {
                header("Location: " . BASE_URL . "home?msg=error_db");
            }
            exit();
        }
    }

    // =========================================================
    // 🛡️ SECCIÓN: VERIFICACIÓN Y RECUPERACIÓN DE CLAVE
    // =========================================================

    public function verificar()
    {
        $token = $_GET['token'] ?? null;

        if ($token) {
            // Buscamos al usuario que tenga este token de verificación
            // (Asegúrate de que la columna en tu BD se llame 'token')
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user) {
                // Si existe, marcamos su correo como verificado y vaciamos el token
                $update = $this->db->prepare("UPDATE usuarios SET email_verificado = 1, token = NULL WHERE id = ?");
                $update->execute([$user['id']]);

                header("Location: " . BASE_URL . "home?msg=verificacion_exito");
                exit();
            }
        }

        // Si el token no existe o ya fue usado
        header("Location: " . BASE_URL . "home?msg=error_token_invalido");
        exit();
    }

    public function forgot()
    {
        // Esta solo muestra la vista. Asumo que tu vista tiene el formulario 
        // que apunta a action="BASE_URL/auth/send-recovery"
        $titulo = "Recuperar Contraseña";
        require __DIR__ . '/../../views/auth/forgot.php';
    }

    public function reset()
    {
        $token = $_GET['token'] ?? null;

        if (!$token) {
            header("Location: " . BASE_URL . "home?msg=error_token_invalido");
            exit();
        }

        // Verificamos que el token de recuperación exista en la BD
        $stmt = $this->db->prepare("SELECT id, email FROM usuarios WHERE token_recuperacion = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            header("Location: " . BASE_URL . "home?msg=error_token_invalido");
            exit();
        }

        // Si el token es válido, mostramos la vista para escribir la nueva clave
        $titulo = "Crear Nueva Contraseña";
        require __DIR__ . '/../../views/auth/reset.php';
    }

    public function updatePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $token = $_POST['token'] ?? '';
            $password = $_POST['password'] ?? '';

            // 1. Candado de seguridad: Mínimo 6 caracteres
            if (strlen($password) < 6) {
                header("Location: " . BASE_URL . "auth/reset?token=" . urlencode($token) . "&msg=error_password_corta");
                exit();
            }

            // 2. Buscamos al usuario por el token
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE token_recuperacion = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user) {
                // 3. Encriptamos la nueva clave y limpiamos el token para que no se pueda reusar
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $update = $this->db->prepare("UPDATE usuarios SET password = ?, token_recuperacion = NULL WHERE id = ?");
                $update->execute([$hash, $user['id']]);

                header("Location: " . BASE_URL . "home?msg=password_actualizada");
            } else {
                header("Location: " . BASE_URL . "home?msg=error_token_invalido");
            }
            exit();
        }
    }
    // =========================================================
    // 📨 SECCIÓN 3: RECUPERAR CONTRASEÑA
    // =========================================================
    public function sendRecovery()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'] ?? '';

            // 1. Buscamos si el usuario existe
            $user = $this->userModel->getByEmail($email);

            if ($user) {
                // 2. Generamos un token seguro
                $token = bin2hex(random_bytes(32));

                try {
                    // 3. Guardamos el token en la base de datos
                    // (Nota: Si tu columna se llama solo 'token', cambia 'token_recuperacion' por 'token')
                    $stmt = $this->db->prepare("UPDATE usuarios SET token_recuperacion = ? WHERE email = ?");
                    $stmt->execute([$token, $email]);

                    // 4. Enviamos el correo con tu MailService
                    $mailService = new \App\Services\MailService();
                    if (method_exists($mailService, 'enviarRecuperacion')) {
                        $mailService->enviarRecuperacion($email, $user['nombre'] ?? 'Cliente', $token);
                    }
                } catch (\Exception $e) {
                    error_log("Error en recuperación de clave: " . $e->getMessage());
                }
            }

            // 5. Redirigimos siempre con éxito (Por seguridad anti-hackers, nunca se dice si el correo NO existe)
            header("Location: " . BASE_URL . "home?msg=recovery_enviado");
            exit();
        }
    }

    // =========================================================
    // 🕵️ SECCIÓN 4: UTILIDADES SII Y MAPAS (SIN CAMBIOS)
    // =========================================================

    public function consultarSii()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $rut = preg_replace('/[^0-9kK]/', '', $_GET['rut'] ?? '');
        if (strlen($rut) < 8) {
            echo json_encode(['success' => false]);
            exit;
        }

        $ch = curl_init("https://api.libreapi.cl/rut/activities?rut=" . $rut);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 2]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && $res) {
            $data = json_decode($res, true);
            if (isset($data['data'])) {
                echo json_encode(['success' => true, 'razon_social' => $data['data']['name'], 'giro' => $data['data']['activities'][0]['activity_name'] ?? '', 'origen' => 'API']);
                exit;
            }
        }
        echo json_encode(['success' => true, 'razon_social' => 'EMPRESA MANUAL', 'giro' => 'VENTA DE INSUMOS', 'origen' => 'OFFLINE']);
        exit;
    }

    public function geolocalizar()
    { /* ... igual ... */
    }

    // =========================================================
    // 🛒 SECCIÓN 5: INVITADOS Y SESIONES (AJUSTADO)
    // =========================================================

    public function guestLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tel = str_replace(['+569', ' ', '+'], '', $_POST['guest_telefono'] ?? '');
            $_SESSION['invitado'] = [
                'nombre'   => trim($_POST['guest_nombre'] ?? 'Invitado'),
                'email'    => trim($_POST['guest_email'] ?? ''),
                'rut'      => trim($_POST['guest_rut'] ?? ''),
                'telefono' => "+569" . $tel
            ];
            session_write_close();
            header("Location: " . BASE_URL . "checkout");
            exit();
        }
    }

    private function setSession($user)
    {
        $_SESSION['user_id']     = $user->id;
        $_SESSION['user_nombre'] = $user->nombre;
        $_SESSION['user_email']  = $user->email;

        // 🔥 NUEVA LÓGICA DE ROLES NUMÉRICOS
        $_SESSION['rol_id']      = $user->rol_id;
        $_SESSION['rol_nombre']  = $user->nombre_rol ?? 'Cliente';

        // Validamos si es Admin (1=SuperAdmin, 2=Admin Sucursal)
        if (in_array($user->rol_id, [1, 2])) {
            $_SESSION['admin_sucursal'] = $user->sucursal_admin_id ?? null;
        }

        // Sucursal por defecto (Buscando dirección principal si existe)
        $suc_asignada = 29;
        try {
            // Buscamos la comuna en su dirección principal
            $stmt = $this->db->prepare("SELECT d.comuna_id FROM direcciones_usuarios d WHERE d.usuario_id = ? AND d.es_principal = 1");
            $stmt->execute([$user->id]);
            $comuna_id = $stmt->fetchColumn();

            if ($comuna_id) {
                $stmtS = $this->db->prepare("SELECT sucursal_id FROM comunas WHERE id = ?");
                $stmtS->execute([$comuna_id]);
                $found = $stmtS->fetchColumn();
                if ($found) $suc_asignada = $found;
            }
        } catch (PDOException $e) {
            error_log("Error sucursal sesión: " . $e->getMessage());
        }

        $_SESSION['sucursal_activa'] = $suc_asignada;
        $_SESSION['comuna_nombre'] = ($suc_asignada == 10) ? 'Villa Alemana' : 'La Calera';
    }
    
    public function checkDuplicate()
{
    header('Content-Type: application/json');
    $campo = $_GET['campo'] ?? '';
    $valor = trim($_GET['valor'] ?? '');

    if (empty($valor)) {
        echo json_encode(['existe' => false]);
        exit;
    }

    try {
        if ($campo === 'rut') {
            $valorLimpio = preg_replace('/[^0-9kK]/', '', $valor);
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE REPLACE(REPLACE(rut, '.', ''), '-', '') = ? LIMIT 1");
            $stmt->execute([$valorLimpio]);
        } 
        elseif ($campo === 'telefono') {
            // 1. Limpiamos el valor que viene (quitamos todo lo que no sea número)
            $soloNumeros = preg_replace('/\D/', '', $valor);
            
            // 2. Si el usuario escribió el +569 o el 9, nos quedamos solo con los últimos 8 dígitos
            // (que es lo que realmente identifica al número en Chile)
            $fragmentoBusqueda = (strlen($soloNumeros) > 8) ? substr($soloNumeros, -8) : $soloNumeros;

            // 3. ¡IMPORTANTE! Según tu SQL, los teléfonos están en 'usuario_telefonos'
            // Buscamos en esa tabla limpiando el campo 'numero' de cualquier signo
            $stmt = $this->db->prepare("SELECT id FROM usuario_telefonos WHERE REPLACE(REPLACE(numero, '+', ''), ' ', '') LIKE ? LIMIT 1");
            $stmt->execute(["%$fragmentoBusqueda"]);
        } 
        else {
            // Para el email, comparación directa
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE $campo = ? LIMIT 1");
            $stmt->execute([$valor]);
        }

        $existe = $stmt->fetchColumn() ? true : false;
        echo json_encode(['existe' => $existe]);

    } catch (\PDOException $e) {
        echo json_encode(['existe' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
}
