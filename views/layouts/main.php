<?php
// 1. CONFIGURACIÓN INICIAL Y HELPER DE VISTA
if (session_status() === PHP_SESSION_NONE) session_start();

// Detección de Admin
if (!isset($esAdmin)) {
    $esAdmin = (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin');
}

// Helper para menú activo
if (!function_exists('isActiveAdmin')) {
    function isActiveAdmin($ruta)
    {
        $current = $_GET['url'] ?? 'admin/dashboard';
        return (strpos($current, $ruta) === 0) ? 'bg-cenco-indigo text-white shadow-sm' : 'text-secondary hover-bg-light';
    }
}

$categoriasMenu = $listaCategorias ?? [];
include __DIR__ . '/header.php';
?>

<div class="d-flex" id="wrapper">
    <div id="page-content-wrapper" class="d-flex flex-column min-vh-100 w-100">
        <?php include __DIR__ . '/navbar.php'; ?>
        
        <main class="container-fluid px-0 flex-grow-1">
            <?php
            if (isset($content)) echo $content;
            else echo "<div class='container py-5 text-center'>No content selected</div>";
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
            <?php if (isset($categorias) && !empty($categorias)): foreach ($categorias as $cat):
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
            <?php endforeach; endif; ?>
        </div>
    </div>
    <div class="offcanvas-footer p-4 bg-white border-top shadow-sm">
        <div class="d-grid">
            <a href="#" class="btn btn-outline-cenco-indigo rounded-pill fw-bold py-2"><i class="bi bi-headset me-2"></i> Centro de Ayuda</a>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCarrito" data-bs-scroll="true" data-bs-backdrop="false">
    <div class="offcanvas-header bg-cenco-indigo text-white">
        <h5 class="offcanvas-title fw-bold"><i class="bi bi-cart3 me-2"></i> Tu Carrito</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div id="contenido-carrito-lateral">
            <div class="text-center py-5 text-muted">
                <div class="spinner-border text-secondary" role="status"></div>
                <p class="mt-2 small">Cargando carrito...</p>
            </div>
        </div>
    </div>
    <div class="offcanvas-footer p-3 border-top bg-light">
        <div class="d-flex justify-content-between mb-3 align-items-center">
            <span class="fw-bold fs-5">Total:</span>
            <span class="fw-black text-cenco-red fs-4" id="total-carrito-lateral">$0</span>
        </div>
        <div class="d-grid gap-2">
            <a href="<?= BASE_URL ?>checkout" class="btn btn-cenco-green fw-bold shadow-sm">Ir a Pagar</a>
            <a href="<?= BASE_URL ?>carrito/ver" class="btn btn-outline-secondary btn-sm">Ver Carrito Completo</a>
        </div>
    </div>
</div>

<?php if (isset($esAdmin) && $esAdmin): ?>
    <div class="offcanvas offcanvas-start rounded-end-4" tabindex="-1" id="adminSidebar" style="width: 300px; z-index: 1055;">
        <div class="offcanvas-header border-bottom bg-light">
            <h5 class="offcanvas-title fw-black text-cenco-indigo">
                <i class="bi bi-shield-lock-fill me-2 text-cenco-green"></i>ADMIN PANEL
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="list-group list-group-flush py-3">
                <div class="small fw-bold text-uppercase text-muted px-4 mb-2 ls-1" style="font-size: 0.75rem;">Principal</div>
                <a href="<?= BASE_URL ?>admin/dashboard" class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex align-items-center gap-3 fw-bold rounded-end-pill me-2 <?= isActiveAdmin('admin/dashboard') ?>">
                    <i class="bi bi-speedometer2 fs-5"></i> Dashboard
                </a>
                <a href="<?= BASE_URL ?>admin/analytics" class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex align-items-center gap-3 fw-bold rounded-end-pill me-2 <?= isActiveAdmin('admin/analytics') ?>">
                    <i class="bi bi-graph-up-arrow fs-5"></i> Analítica Web
                </a>
                <a class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex align-items-center justify-content-between gap-3 fw-bold rounded-end-pill me-2 text-secondary hover-bg-light"
                   data-bs-toggle="collapse" href="#menuCategoriasAdmin">
                    <span class="d-flex align-items-center gap-3"><i class="bi bi-tags-fill fs-5"></i> Categorías</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse <?= (isset($_GET['categoria'])) ? 'show' : '' ?>" id="menuCategoriasAdmin">
                    <div class="bg-light mx-4 my-2 rounded-3 border overflow-hidden">
                        <?php if (empty($categoriasMenu)): ?>
                            <div class="p-3 text-muted small">No hay categorías.</div>
                        <?php else: ?>
                            <?php foreach ($categoriasMenu as $cat):
                                $cId = is_array($cat) ? $cat['id'] : $cat->id;
                                $cNom = is_array($cat) ? $cat['nombre'] : $cat->nombre;
                                $isActiveCat = (isset($_GET['categoria']) && $_GET['categoria'] == $cId);
                            ?>
                                <a href="<?= BASE_URL ?>admin/productos?categoria=<?= $cId ?>" class="d-block px-3 py-2 text-decoration-none small border-bottom <?= $isActiveCat ? 'bg-cenco-indigo text-white fw-bold' : 'text-muted hover-bg-white' ?>">
                                    <?= htmlspecialchars($cNom) ?>
                                </a>
                            <?php endforeach; ?>
                            <a href="<?= BASE_URL ?>admin/productos" class="d-block px-3 py-2 text-decoration-none small text-cenco-indigo fw-bold bg-white text-center">Ver Todo</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="small fw-bold text-uppercase text-muted px-4 mt-4 mb-2 ls-1 border-top pt-3" style="font-size: 0.75rem;">Gestión</div>
                <a href="<?= BASE_URL ?>admin/productos" class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex align-items-center gap-3 fw-bold rounded-end-pill me-2 <?= isActiveAdmin('admin/productos') ?>">
                    <i class="bi bi-box-seam fs-5"></i> Inventario Completo
                </a>
                <a href="<?= BASE_URL ?>admin/pedidos" class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex align-items-center gap-3 fw-bold rounded-end-pill me-2 <?= isActiveAdmin('admin/pedidos') ?>">
                    <i class="bi bi-cart-check fs-5"></i> Pedidos y Ventas
                </a>
                <div class="small fw-bold text-uppercase text-muted px-4 mt-4 mb-2 ls-1 border-top pt-3" style="font-size: 0.75rem;">Herramientas</div>
                <a href="<?= BASE_URL ?>admin/importar_erp" class="list-group-item list-group-item-action border-0 px-4 py-2 mb-1 d-flex align-items-center gap-3 rounded-end-pill me-2 <?= isActiveAdmin('admin/importar_erp') ?>">
                    <i class="bi bi-arrow-repeat"></i> Sincronizar ERP
                </a>
                <a href="<?= BASE_URL ?>admin/exportar_pedidos" class="list-group-item list-group-item-action border-0 px-4 py-2 mb-1 d-flex align-items-center gap-3 rounded-end-pill me-2 <?= isActiveAdmin('admin/exportar_pedidos') ?>">
                    <i class="bi bi-file-earmark-excel"></i> Reportes Excel
                </a>
            </div>
        </div>
        <div class="offcanvas-footer p-3 border-top bg-light">
            <a href="<?= BASE_URL ?>home" class="btn btn-outline-cenco-indigo w-100 rounded-pill fw-bold mb-2"><i class="bi bi-shop-window me-2"></i> Ver Tienda</a>
            <a href="<?= BASE_URL ?>auth/logout" class="btn btn-cenco-red w-100 rounded-pill fw-bold text-white"><i class="bi bi-box-arrow-left me-2"></i> Salir</a>
        </div>
    </div>
<?php endif; ?>

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
                        <label for="loginEmail" class="text-muted">Correo electrónico</label>
                    </div>
                    <div class="form-floating mb-2">
                        <input type="password" name="password" class="form-control rounded-3 bg-light border-0" id="loginPass" placeholder="Password" required>
                        <label for="loginPass" class="text-muted">Contraseña</label>
                    </div>
                    <div class="text-end mb-4">
                        <a href="#" class="small text-cenco-green fw-bold text-decoration-none hover-underline" onclick="cambiarModal('loginModal', 'forgotModal')">¿Olvidaste tu contraseña?</a>
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
                <form action="<?= BASE_URL ?>auth/register" method="POST" id="formRegistro">
                    <input type="hidden" name="nombre" id="inputNombreCompleto">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label small fw-bold">Nombre</label><input type="text" id="reg_nombre" class="form-control bg-light border-0" required></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Apellido</label><input type="text" id="reg_apellido" class="form-control bg-light border-0" required></div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">RUT</label>
                            <div class="input-group"><input type="text" name="rut" class="form-control bg-light border-0" oninput="formatearRut(this)" required><button class="btn btn-outline-cenco-indigo border-0 bg-cenco-indigo bg-opacity-10" type="button"><i class="bi bi-search"></i></button></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Celular</label>
                            <div class="input-group"><span class="input-group-text bg-white border-0 text-muted">+569</span><input type="tel" name="telefono" class="form-control bg-light border-0" maxlength="8" required></div>
                        </div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Correo</label><input type="email" name="email" class="form-control bg-light border-0" required></div>
                        <div class="col-12"><label class="form-label small fw-bold">Contraseña</label><input type="password" name="password" class="form-control bg-light border-0" required></div>
                        <div class="col-12"><label class="form-label small fw-bold text-muted">Giro (Opcional)</label><input type="text" name="giro" class="form-control bg-light border-0"></div>
                        
                        <div class="col-12 bg-white border border-light p-3 rounded-3 mt-3 shadow-sm">
                            <div class="form-check form-switch d-flex align-items-center gap-2">
                                <input class="form-check-input fs-5" type="checkbox" id="checkDireccion" onchange="toggleDireccion()" style="cursor: pointer;">
                                <label class="form-check-label fw-bold text-cenco-green" for="checkDireccion">¿Desea añadir una dirección?</label>
                            </div>
                            <div id="direccion-box" style="display:none;" class="mt-3">
                                <div id="mapa-container" style="height: 250px; width: 100%; border-radius: 12px; border: 2px solid #eee;" class="mb-3"></div>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-geo-alt text-danger"></i></span>
                                    <input type="text" name="direccion" id="direccion-input" class="form-control bg-light border-0" placeholder="Calle, Número, Comuna">
                                    <button class="btn btn-cenco-indigo text-white shadow-sm" type="button" id="btnBuscarDireccion" onclick="buscarEnMapaRegister()">Ubicar</button>
                                </div>
                                <input type="hidden" name="latitud" id="latitud"><input type="hidden" name="longitud" id="longitud">
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <div class="form-check p-3 bg-light rounded-3">
                                <input class="form-check-input" type="checkbox" name="terms" id="checkTerms">
                                <label class="form-check-label small" for="checkTerms">He leído y acepto los <a href="#" class="text-cenco-indigo fw-bold" onclick="cambiarModal('registerModal', 'termsModal')">Términos y Condiciones</a></label>
                            </div>
                        </div>
                        <div class="mt-4"><button type="submit" class="btn btn-cenco-green w-100 rounded-pill py-3 fw-bold shadow-sm">¡Registrarme!</button></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 justify-content-center"><small>¿Ya tienes cuenta? <a href="#" class="fw-bold text-cenco-indigo" onclick="cambiarModal('registerModal', 'loginModal')">Ingresa aquí</a></small></div>
        </div>
    </div>
</div>

<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg text-center p-4 bg-gradient-success-light">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
            <div class="mt-n5 mb-3">
                <img id="successImage" src="<?= BASE_URL ?>img/cencocalin/cencocalin_logrado.png" alt="Éxito" class="img-fluid img-mascota-modal">
            </div>
            <h3 class="fw-black text-cenco-green mb-2" id="successTitle">¡Excelente!</h3>
            <p class="text-muted fs-5" id="successMessage">Acción completada.</p>
            <button type="button" class="btn btn-cenco-indigo rounded-pill px-5 fw-bold mt-3 shadow-sm" data-bs-dismiss="modal">Entendido</button>
        </div>
    </div>
</div>

<div class="modal fade" id="loginErrorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg text-center p-4">
            <div class="modal-body p-2">
                <div class="mb-3 position-relative d-inline-block">
                    <div class="position-absolute top-50 start-50 translate-middle bg-danger bg-opacity-10 rounded-circle" style="width: 120px; height: 120px; filter: blur(15px);"></div>
                    <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_algo_fallo.png" alt="Ups" style="width: 140px; position: relative; z-index: 2;">
                </div>
                <h3 class="fw-black text-cenco-red mb-2">¡Ups! Algo falló</h3>
                <p class="text-muted fw-bold mb-1">Usuario o contraseña inválidos.</p>
                <button type="button" class="btn btn-cenco-indigo rounded-pill px-5 py-2 fw-bold shadow-sm" onclick="reabrirLogin()">Entendido, probar de nuevo</button>
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

<div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0 pb-0 bg-light">
                <h5 class="modal-title fw-bold text-cenco-indigo"><i class="bi bi-file-text me-2"></i>Términos y Condiciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-justify bg-white" style="font-size: 0.9rem; line-height: 1.6;">
                <h6 class="fw-bold text-cenco-green mt-2">1. Antecedentes Generales</h6>
                <p class="text-muted">El acceso y uso de este sitio web se rige por los siguientes términos y condiciones. Al utilizar este sitio, usted acepta estos términos en su totalidad.</p>
                <h6 class="fw-bold text-cenco-green mt-3">2. Registro del Usuario</h6>
                <p class="text-muted">Es requisito necesario para la adquisición de productos ofrecidos en este sitio, la aceptación de las presentes condiciones y el registro por parte del usuario.</p>
                <h6 class="fw-bold text-cenco-green mt-3">3. Despacho y Entrega</h6>
                <p class="text-muted">Los productos adquiridos se sujetarán a las condiciones de despacho y entrega elegidas por el usuario y disponibles en el sitio.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center bg-light">
                <button type="button" class="btn btn-cenco-green rounded-pill px-5 fw-bold shadow-sm" data-bs-dismiss="modal">
                    <i class="bi bi-check-lg me-2"></i> Entendido
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="nosotrosModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-lg"> 
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="position-relative">
                <img src="<?= BASE_URL ?>img/banner/banner Cencocal.png" class="w-100 object-fit-cover" style="height: 200px; filter: brightness(0.7);" alt="Equipo Cencocal">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <ul class="nav nav-pills nav-fill mb-4 p-1 bg-white rounded-pill shadow-sm" id="pills-tab" role="tablist">
                    <li class="nav-item"><button class="nav-link active rounded-pill fw-bold" id="pills-objetivo-tab" data-bs-toggle="pill" data-bs-target="#pills-objetivo">Objetivo</button></li>
                    <li class="nav-item"><button class="nav-link rounded-pill fw-bold" id="pills-mision-tab" data-bs-toggle="pill" data-bs-target="#pills-mision">Misión</button></li>
                    <li class="nav-item"><button class="nav-link rounded-pill fw-bold" id="pills-vision-tab" data-bs-toggle="pill" data-bs-target="#pills-vision">Visión</button></li>
                    <li class="nav-item"><button class="nav-link rounded-pill fw-bold" id="pills-valores-tab" data-bs-toggle="pill" data-bs-target="#pills-valores">Valores</button></li>
                </ul>
                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade show active" id="pills-objetivo"><div class="bg-white p-4 rounded-4 shadow-sm border-start border-5 border-cenco-green"><h5 class="fw-bold text-cenco-indigo">Objetivo</h5><p class="text-muted">Ser la mejor distribuidora de la zona.</p></div></div>
                    <div class="tab-pane fade" id="pills-mision"><div class="bg-white p-4 rounded-4 shadow-sm border-start border-5 border-primary"><h5 class="fw-bold text-cenco-indigo">Misión</h5><p class="text-muted">Conveniencia y confianza en todo el país.</p></div></div>
                    <div class="tab-pane fade" id="pills-vision"><div class="bg-white p-4 rounded-4 shadow-sm border-start border-5 border-warning"><h5 class="fw-bold text-cenco-indigo">Visión</h5><p class="text-muted">Liderazgo en el mercado nacional.</p></div></div>
                    <div class="tab-pane fade" id="pills-valores"><div class="bg-white p-4 rounded-4 shadow-sm"><h5 class="fw-bold text-cenco-indigo">Valores</h5><p class="text-muted">Ética, Compromiso e Innovación.</p></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="localesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-cenco-indigo"><i class="bi bi-shop me-2"></i>Nuestras Sucursales</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0">Casa Matriz - La Calera</h6>
                            <small class="text-muted">Huici 353</small>
                        </div>
                        <a href="#" class="btn btn-sm btn-outline-cenco-green rounded-pill"><i class="bi bi-geo-alt-fill"></i> Ir</a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0">Sucursal Valparaíso</h6>
                            <small class="text-muted">Av. Brasil 1234</small>
                        </div>
                        <a href="#" class="btn btn-sm btn-outline-cenco-green rounded-pill"><i class="bi bi-geo-alt-fill"></i> Ir</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<button class="btn btn-accessibility rounded-circle shadow-lg position-fixed bottom-0 start-0 m-4 d-flex align-items-center justify-content-center" 
        data-bs-toggle="modal" data-bs-target="#accessibilityModal" 
        style="z-index: 2050;">
    <i class="bi bi-universal-access-circle fs-1"></i>
</button>

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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>

<script>
    // Variable global vital para JS
    window.BASE_URL = "<?= BASE_URL ?>";
</script>

<?php if (!isset($esAdmin) || !$esAdmin): ?>
    <script src="<?= BASE_URL ?>js/scripts.js"></script>
<?php endif; ?>

</body>
</html>