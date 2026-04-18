<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-lg border-0 overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4 position-relative">
                <div class="w-100 text-center">
                    <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_positivo.png" alt="Seguridad" style="width: 100px; margin-bottom: 10px;">
                    <h4 class="modal-title fw-black text-cenco-indigo ls-1" id="loginModalLabel">¡Bienvenido!</h4>
                    <p class="text-muted small mb-0">Ingresa o crea tu cuenta para ingresar.</p>
                </div>
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
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



            <div id="wrapper-direccion" class="d-none animate__animated animate__fadeIn">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Región</label>
                        <input type="text" class="form-control bg-light" value="V Región de Valparaíso" readonly name="region">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Comuna</label>
                        <select class="form-select" name="comuna" id="select-comuna">
                            <option value="" selected disabled>Selecciona tu comuna</option>
                            <option value="Nogales">Nogales</option>
                            <option value="Hijuelas">Hijuelas</option>
                            <option value="La Calera">La Calera</option>
                            <option value="La Cruz">La Cruz</option>
                            <option value="Quillota">Quillota</option>
                            <option value="Peñablanca">Peñablanca</option>
                            <option value="Villa Alemana">Villa Alemana</option>
                            <option value="Quilpue">Quilpué</option>
                            <option value="Limache">Limache</option>
                            <option value="Con Con">Concón</option>
                            <option value="Viña del Mar">Viña del Mar</option>
                            <option value="Valparaiso">Valparaíso</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Calle</label>
                        <input type="text" class="form-control" name="calle" placeholder="Ej: Av. Libertad">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Número</label>
                        <input type="text" class="form-control" name="numero" placeholder="Ej: 123">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold text-indigo">Ajusta el pin en tu ubicación exacta:</label>
                        <div id="map-registro" style="height: 300px;" class="rounded-4 border shadow-sm"></div>
                        <input type="hidden" name="latitud" id="lat-reg">
                        <input type="hidden" name="longitud" id="lng-reg">
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 justify-content-center bg-light py-3">
                <span class="text-muted">¿Eres nuevo?</span>
                <a href="#" class="text-cenco-red fw-black text-decoration-none ms-1" onclick="cambiarModal('loginModal', 'registerModal')">Crear cuenta</a>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 shadow-lg border-0">
            <div class="modal-header border-0 bg-cenco-indigo text-white p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white rounded-circle p-2 shadow-sm">
                        <i class="bi bi-person-plus-fill text-cenco-indigo fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-black mb-0">Crea tu Cuenta</h5>
                        <p class="mb-0 small opacity-75">Únete a la familia Cencocal S.A.</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-light">
                <div class="text-center mb-4">
                    <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_bienvenida.png" style="width: 90px;" alt="Bienvenida" class="floating-anim">
                </div>

                <form action="<?= BASE_URL ?>auth/register" method="POST" id="formRegistro">
                    <input type="hidden" name="nombre" id="inputNombreCompleto">

                    <div class="bg-white p-4 rounded-4 shadow-sm mb-4">
                        <h6 class="text-uppercase text-muted fw-bold mb-4 border-bottom pb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Información Personal</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nombre</label>
                                <input type="text" id="reg_nombre" class="form-control border-2 shadow-none" required placeholder="Ej: Juan">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Apellido</label>
                                <input type="text" id="reg_apellido" class="form-control border-2 shadow-none" required placeholder="Ej: Pérez">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">RUT</label>
                                <div class="input-group">
                                    <input type="text" name="rut" class="form-control border-2 shadow-none" placeholder="12.345.678-9" maxlength="12" required>
                                    <span class="input-group-text bg-light border-2 border-start-0"><i class="bi bi-person-badge"></i></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Celular</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-2 border-end-0 fw-bold text-muted">+569</span>
                                    <input type="tel" name="telefono" class="form-control border-2 shadow-none border-start-0" maxlength="8" required placeholder="87654321" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control border-2 shadow-none" required placeholder="nombre@ejemplo.com">
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-4 shadow-sm mb-4">
                        <h6 class="text-uppercase text-muted fw-bold mb-4 border-bottom pb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Seguridad</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Contraseña</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="reg_password" class="form-control border-2 shadow-none border-end-0" required minlength="6" placeholder="Mín. 6 caracteres" autocomplete="new-password">
                                    <button class="btn btn-light border-2 border-start-0" type="button" onclick="togglePassword('reg_password', this)"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Confirmar Contraseña</label>
                                <div class="input-group">
                                    <input type="password" id="reg_password_confirm" class="form-control border-2 shadow-none border-end-0" required minlength="6" placeholder="Repite tu clave" autocomplete="new-password">
                                    <button class="btn btn-light border-2 border-start-0" type="button" onclick="togglePassword('reg_password_confirm', this)"><i class="bi bi-eye"></i></button>
                                </div>
                                <div id="msg_password_error" class="text-danger small fw-bold mt-1 d-none">
                                    <i class="bi bi-exclamation-circle-fill me-1"></i> Las contraseñas no coinciden.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-4 shadow-sm mb-4">
                        <label class="form-label small fw-bold text-muted">Giro Comercial (Opcional)</label>
                        <input type="text" name="giro" class="form-control border-2 shadow-none" placeholder="Ej: Minimarket, Almacén...">
                    </div>

                    <div class="col-12 mb-4">
                        <div class="form-check form-switch p-3 border rounded-4 bg-white shadow-sm d-flex align-items-center">
                            <input class="form-check-input ms-0 me-3 fs-5" type="checkbox" id="reg-check-direccion" name="quiere_direccion">
                            <label class="form-check-label fw-bold text-dark mb-0" for="reg-check-direccion">
                                ¿Deseas añadir tu dirección de despacho ahora? <small class="text-muted fw-normal">(Opcional)</small>
                            </label>
                        </div>
                    </div>

                    <div id="reg-wrapper-direccion" class="d-none animate__animated animate__fadeIn">
                        <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border border-indigo-subtle">
                            <h6 class="text-uppercase text-muted fw-bold mb-4 border-bottom pb-2" style="font-size: 0.75rem; letter-spacing: 1px;">
                                <i class="bi bi-geo-alt-fill text-cenco-red me-1"></i> Dirección de Despacho
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Región</label>
                                    <input type="text" class="form-control bg-light" value="V Región de Valparaíso" readonly name="region">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Comuna</label>
                                    <select class="form-select" name="comuna" id="reg-select-comuna">
                                        <option value="" selected disabled>Selecciona tu comuna</option>
                                        <option value="Nogales">Nogales</option>
                                        <option value="Hijuelas">Hijuelas</option>
                                        <option value="La Calera">La Calera</option>
                                        <option value="La Cruz">La Cruz</option>
                                        <option value="Quillota">Quillota</option>
                                        <option value="Peñablanca">Peñablanca</option>
                                        <option value="Villa Alemana">Villa Alemana</option>
                                        <option value="Quilpue">Quilpué</option>
                                        <option value="Limache">Limache</option>
                                        <option value="Con Con">Concón</option>
                                        <option value="Viña del Mar">Viña del Mar</option>
                                        <option value="Valparaiso">Valparaíso</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-9">
                                    <label class="form-label small fw-bold text-muted">Calle</label>
                                    <input type="text" class="form-control" name="calle" id="reg-calle" placeholder="Nombre de la calle" disabled>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">N°</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control border-end-0" name="numero" id="reg-numero" placeholder="123" disabled>
                                        <button class="btn btn-outline-secondary border-start-0 text-cenco-indigo" type="button" id="btn-buscar-direccion" title="Ubicar en el mapa">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-12 mt-4">
                                    <p class="small text-muted mb-2"><i class="bi bi-pin-map-fill text-danger"></i> (Opcional) Puedes ajustar el pin para mayor precisión en el mapa:</p>
                                    <div id="reg-map" style="height: 250px; z-index: 1;" class="rounded-4 border shadow-sm mb-3"></div>

                                    <input type="hidden" name="latitud" id="reg-lat">
                                    <input type="hidden" name="longitud" id="reg-lng">

                                    <div class="d-grid mt-3">
                                        <button type="button" id="btn-guardar-direccion-ui" class="btn btn-outline-success rounded-pill fw-bold">
                                            <i class="bi bi-check-circle-fill me-2"></i> Confirmar Dirección
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-check p-3 bg-white rounded-3 shadow-sm border mb-4 text-center">
                        <input class="form-check-input ms-0" type="checkbox" name="terms" id="checkTerms" required>
                        <label class="form-check-label small ms-2" for="checkTerms">
                            He leído y acepto los <a href="#" class="text-cenco-indigo fw-bold text-decoration-none" onclick="cambiarModal('registerModal', 'termsModal')">Términos y Condiciones</a>
                        </label>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" id="btnSubmitRegistro" class="btn btn-cenco-green rounded-pill py-3 fw-black shadow-sm transition-hover">
                            ¡CREAR MI CUENTA! <i class="bi bi-rocket-takeoff-fill ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="forgotModal" tabindex="-1" aria-labelledby="forgotModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden p-2">
            <div class="modal-header border-0 p-3"><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body text-center px-4 px-sm-5 pt-0 pb-5">
                <div class="mb-4 position-relative d-inline-block">
                    <div class="position-absolute top-50 start-50 translate-middle bg-cenco-green opacity-10 rounded-circle" style="width: 120px; height: 120px; filter: blur(20px);"></div>
                    <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_recordando.png" alt="Recuperar" class="position-relative" style="width: 140px;">
                </div>
                <h3 class="fw-black text-cenco-indigo mb-2" id="forgotModalLabel">¿Olvidaste tu contraseña?</h3>
                <p class="text-muted mb-4">Ingresa tu correo y te enviaremos un enlace.</p>
                <form action="<?= BASE_URL ?>auth/send-recovery" method="POST">
                    <div class="form-floating mb-4"><input type="email" class="form-control rounded-3" name="email" required placeholder="email" id="forgotEmail"><label for="forgotEmail">Correo Electrónico</label></div>
                    <button type="submit" class="btn btn-cenco-green w-100 rounded-pill py-3 fw-bold">Enviar Enlace <i class="bi bi-send-fill"></i></button>
                </form>
                <div class="mt-4"><button class="btn btn-link text-decoration-none text-muted small fw-bold" onclick="cambiarModal('forgotModal', 'loginModal')">Volver</button></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="checkoutAuthModal" tabindex="-1" aria-labelledby="checkoutAuthModalLabel">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-md-6 p-4 p-md-5 bg-white border-end">
                        <div class="text-center mb-4">
                            <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_positivo.png" style="width: 70px;" alt="Login">
                            <h5 class="fw-black text-cenco-indigo mt-3" id="checkoutAuthModalLabel">Ya tengo cuenta</h5>
                            <p class="text-muted small">Ingresa para un pago rápido y seguimiento.</p>
                        </div>
                        <form action="<?= BASE_URL ?>auth/login?redirect=checkout" method="POST">
                            <div class="form-floating mb-3">
                                <input type="email" name="email" class="form-control bg-light border-0 rounded-3" placeholder="correo" required id="chkEmail">
                                <label for="chkEmail">Correo electrónico</label>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="password" name="password" class="form-control bg-light border-0 rounded-3" placeholder="clave" required id="chkPass">
                                <label for="chkPass">Contraseña</label>
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
                                <input type="text" name="rut" class="form-control border-2 shadow-none" placeholder="12.345.678-9" maxlength="12" required>
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
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
    </div>
</div>

<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-cenco-indigo text-white border-0">
                <h5 class="modal-title fw-bold" id="termsModalLabel"><i class="bi bi-file-earmark-text me-2"></i> Términos y Condiciones</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-muted" style="font-size: 0.95rem; text-align: justify;">
                <h6 class="fw-bold text-dark">1. Condiciones Generales</h6>
                <p>Bienvenido a la Tienda Online de Cencocal S.A. Al registrarte y utilizar nuestra plataforma, aceptas estar sujeto a los siguientes términos y condiciones.</p>
                <h6 class="fw-bold text-dark mt-4">2. Registro y Seguridad de la Cuenta</h6>
                <p>Para realizar compras, debes registrarte proporcionando información veraz y actualizada (Nombre, RUT, Dirección). Eres responsable de mantener la confidencialidad de tu contraseña.</p>
                <h6 class="fw-bold text-dark mt-4">3. Despachos y Zonas de Entrega</h6>
                <p>Realizamos despachos en las zonas geográficas habilitadas por nuestras sucursales (V Región). Los tiempos de entrega están sujetos a disponibilidad de stock.</p>
                <h6 class="fw-bold text-dark mt-4">4. Devoluciones y Garantías</h6>
                <p>Si recibes un producto dañado o incorrecto, tienes un plazo de 10 días para reportarlo a través de nuestros canales oficiales.</p>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-cenco-indigo rounded-pill px-4 fw-bold shadow-sm" onclick="cambiarModal('termsModal', 'registerModal')">Entendido, volver al registro</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg text-center p-4 bg-gradient-success-light">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="mt-n5 mb-3">
                <img id="successImage" src="<?= BASE_URL ?>img/cencocalin/cencocalin_logrado.png" alt="Éxito" class="img-fluid img-mascota-modal" style="width:120px;">
            </div>
            <h3 class="fw-black text-cenco-green mb-2" id="successTitle">¡Excelente!</h3>
            <p class="text-muted fs-5" id="successMessage">Acción completada.</p>
            <button type="button" class="btn btn-cenco-indigo rounded-pill px-5 fw-bold mt-3 shadow-sm" data-bs-dismiss="modal">Entendido</button>
        </div>
    </div>
</div>

<div class="modal fade" id="accessibilityModal" tabindex="-1" aria-labelledby="accessibilityModalLabel" style="z-index: 2060;">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-cenco-indigo text-white border-0">
                <h5 class="modal-title fw-bold small" id="accessibilityModalLabel"><i class="bi bi-person-wheelchair me-2"></i>Accesibilidad</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 bg-light">
                <div class="d-grid gap-2">
                   <div class="bg-white p-2 rounded border shadow-sm">
    <span class="text-uppercase text-muted fw-bold d-block mb-2" style="font-size: 0.7rem;">Visualización</span>
    <button onclick="window.AccessManager.toggle('dark')" class="btn btn-sm btn-light w-100 text-start mb-1"><i class="bi bi-moon-stars me-2 text-primary"></i>Modo Oscuro</button>
    <button onclick="window.AccessManager.toggle('high-contrast')" class="btn btn-sm btn-light w-100 text-start mb-1"><i class="bi bi-brightness-high me-2 text-warning"></i>Alto Contraste</button>
    <button onclick="window.AccessManager.toggle('invert')" class="btn btn-sm btn-light w-100 text-start"><i class="bi bi-circle-half me-2"></i>Invertir</button>
</div>
<div class="bg-white p-2 rounded border shadow-sm">
    <span class="text-uppercase text-muted fw-bold d-block mb-2" style="font-size: 0.7rem;">Lectura</span>
    <button onclick="window.AccessManager.cycleText()" class="btn btn-sm btn-light w-100 text-start mb-1 d-flex align-items-center"><i id="text-size-icon" class="bi bi-type me-2"></i><span id="text-size-label">Tamaño</span><small class="ms-auto">(Rotar)</small></button>
    <button onclick="window.AccessManager.toggle('dyslexic')" class="btn btn-sm btn-light w-100 text-start"><i class="bi bi-book me-2 text-info"></i>Fuente Dislexia</button>
</div>
<div class="bg-white p-2 rounded border shadow-sm">
    <span class="text-uppercase text-muted fw-bold d-block mb-2" style="font-size: 0.7rem;">Motor y Color</span>
    <button onclick="window.AccessManager.toggle('no-anim')" class="btn btn-sm btn-light w-100 text-start mb-2"><i class="bi bi-pause-circle me-2 text-danger"></i>Sin Animaciones</button>
    <select class="form-select form-select-sm" onchange="window.AccessManager.setFilter(this.value)" aria-label="Filtro de Color">
        <option value="">Filtro Color (Normal)</option>
        <option value="grayscale">Escala de Grises</option>
        <option value="protanopia">Protanopia (Rojo)</option>
        <option value="deuteranopia">Deuteranopia (Verde)</option>
        <option value="tritanopia">Tritanopia (Azul)</option>
    </select>
</div>
<button onclick="window.AccessManager.reset()" class="btn btn-outline-danger btn-sm w-100 rounded-pill mt-2">Restaurar Todo</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="nosotrosModal" tabindex="-1" aria-labelledby="nosotrosModalLabel" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            
            <div class="position-relative">
                <img src="<?= BASE_URL ?>img/banner/banner Cencocal.png" class="w-100 object-fit-cover" style="height: 200px; filter: brightness(0.7);" alt="Equipo Cencocal">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-light">
                <ul class="nav nav-pills nav-fill mb-4 p-1 bg-white rounded-pill shadow-sm" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill fw-bold" id="pills-objetivo-tab" data-bs-toggle="pill" data-bs-target="#pills-objetivo" type="button" role="tab">Objetivo</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-bold" id="pills-mision-tab" data-bs-toggle="pill" data-bs-target="#pills-mision" type="button" role="tab">Misión</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-bold" id="pills-vision-tab" data-bs-toggle="pill" data-bs-target="#pills-vision" type="button" role="tab">Visión</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-bold" id="pills-valores-tab" data-bs-toggle="pill" data-bs-target="#pills-valores" type="button" role="tab">Valores</button>
                    </li>
                </ul>

                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade show active" id="pills-objetivo" role="tabpanel">
                        <div class="bg-white p-4 rounded-4 shadow-sm border-start border-5 border-cenco-green h-100">
                            <h5 class="fw-bold text-cenco-indigo mb-3"><i class="bi bi-bullseye me-2 text-cenco-green"></i>Nuestro Objetivo</h5>
                            <p class="text-muted text-justify mb-0">
                                Mantenernos como la mejor distribuidora de abarrotes de la zona norte y centro de nuestro país, con altas expectativas de crecimiento geográfico en base a la <strong>conveniencia y confianza</strong> consolidada con nuestros clientes.<br><br>
                                Nos basamos en el <strong>compromiso y agilidad</strong> en la entrega de nuestro amplio portafolio, asegurando una total satisfacción y experiencia de servicio.
                            </p>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pills-mision" role="tabpanel">
                        <div class="bg-white p-4 rounded-4 shadow-sm border-start border-5 border-primary h-100">
                            <h5 class="fw-bold text-cenco-indigo mb-3"><i class="bi bi-flag-fill me-2 text-primary"></i>Nuestra Misión</h5>
                            <p class="text-muted text-justify mb-0">
                                Convertirse en la compañía de distribución más conveniente y confiable a lo largo de nuestro país.<br><br>
                                Lograremos esto a través del <strong>compromiso, ética y desarrollo</strong> de nuestros colaboradores, satisfaciendo las necesidades de nuestros clientes y creando valor real en cada producto entregado.
                            </p>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pills-vision" role="tabpanel">
                        <div class="bg-white p-4 rounded-4 shadow-sm border-start border-5 border-warning h-100">
                            <h5 class="fw-bold text-cenco-indigo mb-3"><i class="bi bi-eye-fill me-2 text-warning"></i>Nuestra Visión</h5>
                            <p class="text-muted text-justify mb-0">
                                Ser la <strong>distribuidora líder en el mercado nacional</strong>, alcanzando una expansión geográfica exponencial junto con un crecimiento financiero sólido.<br><br>
                                Buscamos estar siempre a la <strong>vanguardia</strong> de los cambios que exigen nuestros clientes y fortalecer nuestras alianzas estratégicas.
                            </p>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pills-valores" role="tabpanel">
                        <div class="bg-white p-4 rounded-4 shadow-sm h-100">
                            <h5 class="fw-bold text-cenco-indigo mb-3 text-center"><i class="bi bi-gem me-2 text-cenco-red"></i>Nuestros Valores</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 h-100 border-start border-3 border-success">
                                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Ética y Compromiso</h6>
                                        <p class="small text-muted mb-0">Actuamos con integridad profesional, generando confianza en cada relación comercial.</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 h-100 border-start border-3 border-warning">
                                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-lightbulb-fill text-warning me-2"></i>Iniciativa e Innovación</h6>
                                        <p class="small text-muted mb-0">Buscamos siempre nuevas formas de mejorar y crecer junto a nuestros clientes.</p>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="p-3 bg-light rounded-3 border-start border-3 border-primary">
                                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-people-fill text-primary me-2"></i>Orientación al Cliente y Equipo</h6>
                                        <p class="small text-muted mb-0">Nuestro pilar son nuestros colaboradores. Fomentamos un liderazgo participativo y un ambiente de crecimiento personal y laboral.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 bg-light p-0 position-relative overflow-hidden d-flex align-items-center justify-content-center" style="height: 70px;">
                <img src="<?= BASE_URL ?>img/logo.png" alt="Cencocal" class="img-fluid w-100 opacity-25" style="height: 100%; object-fit: contain; filter: grayscale(100%); mix-blend-mode: multiply; transform: scale(0.8);">
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalComuna" tabindex="-1" aria-labelledby="modalComunaLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4 pb-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-cenco-indigo p-3 rounded-circle text-white shadow-sm">
                        <i class="bi bi-geo-alt-fill fs-3"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-black text-cenco-indigo mb-0" id="modalComunaLabel">¿Dónde entregamos?</h5>
                        <p class="mb-0 mt-1" style="font-size: 0.85rem; color: #555;">
                            Estás viendo el stock en la
                            <span class="fw-bold text-cenco-green">
                                <?= (($_SESSION['sucursal_activa'] ?? 29) == 29) ? 'Sucursal Prat de La Calera' : 'Sucursal Villa Alemana'; ?>
                            </span>
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-2">
                    <div class="col-12">
                        <p class="fw-bold text-cenco-indigo small mb-2 border-bottom pb-1">Sucursal Prat - La Calera</p>
                    </div>
                    <div class="col-12 mb-2">
                        <button onclick="cambiarComunaRapida('La Calera', 29)" class="btn btn-outline-secondary w-100 text-center py-3 px-3 small fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm transition-hover">
                            <i class="bi bi-shop fs-5 text-cenco-indigo"></i> La Calera (Solo Retiro en Tienda)
                        </button>
                    </div>
                    <div class="col-12 mt-4">
                        <p class="fw-bold text-cenco-green small mb-2 border-bottom pb-1">Sucursal Villa Alemana</p>
                    </div>
                    <?php foreach (['Villa Alemana', 'Quilpué', 'Peñablanca', 'Viña del Mar', 'Valparaíso', 'Concón'] as $nombre): ?>
                        <div class="col-6 col-md-4">
                            <button onclick="cambiarComunaRapida('<?= $nombre ?>', 10)" class="btn btn-outline-success w-100 text-start py-2 px-3 small transition-hover"><?= $nombre ?></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalVaciarCarrito" tabindex="-1" aria-labelledby="vaciarCarritoLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg text-center p-4">
            <div class="modal-body p-2">
                <div class="mb-3 position-relative d-inline-block">
                    <div class="position-absolute top-50 start-50 translate-middle bg-danger bg-opacity-10 rounded-circle" style="width: 120px; height: 120px; filter: blur(15px);"></div>
                    <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_preocupado.png" alt="¿Seguro?" style="width: 130px; position: relative; z-index: 2; transform: rotate(-5deg);">
                </div>
                <h3 class="fw-black text-cenco-indigo mb-2" id="vaciarCarritoLabel">¿Estás seguro?</h3>
                <p class="text-muted mb-4">Estás a punto de eliminar todos los productos de tu carrito.</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <a href="<?= BASE_URL ?>carrito/vaciar" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm">Sí, vaciar todo</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalResumenCompra" tabindex="-1" aria-labelledby="modalResumenLabel">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-cenco-indigo text-white rounded-top-4 py-3">
                <h5 class="modal-title fw-bold" id="modalResumenLabel"><i class="bi bi-card-checklist me-2"></i>Confirma tu Pedido</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-6 border-end">
                        <h6 class="fw-black text-cenco-indigo border-bottom pb-2 mb-3">Tus Datos</h6>
                        <p class="small mb-1 fw-bold text-dark"><i class="bi bi-person text-secondary me-1"></i> <span id="resumen-nombre"></span></p>
                        <div class="small mb-4 text-dark" id="resumen-telefono"></div>
                        <h6 class="fw-black text-cenco-indigo border-bottom pb-2 mb-3" id="resumen-titulo-entrega">Datos de Entrega</h6>
                        <div class="small bg-light p-3 rounded-3 border border-light shadow-sm" id="resumen-detalle-entrega"></div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-black text-cenco-indigo border-bottom pb-2 mb-3">Resumen de Compra</h6>
                        <ul class="list-group list-group-flush small mb-3 overflow-auto" id="resumen-lista-productos" style="max-height: 200px;"></ul>
                        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 border border-success border-opacity-25 shadow-sm">
                            <span class="fw-bold text-dark fs-6">Total a Pagar:</span>
                            <span class="text-cenco-red fw-black fs-4" id="resumen-total-monto">$0</span>
                        </div>
                    </div>
                </div>
                <div class="alert alert-warning d-flex align-items-center mt-4 mb-0 py-2 border-0 bg-warning bg-opacity-10">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-4"></i>
                    <p class="mb-0 small fw-bold" id="texto-advertencia-pago">Si no hay stock de algún producto, te contactaremos para coordinar.</p>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 rounded-bottom-4 py-3 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary fw-bold rounded-pill px-4" data-bs-dismiss="modal">Volver y Editar</button>
                <button type="button" class="btn btn-cenco-green fw-bold rounded-pill px-4 shadow-sm" onclick="procesarPagoFinal();">Estoy de acuerdo y Pagar <i class="bi bi-check-circle-fill ms-2"></i></button>
            </div>
        </div>
    </div>
</div>

<button class="btn btn-accessibility shadow-lg"
    data-bs-toggle="modal" data-bs-target="#accessibilityModal" aria-label="Menú de Accesibilidad">
    <i class="bi bi-universal-access-circle fs-1"></i>
</button>