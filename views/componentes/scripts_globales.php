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
        settings: { activeClasses: [], filter: '', textLevel: 0 },
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
            this.settings = { activeClasses: [], filter: '', textLevel: 0 };
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

    let mapaReg = null;
    let markerReg = null;

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
            requestAnimationFrame(() => {
                setTimeout(() => { inicializarMapaRegistro(); }, 300);
            });
        } else {
            box.style.display = 'none';
        }
    }

    function inicializarMapaRegistro() {
        if (mapaReg !== null) { mapaReg.invalidateSize(); return; }

        const latInicial = -32.7845;
        const lngInicial = -71.2136;
        mapaReg = L.map('mapa-container').setView([latInicial, lngInicial], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(mapaReg);
        markerReg = L.marker([latInicial, lngInicial], { draggable: true }).addTo(mapaReg);

        setTimeout(() => { mapaReg.invalidateSize(); }, 500);

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

        if (window.innerWidth <= 768 && navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = pos.coords.latitude; const lng = pos.coords.longitude;
                    mapaReg.setView([lat, lng], 17); markerReg.setLatLng([lat, lng]);
                    document.getElementById('latitud').value = lat; document.getElementById('longitud').value = lng;
                },
                (err) => { console.warn("GPS denegado"); }
            );
        }
    }

    function ubicarEnMapa() {
        actualizarInputFinal();
        const dir = document.getElementById('direccion-input').value;
        const comuna = document.getElementById('reg_comuna').value;

        if (dir.trim() === '' || !comuna) { Swal.fire('Faltan Datos', 'Ingresa tu calle y selecciona una comuna.', 'warning'); return; }

        const busqueda = dir + ", Región de Valparaíso, Chile";
        const btn = event.currentTarget;
        const oldHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Buscando...';
        btn.disabled = true;

        fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(busqueda))
            .then(res => res.json())
            .then(data => {
                if (data.length > 0) {
                    const lat = parseFloat(data[0].lat); const lng = parseFloat(data[0].lon);
                    mapaReg.setView([lat, lng], 17); markerReg.setLatLng([lat, lng]);
                    document.getElementById('latitud').value = lat; document.getElementById('longitud').value = lng;
                } else {
                    Swal.fire('No encontrada', 'No pudimos ubicar la calle. Arrastra el pin rojo del mapa hasta tu casa.', 'info');
                }
            })
            .finally(() => { btn.innerHTML = oldHtml; btn.disabled = false; });
    }

    function procesarIrAPagar() {
        const usuarioAutorizado = <?= (isset($_SESSION['user_id']) || isset($_SESSION['invitado'])) ? 'true' : 'false' ?>;
        if (usuarioAutorizado) {
            window.location.href = BASE_URL + 'checkout';
        } else {
            bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasCarrito')).hide();
            setTimeout(() => { bootstrap.Modal.getOrCreateInstance(document.getElementById('checkoutAuthModal')).show(); }, 350);
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
 // 8. Éxito al crear cuenta (Registro exitoso)
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
            customClass: { popup: 'rounded-4 shadow-lg border-0' }
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // 9. Éxito, pero falló el envío de correo (Error de Gmail)
    else if (msg === 'registro_exito_sin_correo') {
        Swal.fire({
            title: '<span style="color: var(--cenco-indigo); font-weight: 900;">Cuenta creada</span>',
            html: 'Tu cuenta fue guardada en la base de datos, pero <b>tuvimos un bloqueo al enviar el correo de activación</b>.<br><br><small><i>Tip técnico: Revisa la contraseña de aplicación de Gmail.</i></small>',
            icon: 'warning', 
            confirmButtonColor: '#E53935', 
            confirmButtonText: 'Entendido',
            customClass: { popup: 'rounded-4 shadow-lg border-0' }
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // 10. Correo duplicado al intentar registrarse
    else if (msg === 'error_email_duplicado') {
        Swal.fire({
            title: '¡Ups!', 
            text: 'Este correo electrónico ya está registrado en nuestra base de datos. Intenta iniciar sesión.',
            icon: 'error', 
            confirmButtonColor: '#E53935',
            customClass: { popup: 'rounded-4 shadow-lg border-0' }
        }).then(() => {
            if(document.getElementById('loginModal')) {
                new bootstrap.Modal(document.getElementById('loginModal')).show();
            }
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // 11. Intentar comprar como invitado con un correo que ya existe
    else if (msg === 'cuenta_existente') {
        Swal.fire({
            title: '<span style="color: var(--cenco-indigo); font-weight: 900;">¡Ya eres parte de Cencocal!</span>',
            html: 'El correo que ingresaste ya tiene una cuenta registrada con nosotros.<br><br>Por seguridad, <b>por favor inicia sesión</b> en el panel izquierdo para continuar con tu compra.',
            icon: 'info', 
            confirmButtonColor: '#2A1B5E', 
            confirmButtonText: 'Iniciar Sesión',
            customClass: { popup: 'rounded-4 shadow-lg border-0' }
        }).then(() => {
            // Buscamos el modal del checkout, si no existe, abrimos el normal
            const checkoutAuthModal = document.getElementById('checkoutAuthModal');
            const loginModal = document.getElementById('loginModal');
            
            if (checkoutAuthModal) {
                bootstrap.Modal.getOrCreateInstance(checkoutAuthModal).show();
            } else if (loginModal) {
                bootstrap.Modal.getOrCreateInstance(loginModal).show();
            }
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // 12. Error general de Base de Datos
    else if (msg === 'error_db') {
        Swal.fire({
            title: 'Error del Sistema', 
            text: 'No pudimos guardar los datos en la base de datos.', 
            icon: 'error',
            confirmButtonColor: '#E53935',
            customClass: { popup: 'rounded-4 shadow-lg border-0' }
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }
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