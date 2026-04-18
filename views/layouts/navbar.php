<?php
// ==========================================
// 🔥 DESTRUCTOR DE SESIÓN UNIVERSAL
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'force_logout') {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: " . BASE_URL . "home");
    exit();
}

// ==========================================
// 🔥 VARIABLES GLOBALES DEL NAVBAR
// ==========================================
$rolId = (int)($_SESSION['rol_id'] ?? 0);
$esAdmin = in_array($rolId, [1, 2]);
$puedeCambiarSede = in_array($rolId, [1, 4]);
$modoVentaAsistida = isset($_SESSION['modo_venta_asistida']) && $_SESSION['modo_venta_asistida'] === true;

// Detectamos si estamos dentro de una ruta de administración (Panel)
$urlActual = $_GET['url'] ?? 'home';
$esRutaAdmin = (strpos($urlActual, 'admin/') === 0 || strpos($urlActual, 'empleos/rrhh_') === 0);

// Comprobamos si el carrito tiene productos para el botón de "Continuar Venta"
$carritoTieneProductos = (isset($_SESSION['carrito']) && count($_SESSION['carrito']) > 0);

// ==========================================
// 🔥 ENRUTADOR DEL LOGO (Contextual)
// ==========================================
$enlaceLogo = BASE_URL . "home";
if (!$esRutaAdmin && $modoVentaAsistida) {
    // Si NO está en el panel y la venta está activa, va a la tienda
    $enlaceLogo = BASE_URL . "home";
} elseif (in_array($rolId, [1, 2, 3])) {
    // Si está gestionando (Panel), el logo lo mantiene en el Dashboard
    $enlaceLogo = BASE_URL . "admin/dashboard";
} elseif ($rolId === 4) {
    $enlaceLogo = BASE_URL . "empleos/rrhh_dashboard";
} elseif ($rolId === 5) {
    $enlaceLogo = BASE_URL . "transporte/misEntregas";
}

// Enrutador Específico para el Panel de Control (Usado en botones)
$enlaceDashboard = null;
if (in_array($rolId, [1, 2, 3])) $enlaceDashboard = BASE_URL . "admin/dashboard";
elseif ($rolId === 4) $enlaceDashboard = BASE_URL . "empleos/rrhh_dashboard";
elseif ($rolId === 5) $enlaceDashboard = BASE_URL . "transporte/misEntregas";

// Nombre de la Sucursal
$adminSucursal = $_SESSION['admin_sucursal'] ?? 0;
$nombreSucursalInterna = ($adminSucursal == 29) ? "SUCURSAL PRAT LA CALERA" : (($adminSucursal == 10) ? "SUCURSAL VILLA ALEMANA" : "CASA MATRIZ");
?>

<link rel="stylesheet" href="<?= BASE_URL ?>css/shop/navbar.css">

<nav class="navbar navbar-expand-lg navbar-dark bg-cenco-indigo shadow-sm py-2 border-bottom border-3" style="border-color: var(--cenco-green) !important;">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand fw-bold d-flex align-items-center py-0 me-2" href="<?= $enlaceLogo ?>">
            <img src="<?= BASE_URL ?>img/logo_blanco.png" alt="Cencocal S.A." class="img-fluid transition-hover" style="width: clamp(140px, 25vw, 250px); height: auto; object-fit: contain;">
        </a>

        <div class="d-flex align-items-center me-auto ms-3">

            <?php if (in_array($rolId, [1, 2, 4])): ?>
                <?php if (!$esRutaAdmin && $modoVentaAsistida): ?>
                    <button class="btn btn-cenco-green text-white fw-bold d-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow-sm transition-hover me-3"
                        type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCategorias">
                        <i class="bi bi-grid-3x3-gap-fill fs-5"></i>
                        <span class="d-none d-md-inline">Categorías</span>
                    </button>

                    <div class="d-none d-md-flex align-items-center text-white px-3 py-2 rounded-pill shadow-sm" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2);">
                        <i class="bi bi-shop text-warning fs-5 me-2"></i>
                        <div class="text-start lh-1">
                            <span class="d-block text-warning fw-bold" style="font-size: 0.65rem; text-transform: uppercase;">Caja Activa</span>
                            <span class="fw-black text-white" style="font-size: 0.90rem; letter-spacing: 0.5px;">
                                <?= $nombreSucursalInterna ?>
                            </span>
                        </div>
                    </div>

                <?php else: ?>
                    <?php if ($puedeCambiarSede): ?>
                        <button class="btn btn-outline-light border-0 d-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow-sm transition-hover"
                            type="button" data-bs-toggle="modal" data-bs-target="#modalCambioSucursalAdmin"
                            style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.15);">
                            <i class="bi bi-building-gear text-cenco-green fs-5"></i>
                            <div class="text-start lh-1">
                                <span class="d-block text-white opacity-75 small text-uppercase">Cambiar Sucursal</span>
                                <span class="fw-black text-white"><?= $nombreSucursalInterna ?></span>
                            </div>
                            <i class="bi bi-chevron-down small opacity-50 ms-1 text-white"></i>
                        </button>
                    <?php else: ?>
                        <div class="d-flex align-items-center text-white px-3 py-2 rounded-pill shadow-sm" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1);">
                            <i class="bi bi-shop text-cenco-green fs-5 me-2"></i>
                            <div class="text-start lh-1">
                                <span class="d-block text-white opacity-75" style="font-size: 0.65rem; text-transform: uppercase;">Sucursal Asignada</span>
                                <span class="fw-black text-warning" style="font-size: 0.90rem; letter-spacing: 0.5px;">
                                    <?= $nombreSucursalInterna ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

            <?php else: ?>
                <button class="btn btn-cenco-green text-white fw-bold d-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow-sm transition-hover"
                    type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCategorias">
                    <i class="bi bi-grid-3x3-gap-fill fs-5"></i>
                    <span class="d-none d-md-inline">Categorías</span>
                </button>

                <?php if (!$modoVentaAsistida): ?>
                    <button class="btn btn-outline-light border-0 d-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow-sm transition-hover ms-3"
                        type="button" data-bs-toggle="modal" data-bs-target="#modalComuna" style="background: rgba(255,255,255,0.1);">
                        <i class="bi bi-geo-alt-fill text-cenco-green fs-4"></i>
                        <div class="text-start lh-1">
                            <span class="d-block opacity-75" style="font-size: 0.7rem; text-transform: uppercase;">Estás en:</span>
                            <span id="nombreComunaNav" class="fw-bold text-white" style="font-size: 0.95rem;">
                                <?= htmlspecialchars($_SESSION['texto_navbar_sucursal'] ?? $_SESSION['comuna_nombre'] ?? 'La Calera') ?>
                            </span>
                        </div>
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="ms-auto d-flex align-items-center gap-3">

            <?php if ($esAdmin): ?>
                <?php if (!$esRutaAdmin): ?>
                    <a href="<?= $enlaceDashboard ?>" class="btn btn-primary text-white fw-black rounded-pill px-4 shadow-sm hover-scale d-flex align-items-center gap-2 me-2" style="background-color: #432C85; border-color: #432C85;">
                        <i class="bi bi-speedometer2 fs-5"></i>
                        <span class="d-none d-xl-inline">Panel de Control</span>
                    </a>
                <?php else: ?>
                    <?php if ($modoVentaAsistida && $carritoTieneProductos): ?>
                        <a href="<?= BASE_URL ?>home" class="btn btn-info text-white fw-black rounded-pill px-4 shadow-sm hover-scale d-flex align-items-center gap-2 me-2">
                            <i class="bi bi-arrow-right-circle-fill fs-5"></i>
                            <span class="d-none d-xl-inline">Continuar Venta</span>
                        </a>
                    <?php else: ?>
                        <form action="<?= BASE_URL ?>admin/iniciarVentaAsistida" method="POST" class="m-0 me-2">
                            <button type="submit" class="btn btn-warning text-dark fw-black rounded-pill px-4 shadow-sm hover-scale d-flex align-items-center gap-2">
                                <i class="bi bi-cart-plus-fill fs-5"></i>
                                <span class="d-none d-xl-inline">Iniciar Venta</span>
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>


            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="dropdown">
                    <a class="text-decoration-none text-white d-flex align-items-center gap-2 dropdown-toggle" href="#" id="userMenuDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center border border-2 border-cenco-green shadow-sm" style="width: 42px; height: 42px;">
                            <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_positivo.png" class="img-fluid" style="transform: scale(1.1) translateY(2px);">
                        </div>
                        <div class="d-none d-md-block lh-1 text-start">
                            <?php $nombreMostrar = isset($_SESSION['user_nombre']) ? htmlspecialchars(explode(' ', $_SESSION['user_nombre'])[0]) : 'Usuario'; ?>
                            <span class="d-block small opacity-75">Hola, <?= $nombreMostrar ?></span>
                            <span class="fw-bold">Mi Cuenta</span>
                        </div>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 rounded-4" style="min-width: 240px;">


                        <?php if (in_array($rolId, [4, 6])): ?>
                            <li>
                                <a class="dropdown-item py-2 fw-bold text-secondary transition-hover" href="<?= BASE_URL ?>perfil">
                                    <i class="bi bi-person-gear me-2 text-cenco-green fs-5"></i> Mi Perfil
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                        <?php endif; ?>

                        <li>
                            <a class="dropdown-item py-2 text-cenco-red fw-bold transition-hover" href="?action=force_logout">
                                <i class="bi bi-box-arrow-right me-2 fs-5"></i> Cerrar Sesión
                            </a>
                        </li>
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

            <?php
            $verCarro = false;
            if ($rolId == 6 || $rolId == 0) {
                $verCarro = true; // Clientes o invitados siempre lo ven
            } elseif ($modoVentaAsistida && !$esRutaAdmin) {
                // Admin en venta asistida LO VE, solo si no está en el panel
                $verCarro = true;
            }

            if ($verCarro):
                $cantidadCarrito = isset($_SESSION['carrito']) ? array_sum(array_column($_SESSION['carrito'], 'cantidad')) : 0;
                $totalCarrito = isset($_SESSION['carrito']) ? array_sum(array_map(function ($it) {
                    return $it['precio'] * $it['cantidad'];
                }, $_SESSION['carrito'])) : 0;
            ?>
                <button type="button" class="btn position-relative text-white border-0 p-1 d-flex align-items-center gap-2 ms-2" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCarrito" onclick="actualizarCarritoLateral()">
                    <div class="position-relative">
                        <i class="bi bi-cart-fill fs-3"></i>
                        <span id="badge-carrito-navbar" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-cenco-green border border-2 border-cenco-indigo fw-bold" style="<?= $cantidadCarrito == 0 ? 'display:none;' : '' ?>">
                            <?= $cantidadCarrito ?>
                        </span>
                    </div>
                    <div class="d-none d-md-block text-start lh-1">
                        <span class="d-block small opacity-75" style="font-size: 0.7rem;">Mi Carro</span>
                        <span id="monto-carrito-navbar" class="fw-bold text-white monto-carrito" style="font-size: 0.95rem;">$<?= number_format($totalCarrito, 0, ',', '.') ?></span>
                    </div>
                </button>
            <?php endif; ?>

        </div>
    </div>
</nav>

<?php if ($puedeCambiarSede && !$modoVentaAsistida): ?>
    <div class="modal fade" id="modalCambioSucursalAdmin" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom-0 pb-0">
                    <h6 class="modal-title fw-bold text-cenco-indigo text-uppercase small">Seleccionar Centro de Operación</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-cenco-indigo text-start p-3 rounded-3 d-flex align-items-center justify-content-between transition-hover" onclick="cambiarSucursalAdmin(29, 'Prat La Calera')">
                            <div>
                                <span class="d-block fw-bold">Prat La Calera</span>
                                <small class="opacity-75">Sucursal 29</small>
                            </div>
                            <?php if ($adminSucursal == 29) echo '<i class="bi bi-check-circle-fill text-success fs-5"></i>'; ?>
                        </button>
                        <button type="button" class="btn btn-outline-cenco-indigo text-start p-3 rounded-3 d-flex align-items-center justify-content-between transition-hover" onclick="cambiarSucursalAdmin(10, 'Villa Alemana')">
                            <div>
                                <span class="d-block fw-bold">Villa Alemana</span>
                                <small class="opacity-75">Sucursal 10</small>
                            </div>
                            <?php if ($adminSucursal == 10) echo '<i class="bi bi-check-circle-fill text-success fs-5"></i>'; ?>
                        </button>
                        <?php if ($rolId === 1): ?>
                            <button type="button" class="btn btn-outline-secondary text-start p-3 rounded-3 d-flex align-items-center justify-content-between transition-hover" onclick="cambiarSucursalAdmin(0, 'Casa Matriz')">
                                <div>
                                    <span class="d-block fw-bold">Casa Matriz</span>
                                    <small class="opacity-75">Vista Global</small>
                                </div>
                                <?php if ($adminSucursal == 0) echo '<i class="bi bi-check-circle-fill text-success fs-5"></i>'; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function cambiarSucursalAdmin(id, nombre) {
            fetch('<?= BASE_URL ?>admin/cambiar_sucursal', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `sucursal_id=${id}`
            }).then(res => res.json()).then(data => {
                if (data.status === 'success') window.location.reload();
            });
        }
    </script>
<?php endif; ?>

<script>
    window.NavbarConfig = {
        comunaCargada: <?= isset($_SESSION['comuna_id']) ? 'true' : 'false' ?>
    };
</script>
<script src="<?= BASE_URL ?>js/shop/navbar.js"></script>