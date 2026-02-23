<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center vh-100 bg-light">
    <div class="container" style="max-width: 400px;">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 text-center">
                <h4 class="mb-3 text-primary">Recuperar Acceso</h4>
                <p class="text-muted small mb-4">Ingresa tu correo y te enviaremos instrucciones.</p>
                
                <form action="<?= BASE_URL ?>auth/send-recovery" method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label small fw-bold">Email Registrado</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3">Enviar Enlace</button>
                    <a href="<?= BASE_URL ?>auth/login" class="small text-decoration-none">Volver al Login</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>