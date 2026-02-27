// =========================================================
// MÓDULO DE ACCESIBILIDAD (GLOBAL)
// =========================================================
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
        localStorage.setItem('cenco_accessibility', JSON.stringify(this.settings));
    }
};

// =========================================================
// UTILIDADES
// =========================================================
var formateadorCLP = formateadorCLP || new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 });

function trackEvento(tipo, etiqueta) {
    if (typeof BASE_URL !== 'undefined') {
        fetch(BASE_URL + 'analytics/registrar-evento', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tipo: tipo, etiqueta: etiqueta })
        }).catch(err => console.log("Tracking silent error:", err));
    }
}

// =========================================================
// UI GLOBAL (Modales, Carrito, Mapa)
// =========================================================
function cambiarModal(idCerrar, idAbrir) {
    const elCerrar = document.getElementById(idCerrar);
    if (elCerrar) bootstrap.Modal.getInstance(elCerrar).hide();
    setTimeout(() => {
        const elAbrir = document.getElementById(idAbrir);
        if (elAbrir) new bootstrap.Modal(elAbrir).show();
    }, 200);
}

function reabrirLogin() {
    const errorEl = document.getElementById('loginErrorModal');
    if (errorEl) bootstrap.Modal.getInstance(errorEl).hide();
    const loginEl = document.getElementById('loginModal');
    if (loginEl) new bootstrap.Modal(loginEl).show();
}

// CARRITO
function agregarAlCarrito(e, form, idProducto) {
    if (e) e.preventDefault();

    const btn = form.querySelector('button');
    const original = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    btn.disabled = true;

    const formData = new FormData(form);
    // IMPORTANTE: Aseguramos que el ID vaya sí o sí
    formData.append('id', idProducto);

    fetch(BASE_URL + 'carrito/agregarAjax', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                trackEvento('add_to_cart', 'Producto ID: ' + idProducto);

                // 1. Actualiza el numerito del carrito en el Navbar
                actualizarInterfazGlobal(data);

                // 2. Actualiza la tarjeta del catálogo (si existe en la página)
                actualizarVistaProducto(idProducto, data.cantidadItem);

                // 3. NUEVO: Si estamos en la ficha de producto (detalle), actualizamos sus IDs únicos
                const badgeDetalle = document.getElementById('badge-llevas-detalle');
                const countDetalle = document.getElementById('count-detalle');
                const cardCountDetalle = document.getElementById('card-count-detalle');
                const controlesDetalle = document.getElementById('controles-detalle');
                const formDetalle = document.getElementById('form-add-detalle');

                if (badgeDetalle) badgeDetalle.classList.remove('d-none');
                if (countDetalle) countDetalle.innerText = data.cantidadItem;
                if (cardCountDetalle) cardCountDetalle.innerText = data.cantidadItem;
                if (controlesDetalle) controlesDetalle.classList.remove('d-none');
                if (formDetalle) formDetalle.classList.add('d-none');

                // 4. Abrimos el carrito lateral para dar feedback al usuario
                abrirCarritoLateral();
            } else {
                // Si el controlador blindado detecta precio $0 o sin stock
                Swal.fire({
                    icon: 'error',
                    title: 'Atención',
                    text: data.mensaje,
                    confirmButtonColor: '#2A1B5E'
                });
            }
        })
        .catch(err => console.error("Error:", err))
        .finally(() => {
            btn.innerHTML = original;
            btn.disabled = false;
        });
}

function gestionarClickTarjeta(id, accion) {
    const span = document.getElementById('card-count-' + id);
    let cant = span ? (parseInt(span.innerText) || 0) : 0;
    cambiarCantidad(id, accion, cant);
}

function cambiarCantidad(id, accion, cantidadActual = 0) {
    // Cencocalin Preocupado
    const imgMascota = BASE_URL + 'img/cencocalin/cencocalin_preocupado.png';
    const swalOpts = {
        imageUrl: imgMascota, imageWidth: 140, buttonsStyling: false, reverseButtons: true,
        customClass: { popup: 'rounded-4 shadow', confirmButton: 'btn btn-outline-danger rounded-pill fw-bold px-4 m-1', cancelButton: 'btn btn-cenco-indigo rounded-pill fw-bold px-4 m-1' }
    };

    if (accion === 'eliminar') {
        Swal.fire({ ...swalOpts, title: '¿Lo sacamos?', text: "Se eliminará del carro.", showCancelButton: true, confirmButtonText: 'Sí, sacar', cancelButtonText: 'No' })
            .then(r => { if (r.isConfirmed) procesarCambio(id, accion); });
        return;
    }
    if (accion === 'bajar' && cantidadActual == 1) {
        Swal.fire({ ...swalOpts, title: '¿Descartar?', text: "Queda 1 unidad. ¿Eliminar?", showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Mantener' })
            .then(r => { if (r.isConfirmed) procesarCambio(id, accion); });
        return;
    }
    procesarCambio(id, accion);
}
function procesarCambio(id, accion) {
    const fd = new FormData();
    fd.append('id', id);
    fd.append('accion', accion);

    fetch(BASE_URL + 'carrito/modificarAjax', {
        method: 'POST',
        body: fd
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                actualizarInterfazGlobal(data);
                actualizarVistaProducto(id, data.cantidadItem);

                // Actualizar también el contador si estamos en la vista de detalle
                const detCount = document.getElementById('card-count-detalle');
                if (detCount) detCount.innerText = data.cantidadItem;

                // Si la cantidad llega a 0 en el detalle, mostrar el formulario de nuevo
                if (data.cantidadItem === 0) {
                    const fDet = document.getElementById('form-add-detalle');
                    const cDet = document.getElementById('controles-detalle');
                    const bDet = document.getElementById('badge-llevas-detalle');
                    if (fDet) fDet.classList.remove('d-none');
                    if (cDet) cDet.classList.add('d-none');
                    if (bDet) bDet.classList.add('d-none');
                }
            }
        });
}

function actualizarInterfazGlobal(data) {
    const badge = document.getElementById('contador-carrito');
    if (badge) badge.innerText = data.totalCantidad;
    actualizarCarritoLateral();
}

function actualizarVistaProducto(id, cantidad) {
    // Lógica visual de tarjeta (simplificada)
    const card = document.getElementById('card-prod-' + id);
    const badge = document.getElementById('badge-llevas-' + id);
    const form = document.getElementById('form-add-' + id);
    const ctrls = document.getElementById('controls-' + id);
    const counts = document.querySelectorAll(`#count-${id}, #card-count-${id}`);

    if (cantidad > 0) {
        if (card) { card.classList.remove('border-0'); card.classList.add('border-cenco-green'); }
        if (badge) badge.classList.remove('d-none');
        if (form) form.classList.add('d-none');
        if (ctrls) ctrls.classList.remove('d-none');
        counts.forEach(el => el.innerText = cantidad);
    } else {
        if (card) { card.classList.add('border-0'); card.classList.remove('border-cenco-green'); }
        if (badge) badge.classList.add('d-none');
        if (form) form.classList.remove('d-none');
        if (ctrls) ctrls.classList.add('d-none');
    }
}

function abrirCarritoLateral() {
    const el = document.getElementById('offcanvasCarrito');
    const bs = bootstrap.Offcanvas.getOrCreateInstance(el);
    actualizarCarritoLateral();
    bs.show();
}

function actualizarCarritoLateral() {
    fetch(BASE_URL + 'carrito/obtenerHtml').then(r => r.json()).then(data => {
        const container = document.getElementById('contenido-carrito-lateral');
        const total = document.getElementById('total-carrito-lateral');
        if (container) container.innerHTML = data.html;
        if (total) total.innerText = data.total;
    });
}

// MAPA REGISTRO
let mapRegister = null; let markerRegister = null;
function toggleDireccion() {
    const check = document.getElementById('checkDireccion');
    const box = document.getElementById('direccion-box');
    if (check && check.checked) {
        box.style.display = 'block';
        if (!mapRegister) initMapRegister();
        setTimeout(() => { if (mapRegister) mapRegister.invalidateSize(); }, 300);
    } else if (box) { box.style.display = 'none'; }
}

function initMapRegister() {
    if (mapRegister || !document.getElementById('mapa-container')) return;
    mapRegister = L.map('mapa-container').setView([-33.4489, -70.6693], 13);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(mapRegister);
    markerRegister = L.marker([-33.4489, -70.6693], { draggable: true }).addTo(mapRegister);
    markerRegister.on('dragend', function (e) { updateCoordsRegister(markerRegister.getLatLng()); });
    mapRegister.on('click', function (e) { markerRegister.setLatLng(e.latlng); updateCoordsRegister(e.latlng); });
}

function updateCoordsRegister(pos) {
    document.getElementById('latitud').value = pos.lat;
    document.getElementById('longitud').value = pos.lng;
}

async function buscarEnMapaRegister() {
    const direccion = document.getElementById('direccion-input').value;
    if (direccion.length < 5) return Swal.fire('Error', 'Ingresa una dirección válida.', 'warning');
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(direccion)}, Chile`);
        const data = await res.json();
        if (data && data.length > 0) {
            const nuevaPos = new L.LatLng(data[0].lat, data[0].lon);
            mapRegister.setView(nuevaPos, 16); markerRegister.setLatLng(nuevaPos); updateCoordsRegister(nuevaPos);
        } else { Swal.fire('Ups', 'No encontramos esa dirección.', 'info'); }
    } catch (e) { console.error(e); }
}

function formatearRut(rutInput) {
    let valor = rutInput.value.replace(/\./g, '').replace(/-/g, '');
    if (valor.length > 1) {
        let cuerpo = valor.slice(0, -1);
        let dv = valor.slice(-1).toUpperCase();
        rutInput.value = cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, ".") + "-" + dv;
    }
}

// =========================================================
// INICIALIZACIÓN GLOBAL (DOM READY)
// =========================================================
document.addEventListener("DOMContentLoaded", function () {
    window.AccessManager.init();

    // Fix Sidebar Admin
    const sidebarElement = document.getElementById('adminSidebar');
    const btnMenu = document.querySelector('[data-bs-target="#adminSidebar"]');
    if (sidebarElement && btnMenu) {
        btnMenu.addEventListener('click', function (e) {
            e.preventDefault(); const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(sidebarElement); bsOffcanvas.show();
        });
    }

    // Fix Mapa Registro
    const registerModalEl = document.getElementById('registerModal');
    if (registerModalEl) {
        registerModalEl.addEventListener('shown.bs.modal', function () {
            if (document.getElementById('checkDireccion').checked && mapRegister) setTimeout(() => mapRegister.invalidateSize(), 200);
        });
    }

    // Validación Términos (Cencocalin Abogado)
    const formRegistro = document.getElementById('formRegistro');
    if (formRegistro) {
        formRegistro.addEventListener('submit', function (e) {
            const checkTerms = document.getElementById('checkTerms');
            if (!checkTerms.checked) {
                e.preventDefault();
                Swal.fire({
                    imageUrl: BASE_URL + 'img/cencocalin/cencocalin_abogado.png',
                    imageHeight: 150, title: 'Un momento...', text: 'Nuestros abogados dicen que debes aceptar los Términos y Condiciones.',
                    confirmButtonText: 'Leer y Aceptar', confirmButtonColor: '#2A1B5E'
                });
            }
            // Unir nombres
            const nombre = document.getElementById('reg_nombre').value.trim();
            const apellido = document.getElementById('reg_apellido').value.trim();
            document.getElementById('inputNombreCompleto').value = nombre + ' ' + apellido;
        });
    }

    // MENSAJES Y CENCOCALINES DINÁMICOS
    // MENSAJES Y CENCOCALINES DINÁMICOS (Versión Profesional)
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');

    if (msg) {
        let title = ""; let text = ""; let img = "cencocalin_logrado.png";
        let mostrarModal = true;

        switch (msg) {
            case 'compra_exitosa': case 'pago_exitoso':
                title = "¡Compra Exitosa!";
                text = "¡Gracias por preferirnos! Tu pedido está en proceso.";
                img = "cencocalin_celebrando_compra.png";
                break;
            case 'pago_rechazado_banco':
                title = "Pago Rechazado";
                text = "Tu banco no autorizó la transacción. Prueba con otro medio.";
                img = "cencocalin_algo_fallo.png";
                break;
            case 'pago_anulado_usuario':
                title = "Pago Cancelado";
                text = "Has cancelado el proceso de pago.";
                img = "cencocalin_preocupado.png";
                break;
            case 'login_exito':
                title = "¡Hola de nuevo!"; text = "¡Estamos felices de tenerte de vuelta!"; img = "cencocalin_bienvenida.png"; break;
            case 'logout_exito':
                title = "¡Hasta la próxima!"; text = "Esperamos verte pronto."; img = "cencocalin_despidiendo.png"; break;
            // ... (mantén tus otros casos de contraseña o registro) ...
            default:
                // Si el mensaje no está mapeado, NO mostramos el modal por defecto
                // Esto evita el pop-up de "Acción realizada" que bloquea Webpay
                mostrarModal = false;
                break;
        }

        if (mostrarModal) {
            const modalEl = document.getElementById('successModal');
            if (modalEl) {
                document.getElementById('successTitle').innerText = title;
                document.getElementById('successMessage').innerText = text;
                const imgEl = document.getElementById('successImage');
                if (imgEl) imgEl.src = BASE_URL + 'img/cencocalin/' + img;
                new bootstrap.Modal(modalEl).show();
            }
        }
        // Limpiamos la URL para que el mensaje no salga de nuevo al recargar
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});