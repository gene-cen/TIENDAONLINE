<style>
    /* GARANTIZAR NAVBAR FIJO Y VISIBLE */
    nav.navbar {
        position: fixed !important;
        /* Forzamos posición fija */
        top: 0;
        width: 100%;
        z-index: 1080 !important;
        /* Más alto que modales estándar y offcanvas */
    }

    /* COMPENSACIÓN DE ALTURA */
    /* Empujamos el contenido hacia abajo para que no quede oculto detrás del navbar fijo */
    body {
        padding-top: 110px;
        /* Ajusta este valor según la altura real de tu navbar */
    }

    /* OFFCANVAS DEBAJO DEL NAVBAR (Opcional, si quieres que el menú salga debajo) */
    #offcanvasCategorias {
        top: 110px !important;
        /* Mismo valor que el padding-top del body */
        height: calc(100vh - 110px) !important;
        z-index: 1070;
    }

    .offcanvas-backdrop {
        top: 110px !important;
        /* El fondo oscuro también empieza debajo */
        z-index: 1060;
    }

    /* Hover effects */
    .hover-scale {
        transition: transform 0.2s;
    }

    .hover-scale:hover {
        transform: scale(1.05);
    }

    .text-decoration-underline-hover:hover {
        text-decoration: underline !important;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark bg-cenco-indigo shadow-sm py-2 border-bottom border-3" style="border-color: var(--cenco-green) !important;">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-3 py-0" href="<?= BASE_URL ?>home">
            <div class="bg-white rounded-4 d-flex align-items-center justify-content-center shadow-sm transition-hover overflow-hidden"
                style="height: 75px; min-width: 180px; padding: 0;">

                <img src="<?= BASE_URL ?>img/logo.png"
                    alt="Cencocal"
                    class="img-fluid"
                    style="height: 100%; width: 100%; object-fit: contain;">
            </div>

            <div class="d-none d-sm-block bg-white opacity-25 rounded my-auto" style="width: 1px; height: 40px;"></div>
        </a>

        <button class="btn text-white p-1 me-2 hover-scale transition-hover d-flex align-items-center gap-2"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#offcanvasCategorias"
            title="Ver todas las categorías">
            <i class="bi bi-list" style="font-size: 2rem;"></i>
            <span class="fw-bold d-none d-sm-inline text-uppercase" style="font-size: 0.85rem; letter-spacing: 1px;">
                Categorías
            </span>
        </button>

        <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin'): ?>
            <button class="btn btn-warning text-dark fw-bold rounded-pill px-3 me-3 d-flex align-items-center gap-2 shadow-sm hover-scale ms-2"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#adminSidebar"
                aria-controls="adminSidebar">
                <i class="bi bi-shield-lock-fill"></i>
                <span class="d-none d-md-inline">Admin</span>
            </button>
        <?php endif; ?>

        <div class="ms-auto d-flex align-items-center gap-3 gap-lg-4">

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="dropdown">
                    <a class="text-decoration-none text-white d-flex align-items-center gap-2 dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="bg-white rounded-circle p-0 d-flex align-items-center justify-content-center overflow-hidden shadow-sm border border-2 border-cenco-green position-relative" style="width: 42px; height: 42px;">
                            <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_positivo.png" alt="Mi Perfil" class="img-fluid" style="width: 100%; height: 100%; object-fit: cover; transform: scale(1.1) translateY(2px);">
                        </div>
                        <div class="d-none d-md-block lh-1 text-start">
                            <span class="d-block small opacity-75">Hola, <?= explode(' ', $_SESSION['user_nombre'])[0] ?></span>
                            <span class="fw-bold">Mi Cuenta</span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3 overflow-hidden font-sm">
                        <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>perfil"><i class="bi bi-person-gear me-2 text-cenco-green"></i> Mi Perfil</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item py-2 text-cenco-red fw-bold" href="<?= BASE_URL ?>auth/logout"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <button type="button" class="btn text-white d-flex align-items-center gap-2 border-0 bg-transparent p-0 transition-hover" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <div class="bg-white rounded-circle p-0 d-flex align-items-center justify-content-center overflow-hidden shadow-sm border border-2 border-white position-relative" style="width: 42px; height: 42px;">
                        <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_positivo.png" alt="Ingresar" class="img-fluid" style="width: 100%; height: 100%; object-fit: cover; transform: scale(1.1) translateY(2px);">
                    </div>
                    <div class="d-none d-md-block lh-1 text-start">
                        <span class="d-block small opacity-75">¡Bienvenido/a!</span>
                        <span class="fw-bold text-decoration-underline-hover" style="font-size: 0.9rem;">Inicia Sesión</span>
                    </div>
                </button>
            <?php endif; ?>

            <button type="button" class="btn position-relative text-white border-0 p-1 me-2 d-flex align-items-center gap-2" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCarrito" onclick="actualizarCarritoLateral()">
                <div class="position-relative">
                    <i class="bi bi-cart-fill fs-3"></i>
                    <span id="contador-carrito" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-cenco-green border border-2 border-cenco-indigo fw-bold text-white">
                        <?= isset($_SESSION['carrito']) ? array_sum(array_column($_SESSION['carrito'], 'cantidad')) : 0 ?>
                    </span>
                </div>
                <div class="d-none d-md-block text-start lh-1">
                    <span class="d-block small opacity-75" style="font-size: 0.7rem;">Mi Carro</span>
                    <span id="total-monto-navbar" class="fw-bold text-white" style="font-size: 0.9rem;">
                        <?php
                        $totalMonto = 0;
                        if (isset($_SESSION['carrito'])) {
                            foreach ($_SESSION['carrito'] as $it) $totalMonto += $it['precio'] * $it['cantidad'];
                        }
                        echo '$' . number_format($totalMonto, 0, ',', '.');
                        ?>
                    </span>
                </div>
            </button>

        </div>
    </div>
</nav>