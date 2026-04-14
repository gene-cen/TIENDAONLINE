
// =========================================================
// 1. UTILIDADES Y FUNCIONES AUXILIARES
// =========================================================

const formatearRut = (rut) => {
    if (!rut) return "";
    let actual = rut.replace(/^0+/, "");
    let clean = actual.replace(/[^0-9kK]/g, "");

    // 🔥 NUEVO CANDADO: Cortamos la cadena a un máximo de 9 caracteres base
    if (clean.length > 9) {
        clean = clean.slice(0, 9);
    }

    if (clean.length === 0) return "";

    let result = "";
    let cuerpo = clean.slice(0, -1);
    let dv = clean.slice(-1).toUpperCase();

    for (let j = 1, i = cuerpo.length - 1; i >= 0; i--, j++) {
        result = cuerpo.charAt(i) + result;
        if (j % 3 === 0 && i !== 0) result = "." + result;
    }
    return result + "-" + dv;
};

const validarRut = (rutCompleto) => {
    let rutLimpio = rutCompleto.replace(/[^0-9kK]/g, '');
    if (rutLimpio.length < 2) return false;

    let cuerpo = rutLimpio.slice(0, -1);
    let dvIngresado = rutLimpio.slice(-1).toUpperCase();

    let suma = 0, multiplo = 2;
    for (let i = 1; i <= cuerpo.length; i++) {
        suma += multiplo * cuerpo.charAt(cuerpo.length - i);
        multiplo = (multiplo < 7) ? multiplo + 1 : 2;
    }

    let dvEsperado = 11 - (suma % 11);
    dvEsperado = (dvEsperado === 11) ? 0 : ((dvEsperado === 10) ? "K" : dvEsperado);
    return dvEsperado.toString() === dvIngresado;
};

async function verificarDuplicadoBD(campo, valor) {
    try {
        let valorLimpio = valor.trim();
        if (campo === 'telefono') {
            valorLimpio = valorLimpio.replace(/[^0-9]/g, '');
        }
        const response = await fetch(`${BASE_URL}auth/check-duplicate?campo=${campo}&valor=${encodeURIComponent(valorLimpio)}`);
        const data = await response.json();
        return data.existe;
    } catch (error) {
        console.error(`Error verificando ${campo}:`, error);
        return false;
    }
}

function buscarPredictivo(texto) {
    const lista = document.getElementById('lista-predictiva');
    if (!lista) return;

    if (texto.length < 2) {
        lista.classList.add('d-none');
        lista.innerHTML = '';
        return;
    }
    if (window._timeoutPredictivo) clearTimeout(window._timeoutPredictivo);
    window._timeoutPredictivo = setTimeout(() => {
        fetch(`${BASE_URL}home/autocomplete?q=${encodeURIComponent(texto)}`)
            .then(response => response.json())
            .then(data => {
                lista.innerHTML = '';
                if (data && data.length > 0) {
                    lista.classList.remove('d-none');
                    data.forEach(item => {
                        const nombreFinal = item.nombre_web || item.nombre || 'Producto';
                        const li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action px-4 py-3 cursor-pointer border-0 border-bottom d-flex align-items-center justify-content-between transition-hover';
                        li.innerHTML = `<div class="d-flex align-items-center"><i class="bi bi-search me-3 text-muted small"></i><span class="fw-semibold text-dark">${nombreFinal}</span></div><i class="bi bi-arrow-up-left text-muted opacity-50 small"></i>`;
                        li.onclick = () => {
                            const input = document.getElementById('inputBusqueda');
                            if (input) input.value = nombreFinal;
                            lista.classList.add('d-none');
                            window.location.href = `${BASE_URL}home/catalogo?q=${encodeURIComponent(nombreFinal)}`;
                        };
                        lista.appendChild(li);
                    });
                } else { lista.classList.add('d-none'); }
            }).catch(e => console.error("Error predictivo:", e));
    }, 300);
}

// =========================================================
// 2. INICIALIZACIÓN PRINCIPAL
// =========================================================
document.addEventListener('DOMContentLoaded', function () {

    // --- A. ALERTAS DE URL ---
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    if (msg) {
        let config = {};
        switch (msg) {
            // 🔥 ALERTAS DE REGISTRO EXITOSO (CON CENCOCALÍN)
            case 'registro_exito': 
                config = { 
                    title: '¡Cuenta creada!', 
                    text: 'Revisa tu bandeja de entrada o spam para verificar tu cuenta.', 
                    imageUrl: `${BASE_URL}img/cencocalin/cencocalin_celebrando_compra.png`,
                    imageWidth: 120,
                    imageAlt: 'Cencocalín celebrando',
                    confirmButtonColor: '#85C226', 
                    modal: 'loginModal' 
                }; 
                break;
            case 'registro_exito_sin_correo': 
                config = { 
                    title: '¡Cuenta creada!', 
                    text: 'Tu cuenta ha sido creada exitosamente. Ya puedes iniciar sesión.', 
                    imageUrl: `${BASE_URL}img/cencocalin/cencocalin_celebrando_compra.png`,
                    imageWidth: 120,
                    imageAlt: 'Cencocalín celebrando',
                    confirmButtonColor: '#85C226', 
                    modal: 'loginModal' 
                };
                break;
            case 'error_password_corta': config = { icon: 'warning', title: 'Contraseña muy corta', text: 'Mínimo 6 caracteres.', confirmButtonColor: '#E53935', modal: 'registerModal' }; break;
            case 'error_email_duplicado': config = { title: 'Correo registrado', text: 'Intenta iniciar sesión.', imageUrl: `${BASE_URL}img/cencocalin/cencocalin_preocupado.png`, imageWidth: 120, confirmButtonColor: '#2A1B5E', modal: 'loginModal' }; break;
            case 'recovery_enviado': config = { title: '¡Correo enviado!', text: 'Revisa tu bandeja de entrada.', imageUrl: `${BASE_URL}img/cencocalin/cencocalin_envio_correo.png`, imageWidth: 110, confirmButtonColor: '#85C226' }; break;
            case 'verificacion_exito': config = { icon: 'success', title: '¡Correo verificado!', text: 'Ya puedes iniciar sesión.', confirmButtonColor: '#85C226', modal: 'loginModal' }; break;
            case 'password_actualizada': config = { icon: 'success', title: '¡Contraseña actualizada!', text: 'Ingresa con tu nueva clave.', confirmButtonColor: '#85C226', modal: 'loginModal' }; break;
            case 'error_token_invalido': config = { icon: 'error', title: 'Enlace no válido', text: 'El enlace expiró.', confirmButtonColor: '#E53935' }; break;
        }
        if (config.title) {
            Swal.fire({ ...config, customClass: { popup: 'rounded-4 shadow-lg border-0' }, didOpen: () => { Swal.getContainer().style.zIndex = '10000'; } }).then(() => {
                if (config.modal && document.getElementById(config.modal)) {
                    new bootstrap.Modal(document.getElementById(config.modal)).show();
                }
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }

    // --- B. LOGIN AJAX ---
    document.querySelectorAll('form[action*="auth/login"]').forEach(form => {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (this.dataset.submitting === '1') return;
            this.dataset.submitting = '1';
            const btn = this.querySelector('button[type="submit"]');
            
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Ingresando...';
            btn.disabled = true;
            try {
                const res = await fetch(this.action, { method: 'POST', body: new FormData(this), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (data.status === 'success') window.location.href = data.redirect;
                else {
                    Swal.fire({ title: '¡Ups!', text: 'Credenciales incorrectas.', imageUrl: `${BASE_URL}img/cencocalin/cencocalin_algo_fallo.png`, imageWidth: 120, confirmButtonColor: '#2A1B5E', didOpen: () => Swal.getContainer().style.zIndex = '10000' });
                    btn.innerHTML = originalText; btn.disabled = false;
                }
            } catch (error) { btn.innerHTML = originalText; btn.disabled = false; } finally { this.dataset.submitting = '0'; }
        });
    });

    // --- C. VALIDACIONES (RUT, Pass, Duplicados) ---

    // 1. Validación de RUT
    document.querySelectorAll('input[name="rut"]').forEach(input => {
        let errorSpan = document.createElement('div');
        errorSpan.className = 'text-danger fw-bold mt-1 d-none';
        errorSpan.style.fontSize = '0.85rem';
        (input.closest('.input-group') || input).after(errorSpan);
        input.addEventListener('input', function () {
            this.value = formatearRut(this.value);
            this.classList.remove('is-invalid', 'border-danger');
            errorSpan.classList.add('d-none');
        });
        input.addEventListener('blur', async function () {
            if (this.value.length < 8) return;
            if (!validarRut(this.value)) {
                this.classList.add('is-invalid', 'border-danger');
                errorSpan.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> RUT inválido.';
                errorSpan.classList.remove('d-none');
            } else {
                const existe = await verificarDuplicadoBD('rut', this.value);
                if (existe) {
                    this.classList.add('is-invalid', 'border-danger');
                    errorSpan.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Este RUT ya está registrado.';
                    errorSpan.classList.remove('d-none');
                }
            }
        });
    });

    // 2. Validación de Contraseñas (Longitud y Coincidencia)
    const regPass = document.getElementById('reg_password');
    const regPassConf = document.getElementById('reg_password_confirm');
    const msgMismatch = document.getElementById('msg_password_error');

    if (regPass && regPassConf) {
        let errorLen = document.createElement('div');
        errorLen.className = 'text-danger small fw-bold mt-1 d-none';
        errorLen.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i> Mínimo 6 caracteres.';
        regPass.closest('.input-group').after(errorLen);

        const checkPasswords = () => {
            // A. Validar Longitud (Mínimo 6)
            if (regPass.value.length > 0 && regPass.value.length < 6) {
                regPass.classList.add('is-invalid', 'border-danger');
                errorLen.classList.replace('d-none', 'd-block');
            } else {
                regPass.classList.remove('is-invalid', 'border-danger');
                errorLen.classList.replace('d-block', 'd-none');
            }

            // B. Validar Coincidencia
            if (regPassConf.value.length > 0) {
                if (regPass.value !== regPassConf.value) {
                    regPassConf.classList.add('is-invalid', 'border-danger');
                    if (msgMismatch) msgMismatch.classList.replace('d-none', 'd-block');
                } else {
                    regPassConf.classList.remove('is-invalid', 'border-danger');
                    if (msgMismatch) msgMismatch.classList.replace('d-block', 'd-none');
                }
            } else {
                regPassConf.classList.remove('is-invalid', 'border-danger');
                if (msgMismatch) msgMismatch.classList.replace('d-block', 'd-none');
            }
        };

        regPass.addEventListener('input', checkPasswords);
        regPassConf.addEventListener('input', checkPasswords);
    }

    // 3. Validación Duplicados Email/Teléfono
    const setupDuplicado = (selector, campo, msg) => {
        document.querySelectorAll(selector).forEach(input => {
            if (input.closest('form').action.includes('auth/login')) return;
            if (input.dataset.dupConfigurado === '1') return;
            input.dataset.dupConfigurado = '1';
            let errorSpan = document.createElement('div');
            
            errorSpan.className = 'text-danger fw-bold mt-1 d-none';
            errorSpan.style.fontSize = '0.85rem';
            errorSpan.innerHTML = `<i class="bi bi-exclamation-circle-fill"></i> ${msg}`;
            (input.closest('.input-group') || input).after(errorSpan);
            
            input.addEventListener('blur', async function () {
                const valor = this.value.trim();
                if (valor === '' || valor.length < 5) return;
                const existe = await verificarDuplicadoBD(campo, valor);
                if (existe) {
                    this.classList.add('is-invalid', 'border-danger');
                    errorSpan.classList.replace('d-none', 'd-block');
                } else {
                    this.classList.remove('is-invalid', 'border-danger');
                    errorSpan.classList.replace('d-block', 'd-none');
                }
            });
            input.addEventListener('input', function () {
                this.classList.remove('is-invalid', 'border-danger');
                errorSpan.classList.replace('d-block', 'd-none');
            });
        });
    };
    setupDuplicado('input[name="email"]', 'email', 'Este correo ya está registrado.');
    setupDuplicado('input[name="telefono"], input[name="celular"]', 'telefono', 'Este celular ya está registrado.');

    // 4. Capitalización automática de Nombre y Apellido
    const regNombre = document.getElementById('reg_nombre');
    const regApellido = document.getElementById('reg_apellido');
    const formatearNombres = function() {
        this.value = this.value.replace(/\b\w/g, letra => letra.toUpperCase());
    };

    if (regNombre) regNombre.addEventListener('input', formatearNombres);
    if (regApellido) regApellido.addEventListener('input', formatearNombres);

    // =========================================================
    // 🛡️ D. DIRECCIÓN OPCIONAL Y MAPA (FLUJO SECUENCIAL E INCLUSIVO)
    // =========================================================
    const checkDir = document.getElementById('reg-check-direccion');
    const wrapperDir = document.getElementById('reg-wrapper-direccion');
    const inputCalle = document.getElementById('reg-calle');
    const inputNumero = document.getElementById('reg-numero');
    const btnBuscar = document.getElementById('btn-buscar-direccion');
    const selectComuna = document.getElementById('reg-select-comuna');
    const btnGuardarUI = document.getElementById('btn-guardar-direccion-ui');

    let mapReg = null, markerReg = null;

    if (inputCalle) inputCalle.disabled = true;
    if (inputNumero) inputNumero.disabled = true;

    if (checkDir) {
        checkDir.addEventListener('change', function () {
            if (this.checked) {
                wrapperDir.classList.remove('d-none');
                initMapRegistro();
            } else {
                wrapperDir.classList.add('d-none');
            }
        });
    }

    if (selectComuna) {
        selectComuna.addEventListener('change', function () {
            if (this.value) {
                inputCalle.disabled = false;
                inputCalle.focus();
            } else {
                inputCalle.disabled = true;
                inputNumero.disabled = true;
            }
            inputCalle.value = '';
            inputNumero.value = '';
        });
    }

    if (inputCalle) {
        inputCalle.addEventListener('input', function () {
            if (this.value.length > 0) {
                this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
                inputNumero.disabled = false;
            } else {
                inputNumero.disabled = true;
                inputNumero.value = '';
            }
        });
    }

    if (btnBuscar) {
        btnBuscar.addEventListener('click', async function () {
            const comuna = selectComuna ? selectComuna.value : '';
            const calle = inputCalle ? inputCalle.value.trim() : '';
            const numero = inputNumero ? inputNumero.value.trim() : '';

            if (!comuna) {
                Swal.fire({ icon: 'error', title: 'Selecciona una comuna', text: 'Es obligatorio elegir una comuna para usar la búsqueda.', confirmButtonColor: '#2A1B5E', didOpen: () => Swal.getContainer().style.zIndex = '10000' });
                return;
            }
            if (!calle || !numero) {
                Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Ingresa calle y número para buscar.', confirmButtonColor: '#2A1B5E', didOpen: () => Swal.getContainer().style.zIndex = '10000' });
                return;
            }

            const icon = btnBuscar.querySelector('i');
            const originalClass = icon.className;
            icon.className = 'spinner-border spinner-border-sm';

            try {
                const query = `${calle} ${numero}, ${comuna}, Region de Valparaiso, Chile`;
                const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`;
                const response = await fetch(url);
                const data = await response.json();
                if (data && data.length > 0) {
                    const lat = data[0].lat;
                    const lon = data[0].lon;
                    mapReg.setView([lat, lon], 17);
                    markerReg.setLatLng([lat, lon]);
                    document.getElementById('reg-lat').value = lat;
                    document.getElementById('reg-lng').value = lon;
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Dirección no ubicada en el mapa',
                        text: 'No ubicamos el punto exacto. No te preocupes, esto es opcional. Si gustas, puedes mover el pin manualmente o confirmar tu dirección escrita.',
                        confirmButtonColor: '#2A1B5E',
                        didOpen: () => Swal.getContainer().style.zIndex = '10000'
                    });
                }
            } catch (error) {
                console.error("Error en geocodificación:", error);
            } finally {
                icon.className = originalClass;
            }
        });
    }

    if (btnGuardarUI) {
        btnGuardarUI.addEventListener('click', function () {
            const calle = inputCalle.value.trim();
            const num = inputNumero.value.trim();
            const com = selectComuna.value;

            if (!calle || !num || !com) {
                Swal.fire({ icon: 'warning', title: '¡Casi listo!', text: 'Por favor, completa tu calle, número y comuna antes de confirmar.', confirmButtonColor: '#E53935', didOpen: () => Swal.getContainer().style.zIndex = '10000' });
                return;
            }

            Swal.fire({
                title: '¡Dirección lista!',
                text: 'Cencocalín ya sabe dónde entregarte tus pedidos. ¡Excelente!',
                imageUrl: BASE_URL + 'img/cencocalin/cencocalin_celebrando_compra.png',
                imageWidth: 120,
                imageAlt: 'Cencocalín Celebrando',
                confirmButtonColor: '#85C226',
                confirmButtonText: '¡Genial!',
                customClass: { popup: 'rounded-4 shadow-lg border-0' },
                didOpen: () => Swal.getContainer().style.zIndex = '10000'
            });
        });
    }

    function initMapRegistro() {
        if (mapReg) {
            setTimeout(() => { mapReg.invalidateSize(); }, 200);
            return;
        }
        const latIni = -32.7889, lngIni = -71.2039; // La Calera
        const mapContainer = document.getElementById('reg-map');
        if (!mapContainer) return;
        mapReg = L.map('reg-map').setView([latIni, lngIni], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(mapReg);
        markerReg = L.marker([latIni, lngIni], { draggable: true }).addTo(mapReg);

        const updateCoords = (lat, lng) => {
            const latIn = document.getElementById('reg-lat');
            const lngIn = document.getElementById('reg-lng');
            if (latIn) latIn.value = lat;
            if (lngIn) lngIn.value = lng;
        };
        markerReg.on('dragend', () => {
            const pos = markerReg.getLatLng();
            updateCoords(pos.lat, pos.lng);
        });
        mapReg.on('click', (e) => {
            markerReg.setLatLng(e.latlng);
            updateCoords(e.latlng.lat, e.latlng.lng);
        });
        updateCoords(latIni, lngIni);
    }

    // =========================================================
    // 🛡️ E. INTERCEPTOR FINAL DE FORMULARIO (CANDADO MAESTRO)
    // =========================================================
    document.querySelectorAll('form[action*="auth/register"]').forEach(form => {
        form.addEventListener('submit', function (e) {

            // Candado 1: Contraseñas distintas
            const pass = document.getElementById('reg_password');
            const passConf = document.getElementById('reg_password_confirm');

            if (pass && passConf && pass.value !== passConf.value) {
                e.preventDefault();
                passConf.focus();
                passConf.classList.add('animate__animated', 'animate__headShake');
                setTimeout(() => passConf.classList.remove('animate__animated', 'animate__headShake'), 500);

                Swal.fire({
                    icon: 'error',
                    title: 'Contraseñas distintas',
                    text: 'Asegúrate de escribir la misma clave en ambos campos.',
                    confirmButtonColor: '#E53935',
                    didOpen: () => { Swal.getContainer().style.zIndex = '10000'; }
                });
                return; // Detener flujo
            }

            // Candado 2: Algún campo quedó marcado en rojo (.is-invalid)
            const camposMalos = this.querySelectorAll('.is-invalid');
            if (camposMalos.length > 0) {
                e.preventDefault();
                camposMalos[0].focus();
                camposMalos[0].classList.add('animate__animated', 'animate__headShake');
                setTimeout(() => camposMalos[0].classList.remove('animate__animated', 'animate__headShake'), 500);

                Swal.fire({
                    icon: 'error',
                    title: 'Revisa tus datos',
                    text: 'Corrige los campos marcados en rojo para continuar.',
                    confirmButtonColor: '#E53935',
                    didOpen: () => { Swal.getContainer().style.zIndex = '10000'; }
                });
            }
        });
    });

    // Cerrar buscador predictivo al hacer clic fuera
    document.addEventListener('click', (e) => {
        const l = document.getElementById('lista-predictiva'), i = document.getElementById('inputBusqueda');
        if (l && i && !i.contains(e.target) && !l.contains(e.target)) l.classList.add('d-none');
    });

}); // FIN DEL DOMContentLoaded