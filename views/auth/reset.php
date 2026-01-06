<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center vh-100 bg-light">
    <div class="container" style="max-width: 400px;">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h4 class="mb-3 text-center text-primary">Nueva Contraseña</h4>
                
                <form action="<?= BASE_URL ?>auth/update-password" method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Ingresa tu nueva clave</label>
                        <input type="password" name="password" class="form-control" minlength="6" placeholder="Mínimo 6 caracteres" required>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 mb-3">Cambiar Contraseña</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>