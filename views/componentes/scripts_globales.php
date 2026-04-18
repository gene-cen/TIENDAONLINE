<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>

<script src="<?= BASE_URL ?>js/shop/carrito_global.js"></script>
<script src="<?= BASE_URL ?>js/auth_modales.js"></script>
<script src="<?= BASE_URL ?>js/scripts.js"></script>

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
            // 1. Limpiamos niveles previos del HTML
            document.documentElement.classList.remove('access-lvl-1', 'access-lvl-2', 'access-lvl-3');

            this.settings.textLevel++;
            if (this.settings.textLevel > 3) this.settings.textLevel = 0;

            // 2. Aplicamos el nuevo nivel al HTML para que Bootstrap escale todo
            if (this.settings.textLevel > 0) {
                document.documentElement.classList.add('access-lvl-' + this.settings.textLevel);
            }

            this.updateTextButtonLabel();
            this.save();
        },

        applySettings: function() {
            this.settings.activeClasses.forEach(c => document.body.classList.add(c));
            if (this.settings.filter) this.setFilter(this.settings.filter);
            // Aplicamos el texto al HTML al cargar
            if (this.settings.textLevel > 0) document.documentElement.classList.add('access-lvl-' + this.settings.textLevel);
            this.updateTextButtonLabel();
        },

        reset: function() {
            this.settings = {
                activeClasses: [],
                filter: '',
                textLevel: 0
            };

            // Limpiamos el BODY
            const bodyClasses = ['access-dark', 'access-invert', 'access-high-contrast', 'access-dyslexic', 'access-no-anim'];
            bodyClasses.forEach(c => document.body.classList.remove(c));

            // Limpiamos el HTML (Filtros y Tamaños de texto)
            document.documentElement.classList.remove('filter-grayscale', 'filter-protanopia', 'filter-deuteranopia', 'filter-tritanopia', 'access-lvl-1', 'access-lvl-2', 'access-lvl-3');

            const selectFiltro = document.querySelector('select[aria-label="Filtro de Color"]');
            if (selectFiltro) selectFiltro.value = '';

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
            // Quitamos los filtros antiguos del HTML
            document.documentElement.classList.remove('filter-grayscale', 'filter-protanopia', 'filter-deuteranopia', 'filter-tritanopia');

            // Aplicamos el nuevo
            if (filterName) document.documentElement.classList.add('filter-' + filterName);

            // Guardamos en el JSON maestro
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
            // 1. Limpiamos la memoria interna
            this.settings = {
                activeClasses: [],
                filter: '',
                textLevel: 0
            };

            // 2. Limpiamos el BODY (Modo oscuro, dislexia, tamaños de texto)
            const classes = ['access-dark', 'access-invert', 'access-high-contrast', 'access-dyslexic', 'access-no-anim', 'access-lvl-1', 'access-lvl-2', 'access-lvl-3'];
            classes.forEach(c => document.body.classList.remove(c));

            // 3. Limpiamos el HTML (Filtros de Daltonismo)
            document.documentElement.classList.remove('filter-grayscale', 'filter-protanopia', 'filter-deuteranopia', 'filter-tritanopia');

            // 4. Reiniciamos el selector (Dropdown) visualmente a su estado "Normal"
            const selectFiltro = document.querySelector('select[aria-label="Filtro de Color"]');
            if (selectFiltro) {
                selectFiltro.value = '';
            }

            // 5. Actualizamos textos y guardamos
            this.updateTextButtonLabel();
            this.save();
        },
        save: function() {
            localStorage.setItem('cenco_accessibility', JSON.stringify(this.settings));
        }
    };

    function cambiarModal(actualId, siguienteId) {
        if (document.activeElement) document.activeElement.blur();
        bootstrap.Modal.getOrCreateInstance(document.getElementById(actualId)).hide();
        setTimeout(() => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById(siguienteId)).show();
        }, 350);
    }

    document.addEventListener("DOMContentLoaded", function() {
        window.AccessManager.init();

        const inputsNombres = ['reg_nombre', 'reg_apellido'];
        inputsNombres.forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener('input', function(e) {
                    let palabras = e.target.value.toLowerCase().split(' ');
                    for (let i = 0; i < palabras.length; i++) {
                        if (palabras[i].length > 0) palabras[i] = palabras[i][0].toUpperCase() + palabras[i].substring(1);
                    }
                    e.target.value = palabras.join(' ');
                });
            }
        });
    });

    function validarRegistro(event) {
        const pass1 = document.getElementById('reg_password').value;
        const pass2 = document.getElementById('reg_password_confirm').value;

        if (pass1 !== pass2) {
            event.preventDefault();
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
        document.getElementById('reg_password').classList.remove('is-invalid');
        document.getElementById('reg_password_confirm').classList.remove('is-invalid');

        const nombre = document.getElementById('reg_nombre').value.trim();
        const apellido = document.getElementById('reg_apellido').value.trim();
        document.getElementById('inputNombreCompleto').value = nombre + ' ' + apellido;
        return true;
    }

    function cambiarFiltroAccesibilidad(nombreFiltro) {
        // 1. Lista de filtros posibles para limpiar los anteriores
        const filtros = ['filter-grayscale', 'filter-protanopia', 'filter-deuteranopia', 'filter-tritanopia'];

        // 2. Quitamos todos los filtros actuales del <html>
        filtros.forEach(f => document.documentElement.classList.remove(f));

        // 3. Aplicamos el nuevo y lo guardamos
        if (nombreFiltro) {
            document.documentElement.classList.add(nombreFiltro);
            localStorage.setItem('cenco_access_filter', nombreFiltro);
        } else {
            // Si es "Restablecer"
            localStorage.removeItem('cenco_access_filter');
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
        if (val.length > 0) input.value = val.charAt(0).toUpperCase() + val.slice(1);
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


    function procesarIrAPagar() {
        const usuarioAutorizado = <?= (isset($_SESSION['user_id']) || isset($_SESSION['invitado'])) ? 'true' : 'false' ?>;
        if (usuarioAutorizado) {
            window.location.href = BASE_URL + 'checkout';
        } else {
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
            // 1. CAPTURA SEGURA DE LA VARIABLE
            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get('msg');

            // Si por alguna razón no hay mensaje, detenemos la ejecución
            if (!msg) return;

            // ==========================================
            // ALERTAS DE REGISTRO Y BASE DE DATOS
            // ==========================================

            // 8. Éxito al crear cuenta (Registro exitoso)
            if (msg === 'registro_ok' || msg === 'registro_exito') {
                Swal.fire({
                    title: '<span style="color: var(--cenco-indigo); font-weight: 900;">¡Has creado tu cuenta exitosamente!</span>',
                    html: 'Te enviamos un enlace de validación para activarla.',
                    imageUrl: BASE_URL + 'img/cencocalin/cencocalin_envio_correo.png',
                    imageWidth: 140,
                    imageAlt: 'Correo Enviado',
                    confirmButtonColor: '#76C043',
                    confirmButtonText: '¡Iré a revisar!',
                    backdrop: `rgba(42, 27, 94, 0.4)`,
                    customClass: {
                        popup: 'rounded-4 shadow-lg border-0'
                    }
                });
            }

            // 9. Éxito, pero falló el envío de correo (Error de Gmail)
            else if (msg === 'registro_exito_sin_correo') {
                Swal.fire({
                    title: '<span style="color: var(--cenco-indigo); font-weight: 900;">Cuenta creada</span>',
                    html: 'Tu cuenta fue guardada en la base de datos, pero <b>tuvimos un bloqueo al enviar el correo de activación</b>.<br><br><small><i>Tip técnico: Revisa la contraseña de aplicación de Gmail.</i></small>',
                    icon: 'warning',
                    confirmButtonColor: '#E53935',
                    confirmButtonText: 'Entendido',
                    customClass: {
                        popup: 'rounded-4 shadow-lg border-0'
                    }
                });
            }

            // 10. Correo duplicado al intentar registrarse
            else if (msg === 'error_email_duplicado') {
                Swal.fire({
                    title: '¡Ups!',
                    text: 'Este correo electrónico ya está registrado en nuestra base de datos. Intenta iniciar sesión.',
                    icon: 'error',
                    confirmButtonColor: '#E53935',
                    customClass: {
                        popup: 'rounded-4 shadow-lg border-0'
                    }
                }).then(() => {
                    const loginEl = document.getElementById('loginModal');
                    if (loginEl) {
                        bootstrap.Modal.getOrCreateInstance(loginEl).show();
                    }
                });
            }

            // 11. Intentar comprar como invitado con un correo que ya existe
            else if (msg === 'cuenta_existente') {
                Swal.fire({
                    title: '<span style="color: var(--cenco-indigo); font-weight: 900;">¡Ya eres parte de Cencocal!</span>',
                    html: 'El correo que ingresaste ya tiene una cuenta registrada con nosotros.<br><br>Por seguridad, <b>por favor inicia sesión</b> para continuar con tu compra.',
                    icon: 'info',
                    confirmButtonColor: '#2A1B5E',
                    confirmButtonText: 'Iniciar Sesión',
                    customClass: {
                        popup: 'rounded-4 shadow-lg border-0'
                    }
                }).then(() => {
                    const checkoutAuthModal = document.getElementById('checkoutAuthModal');
                    const loginModal = document.getElementById('loginModal');

                    if (checkoutAuthModal) {
                        bootstrap.Modal.getOrCreateInstance(checkoutAuthModal).show();
                    } else if (loginModal) {
                        bootstrap.Modal.getOrCreateInstance(loginModal).show();
                    }
                });
            }

            // 12. Error general de Base de Datos
            else if (msg === 'error_db') {
                Swal.fire({
                    title: 'Error del Sistema',
                    text: 'No pudimos guardar los datos en la base de datos.',
                    icon: 'error',
                    confirmButtonColor: '#E53935',
                    customClass: {
                        popup: 'rounded-4 shadow-lg border-0'
                    }
                });
            }

            // 🔥 LIMPIEZA FINAL DE LA URL (Se ejecuta una sola vez al terminar)
            const url = new URL(window.location);
            url.searchParams.delete('msg');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        });
    </script>
<?php endif; ?>

<script src="<?= BASE_URL ?>js/scripts.js?v=2.0"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const todosLosModales = document.querySelectorAll('.modal');
        todosLosModales.forEach(modal => {
            document.body.appendChild(modal);
            modal.addEventListener('show.bs.modal', function() {
                const offcanvasAbiertos = document.querySelectorAll('.offcanvas.show');
                offcanvasAbiertos.forEach(function(off) {
                    const bsOffcanvas = bootstrap.Offcanvas.getInstance(off);
                    if (bsOffcanvas) bsOffcanvas.hide();
                });
            });
            modal.addEventListener('hidden.bs.modal', function() {
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            });
        });

        if (document.body.classList.contains('modal-open')) {
            document.body.classList.remove('modal-open');
            document.body.style.overflow = 'auto';
            document.body.style.paddingRight = '';
        }
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
    });
</script>