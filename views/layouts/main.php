<?php

/**
 * ARCHIVO: layout_main.php (Estructura principal)
 * Descripción: Contenedor global que incluye Header, Navbar, Sidebar (si es admin) y Modals.
 */

// 1. CONFIGURACIÓN INICIAL
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definición de URL base si no existe (Asegúrate de tenerla en tu config)
if (!defined('BASE_URL')) define('BASE_URL', '/');

// Detección de Rol de Administrador
$esAdmin = (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin');

// Helper para menú activo en Sidebar Admin
if (!function_exists('isActiveAdmin')) {
    function isActiveAdmin($ruta)
    {
        $current = $_GET['url'] ?? 'admin/dashboard';
        return (strpos($current, $ruta) === 0) ? 'bg-cenco-indigo text-white shadow-sm' : 'text-secondary hover-bg-light';
    }
}

// Variables de vista base
$categoriasMenu = $listaCategorias ?? [];

// =========================================================================================
// 🚀 FIX GLOBAL: "EL SALVAVIDAS DE CATEGORÍAS"
// Si un controlador (ej: Perfil, Checkout) no mandó las categorías, el Layout las busca solo.
// =========================================================================================
if (empty($categoriasMenu) && isset($this->db)) {
    try {
        // Asumiendo que la tabla es 'productos' y la columna 'categoria'
        $stmt = $this->db->query("SELECT DISTINCT categoria AS nombre FROM productos WHERE activo = 1 AND categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC");
        $categoriasMenu = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        $categoriasMenu = []; // Si hay error, se deja vacío para no romper la web
    }
}
// =========================================================================================


// Inclusión del Header (Abre <html> y <head>)
include __DIR__ . '/header.php';
?>

<div class="d-flex" id="wrapper">

    <?php if ($esAdmin): ?>
        <?php include __DIR__ . '/sidebar.php'; ?>
    <?php endif; ?>

    <div id="page-content-wrapper" class="d-flex flex-column min-vh-100 w-100">

        <?php include __DIR__ . '/navbar.php'; ?>

        <main class="container-fluid px-0 flex-grow-1">
            <?php
            if (isset($content)) {
                echo $content;
            } else {
                echo "<div class='container py-5 text-center'><i class='bi bi-exclamation-circle fs-1 text-muted'></i><p class='mt-3'>No se ha seleccionado contenido para mostrar.</p></div>";
            }
            ?>
        </main>

        <?php include __DIR__ . '/footer.php'; ?>
    </div>
</div>

<div class="offcanvas offcanvas-start rounded-end-4" tabindex="-1" id="offcanvasCategorias">
    <div class="offcanvas-body p-0 bg-light overflow-hidden">

        <div class="list-group list-group-flush border-0 mt-2">
            <a href="<?= BASE_URL ?>home/catalogo" class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white">
                <div class="bg-white p-2 rounded-circle shadow-sm me-3 d-flex align-items-center justify-content-center text-cenco-green" style="width:40px;height:40px;">
                    <i class="bi bi-grid-fill fs-5"></i>
                </div>
                Ver Todo el Catálogo
            </a>
            <hr class="my-2 mx-4 opacity-25">
            <?php if (!empty($categoriasMenu)): foreach ($categoriasMenu as $cat):
                    $catNombre = is_object($cat) ? $cat->nombre : $cat['nombre'];
                    $icono = function_exists('obtenerIconoCategoria') ? obtenerIconoCategoria($catNombre) : 'fa-solid fa-tag';
            ?>
                    <a href="<?= BASE_URL ?>home/catalogo?categoria=<?= urlencode($catNombre) ?>" class="list-group-item list-group-item-action py-3 px-4 border-0 bg-transparent d-flex align-items-center justify-content-between transition-hover hover-bg-white group-link">
                        <span class="d-flex align-items-center fw-semibold text-dark">
                            <span class="d-flex align-items-center justify-content-center me-3 text-secondary opacity-75" style="width: 25px;"><i class="<?= $icono ?> fs-4"></i></span>
                            <?= htmlspecialchars($catNombre) ?>
                        </span>
                        <i class="bi bi-chevron-right text-muted opacity-25 small"></i>
                    </a>
            <?php endforeach;
            endif; ?>
        </div>
    </div>
    <div class="offcanvas-footer p-4 bg-white border-top shadow-sm">
        <div class="d-grid">
            <a href="#" class="btn btn-outline-cenco-indigo rounded-pill fw-bold py-2"><i class="bi bi-headset me-2"></i> Centro de Ayuda</a>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end shadow" tabindex="-1" id="offcanvasCarrito" aria-labelledby="offcanvasCarritoLabel">
    <div class="offcanvas-header bg-cenco-indigo text-white border-0">
        <h5 class="offcanvas-title fw-bold" id="offcanvasCarritoLabel"><i class="bi bi-cart3 me-2"></i> Tu Carrito</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-0 custom-scrollbar" id="contenedor-carrito-lista">
        <div class="text-center py-5 mt-5">
            <div class="spinner-border text-cenco-indigo mb-3" role="status"></div>
            <h5 class="text-muted fw-bold">Cargando carrito...</h5>
        </div>
    </div>

    <div class="offcanvas-footer border-top bg-light p-4">
        <div class="offcanvas-footer border-top bg-light p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted fw-bold fs-5">Total a Pagar:</span>
                <span class="fs-3 fw-black text-cenco-red monto-carrito" id="contenedor-carrito-total">$0</span>
            </div>
            <div class="d-grid gap-2">
                <button type="button" onclick="procesarIrAPagar()" class="btn btn-cenco-green fw-bold py-3 shadow-sm rounded-pill hover-scale text-white">
                    Ir a Pagar <i class="bi bi-arrow-right ms-2"></i>
                </button>

                <a href="<?= BASE_URL ?>carrito/ver" class="btn btn-outline-secondary fw-bold py-2 rounded-pill">Ver Carrito Completo</a>
            </div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-start rounded-end-4 shadow" tabindex="-1" id="adminSidebar">
    <div class="offcanvas-header bg-cenco-indigo text-white border-bottom border-3" style="border-color: var(--cenco-green) !important;">
        <h5 class="offcanvas-title fw-black ls-1 d-flex align-items-center gap-2">
            <i class="bi bi-shield-lock-fill text-cenco-green fs-4"></i> ADMINISTRACIÓN
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body p-0 bg-light overflow-hidden">
        <div class="bg-white p-3 border-bottom text-center">
            <span class="d-block text-muted x-small text-uppercase fw-bold mb-1">Operando en:</span>
            <span class="badge bg-cenco-green text-white fs-6 shadow-sm rounded-pill px-3">
                <?= ($_SESSION['admin_sucursal'] ?? 29) == 29 ? 'Sucursal Prat (La Calera)' : 'Sucursal Villa Alemana' ?>
            </span>
        </div>
        <div class="list-group list-group-flush border-0 mt-2">
            <a href="<?= BASE_URL ?>admin/dashboard" class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white">
                <i class="bi bi-graph-up-arrow fs-5 me-3 text-cenco-green"></i> Dashboard
            </a>

            <a href="<?= BASE_URL ?>admin/pedidos" class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white">
                <i class="bi bi-box-seam fs-5 me-3 text-cenco-green"></i> Gestión de Pedidos
            </a>

            <a href="<?= BASE_URL ?>admin/productos" class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white">
                <i class="bi bi-tags fs-5 me-3 text-cenco-green"></i> Control de Inventario
            </a>

            <a href="<?= BASE_URL ?>admin/productos_nuevos" class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white">
                <i class="bi bi-magic fs-5 me-3 text-cenco-green"></i> Productos Nuevos
            </a>

            <?php if (empty($_SESSION['admin_sucursal'])): ?>

                <a href="<?= BASE_URL ?>admin/analytics" class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white">
                    <i class="bi bi-pie-chart-fill fs-5 me-3 text-cenco-green"></i> Analítica Web
                </a>

                <a href="<?= BASE_URL ?>admin/marcas" class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white">
                    <i class="bi bi-award fs-5 me-3 text-cenco-green"></i> Marcas Destacadas
                </a>

                <a href="<?= BASE_URL ?>admin/banners" class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white">
                    <i class="bi bi-images fs-5 me-3 text-cenco-green"></i> Banners Publicitarios
                </a>

            <?php endif; ?>
            <a href="<?= BASE_URL ?>transporte/misEntregas" class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white border-top mt-2">
                <i class="bi bi-truck-flatbed fs-5 me-3 text-cenco-green"></i> Panel Transporte
            </a>

            <a href="<?= BASE_URL ?>admin/stock_fantasma" class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center gap-3">
                <i class="bi bi-radar text-danger fs-4"></i>
                <div>
                    <span class="d-block fw-bold">Stock Fantasma</span>
                    <small class="text-muted">Ajustes de inventario pendientes</small>
                </div>
            </a>
        </div>
    </div>

    <div class="offcanvas-footer p-4 bg-white border-top shadow-sm">
        <div class="d-grid">
            <a href="<?= BASE_URL ?>home" class="btn btn-outline-cenco-indigo rounded-pill fw-bold py-2 shadow-sm transition-hover">
                <i class="bi bi-shop me-2"></i> Ir a la Tienda
            </a>
        </div>
    </div>
</div>

<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-lg border-0 overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4 position-relative">
                <div class="w-100 text-center">
                    <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_positivo.png" alt="Seguridad" style="width: 100px; margin-bottom: 10px;">
                    <h4 class="modal-title fw-black text-cenco-indigo ls-1">¡Hola de nuevo!</h4>
                    <p class="text-muted small mb-0">Ingresa seguro para gestionar tus pedidos.</p>
                </div>
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-3">
                <a href="<?= BASE_URL ?>auth/google" class="btn btn-white border w-100 py-2 mb-3 d-flex align-items-center justify-content-center fw-bold text-secondary shadow-sm rounded-pill transition-hover">
                    <i class="bi bi-google me-2 text-danger"></i> Continuar con Google
                </a>
                <div class="position-relative mb-4 text-center">
                    <hr class="text-muted opacity-25">
                    <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 text-muted small">o con tu correo</span>
                </div>
                <form action="<?= BASE_URL ?>auth/login" method="POST">
                    <div class="form-floating mb-3">
                        <input type="email" name="email" class="form-control rounded-3 bg-light border-0" id="loginEmail" placeholder="name@example.com" required>
                        <label for="loginEmail">Correo electrónico</label>
                    </div>
                    <div class="form-floating mb-2">
                        <input type="password" name="password" class="form-control rounded-3 bg-light border-0" id="loginPass" placeholder="Password" autocomplete="current-password" required>
                        <label for="loginPass">Contraseña</label>
                    </div>
                    <div class="text-end mb-4">
                        <a href="#" class="small text-cenco-green fw-bold text-decoration-none" onclick="cambiarModal('loginModal', 'forgotModal')">¿Olvidaste tu contraseña?</a>
                    </div>
                    <button type="submit" class="btn btn-cenco-indigo w-100 rounded-pill py-3 fw-bold shadow-sm transition-hover">Iniciar Sesión</button>
                </form>
            </div>
            <div class="modal-footer border-0 justify-content-center bg-light py-3">
                <span class="text-muted">¿Eres nuevo?</span>
                <a href="#" class="text-cenco-red fw-black text-decoration-none ms-1" onclick="cambiarModal('loginModal', 'registerModal')">Crear cuenta</a>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 shadow border-0">
            <div class="modal-header border-0 bg-cenco-indigo text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i> Únete a Cencocal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_bienvenida.png" style="width: 100px;">
                </div>
                <form action="<?= BASE_URL ?>auth/register" method="POST" id="formRegistro" onsubmit="return validarRegistro(event)">
                    <input type="hidden" name="nombre" id="inputNombreCompleto">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label small fw-bold">Nombre</label><input type="text" id="reg_nombre" class="form-control bg-light border-0" required></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Apellido</label><input type="text" id="reg_apellido" class="form-control bg-light border-0" required></div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">RUT</label>
                            <div class="input-group">
                                <input type="text" name="rut" class="form-control bg-light border-0" oninput="formatearRut(this)" placeholder="11.111.111-1" required>
                                <button class="btn btn-outline-cenco-indigo border-0 bg-cenco-indigo bg-opacity-10" type="button"><i class="bi bi-person-badge"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Celular</label>
                            <div class="input-group"><span class="input-group-text bg-white border-0 text-muted">+569</span><input type="tel" name="telefono" class="form-control bg-light border-0" maxlength="8" required></div>
                        </div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Correo</label><input type="email" name="email" class="form-control bg-light border-0" autocomplete="username" required></div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contraseña</label>
                            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                <input type="password" name="password" id="reg_password" class="form-control bg-light border-0" autocomplete="new-password" required>
                                <button class="btn bg-light border-0 text-muted px-3" type="button" onclick="togglePassword('reg_password', 'icon_pass1')"><i class="bi bi-eye-fill" id="icon_pass1"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Confirmar Contraseña</label>
                            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                <input type="password" id="reg_password_confirm" class="form-control bg-light border-0" autocomplete="new-password" required>
                                <button class="btn bg-light border-0 text-muted px-3" type="button" onclick="togglePassword('reg_password_confirm', 'icon_pass2')"><i class="bi bi-eye-fill" id="icon_pass2"></i></button>
                            </div>
                        </div>

                        <div class="col-12"><label class="form-label small fw-bold text-muted">Giro (Opcional)</label><input type="text" name="giro" class="form-control bg-light border-0"></div>

                        <div class="col-12 bg-white border border-light p-3 rounded-3 mt-3 shadow-sm">
                            <div class="form-check form-switch d-flex align-items-center gap-2">
                                <input class="form-check-input fs-5" type="checkbox" id="checkDireccion" onchange="toggleDireccion()" style="cursor: pointer;">
                                <label class="form-check-label fw-bold text-cenco-green" for="checkDireccion">¿Desea añadir una dirección de despacho?</label>
                            </div>

                            <div id="direccion-box" style="display:none;" class="mt-3">
                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-cenco-indigo">Región</label>
                                        <select class="form-select bg-light border-0 fw-bold" disabled>
                                            <option selected>Región de Valparaíso</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-cenco-indigo">Comuna</label>
                                        <select class="form-select bg-light border-0" id="reg_comuna" onchange="actualizarInputFinal()">
                                            <option value="">Selecciona tu comuna...</option>
                                            <option value="La Calera">La Calera</option>
                                            <option value="La Cruz">La Cruz</option>
                                            <option value="Quillota">Quillota</option>
                                            <option value="Nogales">Nogales</option>
                                            <option value="Hijuelas">Hijuelas</option>
                                            <option value="Limache">Limache</option>
                                            <option value="Villa Alemana">Villa Alemana</option>
                                            <option value="Quilpué">Quilpué</option>
                                            <option value="Peñablanca">Peñablanca</option>
                                            <option value="Viña del Mar">Viña del Mar</option>
                                            <option value="Valparaíso">Valparaíso</option>
                                            <option value="Concón">Concón</option>
                                        </select>
                                        <div class="form-text text-cenco-green fw-bold" style="font-size: 0.7rem;"><i class="bi bi-info-circle-fill me-1"></i>Si no ves tu comuna, ¡pronto estaremos en ella!</div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label small fw-bold">Calle / Pasaje</label>
                                        <input type="text" id="reg_calle" class="form-control bg-light border-0" placeholder="Ej: San Carlos" oninput="capitalizarPrimeraLetra(this); actualizarInputFinal();">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Número (Opcional)</label>
                                        <input type="text" id="reg_numero" class="form-control bg-light border-0" placeholder="Ej: 123" oninput="actualizarInputFinal()">
                                    </div>
                                    <div class="col-12 mt-2">
                                        <button class="btn btn-outline-cenco-indigo w-100 fw-bold shadow-sm" type="button" onclick="ubicarEnMapa()"><i class="bi bi-geo-alt-fill me-2"></i>Buscar en el Mapa</button>
                                    </div>
                                </div>

                                <div id="mapa-container" style="height: 250px; width: 100%; border-radius: 12px; border: 2px solid #eee;" class="mb-3 position-relative z-0"></div>

                                <input type="hidden" name="direccion" id="direccion-input">
                                <input type="hidden" name="latitud" id="latitud">
                                <input type="hidden" name="longitud" id="longitud">
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <div class="form-check p-3 bg-light rounded-3">
                                <input class="form-check-input" type="checkbox" name="terms" id="checkTerms" required>
                                <label class="form-check-label small" for="checkTerms">He leído y acepto los <a href="#" class="text-cenco-indigo fw-bold" onclick="cambiarModal('registerModal', 'termsModal')">Términos y Condiciones</a></label>
                            </div>
                        </div>
                        <div class="mt-4"><button type="submit" class="btn btn-cenco-green w-100 rounded-pill py-3 fw-bold shadow-sm transition-hover">¡Registrarme!</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="forgotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden p-2">
            <div class="modal-header border-0 p-3"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center px-4 px-sm-5 pt-0 pb-5">
                <div class="mb-4 position-relative d-inline-block">
                    <div class="position-absolute top-50 start-50 translate-middle bg-cenco-green opacity-10 rounded-circle" style="width: 120px; height: 120px; filter: blur(20px);"></div>
                    <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_recordando.png" alt="Recuperar" class="position-relative" style="width: 140px;">
                </div>
                <h3 class="fw-black text-cenco-indigo mb-2">¿Olvidaste tu contraseña?</h3>
                <p class="text-muted mb-4">Ingresa tu correo y te enviaremos un enlace.</p>
                <form action="<?= BASE_URL ?>auth/send-recovery" method="POST">
                    <div class="form-floating mb-4"><input type="email" class="form-control rounded-3" name="email" required placeholder="email"><label>Correo Electrónico</label></div>
                    <button type="submit" class="btn btn-cenco-green w-100 rounded-pill py-3 fw-bold">Enviar Enlace <i class="bi bi-send-fill"></i></button>
                </form>
                <div class="mt-4"><button class="btn btn-link text-decoration-none text-muted small fw-bold" onclick="cambiarModal('forgotModal', 'loginModal')">Volver</button></div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="checkoutAuthModal" tabindex="-1" aria-hidden="true" style="z-index: 1085;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-md-6 p-4 p-md-5 bg-white border-end">
                        <div class="text-center mb-4">
                            <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_positivo.png" style="width: 70px;">
                            <h5 class="fw-black text-cenco-indigo mt-3">Ya tengo cuenta</h5>
                            <p class="text-muted small">Ingresa para un pago rápido y seguimiento.</p>
                        </div>

                        <form action="<?= BASE_URL ?>auth/login?redirect=checkout" method="POST">
                            <div class="form-floating mb-3">
                                <input type="email" name="email" class="form-control bg-light border-0 rounded-3" placeholder="correo" required>
                                <label>Correo electrónico</label>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="password" name="password" class="form-control bg-light border-0 rounded-3" placeholder="clave" required>
                                <label>Contraseña</label>
                            </div>
                            <button type="submit" class="btn btn-cenco-indigo w-100 rounded-pill py-3 fw-bold shadow-sm transition-hover">ENTRAR Y PAGAR</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="#" class="small text-cenco-red fw-bold text-decoration-none" onclick="cambiarModal('checkoutAuthModal', 'registerModal')">¿Eres nuevo? Crea tu cuenta aquí</a>
                        </div>
                    </div>

                    <div class="col-md-6 p-4 p-md-5 bg-light">
                        <div class="text-center mb-4">
                            <div class="bg-white d-inline-flex p-3 rounded-circle shadow-sm mb-2">
                                <i class="bi bi-person-walking text-cenco-green fs-2"></i>
                            </div>
                            <h5 class="fw-black text-cenco-green">Compra como Invitado</h5>
                            <p class="text-muted small">Paga rápidamente sin crear cuenta.</p>
                        </div>

                        <form action="<?= BASE_URL ?>auth/guestLogin" method="POST">
                            <div class="mb-3">
                                <input type="text" name="guest_nombre" class="form-control bg-white border-0 shadow-sm rounded-3 py-2" placeholder="Nombre y Apellido" oninput="capitalizarPrimeraLetra(this)" required>
                            </div>
                            <div class="mb-3">
                                <input type="text" name="guest_rut" class="form-control bg-white border-0 shadow-sm rounded-3 py-2" placeholder="RUT (12.345.678-9)" oninput="formatearRut(this)" required>
                            </div>
                            <div class="mb-3">
                                <input type="email" name="guest_email" class="form-control bg-white border-0 shadow-sm rounded-3 py-2" placeholder="Correo electrónico (Opcional)">
                            </div>
                            <div class="mb-4">
                                <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                    <span class="input-group-text bg-white border-0 text-muted fw-bold">+569</span>
                                    <input type="tel" name="guest_telefono" class="form-control bg-white border-0 py-2" placeholder="Celular" maxlength="8" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-cenco-green w-100 rounded-pill py-3 fw-bold shadow-sm text-white">CONTINUAR AL PAGO <i class="bi bi-arrow-right ms-2"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
        </div>
    </div>
</div>

<div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true" style="z-index: 1085;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-cenco-indigo text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-text me-2"></i> Términos y Condiciones</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-muted" style="font-size: 0.95rem; text-align: justify;">
                <h6 class="fw-bold text-dark">1. Condiciones Generales</h6>
                <p>Bienvenido a la Tienda Online de Cencocal S.A. Al registrarte y utilizar nuestra plataforma, aceptas estar sujeto a los siguientes términos y condiciones. Por favor, léelos cuidadosamente antes de usar nuestros servicios.</p>

                <h6 class="fw-bold text-dark mt-4">2. Registro y Seguridad de la Cuenta</h6>
                <p>Para realizar compras, debes registrarte proporcionando información veraz y actualizada (Nombre, RUT, Dirección). Eres responsable de mantener la confidencialidad de tu contraseña y de todas las actividades que ocurran bajo tu cuenta.</p>

                <h6 class="fw-bold text-dark mt-4">3. Despachos y Zonas de Entrega</h6>
                <p>Realizamos despachos en las zonas geográficas habilitadas por nuestras sucursales (V Región). Los tiempos de entrega están sujetos a la disponibilidad de stock y a la ruta logística diaria de nuestros transportistas.</p>

                <h6 class="fw-bold text-dark mt-4">4. Devoluciones y Garantías</h6>
                <p>Si recibes un producto dañado o incorrecto, tienes un plazo de 10 días para reportarlo a través de nuestros canales oficiales, presentando el comprobante de entrega.</p>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-cenco-indigo rounded-pill px-4 fw-bold shadow-sm" onclick="cambiarModal('termsModal', 'registerModal')">Entendido, volver al registro</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg text-center p-4 bg-gradient-success-light">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
            <div class="mt-n5 mb-3">
                <img id="successImage" src="<?= BASE_URL ?>img/cencocalin/cencocalin_logrado.png" alt="Éxito" class="img-fluid img-mascota-modal" style="width:120px;">
            </div>
            <h3 class="fw-black text-cenco-green mb-2" id="successTitle">¡Excelente!</h3>
            <p class="text-muted fs-5" id="successMessage">Acción completada.</p>
            <button type="button" class="btn btn-cenco-indigo rounded-pill px-5 fw-bold mt-3 shadow-sm" data-bs-dismiss="modal">Entendido</button>
        </div>
    </div>
</div>

<div class="modal fade" id="accessibilityModal" tabindex="-1" aria-hidden="true" style="z-index: 2060;">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-cenco-indigo text-white border-0">
                <h5 class="modal-title fw-bold small"><i class="bi bi-person-wheelchair me-2"></i>Accesibilidad</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3 bg-light">
                <div class="d-grid gap-2">
                    <div class="bg-white p-2 rounded border shadow-sm">
                        <span class="text-uppercase text-muted fw-bold d-block mb-2" style="font-size: 0.7rem;">Visualización</span>
                        <button onclick="AccessManager.toggle('dark')" class="btn btn-sm btn-light w-100 text-start mb-1"><i class="bi bi-moon-stars me-2 text-primary"></i>Modo Oscuro</button>
                        <button onclick="AccessManager.toggle('high-contrast')" class="btn btn-sm btn-light w-100 text-start mb-1"><i class="bi bi-brightness-high me-2 text-warning"></i>Alto Contraste</button>
                        <button onclick="AccessManager.toggle('invert')" class="btn btn-sm btn-light w-100 text-start"><i class="bi bi-circle-half me-2"></i>Invertir</button>
                    </div>
                    <div class="bg-white p-2 rounded border shadow-sm">
                        <span class="text-uppercase text-muted fw-bold d-block mb-2" style="font-size: 0.7rem;">Lectura</span>
                        <button onclick="AccessManager.cycleText()" class="btn btn-sm btn-light w-100 text-start mb-1 d-flex align-items-center"><i id="text-size-icon" class="bi bi-type me-2"></i><span id="text-size-label">Tamaño</span><small class="ms-auto">(Rotar)</small></button>
                        <button onclick="AccessManager.toggle('dyslexic')" class="btn btn-sm btn-light w-100 text-start"><i class="bi bi-book me-2 text-info"></i>Fuente Dislexia</button>
                    </div>
                    <div class="bg-white p-2 rounded border shadow-sm">
                        <span class="text-uppercase text-muted fw-bold d-block mb-2" style="font-size: 0.7rem;">Motor y Color</span>
                        <button onclick="AccessManager.toggle('no-anim')" class="btn btn-sm btn-light w-100 text-start mb-2"><i class="bi bi-pause-circle me-2 text-danger"></i>Sin Animaciones</button>
                        <select class="form-select form-select-sm" onchange="AccessManager.setFilter(this.value)">
                            <option value="">Filtro Color (Normal)</option>
                            <option value="grayscale">Escala de Grises</option>
                            <option value="protanopia">Protanopia (Rojo)</option>
                            <option value="deuteranopia">Deuteranopia (Verde)</option>
                            <option value="tritanopia">Tritanopia (Azul)</option>
                        </select>
                    </div>
                    <button onclick="AccessManager.reset()" class="btn btn-outline-danger btn-sm w-100 rounded-pill mt-2">Restaurar Todo</button>
                </div>
            </div>
        </div>
    </div>
</div>

<button class="btn btn-accessibility rounded-circle shadow-lg position-fixed bottom-0 start-0 m-4 d-flex align-items-center justify-content-center"
    data-bs-toggle="modal" data-bs-target="#accessibilityModal"
    style="z-index: 2050; width: 60px; height: 60px;">
    <i class="bi bi-universal-access-circle fs-1"></i>
</button>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>

<script>
    window.BASE_URL = "<?= BASE_URL ?>";

    window.AccessManager = {
        settings: {
            activeClasses: [],
            filter: '',
            textLevel: 0
        },
        init: function() {
            const saved = localStorage.getItem('cenco_accessibility');
            if (saved) {
                this.settings = JSON.parse(saved);
                if (typeof this.settings.textLevel === 'undefined') this.settings.textLevel = 0;
                this.applySettings();
            }
        },
        toggle: function(className) {
            const body = document.body;
            const fullClass = 'access-' + className;
            if (body.classList.contains(fullClass)) {
                body.classList.remove(fullClass);
                this.settings.activeClasses = this.settings.activeClasses.filter(c => c !== fullClass);
            } else {
                if (className === 'dark') this.removeExclusive(['access-invert', 'access-high-contrast']);
                if (className === 'invert') this.removeExclusive(['access-dark', 'access-high-contrast']);
                if (className === 'high-contrast') this.removeExclusive(['access-dark', 'access-invert']);
                body.classList.add(fullClass);
                this.settings.activeClasses.push(fullClass);
            }
            this.save();
        },
        cycleText: function() {
            document.body.classList.remove('access-lvl-1', 'access-lvl-2', 'access-lvl-3');
            this.settings.textLevel++;
            if (this.settings.textLevel > 3) this.settings.textLevel = 0;
            else if (this.settings.textLevel > 0) document.body.classList.add('access-lvl-' + this.settings.textLevel);
            this.updateTextButtonLabel();
            this.save();
        },
        updateTextButtonLabel: function() {
            const btnLabel = document.getElementById('text-size-label');
            if (!btnLabel) return;
            const labels = ['Texto Normal', 'Texto Grande', 'Texto Muy Grande', 'Texto Gigante'];
            btnLabel.innerText = labels[this.settings.textLevel];
        },
        setFilter: function(filterName) {
            document.body.classList.remove('filter-grayscale', 'filter-protanopia', 'filter-deuteranopia', 'filter-tritanopia');
            if (filterName) document.body.classList.add('filter-' + filterName);
            this.settings.filter = filterName;
            this.save();
        },
        removeExclusive: function(ClasesToRemove) {
            ClasesToRemove.forEach(c => {
                document.body.classList.remove(c);
                this.settings.activeClasses = this.settings.activeClasses.filter(ac => ac !== c);
            });
        },
        applySettings: function() {
            this.settings.activeClasses.forEach(c => document.body.classList.add(c));
            if (this.settings.filter) this.setFilter(this.settings.filter);
            if (this.settings.textLevel > 0) document.body.classList.add('access-lvl-' + this.settings.textLevel);
            this.updateTextButtonLabel();
        },
        reset: function() {
            this.settings = {
                activeClasses: [],
                filter: '',
                textLevel: 0
            };
            const classes = ['access-dark', 'access-invert', 'access-high-contrast', 'access-dyslexic', 'access-no-anim', 'access-lvl-1', 'access-lvl-2', 'access-lvl-3'];
            classes.forEach(c => document.body.classList.remove(c));
            this.setFilter('');
            this.updateTextButtonLabel();
            this.save();
        },
        save: function() {
            localStorage.setItem('cenco_accessibility', JSON.stringify(this.settings));
        }
    };

    // Helper para cambiar entre modales
    function cambiarModal(actualId, siguienteId) {
        if (document.activeElement) {
            document.activeElement.blur(); // Quita el foco para evitar el warning ARIA de Chrome
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById(actualId)).hide();
        setTimeout(() => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById(siguienteId)).show();
        }, 350);
    }

    document.addEventListener("DOMContentLoaded", function() {
        window.AccessManager.init();

        // 1. MAYÚSCULAS AUTOMÁTICAS EN NOMBRES
        const inputsNombres = ['reg_nombre', 'reg_apellido'];
        inputsNombres.forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener('input', function(e) {
                    let palabras = e.target.value.toLowerCase().split(' ');
                    for (let i = 0; i < palabras.length; i++) {
                        if (palabras[i].length > 0) {
                            palabras[i] = palabras[i][0].toUpperCase() + palabras[i].substring(1);
                        }
                    }
                    e.target.value = palabras.join(' ');
                });
            }
        });
    });

    // ==========================================
    // VALIDACIÓN DE CONTRASEÑAS (Bloqueo de Envío)
    // ==========================================
    function validarRegistro(event) {
        const pass1 = document.getElementById('reg_password').value;
        const pass2 = document.getElementById('reg_password_confirm').value;

        // Si NO coinciden, bloqueamos todo
        if (pass1 !== pass2) {
            event.preventDefault(); // Detiene el POST al servidor

            Swal.fire({
                icon: 'error',
                title: 'Contraseñas no coinciden',
                text: 'Por favor, asegúrate de que ambas contraseñas sean idénticas.',
                confirmButtonColor: '#E53935'
            });

            document.getElementById('reg_password').classList.add('is-invalid');
            document.getElementById('reg_password_confirm').classList.add('is-invalid');

            return false;
        }

        // Si coinciden, limpiamos bordes rojos y preparamos el nombre completo
        document.getElementById('reg_password').classList.remove('is-invalid');
        document.getElementById('reg_password_confirm').classList.remove('is-invalid');

        const nombre = document.getElementById('reg_nombre').value.trim();
        const apellido = document.getElementById('reg_apellido').value.trim();
        document.getElementById('inputNombreCompleto').value = nombre + ' ' + apellido;

        return true;
    }

    // ==========================================
    // HERRAMIENTAS DE FORMULARIO
    // ==========================================
    function formatearRut(input) {
        let valor = input.value.replace(/[^0-9kK]/g, '').toUpperCase();
        if (valor.length > 9) valor = valor.slice(0, 9);
        let cuerpo = valor.slice(0, -1);
        let dv = valor.slice(-1);
        if (valor.length > 1) {
            cuerpo = cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            input.value = cuerpo + '-' + dv;
        } else {
            input.value = valor;
        }
    }

    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
        } else {
            input.type = "password";
            icon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
        }
    }

    function capitalizarPrimeraLetra(input) {
        let val = input.value;
        if (val.length > 0) {
            input.value = val.charAt(0).toUpperCase() + val.slice(1);
        }
    }

    function actualizarInputFinal() {
        const calle = document.getElementById('reg_calle').value.trim();
        const numero = document.getElementById('reg_numero').value.trim();
        const comuna = document.getElementById('reg_comuna').value;

        let direccionCompleta = calle;
        if (numero) direccionCompleta += " " + numero;
        if (comuna) direccionCompleta += ", " + comuna;

        document.getElementById('direccion-input').value = direccionCompleta;
    }

    // ==========================================
    // MAPA LEAFLET
    // ==========================================
    let mapaReg = null;
    let markerReg = null;

    // Corrección íconos 404
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
        iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
    });

    function toggleDireccion() {
        const box = document.getElementById('direccion-box');
        const isChecked = document.getElementById('checkDireccion').checked;

        if (isChecked) {
            box.style.display = 'block';
            // Reflow: forzamos que el navegador asimile el display:block antes de inicializar
            requestAnimationFrame(() => {
                setTimeout(() => {
                    inicializarMapaRegistro();
                }, 300);
            });
        } else {
            box.style.display = 'none';
        }
    }

    function inicializarMapaRegistro() {
        if (mapaReg !== null) {
            mapaReg.invalidateSize();
            return;
        }

        const latInicial = -32.7845;
        const lngInicial = -71.2136;

        mapaReg = L.map('mapa-container').setView([latInicial, lngInicial], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(mapaReg);

        markerReg = L.marker([latInicial, lngInicial], {
            draggable: true
        }).addTo(mapaReg);

        // Doble validación para acomodar grillas
        setTimeout(() => {
            mapaReg.invalidateSize();
        }, 500);

        markerReg.on('dragend', function(e) {
            const position = markerReg.getLatLng();
            document.getElementById('latitud').value = position.lat;
            document.getElementById('longitud').value = position.lng;
        });

        mapaReg.on('click', function(e) {
            markerReg.setLatLng(e.latlng);
            document.getElementById('latitud').value = e.latlng.lat;
            document.getElementById('longitud').value = e.latlng.lng;
        });

        // Geolocalizar celular
        if (window.innerWidth <= 768 && navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    mapaReg.setView([lat, lng], 17);
                    markerReg.setLatLng([lat, lng]);
                    document.getElementById('latitud').value = lat;
                    document.getElementById('longitud').value = lng;
                },
                (err) => {
                    console.warn("GPS denegado");
                }
            );
        }
    }

    function ubicarEnMapa() {
        actualizarInputFinal();
        const dir = document.getElementById('direccion-input').value;
        const comuna = document.getElementById('reg_comuna').value;

        if (dir.trim() === '' || !comuna) {
            Swal.fire('Faltan Datos', 'Ingresa tu calle y selecciona una comuna.', 'warning');
            return;
        }

        const busqueda = dir + ", Región de Valparaíso, Chile";
        const btn = event.currentTarget;
        const oldHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Buscando...';
        btn.disabled = true;

        fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(busqueda))
            .then(res => res.json())
            .then(data => {
                if (data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);

                    mapaReg.setView([lat, lng], 17);
                    markerReg.setLatLng([lat, lng]);
                    document.getElementById('latitud').value = lat;
                    document.getElementById('longitud').value = lng;
                } else {
                    Swal.fire('No encontrada', 'No pudimos ubicar la calle. Arrastra el pin rojo del mapa hasta tu casa.', 'info');
                }
            })
            .finally(() => {
                btn.innerHTML = oldHtml;
                btn.disabled = false;
            });
    }

    // ==========================================
    // FLUJO DE CHECKOUT (LOGIN / INVITADO)
    // ==========================================
    function procesarIrAPagar() {
        // PHP nos dirá si existe una sesión de usuario normal O una sesión temporal de invitado
        const usuarioAutorizado = <?= (isset($_SESSION['user_id']) || isset($_SESSION['invitado'])) ? 'true' : 'false' ?>;

        if (usuarioAutorizado) {
            // Si ya inició sesión o ya llenó sus datos de invitado, pasa directo a la caja
            window.location.href = BASE_URL + 'checkout';
        } else {
            // Si no hay rastro de él, abrimos el Modal
            bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasCarrito')).hide();

            setTimeout(() => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('checkoutAuthModal')).show();
            }, 350);
        }
    }
</script>

<?php if (isset($_SESSION['alerta_carrito'])): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: 'warning',
                title: '<?= $_SESSION['alerta_carrito']['titulo'] ?>',
                html: '<?= $_SESSION['alerta_carrito']['texto'] ?>',
                confirmButtonColor: '#2A1B5E',
                confirmButtonText: 'Entendido'
            }).then(() => {
                if (typeof abrirCarritoLateral === "function") abrirCarritoLateral();
            });
        });
    </script>
    <?php unset($_SESSION['alerta_carrito']); ?>
<?php endif; ?>

<?php if (isset($_GET['msg'])): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            <?php if ($_GET['msg'] === 'registro_ok' || $_GET['msg'] === 'registro_exito'): ?>
                Swal.fire({
                    title: '<span style="color: var(--cenco-indigo); font-weight: 900;">¡Revisa tu correo!</span>',
                    html: 'Hemos creado tu cuenta exitosamente.<br>Te enviamos un enlace de validación para activarla.',
                    imageUrl: '<?= BASE_URL ?>img/cencocalin/cencocalin_envio_correo.png',
                    imageWidth: 140,
                    imageAlt: 'Correo Enviado',
                    confirmButtonColor: '#76C043',
                    confirmButtonText: '¡Iré a revisar!',
                    backdrop: `rgba(42, 27, 94, 0.4)`
                });

            <?php elseif ($_GET['msg'] === 'registro_exito_sin_correo'): ?>
                Swal.fire({
                    title: '<span style="color: var(--cenco-indigo); font-weight: 900;">Cuenta creada</span>',
                    html: 'Tu cuenta fue guardada en la base de datos, pero <b>tuvimos un bloqueo al enviar el correo de activación</b>.<br><br><small><i>Tip técnico: Revisa la contraseña de aplicación de Gmail.</i></small>',
                    icon: 'warning',
                    confirmButtonColor: '#E53935',
                    confirmButtonText: 'Entendido'
                });

            <?php elseif ($_GET['msg'] === 'error_email_duplicado'): ?>
                Swal.fire({
                    title: '¡Ups!',
                    text: 'Este correo electrónico ya está registrado en nuestra base de datos. Intenta iniciar sesión.',
                    icon: 'error',
                    confirmButtonColor: '#E53935'
                });

            <?php elseif ($_GET['msg'] === 'cuenta_existente'): ?>
                Swal.fire({
                    title: '<span style="color: var(--cenco-indigo); font-weight: 900;">¡Ya eres parte de Cencocal!</span>',
                    html: 'El correo que ingresaste ya tiene una cuenta registrada con nosotros.<br><br>Por seguridad, <b>por favor inicia sesión</b> en el panel izquierdo para continuar con tu compra.',
                    icon: 'info',
                    confirmButtonColor: '#2A1B5E',
                    confirmButtonText: 'Iniciar Sesión',
                }).then((result) => {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('checkoutAuthModal')).show();
                });

            <?php elseif ($_GET['msg'] === 'error_db'): ?>
                Swal.fire('Error del Sistema', 'No pudimos guardar los datos en la base de datos.', 'error');
            <?php endif; ?>

        });
    </script>
<?php endif; ?>

<script src="<?= BASE_URL ?>js/scripts.js?v=2.0"></script>

<script>
    // ==========================================
    // SEGURO DE VIDA: LIMPIEZA DE MODALES FANTASMAS
    // ==========================================
    document.addEventListener("DOMContentLoaded", function() {
        // Quitamos el bloqueo del body por si algún modal quedó "abierto" virtualmente
        if (document.body.classList.contains('modal-open')) {
            document.body.classList.remove('modal-open');
            document.body.style.overflow = 'auto';
            document.body.style.paddingRight = '';
        }

        // Eliminamos directamente cualquier fondo oscuro que haya quedado "colgado"
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
    });
</script>

</body>

</html>