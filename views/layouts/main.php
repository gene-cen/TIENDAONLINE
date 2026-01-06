<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda CENCOCAL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        #wrapper { overflow-x: hidden; }
        #sidebar-wrapper { min-width: 250px; max-width: 250px; margin-left: -250px; transition: margin 0.25s ease-out; }
        #sidebar-wrapper .sidebar-heading { font-size: 1.2rem; }
        #page-content-wrapper { width: 100%; }
        body.sb-sidenav-toggled #sidebar-wrapper { margin-left: 0; }
        @media (min-width: 768px) {
            #sidebar-wrapper { margin-left: 0; }
            body.sb-sidenav-toggled #sidebar-wrapper { margin-left: -250px; }
        }
    </style>
</head>

<body>

<div class="d-flex" id="wrapper">
    
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div id="page-content-wrapper" class="d-flex flex-column min-vh-100">

        <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm border-bottom">
            <div class="container-fluid">
                
                <button class="btn btn-primary" id="sidebarToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>

                <a class="navbar-brand fw-bold ms-3" href="<?= BASE_URL ?>home">
                    <img src="<?= BASE_URL ?>img/logo.png" height="30" alt="CENCOCAL" class="me-2">
                </a>

                <div class="ms-auto d-flex align-items-center gap-3">
                    
                    <?php 
                        $cantidad_carrito = 0;
                        if(isset($_SESSION['carrito'])) {
                            foreach($_SESSION['carrito'] as $item) $cantidad_carrito += $item['cantidad'];
                        }
                    ?>
                    <a href="<?= BASE_URL ?>carrito/ver" class="btn btn-light position-relative text-primary border-0">
                        <i class="bi bi-cart-fill fs-5"></i>
                        <?php if($cantidad_carrito > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $cantidad_carrito ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <a class="btn btn-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <?= $_SESSION['user_nombre'] ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>perfil">Perfil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>auth/logout">Salir</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>auth/login" class="btn btn-outline-light btn-sm">Ingresar</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <main class="container-fluid px-4 py-4 flex-grow-1">
            <?php
            if (isset($content)) {
                echo $content;
            } else {
                echo "<div class='alert alert-info'>Seleccione una opción del menú</div>";
            }
            ?>
        </main>

        <footer class="bg-white text-center text-muted py-3 border-top mt-auto">
            <small>&copy; 2026 CENCOCAL S.A.</small>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.addEventListener('DOMContentLoaded', event => {
        const sidebarToggle = document.body.querySelector('#sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', event => {
                event.preventDefault();
                document.body.classList.toggle('sb-sidenav-toggled');
            });
        }
    });
</script>

</body>
</html>