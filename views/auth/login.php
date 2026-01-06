<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - CENCOCAL S.A.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --cencocal-blue: #0056b3;
            /* Azul del logo */
            --cencocal-red: #e31b23;
            /* Rojo acento */
        }

        body {
            background-color: #f4f7f6;
        }

        .card {
            border: none;
            border-radius: 15px;
        }

        .btn-primary {
            background-color: var(--cencocal-blue);
            border: none;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: #004494;
        }

        .btn-google {
            border: 1px solid #dadce0;
            background-color: white;
            color: #3c4043;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }

        .btn-google:hover {
            background-color: #f8f9fa;
            border-color: #d2d4d7;
        }

        .logo-img {
            max-width: 200px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body class="d-flex align-items-center vh-100">
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'cuenta_activada'): ?>
        <div class="alert alert-success text-center mb-4">
            ✅ <b>¡Cuenta activada!</b> Ya puedes iniciar sesión.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] == 'recuperacion_enviada'): ?>
            <div class="alert alert-info small text-center">
                📧 Si el correo existe, te hemos enviado un enlace de recuperación. (Revisa simulacion_email.txt)
            </div>
        <?php elseif ($_GET['msg'] == 'pass_actualizada'): ?>
            <div class="alert alert-success small text-center">
                ✅ ¡Contraseña actualizada! Ya puedes ingresar.
            </div>
        <?php endif; ?>
    <?php endif; ?>


    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4 text-center">
                <img src="<?= BASE_URL ?>img/logo.png" alt="CENCOCAL S.A." class="logo-img">

                <div class="card shadow-lg">
                    <div class="card-body p-4">
                        <h4 class="text-center mb-4" style="color: var(--cencocal-blue);">Bienvenido</h4>

                        <?php if (isset($error) && $error): ?>
                            <div class="alert alert-danger text-center">
                                <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <form action="<?= BASE_URL ?>auth/login" method="POST">
                            <div class="mb-3 text-start">
                                <label class="form-label small fw-bold">Ingresa tu correo</label>
                                <input type="email" name="email" class="form-control" placeholder="ejemplo@cencocal.cl" required>
                            </div>
                            <div class="mb-4 text-start">
                                <label class="form-label small fw-bold">Contraseña</label>
                                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                                Entrar
                            </button>

                            <div class="text-center mt-2">
                                <a href="<?= BASE_URL ?>auth/forgot" class="small text-muted text-decoration-none">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            </div>

                        </form>

                        <div class="position-relative my-4">
                            <hr>
                            <span class="position-absolute top-50 start-50 translate-middle bg-white px-2 small text-muted">O</span>
                        </div>

                        <a href="<?= BASE_URL ?>auth/google" class="btn btn-google w-100 py-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48" style="margin-right: 10px;">
                                <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z" />
                                <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z" />
                                <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z" />
                                <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z" />
                            </svg>
                            Sincronizar con Google
                        </a>
                    </div>
                </div>

                <p class="mt-4 small text-muted">&copy; 2026 CENCOCAL S.A. <br> Tienda Online</p>
            </div>
        </div>
    </div>
</body>

</html>