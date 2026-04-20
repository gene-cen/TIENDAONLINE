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

$urlActual = $_GET['url'] ?? 'home';
$esRutaAdmin = (strpos($urlActual, 'admin/') === 0 || strpos($urlActual, 'empleos/rrhh_') === 0);
$carritoTieneProductos = (isset($_SESSION['carrito']) && count($_SESSION['carrito']) > 0);

// Enrutador del Logo
$enlaceLogo = BASE_URL . "home";
if (!$esRutaAdmin && $modoVentaAsistida) $enlaceLogo = BASE_URL . "home";
elseif (in_array($rolId, [1, 2, 3])) $enlaceLogo = BASE_URL . "admin/dashboard";
elseif ($rolId === 4) $enlaceLogo = BASE_URL . "empleos/rrhh_dashboard";
elseif ($rolId === 5) $enlaceLogo = BASE_URL . "transporte/misEntregas";

$enlaceDashboard = null;
if (in_array($rolId, [1, 2, 3])) $enlaceDashboard = BASE_URL . "admin/dashboard";
elseif ($rolId === 4) $enlaceDashboard = BASE_URL . "empleos/rrhh_dashboard";
elseif ($rolId === 5) $enlaceDashboard = BASE_URL . "transporte/misEntregas";

$adminSucursal = $_SESSION['admin_sucursal'] ?? 0;
$nombreSucursalInterna = ($adminSucursal == 29) ? "PRAT LA CALERA" : (($adminSucursal == 10) ? "VILLA ALEMANA" : "CASA MATRIZ");

// ==========================================
// 🔥 LÓGICA DE GEOLOCALIZACIÓN VISUAL
// ==========================================
$textoUbicacionNav = 'La Calera'; // Valor por defecto
$colorIconoGeo = 'text-cenco-green';

if (!empty($_SESSION['comuna_nombre'])) {
    // Normalizamos el texto (sin tildes, en minúsculas) para evitar errores de coincidencia
    $comunaNormalizada = str_replace(
        ['á','é','í','ó','ú','Á','É','Í','Ó','Ú'], 
        ['a','e','i','o','u','a','e','i','o','u'], 
        strtolower(trim($_SESSION['comuna_nombre']))
    );
    
    // Arrays de zonas
    $zonaCalera = ['hijuelas', 'nogales', 'la cruz', 'quillota', 'la calera'];
    $zonaVillaAlemana = ['valparaiso', 'con con', 'concon', 'vina del mar', 'vina', 'limache', 'penablanca', 'quilpue', 'villa alemana'];
    
    if (in_array($comunaNormalizada, $zonaCalera)) {
        $textoUbicacionNav = 'Suc. La Calera';
    } elseif (in_array($comunaNormalizada, $zonaVillaAlemana)) {
        $textoUbicacionNav = 'Suc. Villa Alemana';
    } else {
        $textoUbicacionNav = 'Sin Cobertura';
        $colorIconoGeo = 'text-warning'; // Cambia a amarillo/naranja si está fuera de zona
    }
} elseif (!empty($_SESSION['texto_navbar_sucursal'])) {
    $textoUbicacionNav = $_SESSION['texto_navbar_sucursal'];
}
?>

<link rel="stylesheet" href="<?= BASE_URL ?>css/shop/navbar.css">

<nav class="navbar navbar-dark bg-cenco-indigo shadow-sm py-2 border-bottom border-3" style="border-color: var(--cenco-green) !important; min-height: 70px;">
    <div class="container-fluid px-2 px-lg-4 d-flex align-items-center justify-content-between h-100">

        <div class="d-flex align-items-center gap-2 gap-lg-4 h-100">
            
            <a class="navbar-brand m-0 p-0 d-flex align-items-center" href="<?= $enlaceLogo ?>">
                <img src="<?= BASE_URL ?>img/logo_blanco.png" class="img-fluid d-block d-lg-none" style="width: 110px; object-fit: contain;" alt="Cencocal">
                <img src="<?= BASE_URL ?>img/logo_blanco.png" class="img-fluid d-none d-lg-block transition-hover" style="width: 180px; object-fit: contain;" alt="Cencocal">
            </a>

            <?php if (in_array($rolId, [1, 2, 4]) && ($esRutaAdmin || !$modoVentaAsistida)): ?>
            <?php else: ?>
                <button class="btn btn-cenco-green text-white fw-bold d-flex align-items-center justify-content-center rounded-pill shadow-sm transition-hover p-2 px-lg-3 py-lg-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCategorias">
                    <i class="bi bi-grid-3x3-gap-fill fs-5 m-0"></i>
                    <span class="d-none d-lg-inline ms-2">Categorias</span>
                </button>
            <?php endif; ?>

            <?php if (in_array($rolId, [1, 2, 4])): ?>
                <?php if (!$esRutaAdmin && $modoVentaAsistida): ?>
                    <div class="d-flex align-items-center justify-content-center text-white rounded-pill shadow-sm p-2 px-lg-3 py-lg-2" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2);">
                        <i class="bi bi-shop text-warning fs-5 m-0"></i>
                        <div class="d-none d-lg-block text-start lh-1 ms-2">
                            <span class="d-block text-warning fw-bold" style="font-size: 0.65rem;">CAJA ACTIVA</span>
                            <span class="fw-black text-white"><?= $nombreSucursalInterna ?></span>
                        </div>
                    </div>
                <?php elseif ($puedeCambiarSede): ?>
                    <button class="btn btn-outline-light border-0 d-flex align-items-center justify-content-center rounded-pill shadow-sm transition-hover p-2 px-lg-3 py-lg-2" type="button" data-bs-toggle="modal" data-bs-target="#modalCambioSucursalAdmin" style="background: rgba(255,255,255,0.12);">
                        <i class="bi bi-building-gear text-cenco-green fs-5 m-0"></i>
                        <div class="d-none d-lg-block text-start lh-1 mx-2">
                            <span class="d-block text-white opacity-75 small">Cambiar Sucursal</span>
                            <span class="fw-black text-white"><?= $nombreSucursalInterna ?></span>
                        </div>
                        <i class="bi bi-chevron-down small opacity-50 text-white d-none d-lg-block m-0"></i>
                    </button>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center text-white rounded-pill shadow-sm p-2 px-lg-3 py-lg-2" style="background: rgba(255,255,255,0.08);">
                        <i class="bi bi-shop text-cenco-green fs-5 m-0"></i>
                        <div class="d-none d-lg-block text-start lh-1 ms-2">
                            <span class="d-block text-white opacity-75 small">Sucursal Asignada</span>
                            <span class="fw-black text-warning"><?= $nombreSucursalInterna ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <?php if (!$modoVentaAsistida): ?>
                    <button class="btn btn-outline-light border-0 d-flex align-items-center justify-content-center rounded-pill shadow-sm transition-hover p-2 px-lg-3 py-lg-2" type="button" data-bs-toggle="modal" data-bs-target="#modalComuna" style="background: rgba(255,255,255,0.1);">
                        <i class="bi <?= $colorIconoGeo ?> fs-5 m-0" id="iconoGeoNav"></i>
                        <div class="d-none d-lg-block text-start lh-1 ms-2">
                            <span class="d-block opacity-75" style="font-size: 0.7rem;">ESTAS EN:</span>
                            <span id="nombreComunaNav" class="fw-bold text-white d-block" style="font-size: 0.95rem;">
                                <?= htmlspecialchars($textoUbicacionNav) ?>
                            </span>
                        </div>
                    </button>
                <?php endif; ?>
            <?php endif; ?>

        </div>


        <div class="d-flex align-items-center gap-2 gap-lg-4 h-100">
            
            <?php if ($esAdmin): ?>
                <?php if (!$esRutaAdmin): ?>
                    <a href="<?= $enlaceDashboard ?>" class="btn btn-primary text-white fw-black rounded-pill shadow-sm hover-scale d-flex align-items-center justify-content-center p-2 px-lg-4 py-lg-2" style="background-color: #432C85; border-color: #432C85;">
                        <i class="bi bi-speedometer2 fs-5 m-0"></i>
                        <span class="d-none d-xl-inline ms-2">Panel</span>
                    </a>
                <?php else: ?>
                    <?php if ($modoVentaAsistida && $carritoTieneProductos): ?>
                        <a href="<?= BASE_URL ?>home" class="btn btn-info text-white fw-black rounded-pill shadow-sm hover-scale d-flex align-items-center justify-content-center p-2 px-lg-4 py-lg-2">
                            <i class="bi bi-arrow-right-circle-fill fs-5 m-0"></i>
                            <span class="d-none d-lg-inline ms-2">Continuar</span>
                        </a>
                    <?php else: ?>
                        <form action="<?= BASE_URL ?>admin/iniciarVentaAsistida" method="POST" class="m-0">
                            <button type="submit" class="btn btn-warning text-dark fw-black rounded-pill shadow-sm hover-scale d-flex align-items-center justify-content-center p-2 px-lg-4 py-lg-2">
                                <i class="bi bi-cart-plus-fill fs-5 m-0"></i>
                                <span class="d-none d-lg-inline ms-2">Iniciar</span>
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="dropdown">
                    <a class="text-decoration-none text-white d-flex align-items-center dropdown-toggle" href="#" id="userMenuDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center border border-2 border-cenco-green shadow-sm" style="width: 40px; height: 40px;">
                            <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_positivo.png" class="img-fluid" style="transform: scale(1.1) translateY(2px);">
                        </div>
                        <div class="d-none d-lg-block lh-1 text-start ms-2">
                            <?php $nombreMostrar = isset($_SESSION['user_nombre']) ? htmlspecialchars(explode(' ', $_SESSION['user_nombre'])[0]) : 'Usuario'; ?>
                            <span class="d-block small opacity-75">Hola, <?= $nombreMostrar ?></span>
                            <span class="fw-bold">Mi Cuenta</span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 rounded-4" style="min-width: 240px;">
                        <?php if (in_array($rolId, [4, 6])): ?>
                            <li><a class="dropdown-item py-2 fw-bold text-secondary transition-hover" href="<?= BASE_URL ?>perfil"><i class="bi bi-person-gear me-2 text-cenco-green fs-5"></i> Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item py-2 text-cenco-red fw-bold transition-hover" href="?action=force_logout"><i class="bi bi-box-arrow-right me-2 fs-5"></i> Cerrar Sesion</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <button type="button" class="btn text-white d-flex align-items-center p-0 m-0 border-0 bg-transparent" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <div class="bg-white rounded-circle d-flex align-items-center justify-content-center overflow-hidden border border-2 border-white shadow-sm" style="width: 40px; height: 40px;">
                        <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_positivo.png" class="img-fluid" style="transform: scale(1.1) translateY(2px);" alt="Login">
                    </div>
                    <div class="d-none d-lg-block lh-1 text-start ms-2">
                        <span class="d-block small opacity-75">Bienvenido/a!</span>
                        <span class="fw-bold text-decoration-underline-hover">Inicia Sesion</span>
                    </div>
                </button>
            <?php endif; ?>

            <?php
            $verCarro = false;
            if ($rolId == 6 || $rolId == 0) $verCarro = true;
            elseif ($modoVentaAsistida && !$esRutaAdmin) $verCarro = true;

            if ($verCarro):
                $cantidadCarrito = isset($_SESSION['carrito']) ? array_sum(array_column($_SESSION['carrito'], 'cantidad')) : 0;
                $totalCarrito = isset($_SESSION['carrito']) ? array_sum(array_map(function ($it) { return $it['precio'] * $it['cantidad']; }, $_SESSION['carrito'])) : 0;
            ?>
                <button type="button" class="btn position-relative text-white border-0 p-0 m-0 d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCarrito" onclick="actualizarCarritoLateral()">
                    <div class="position-relative p-1">
                        <i class="bi bi-cart-fill m-0" style="font-size: 1.8rem;"></i>
                        <span id="badge-carrito-navbar" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-cenco-green border border-2 border-cenco-indigo fw-bold" style="font-size: 0.7rem; padding: 0.3em 0.5em; <?= $cantidadCarrito == 0 ? 'display:none;' : '' ?>"><?= $cantidadCarrito ?></span>
                    </div>
                    <div class="d-none d-lg-block text-start lh-1 ms-2">
                        <span class="d-block small opacity-75" style="font-size: 0.7rem;">Mi Carro</span>
                        <span id="monto-carrito-navbar" class="fw-bold text-white monto-carrito" style="font-size: 1rem;">$<?= number_format($totalCarrito, 0, ',', '.') ?></span>
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
                    <h6 class="modal-title fw-bold text-cenco-indigo text-uppercase small">Seleccionar Centro de Operacion</h6>
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
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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