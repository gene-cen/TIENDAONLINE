<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - CENCOCAL S.A.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --cencocal-blue: #0056b3; /* Azul del logo */
            --cencocal-red: #e31b23;  /* Rojo acento */
        }
        body { background-color: #f4f7f6; }
        .card { border: none; border-radius: 15px; }
        .btn-primary { 
            background-color: var(--cencocal-blue); 
            border: none; 
            font-weight: 600;
        }
        .btn-primary:hover { background-color: #004494; }
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
        .btn-google:hover { background-color: #f8f9fa; border-color: #d2d4d7; }
        .logo-img { max-width: 200px; margin-bottom: 20px; }
    </style>
</head>
<body class="d-flex align-items-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4 text-center">
                <img src="/public/assets/img/logo.png" alt="CENCOCAL S.A." class="logo-img">
                
                <div class="card shadow-lg">
                    <div class="card-body p-4">
                        <h4 class="text-center mb-4" style="color: var(--cencocal-blue);">Bienvenido</h4>
                        
                        <form action="<?= BASE_URL ?>auth/login" method="POST">
                            <div class="mb-3 text-start">
                                <label class="form-label small fw-bold">Email Corporativo</label>
                                <input type="email" name="email" class="form-control" placeholder="ejemplo@cencocal.cl" required>
                            </div>
                            <div class="mb-4 text-start">
                                <label class="form-label small fw-bold">Contraseña</label>
                                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                                Entrar
                            </button>
                        </form>

                        <div class="position-relative my-4">
                            <hr>
                            <span class="position-absolute top-50 start-50 translate-middle bg-white px-2 small text-muted">O</span>
                        </div>

                        <a href="<?= BASE_URL ?>auth/google" class="btn btn-google w-100 py-2">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg" alt="Google" style="width: 18px; margin-right: 10px;">
                            Sincronizar con Google
                        </a>
                    </div>
                </div>
                
                <p class="mt-4 small text-muted">&copy; 2026 CENCOCAL S.A. <br> Plataforma de Tienda Online</p>
            </div>
        </div>
    </div>
</body>
</html>