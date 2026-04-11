<link rel="stylesheet" href="<?= BASE_URL ?>css/shop/navbar.css">

<nav class="navbar navbar-expand-lg navbar-dark bg-cenco-indigo shadow-sm py-2 border-bottom border-3" style="border-color: var(--cenco-green) !important;">
    <div class="container-fluid px-3 px-lg-4">
        
        <a class="navbar-brand fw-bold d-flex align-items-center gap-3 py-0" href="<?= BASE_URL ?>home">
            <div class="bg-white rounded-4 d-flex align-items-center justify-content-center shadow-sm transition-hover overflow-hidden" style="height: 75px; min-width: 180px; padding: 0;">
                <img src="<?= BASE_URL ?>img/logo.png" alt="Cencocal" class="img-fluid" style="height: 100%; width: 100%; object-fit: contain;">
            </div>
        </a>

        <button class="btn text-white p-1 me-2 hover-scale transition-hover d-flex align-items-center gap-2" 
                type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCategorias" aria-controls="offcanvasCategorias">
            <i class="bi bi-list" style="font-size: 2rem;"></i>
            <span class="fw-bold d-none d-sm-inline text-uppercase" style="font-size: 0.85rem; letter-spacing: 1px;">Categorías</span>
        </button>

        <div class="d-flex align-items-center me-auto ms-2">
            <button class="btn btn-outline-light border-0 d-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow-sm transition-hover" 
                    type="button" data-bs-toggle="modal" data-bs-target="#modalComuna" style="background: rgba(255,255,255,0.1);">
                <div class="position-relative">
                    <i class="bi bi-geo-alt-fill text-cenco-green fs-4"></i>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle animate-ping"></span>
                </div>
                <div class="text-start lh-1">
                    <span class="d-block opacity-75" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Estás en:</span>
                    <span id="nombreComunaNav" class="fw-bold text-white" style="font-size: 0.95rem;">
                        <?= htmlspecialchars($_SESSION['comuna_nombre'] ?? 'La Calera') ?>
                    </span>
                </div>
                <i class="bi bi-chevron-down small opacity-50 ms-1"></i>
            </button>
        </div>

        <div class="ms-auto d-flex align-items-center gap-3 gap-lg-4">
            
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                <button class="btn btn-warning text-dark fw-bold rounded-pill px-3 d-flex align-items-center gap-2 shadow-sm hover-scale" 
                        type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span class="d-none d-md-inline">Admin</span>
                </button>
            <?php endif; ?>

            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'transportista'): ?>
                <a href="<?= BASE_URL ?>transporte/misEntregas" class="btn btn-info text-white fw-bold rounded-pill px-3 d-flex align-items-center gap-2 shadow-sm hover-scale">
                    <i class="bi bi-truck-flatbed"></i>
                    <span class="d-none d-md-inline">Mis Entregas</span>
                </a>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="dropdown">
                    <a class="text-decoration-none text-white d-flex align-items-center gap-2 dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center overflow-hidden border border-2 border-cenco-green shadow-sm" style="width: 42px; height: 42px;">
                            <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_positivo.png" class="img-fluid" style="transform: scale(1.1) translateY(2px);" alt="User">
                        </div>
                        <div class="d-none d-md-block lh-1">
                            <span class="d-block small opacity-75">Hola, <?= htmlspecialchars(explode(' ', $_SESSION['user_nombre'])[0]) ?></span>
                            <span class="fw-bold">Mi Cuenta</span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3 animate slideIn">
                        <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>perfil"><i class="bi bi-person-gear me-2 text-cenco-green"></i> Mi Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-cenco-red fw-bold" href="<?= BASE_URL ?>auth/logout"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <button type="button" class="btn text-white d-flex align-items-center gap-2 p-0 border-0 bg-transparent" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <div class="bg-white rounded-circle d-flex align-items-center justify-content-center overflow-hidden border border-2 border-white shadow-sm" style="width: 42px; height: 42px;">
                        <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_positivo.png" class="img-fluid" style="transform: scale(1.1) translateY(2px);" alt="Login">
                    </div>
                    <div class="d-none d-md-block lh-1 text-start">
                        <span class="d-block small opacity-75">¡Bienvenido/a!</span>
                        <span class="fw-bold text-decoration-underline-hover">Inicia Sesión</span>
                    </div>
                </button>
            <?php endif; ?>

            <button type="button" class="btn position-relative text-white border-0 p-1 d-flex align-items-center gap-2" 
                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasCarrito" onclick="actualizarCarritoLateral()" aria-label="Ver Carrito">
                <div class="position-relative">
                    <i class="bi bi-cart-fill fs-3"></i>
                    <?php 
                        $cantidadCarrito = isset($_SESSION['carrito']) ? array_sum(array_column($_SESSION['carrito'], 'cantidad')) : 0;
                        $totalCarrito = isset($_SESSION['carrito']) ? array_sum(array_map(function($it){ return $it['precio'] * $it['cantidad']; }, $_SESSION['carrito'])) : 0;
                    ?>
                    <span id="badge-carrito-navbar" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-cenco-green border border-2 border-cenco-indigo fw-bold badge-carrito" 
                          style="<?= $cantidadCarrito == 0 ? 'display:none;' : '' ?>">
                        <?= $cantidadCarrito ?>
                    </span>
                </div>
                <div class="d-none d-md-block text-start lh-1">
                    <span class="d-block small opacity-75" style="font-size: 0.7rem;">Mi Carro</span>
                    <span id="monto-carrito-navbar" class="fw-bold text-white monto-carrito" style="font-size: 0.95rem;">
                        $<?= number_format($totalCarrito, 0, ',', '.') ?>
                    </span>
                </div>
            </button>
            
        </div>
    </div>
</nav>

<script>
    window.NavbarConfig = {
        comunaCargada: <?= isset($_SESSION['comuna_id']) ? 'true' : 'false' ?>
    };
</script>
<script src="<?= BASE_URL ?>js/shop/navbar.js"></script>