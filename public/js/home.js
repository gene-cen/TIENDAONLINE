// =========================================================
// 1. UTILIDADES Y FUNCIONES AUXILIARES
// =========================================================

const formatearRut = (rut) => {
    if (!rut) return "";
    let actual = rut.replace(/^0+/, "");
    let clean = actual.replace(/[^0-9kK]/g, "");

    if (clean.length > 9) clean = clean.slice(0, 9);
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
        if (campo === 'telefono') valorLimpio = valorLimpio.replace(/[^0-9]/g, '');
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
// 2. INICIALIZACIÓN PRINCIPAL (DOMContentLoaded)
// =========================================================
document.addEventListener('DOMContentLoaded', function () {

    // --- A. ALERTAS DE URL ---
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    if (msg) {
        let config = {};
        switch (msg) {
            case 'registro_exito':
            case 'registro_exito_sin_correo':
                config = {
                    title: '¡Cuenta creada!',
                    text: msg === 'registro_exito' ? 'Revisa tu bandeja de entrada o spam para verificar tu cuenta.' : 'Tu cuenta ha sido creada exitosamente. Ya puedes iniciar sesión.',
                    imageUrl: `${BASE_URL}img/cencocalin/cencocalin_celebrando_compra.png`,
                    imageWidth: 120,
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
            const valorOriginal = this.value.trim();
            if (valorOriginal.length < 8) return;

            if (!validarRut(valorOriginal)) {
                this.classList.add('is-invalid', 'border-danger');
                errorSpan.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> RUT inválido.';
                errorSpan.classList.remove('d-none');
                return;
            }

            const existe = await verificarDuplicadoBD('rut', valorOriginal);

            if (existe) {
                if (this.closest('#seccion-invitado')) {
                    this.value = '';
                    this.classList.remove('is-invalid');

                    Swal.fire({
                        title: '¡Ya eres parte de Cencocal!',
                        text: 'Detectamos que este RUT ya tiene una cuenta registrada. Por favor, inicia sesión para continuar.',
                        imageUrl: `${BASE_URL}img/cencocalin/cencocalin_feliz.png`,
                        imageWidth: 120,
                        confirmButtonColor: '#2A1B5E',
                        confirmButtonText: 'Ir a Iniciar Sesión',
                        customClass: { popup: 'rounded-4 shadow-lg border-0' },
                        didOpen: () => { Swal.getContainer().style.zIndex = '10000'; }
                    }).then(() => {
                        const loginModal = document.getElementById('loginModal');
                        if (loginModal) new bootstrap.Modal(loginModal).show();
                    });
                } else {
                    this.classList.add('is-invalid', 'border-danger');
                    errorSpan.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Este RUT ya está registrado.';
                    errorSpan.classList.remove('d-none');
                }
            }
        });
    });

    const btnContinuarInv = document.querySelector('#seccion-invitado button');
    const inputRutInv = document.querySelector('#seccion-invitado input[name="rut"]');
    if (btnContinuarInv && inputRutInv) {
        const verificarSiEsRegistrado = async () => {
            let rutValor = inputRutInv.value.trim();
            let rutLimpio = rutValor.replace(/[^0-9kK]/g, '').replace(/^0+/, "");
            if (rutLimpio.length >= 8 && validarRut(rutValor)) {
                const existe = await verificarDuplicadoBD('rut', rutValor);
                if (existe) {
                    inputRutInv.value = '';
                    Swal.fire({
                        title: '¡Ya eres parte de Cencocal!',
                        text: 'Detectamos que este RUT ya tiene una cuenta. Inicia sesión para continuar.',
                        imageUrl: `${BASE_URL}img/cencocalin/cencocalin_feliz.png`,
                        imageWidth: 120,
                        confirmButtonColor: '#2A1B5E',
                        confirmButtonText: 'Ir a Iniciar Sesión',
                        customClass: { popup: 'rounded-4 shadow-lg border-0' },
                        didOpen: () => Swal.getContainer().style.zIndex = '10000'
                    }).then(() => {
                        const loginModal = document.getElementById('loginModal');
                        if (loginModal) new bootstrap.Modal(loginModal).show();
                    });
                    return true;
                }
            }
            return false;
        };
        inputRutInv.addEventListener('blur', verificarSiEsRegistrado);
        btnContinuarInv.addEventListener('click', async (e) => {
            e.preventDefault();
            const esRegistrado = await verificarSiEsRegistrado();
            if (!esRegistrado) console.log("Invitado válido, enviando al pago...");
        });
    }

    const regPass = document.getElementById('reg_password');
    const regPassConf = document.getElementById('reg_password_confirm');
    const msgMismatch = document.getElementById('msg_password_error');

    if (regPass && regPassConf) {
        let errorLen = document.createElement('div');
        errorLen.className = 'text-danger small fw-bold mt-1 d-none';
        errorLen.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i> Mínimo 6 caracteres.';
        regPass.closest('.input-group').after(errorLen);

        const checkPasswords = () => {
            if (regPass.value.length > 0 && regPass.value.length < 6) {
                regPass.classList.add('is-invalid', 'border-danger');
                errorLen.classList.replace('d-none', 'd-block');
            } else {
                regPass.classList.remove('is-invalid', 'border-danger');
                errorLen.classList.replace('d-block', 'd-none');
            }
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

    const regNombre = document.getElementById('reg_nombre');
    const regApellido = document.getElementById('reg_apellido');
    const formatearNombres = function () {
        this.value = this.value.replace(/\b\w/g, letra => letra.toUpperCase());
    };
    if (regNombre) regNombre.addEventListener('input', formatearNombres);
    if (regApellido) regApellido.addEventListener('input', formatearNombres);

    // --- D. DIRECCIÓN OPCIONAL Y MAPA ---
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
            if (!comuna || !calle || !numero) {
                Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Ingresa calle, número y comuna.', confirmButtonColor: '#2A1B5E', didOpen: () => Swal.getContainer().style.zIndex = '10000' });
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
                    const lat = data[0].lat, lon = data[0].lon;
                    mapReg.setView([lat, lon], 17);
                    markerReg.setLatLng([lat, lon]);
                    document.getElementById('reg-lat').value = lat;
                    document.getElementById('reg-lng').value = lon;
                } else {
                    Swal.fire({ icon: 'info', title: 'Punto no ubicado', text: 'No ubicamos el punto exacto. Mueve el pin manualmente.', confirmButtonColor: '#2A1B5E', didOpen: () => Swal.getContainer().style.zIndex = '10000' });
                }
            } catch (error) { console.error("Error geocodificación:", error); }
            finally { icon.className = originalClass; }
        });
    }

    if (btnGuardarUI) {
        btnGuardarUI.addEventListener('click', function () {
            if (!inputCalle.value.trim() || !inputNumero.value.trim() || !selectComuna.value) {
                Swal.fire({ icon: 'warning', title: '¡Casi listo!', text: 'Completa calle, número y comuna.', confirmButtonColor: '#E53935', didOpen: () => Swal.getContainer().style.zIndex = '10000' });
                return;
            }
            Swal.fire({ title: '¡Dirección lista!', text: 'Cencocalín ya sabe dónde entregarte.', imageUrl: `${BASE_URL}img/cencocalin/cencocalin_celebrando_compra.png`, imageWidth: 120, confirmButtonColor: '#85C226', didOpen: () => Swal.getContainer().style.zIndex = '10000' });
        });
    }

    function initMapRegistro() {
        if (mapReg) { setTimeout(() => { mapReg.invalidateSize(); }, 200); return; }
        const latIni = -32.7889, lngIni = -71.2039;
        const container = document.getElementById('reg-map');
        if (!container) return;
        mapReg = L.map('reg-map').setView([latIni, lngIni], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(mapReg);
        markerReg = L.marker([latIni, lngIni], { draggable: true }).addTo(mapReg);
        const updateCoords = (lat, lng) => {
            document.getElementById('reg-lat').value = lat;
            document.getElementById('reg-lng').value = lng;
        };
        markerReg.on('dragend', () => { const p = markerReg.getLatLng(); updateCoords(p.lat, p.lng); });
        mapReg.on('click', (e) => { markerReg.setLatLng(e.latlng); updateCoords(e.latlng.lat, e.latlng.lng); });
        updateCoords(latIni, lngIni);
    }

    // --- E. INTERCEPTOR FINAL DE FORMULARIO ---
    document.querySelectorAll('form[action*="auth/register"]').forEach(form => {
        form.addEventListener('submit', function (e) {
            const pass = document.getElementById('reg_password'), passConf = document.getElementById('reg_password_confirm');
            if (pass && passConf && pass.value !== passConf.value) {
                e.preventDefault(); passConf.focus();
                passConf.classList.add('animate__animated', 'animate__headShake');
                setTimeout(() => passConf.classList.remove('animate__animated', 'animate__headShake'), 500);
                Swal.fire({ icon: 'error', title: 'Contraseñas distintas', text: 'Escribe la misma clave en ambos campos.', confirmButtonColor: '#E53935', didOpen: () => Swal.getContainer().style.zIndex = '10000' });
                return;
            }
            const camposMalos = this.querySelectorAll('.is-invalid');
            if (camposMalos.length > 0) {
                e.preventDefault(); camposMalos[0].focus();
                camposMalos[0].classList.add('animate__animated', 'animate__headShake');
                setTimeout(() => camposMalos[0].classList.remove('animate__animated', 'animate__headShake'), 500);
                Swal.fire({ icon: 'error', title: 'Revisa tus datos', text: 'Corrige los campos marcados en rojo.', confirmButtonColor: '#E53935', didOpen: () => Swal.getContainer().style.zIndex = '10000' });
            }
        });
    });

    document.addEventListener('click', (e) => {
        const l = document.getElementById('lista-predictiva'), i = document.getElementById('inputBusqueda');
        if (l && i && !i.contains(e.target) && !l.contains(e.target)) l.classList.add('d-none');
    });

    // Limpiar el modal de rastreo cuando se cierra
    const modalRastreo = document.getElementById('modalRastreo');
    if (modalRastreo) {
        modalRastreo.addEventListener('hidden.bs.modal', function () {
            document.getElementById('inputTracking').value = '';
            document.getElementById('resultadoRastreo').style.display = 'none';
        });
    }


// =========================================================
    // 🔥 NUEVO: ILUMINAR TARJETA VERDE (MUTATION OBSERVER) 🔥
    // =========================================================
    
    // Función central para inyectar o quitar estilos
    function aplicarColorTarjeta(card, activar) {
        if (!card) return;
        const partesInternas = card.querySelectorAll('.fondo-dinamico-imagen, .fondo-dinamico-cuerpo');

        if (activar) {
            card.classList.remove('bg-white', 'border-light');
            card.classList.add('tarjeta-seleccionada', 'shadow', 'border-cenco-green');
            partesInternas.forEach(p => { p.classList.remove('bg-white'); p.classList.add('bg-transparent'); });
        } else {
            card.classList.remove('tarjeta-seleccionada', 'shadow', 'border-cenco-green');
            card.classList.add('bg-white', 'border-light');
            partesInternas.forEach(p => { p.classList.remove('bg-transparent'); p.classList.add('bg-white'); });
        }
    }

    // Función que evalúa el estado actual de una tarjeta específica
    function evaluarTarjetaPorId(id) {
        const card = document.getElementById(`card-prod-${id}`);
        if (!card) return;

        const controlsDiv = document.getElementById(`controls-${id}`);
        const spanCount = document.getElementById(`card-count-${id}`);
        
        let tieneProducto = false;

        // Regla 1: Si los controles de sumar/restar existen y no están ocultos
        if (controlsDiv && !controlsDiv.classList.contains('d-none')) {
            tieneProducto = true;
        }

        // Regla 2: El contador manda. Si dice explícitamente 0, no hay producto.
        if (spanCount) {
            const cantidad = parseInt(spanCount.innerText.trim()) || 0;
            if (cantidad === 0) {
                tieneProducto = false;
            } else {
                tieneProducto = true;
            }
        }

        aplicarColorTarjeta(card, tieneProducto);
    }

    // =========================================================
    // 🔥 SCROLL CON FLECHAS EN CARRUSEL HORIZONTAL 🔥
    // =========================================================
    document.querySelectorAll('.btn-scroll-arrow').forEach(boton => {
        boton.addEventListener('click', function(e) {
            e.preventDefault(); // Evita que la página salte al hacer clic
            
            // Busca el contenedor de productos que pertenece a esta flecha
            const contenedor = this.closest('.wrapper-scroll').querySelector('.scroll-horizontal');
            
            if (contenedor) {
                // Si el botón tiene la clase 'left', retrocede 400px. Si es 'right', avanza 400px.
                const distancia = this.classList.contains('left') ? -400 : 400;
                
                contenedor.scrollBy({ 
                    left: distancia, 
                    behavior: 'smooth' // Movimiento suave
                });
            }
        });
    });

});
// FIN DE DOMContentLoaded

// =========================================================
// 3. FUNCIÓN DE RASTREO PÚBLICO (GLOBAL)
// =========================================================
async function buscarTrazabilidad(event) {
    if (event) event.preventDefault();

    const input = document.getElementById('inputTracking');
    const btn = document.querySelector('#formRastreo button[type="submit"]');
    const resultadoDiv = document.getElementById('resultadoRastreo');
    const codigo = input.value.trim().toUpperCase();

    if (!codigo) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    resultadoDiv.style.display = 'none';

    try {
        const response = await fetch(BASE_URL + 'home/rastrearPedido', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tracking: codigo })
        });

        const res = await response.json();

        if (res.status === 'error') {
            resultadoDiv.innerHTML = `<div class="alert alert-danger border-0 shadow-sm rounded-3"><i class="bi bi-exclamation-circle me-2"></i>${res.msg}</div>`;
        } else {
            const p = res.data;
            const estado = p.estado_id;
            const esRetiro = p.tipo_entrega === 2;
            const h = p.horas || {};

            const textoPaso4 = esRetiro ? 'Listo para Retiro' : 'En Ruta';
            const iconoPaso4 = esRetiro ? 'bi-shop' : 'bi-truck';

            const getStepHtml = (num, icono, titulo, activo) => {
                const horaHito = h[num] ? h[num] : '';
                return `
        <div class="d-flex mb-3 align-items-center opacity-${activo ? '100' : '50'}">
            <div class="bg-${activo ? 'success' : 'secondary'} text-white rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 45px; height: 45px; flex-shrink: 0;">
                <i class="bi ${icono} fs-5"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold ${activo ? 'text-dark' : 'text-muted'}">${titulo}</h6>
                    ${activo && horaHito ? `<span class="badge bg-white text-dark border shadow-sm fw-normal" style="font-size: 0.75rem;">${horaHito} hrs</span>` : ''}
                </div>
                ${activo && num === estado && estado < 5 ? `<small class="text-success fw-bold d-block mt-1">Estado actual</small>` : ''}
            </div>
        </div>
    `;
            };

            let html = `
    <div class="bg-light p-3 rounded-4 border mb-4 text-center shadow-sm">
        <h6 class="fw-bold mb-1 text-muted small">Fecha Estimada de Entrega</h6>
        <span class="text-cenco-indigo fw-black fs-4">${p.fecha_estimada}</span>
    </div>
    <div class="position-relative ms-2">
`;
            html += getStepHtml(1, 'bi-receipt', 'Pedido Recibido', estado >= 1);
            html += getStepHtml(2, 'bi-credit-card-check', 'Pago Confirmado', estado >= 2);
            html += getStepHtml(3, 'bi-box-seam', 'En Preparación', estado >= 3);
            html += getStepHtml(4, iconoPaso4, textoPaso4, estado >= 4);
            html += getStepHtml(5, 'bi-house-check', 'Entregado', estado === 5);

            html += `</div>`;
            resultadoDiv.innerHTML = html;
        }
    } catch (err) {
        console.error("Error en la petición:", err);
        resultadoDiv.innerHTML = `<div class="alert alert-danger rounded-3"><i class="bi bi-wifi-off me-2"></i>Error al conectar con el servidor.</div>`;
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Buscar';
        resultadoDiv.style.display = 'block';
    }
}