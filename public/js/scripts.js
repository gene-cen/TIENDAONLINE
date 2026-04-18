/**
 * ARCHIVO: scripts.js
 * Descripción: Lógica del Núcleo Base de la Tienda (Accesibilidad, Analytics, 
 * y utilidades de interfaz globales).
 */

// =========================================================
// 1. MÓDULO DE ACCESIBILIDAD GLOBAL (APPLE FIX)
// =========================================================
window.AccessManager = {
    settings: { activeClasses: [], filter: '', textLevel: 0 },

    init: function () {
        try {
            const saved = localStorage.getItem('cenco_accessibility');
            if (saved) {
                this.settings = JSON.parse(saved);
                if (typeof this.settings.textLevel === 'undefined') this.settings.textLevel = 0;
                this.applySettings();
            }
        } catch (e) {
            console.warn("Safari/Apple bloqueó el acceso a LocalStorage o está en modo incógnito estricto.");
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
        else document.body.classList.add('access-lvl-' + this.settings.textLevel);
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
        const select = document.querySelector('#accessibilityModal select');
        if (select && this.settings.filter) select.value = this.settings.filter;
    },

    reset: function () {
        this.settings = { activeClasses: [], filter: '', textLevel: 0 };
        const classes = ['access-dark', 'access-invert', 'access-high-contrast', 'access-dyslexic', 'access-no-anim', 'filter-grayscale', 'filter-protanopia', 'filter-deuteranopia', 'filter-tritanopia', 'access-lvl-1', 'access-lvl-2', 'access-lvl-3'];
        classes.forEach(c => document.body.classList.remove(c));
        const select = document.querySelector('#accessibilityModal select');
        if (select) select.value = "";
        this.updateTextButtonLabel();
        this.save();
    },

    save: function () {
        try { localStorage.setItem('cenco_accessibility', JSON.stringify(this.settings)); } 
        catch (e) { console.warn("Safari/Apple bloqueó el guardado en LocalStorage."); }
    }
};

window.addEventListener('pageshow', function (event) {
    // Forzar recarga de accesibilidad si Safari usa caché al apretar "Atrás"
    if (event.persisted) window.AccessManager.init();
});

// =========================================================
// 2. UTILIDADES GLOBALES
// =========================================================
var formateadorCLP = formateadorCLP || new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 });

function trackEvento(tipo, etiqueta) {
    if (typeof window.BASE_URL !== 'undefined') {
        fetch(window.BASE_URL + 'analytics/registrar-evento', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tipo: tipo, etiqueta: etiqueta })
        }).catch(err => console.log("Tracking silent error:", err));
    }
}

function cambiarModal(idCerrar, idAbrir) {
    // 1. Obtenemos las instancias de Bootstrap
    const modalCerrar = bootstrap.Modal.getInstance(document.getElementById(idCerrar));
    const modalAbrir = new bootstrap.Modal(document.getElementById(idAbrir));

    if (modalCerrar) {
        modalCerrar.hide();
        
        // 🔥 TRUCO MAESTRO: Esperamos 350ms (duración de la animación) 
        // para abrir el siguiente sin que se duplique el fondo negro
        setTimeout(() => {
            modalAbrir.show();
        }, 350);
    } else {
        modalAbrir.show();
    }
}

// =========================================================
// 3. LISTENERS GLOBALES DEL SISTEMA
// =========================================================
document.addEventListener("DOMContentLoaded", function () {
    // Iniciar Accesibilidad
    window.AccessManager.init();

    // Arreglar Sidebar Admin
    const sidebarElement = document.getElementById('adminSidebar');
    const btnMenu = document.querySelector('[data-bs-target="#adminSidebar"]');
    if (sidebarElement && btnMenu) {
        btnMenu.addEventListener('click', function (e) {
            e.preventDefault(); 
            const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(sidebarElement); 
            bsOffcanvas.show();
        });
    }

    // Mostrar modales dinámicos (Cencocalines) al leer URL variables (?msg=...)
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');

    if (msg) {
        let title = ""; let text = ""; let img = "cencocalin_logrado.png";
        let mostrarModal = true;

        switch (msg) {
            case 'pago_rechazado_banco':
                title = "Pago Rechazado"; text = "Tu banco no autorizó la transacción. Prueba con otro medio."; img = "cencocalin_algo_fallo.png";
                break;
            case 'pago_anulado_usuario':
                title = "Pago Cancelado"; text = "Has cancelado el proceso de pago."; img = "cencocalin_preocupado.png";
                break;
            default:
                mostrarModal = false;
                break;
        }

        if (mostrarModal) {
            const modalEl = document.getElementById('successModal');
            if (modalEl) {
                document.getElementById('successTitle').innerText = title;
                document.getElementById('successMessage').innerText = text;
                const imgEl = document.getElementById('successImage');
                if (imgEl) imgEl.src = window.BASE_URL + 'img/cencocalin/' + img;
                new bootstrap.Modal(modalEl).show();
            }
        }
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

/**
 * Solo permite números en el campo de teléfono
 */
function validarSoloNumeros(input) {
    input.value = input.value.replace(/[^0-9]/g, '');
}

/**
 * Validación inteligente de contraseñas
 */
document.addEventListener('DOMContentLoaded', function() {
    const pass1 = document.getElementById('reg_password');
    const pass2 = document.getElementById('reg_password_confirm');
    const errorMsg = document.getElementById('msg_password_error');
    const btnSubmit = document.getElementById('btnSubmitRegistro');

    function checkMatch() {
        if (pass2.value.length > 0) {
            if (pass1.value !== pass2.value) {
                // No coinciden
                pass2.classList.add('border-danger');
                pass2.classList.remove('border-success');
                errorMsg.classList.remove('d-none');
                btnSubmit.disabled = true;
                btnSubmit.style.opacity = '0.5';
            } else {
                // Coinciden perfecto
                pass2.classList.remove('border-danger');
                pass2.classList.add('border-success');
                errorMsg.classList.add('d-none');
                btnSubmit.disabled = false;
                btnSubmit.style.opacity = '1';
            }
        } else {
            // Limpiar si el segundo campo está vacío
            pass2.classList.remove('border-danger', 'border-success');
            errorMsg.classList.add('d-none');
            btnSubmit.disabled = false;
        }
    }

    // Escuchar cada tecla
    if(pass1 && pass2) {
        pass1.addEventListener('input', checkMatch);
        pass2.addEventListener('input', checkMatch);
    }
});

/**
 * Función para ver/ocultar contraseña con cambio de icono
 */
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = "password";
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

// Función para cambiar la accesibilidad y GUARDARLA
// ❌ BORRA TODO ESTE BLOQUE DESDE AQUÍ ❌
// Función para cambiar la accesibilidad y GUARDARLA
function aplicarAccesibilidad(clase) {
    // 1. Lista de clases de accesibilidad (modificar según tus opciones)
    const opciones = ['access-dark', 'access-invert', 'access-high-contrast', 'access-dyslexic'];
    const filtros = ['filter-grayscale', 'filter-protanopia', 'filter-deuteranopia', 'filter-tritanopia'];

    if (opciones.includes(clase)) {
        // Es un modo de visualización
        opciones.forEach(opt => document.body.classList.remove(opt));
        if (clase) {
            document.body.classList.add(clase);
            localStorage.setItem('cenco_access_mode', clase);
        } else {
            localStorage.removeItem('cenco_access_mode');
        }
    } else if (filtros.includes(clase)) {
        // Es un filtro de daltonismo
        filtros.forEach(f => document.documentElement.classList.remove(f));
        document.documentElement.classList.add(clase);
        localStorage.setItem('cenco_access_filter', clase);
    }
}


// =========================================================
// 📍 GEOLOCALIZACIÓN EXACTA (ANALYTICS)
// =========================================================
document.addEventListener('DOMContentLoaded', function() {
    // Verificamos en sessionStorage para no molestar pidiendo el GPS en cada página
    if (!sessionStorage.getItem('gps_gestionado')) {
        
       if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                // 📍 Extraemos las coordenadas exactas
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                const urlDestino = (typeof BASE_URL !== 'undefined' ? BASE_URL : '/') + 'location/guardarGPSAjax';
                console.log("Enviando GPS en segundo plano a:", urlDestino);

                // 🚀 Hacemos el envío
                fetch(urlDestino, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ lat: lat, lng: lng })
                })
                .then(res => res.json()) // Leemos directo como JSON
                .then(data => {
                    if(data.status === 'success') {
                        console.log("Ubicación actualizada con éxito:", data.texto_navbar);
                        sessionStorage.setItem('gps_gestionado', 'autorizado');
                        
                        // ==========================================
                        // ✨ MAGIA VISUAL: ACTUALIZAR EL NAVBAR ✨
                        // ==========================================
                        const navElement = document.getElementById('nombreComunaNav');
                        if (navElement && data.texto_navbar) {
                            navElement.innerHTML = data.texto_navbar;
                            // Efecto visual: Brilla en verde por un segundo para que el usuario lo note
                            navElement.classList.add('text-cenco-green');
                            setTimeout(() => navElement.classList.remove('text-cenco-green'), 2000);
                        }

                        // ==========================================
                        // 🛑 ALERTA DE SIN COBERTURA (SweetAlert) 🛑
                        // ==========================================
                        if (data.alerta) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: '¡Bienvenido a Cencocal!',
                                    html: data.alerta,
                                    icon: 'info',
                                    confirmButtonColor: '#2A1B5E',
                                    confirmButtonText: 'Explorar Catálogo',
                                    customClass: { popup: 'rounded-4 shadow-lg border-0' }
                                });
                            } else {
                                // Plan B por si SweetAlert no ha cargado en esa vista
                                alert("Bienvenido. " + data.alerta.replace(/<[^>]*>?/gm, '')); 
                            }
                        }

                    } else {
                        console.error("El servidor no pudo procesar el GPS:", data.msg);
                    }
                })
                .catch(err => {
                    console.log('Silencio: Error de red al enviar GPS, se mantiene por IP.', err);
                });
            },
            function(error) {
                // ¡EL USUARIO DENEGÓ O FALLÓ! 
                console.log("GPS no autorizado. Manteniendo ubicación por IP.");
                sessionStorage.setItem('gps_gestionado', 'denegado');
            },
            { timeout: 7000 } // Esperamos máximo 7 segundos
        );
    }
    }

});