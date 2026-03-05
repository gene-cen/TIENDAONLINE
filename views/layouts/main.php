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
    function isActiveAdmin($ruta) {
        $current = $_GET['url'] ?? 'admin/dashboard';
        return (strpos($current, $ruta) === 0) ? 'bg-cenco-indigo text-white shadow-sm' : 'text-secondary hover-bg-light';
    }
}

// Variables de vista
$categoriasMenu = $listaCategorias ?? [];

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
                        <input type="password" name="password" class="form-control rounded-3 bg-light border-0" id="loginPass" placeholder="Password" required>
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
                                <input class="form-check-input" type="checkbox" name="terms" id="checkTerms" required>
                                <label class="form-check-label small" for="checkTerms">He leído y acepto los <a href="#" class="text-cenco-indigo fw-bold" onclick="cambiarModal('registerModal', 'termsModal')">Términos y Condiciones</a></label>
                            </div>
                        </div>
                        <div class="mt-4"><button type="submit" class="btn btn-cenco-green w-100 rounded-pill py-3 fw-bold shadow-sm">¡Registrarme!</button></div>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>

<script>
    window.BASE_URL = "<?= BASE_URL ?>";

    window.AccessManager = {
        settings: { activeClasses: [], filter: '', textLevel: 0 },
        init: function () {
            const saved = localStorage.getItem('cenco_accessibility');
            if (saved) {
                this.settings = JSON.parse(saved);
                if (typeof this.settings.textLevel === 'undefined') this.settings.textLevel = 0;
                this.applySettings();
            }
        },
        toggle: function (className) {
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
        cycleText: function () {
            document.body.classList.remove('access-lvl-1', 'access-lvl-2', 'access-lvl-3');
            this.settings.textLevel++;
            if (this.settings.textLevel > 3) this.settings.textLevel = 0;
            else if (this.settings.textLevel > 0) document.body.classList.add('access-lvl-' + this.settings.textLevel);
            this.updateTextButtonLabel();
            this.save();
        },
        updateTextButtonLabel: function () {
            const btnLabel = document.getElementById('text-size-label');
            if (!btnLabel) return;
            const labels = ['Texto Normal', 'Texto Grande', 'Texto Muy Grande', 'Texto Gigante'];
            btnLabel.innerText = labels[this.settings.textLevel];
        },
        setFilter: function (filterName) {
            document.body.classList.remove('filter-grayscale', 'filter-protanopia', 'filter-deuteranopia', 'filter-tritanopia');
            if (filterName) document.body.classList.add('filter-' + filterName);
            this.settings.filter = filterName;
            this.save();
        },
        removeExclusive: function (ClasesToRemove) {
            ClasesToRemove.forEach(c => {
                document.body.classList.remove(c);
                this.settings.activeClasses = this.settings.activeClasses.filter(ac => ac !== c);
            });
        },
        applySettings: function () {
            this.settings.activeClasses.forEach(c => document.body.classList.add(c));
            if (this.settings.filter) this.setFilter(this.settings.filter);
            if (this.settings.textLevel > 0) document.body.classList.add('access-lvl-' + this.settings.textLevel);
            this.updateTextButtonLabel();
        },
        reset: function () {
            this.settings = { activeClasses: [], filter: '', textLevel: 0 };
            const classes = ['access-dark', 'access-invert', 'access-high-contrast', 'access-dyslexic', 'access-no-anim', 'access-lvl-1', 'access-lvl-2', 'access-lvl-3'];
            classes.forEach(c => document.body.classList.remove(c));
            this.setFilter('');
            this.updateTextButtonLabel();
            this.save();
        },
        save: function () {
            localStorage.setItem('cenco_accessibility', JSON.stringify(this.settings));
        }
    };

    // Helper para cambiar entre modales
    function cambiarModal(actualId, siguienteId) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById(actualId)).hide();
        setTimeout(() => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById(siguienteId)).show();
        }, 350);
    }

    document.addEventListener("DOMContentLoaded", function() {
        window.AccessManager.init();
    });
</script>

<script src="<?= BASE_URL ?>js/scripts.js"></script>

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
                if(typeof abrirCarritoLateral === "function") abrirCarritoLateral();
            });
        });
    </script>
    <?php unset($_SESSION['alerta_carrito']); ?>
<?php endif; ?>

</body>
</html>