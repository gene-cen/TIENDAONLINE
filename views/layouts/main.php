<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CENCOCAL S.A. | Tienda Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --cencocal-blue: #0056b3; /* Azul Institucional */
            --cencocal-red: #e31b23;  /* Rojo de acento */
        }
        .navbar-custom { background-color: var(--cencocal-blue); }
        .btn-cencocal { background-color: var(--cencocal-red); color: white; }
        .btn-cencocal:hover { background-color: #c41219; color: white; }
        .text-cencocal { color: var(--cencocal-blue); }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>home">
                <img src="<?= BASE_URL ?>assets/img/logo en alta.png" alt="CENCOCAL" height="40" class="d-inline-block align-text-top bg-white rounded px-1">
            </a>
            
            <div class="d-flex gap-3 align-items-center">
                <span class="text-white small">Hola, <?= $_SESSION['user_nombre'] ?? 'Cliente' ?></span>
                <a href="<?= BASE_URL ?>auth/logout" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <main class="container my-4 flex-grow-1">
        <?php echo $content; ?>
    </main>

    <footer class="bg-white text-center py-3 mt-auto border-top text-muted small">
        <div class="container">
            &copy; 2026 CENCOCAL S.A. - Todos los derechos reservados.
        </div>
    </footer>

</body>
</html>