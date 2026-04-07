// =========================================================
// MÓDULO DE ACCESIBILIDAD (GLOBAL) - APPLE FIX
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
        try {
            localStorage.setItem('cenco_accessibility', JSON.stringify(this.settings));
        } catch (e) {
            console.warn("Safari/Apple bloqueó el guardado en LocalStorage.");
        }
    }
};


// =========================================================
// PARCHE SAFARI / IOS: FORZAR RECARGA AL USAR EL BOTÓN "ATRÁS"
// =========================================================
window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
        // Si la página viene del caché (Safari Back Button), aplicamos los ajustes de nuevo
        window.AccessManager.init();
    }
});
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

function cambiarModal(idCerrar, idAbrir) {
    const elCerrar = document.getElementById(idCerrar);
    if (elCerrar) bootstrap.Modal.getInstance(elCerrar).hide();
    setTimeout(() => {
        const elAbrir = document.getElementById(idAbrir);
        if (elAbrir) new bootstrap.Modal(elAbrir).show();
    }, 200);
}

// =========================================================
// CARRITO DE COMPRAS - MOTOR GLOBAL (LIMPIO Y UNIFICADO)
// =========================================================
// 1. EL PRIMER CLIC: Añadir al carro desde cero (Tarjetas de Home y Catálogo)
function agregarAlCarrito(e, form, id) {
    e.preventDefault();
    const formData = new FormData(form);

    fetch(BASE_URL + 'carrito/agregarAjax', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                // Transformar la tarjeta visualmente
                const formAdd = document.getElementById('form-add-' + id);
                const controls = document.getElementById('controls-' + id);
                const badgeLlevas = document.getElementById('badge-llevas-' + id);
                const imgContainer = document.getElementById('img-container-' + id);
                const cardProd = document.getElementById('card-prod-' + id);
                const counts = document.querySelectorAll(`#card-count-${id}, #count-${id}`);

                counts.forEach(el => el.innerText = data.cantidadItem);

                if (formAdd) { formAdd.classList.remove('d-block'); formAdd.classList.add('d-none'); }
                if (controls) { controls.classList.remove('d-none'); controls.classList.add('d-flex'); }
                if (badgeLlevas) { badgeLlevas.classList.remove('d-none'); badgeLlevas.classList.add('d-inline-block'); }
                if (imgContainer) imgContainer.classList.replace('bg-white', 'bg-success-subtle');
                if (cardProd) {
                    cardProd.classList.replace('border-0', 'border-cenco-green');
                    cardProd.classList.add('shadow');
                }

                // AVISAR A TODA LA PÁGINA (NAVBAR Y MENU LATERAL)
                actualizarCarritoGlobal(data.totalCantidad, data.totalMonto);

                // =========================================================
                // MAGIA DE SINCRONIZACIÓN: Recargar si estamos en Checkout
                // =========================================================
                // =========================================================
                // MAGIA DE SINCRONIZACIÓN: Actualización silenciosa (Sin recargar)
                // =========================================================
                if (window.location.pathname.includes('checkout')) {
                    // Vamos a buscar los nuevos totales de forma invisible
                    fetch(window.location.href)
                        .then(res => res.text())
                        .then(html => {
                            const doc = new DOMParser().parseFromString(html, 'text/html');

                            // 1. Actualizamos la tarjeta derecha (El Resumen con todos los precios)
                            const resumenActual = document.querySelector('.col-lg-5 .card-body');
                            const resumenNuevo = doc.querySelector('.col-lg-5 .card-body');
                            if (resumenActual && resumenNuevo) resumenActual.innerHTML = resumenNuevo.innerHTML;

                            // 2. Actualizamos el precio del envío en el botón "A Domicilio"
                            const lblDespacho = document.querySelector('label[for="opcion_despacho"]');
                            const lblDespachoNuevo = doc.querySelector('label[for="opcion_despacho"]');
                            if (lblDespacho && lblDespachoNuevo) lblDespacho.innerHTML = lblDespachoNuevo.innerHTML;

                            // 3. Actualizamos la barra amarilla ("Te faltan $X para envío gratis")
                            const alertaEnvio = document.getElementById('alerta-envio-gratis');
                            const alertaEnvioNuevo = doc.getElementById('alerta-envio-gratis');
                            if (alertaEnvio && alertaEnvioNuevo) {
                                alertaEnvio.innerHTML = alertaEnvioNuevo.innerHTML;
                                alertaEnvio.className = alertaEnvioNuevo.className; // Mantiene visible/oculto
                            }
                        });
                    return; // Evitamos que siga y abra el carrito lateral encima
                }
                // Abrimos el carrito lateral automáticamente (Solo si no estamos en checkout)
                const offcanvasElement = document.getElementById('offcanvasCarrito');
                if (offcanvasElement) {
                    const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement) || new bootstrap.Offcanvas(offcanvasElement);
                    bsOffcanvas.show();
                }

            } else if (data.status === 'error') {
                Swal.fire({ icon: 'warning', title: 'Límite de Stock', text: data.mensaje, confirmButtonColor: '#2A1B5E', customClass: { popup: 'rounded-4 shadow-lg' } });
            }
        })
        .catch(err => console.error('Error al agregar:', err));
}
// 2. PUENTE: Para los botones "+" y "-" de las tarjetas del Catálogo
function gestionarClickTarjeta(id, accion) {
    cambiarCantidad(id, accion);
}
// 3. LA CALCULADORA MAESTRA: Modifica las cantidades de lo que ya está en el carro
function cambiarCantidad(id, accion) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('accion', accion);

    fetch(BASE_URL + 'carrito/modificarAjax', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {

            // --- 1. ACTUALIZAR STOCK DINÁMICO EN LA VISTA ---
            const spanStock = document.getElementById('stock-num-' + id);
            const contenedorStatus = document.getElementById('status-stock-' + id);
            
            if (spanStock && data.stockDisponible !== undefined) {
                // Actualizamos el número (Backend debe enviar stockDisponible)
                spanStock.innerText = data.stockDisponible;

                // Si se agota, mostramos el aviso de "Sin stock"
                if (data.stockDisponible <= 0) {
                    contenedorStatus.innerHTML = `<span class="text-danger small fw-bold"><i class="bi bi-x-circle"></i> Sin stock disponible</span>`;
                } else if (data.stockDisponible < 5) {
                    // Si queda poco, activamos la alerta crítica
                    contenedorStatus.innerHTML = `
                        <span class="badge bg-light text-dark border fw-normal" style="font-size: 0.75rem;">
                            Stock disponible: <strong id="stock-num-${id}">${data.stockDisponible}</strong>
                        </span>
                        <br>
                        <span class="text-danger extra-small fw-bold animate__animated animate__flash animate__infinite">
                            <i class="bi bi-exclamation-triangle"></i> ¡Últimas unidades!
                        </span>`;
                }
            }

            // --- 2. ACTUALIZAR CANTIDADES Y SUBTOTALES ---
            const inputCant = document.getElementById('input-cant-' + id);
            if (inputCant) {
                inputCant.value = data.cantidadItem;
                const precio = parseInt(inputCant.getAttribute('data-precio')) || 0;
                const subtotalFmt = new Intl.NumberFormat('es-CL').format(precio * data.cantidadItem);
                const tdSubtotal = document.getElementById('subtotal-item-' + id);
                if (tdSubtotal) tdSubtotal.innerText = '$' + subtotalFmt;
            }

            // --- 3. MANEJO DE ELIMINACIÓN TOTAL ---
            if (data.cantidadItem === 0) {
                const filaCarrito = document.getElementById('fila-carrito-' + id);
                if (filaCarrito) filaCarrito.remove();

                if (data.totalCantidad === 0 && window.location.href.includes('carrito')) {
                    location.reload();
                    return;
                }

                // Revertir tarjetas en catálogo (Home)
                const idsParaOcultar = ['form-add-', 'controls-', 'badge-llevas-', 'img-container-', 'card-prod-'];
                idsParaOcultar.forEach(prefix => {
                    const el = document.getElementById(prefix + id);
                    if (el) {
                        if (prefix === 'form-add-') el.classList.replace('d-none', 'd-block');
                        if (prefix === 'controls-') el.classList.replace('d-flex', 'd-none');
                        if (prefix === 'badge-llevas-') el.classList.add('d-none');
                        if (prefix === 'img-container-') el.classList.replace('bg-success-subtle', 'bg-white');
                        if (prefix === 'card-prod-') { el.classList.replace('border-cenco-green', 'border-0'); el.classList.remove('shadow'); }
                    }
                });
            } else {
                const counts = document.querySelectorAll(`#card-count-${id}, #count-${id}`);
                counts.forEach(el => el.innerText = data.cantidadItem);
            }

            // --- 4. ACTUALIZAR RESUMEN TOTAL ---
            const resumenTotal = document.getElementById('resumen-total');
            if (resumenTotal) {
                const montoFmt = new Intl.NumberFormat('es-CL').format(data.totalMonto);
                const ivaFmt = new Intl.NumberFormat('es-CL').format(data.totalMonto * 0.19);
                document.getElementById('resumen-subtotal').innerText = '$' + montoFmt;
                document.getElementById('resumen-iva').innerText = '$' + ivaFmt;
                resumenTotal.innerText = '$' + montoFmt;
            }

            actualizarCarritoGlobal(data.totalCantidad, data.totalMonto);

            // --- 5. LÓGICA DE BOTÓN SUBIR (Límite de Stock) ---
            const btnSubirLocal = document.getElementById('btn-subir-' + id);
            if (btnSubirLocal) {
                if (data.stockDisponible <= 0) {
                    btnSubirLocal.disabled = true;
                    btnSubirLocal.classList.replace('text-success', 'text-muted');
                } else {
                    btnSubirLocal.disabled = false;
                    btnSubirLocal.classList.replace('text-muted', 'text-success');
                }
            }

            // --- 6. SINCRONIZACIÓN CHECKOUT (SILENCIOSA) ---
            if (window.location.pathname.includes('checkout')) {
                if (data.totalCantidad === 0) { window.location.reload(); return; }
                fetch(window.location.href)
                    .then(res => res.text())
                    .then(html => {
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const selectors = ['.col-lg-5 .card-body', 'label[for="opcion_despacho"]', '#alerta-envio-gratis'];
                        selectors.forEach(sel => {
                            const actual = document.querySelector(sel);
                            const nuevo = doc.querySelector(sel);
                            if (actual && nuevo) actual.innerHTML = nuevo.innerHTML;
                        });
                    });
            }

        } else if (data.status === 'error') {
            Swal.fire({ icon: 'warning', title: 'Límite de Stock', text: data.mensaje, confirmButtonColor: '#2A1B5E' });
        }
    })
    .catch(error => console.error('Error al modificar carrito:', error));
}

// 4. ATENUAR BOTONES DE TARJETAS AL LLEGAR AL LÍMITE
function actualizarBotonesStock(id, bloquear) {
    const btnMas = document.querySelector(`#controls-${id} .btn-cenco-green`);
    if (btnMas) {
        if (bloquear) {
            btnMas.classList.add('opacity-50');
        } else {
            btnMas.classList.remove('opacity-50');
        }
    }
}

// 5. EL COMUNICADOR: Actualiza el Navbar y el Menú Lateral de un golpe
function actualizarCarritoGlobal(cantidad, monto) {
    const montoFmt = new Intl.NumberFormat('es-CL').format(monto);

    // Actualizar badges rojos
    document.querySelectorAll('.badge-carrito, #badge-carrito-navbar, #contador-carrito').forEach(el => {
        el.innerText = cantidad;
        el.style.display = cantidad > 0 ? 'inline-block' : 'none';
    });

    // Actualizar montos ($)
    document.querySelectorAll('.monto-carrito, #monto-carrito-navbar, #total-monto-navbar').forEach(el => {
        el.innerText = '$' + montoFmt;
    });

    // Siempre refrescar el menú lateral oculto
    actualizarCarritoLateral();
}

// 6. OBTENER HTML DEL LATERAL
function actualizarCarritoLateral() {
    fetch(BASE_URL + 'carrito/obtenerHtml')
        .then(res => res.json())
        .then(data => {
            const contenedorLista = document.getElementById('contenedor-carrito-lista');
            const contenedorTotal = document.getElementById('contenedor-carrito-total');

            if (contenedorLista) contenedorLista.innerHTML = data.html;
            if (contenedorTotal) contenedorTotal.innerText = data.total;
        })
        .catch(err => console.error('Error recargando offcanvas:', err));
}

// =========================================================
// MAPA REGISTRO Y DOM READY
// =========================================================

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

    // Validación Términos
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
            const nombre = document.getElementById('reg_nombre').value.trim();
            const apellido = document.getElementById('reg_apellido').value.trim();
            document.getElementById('inputNombreCompleto').value = nombre + ' ' + apellido;
        });
    }

    // MENSAJES Y CENCOCALINES DINÁMICOS
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');

    if (msg) {
        let title = ""; let text = ""; let img = "cencocalin_logrado.png";
        let mostrarModal = true;

        switch (msg) {
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
                if (imgEl) imgEl.src = BASE_URL + 'img/cencocalin/' + img;
                new bootstrap.Modal(modalEl).show();
            }
        }
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// 8. CONFIRMAR ELIMINACIÓN DE PRODUCTO
function confirmarEliminarCarrito(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Sacarás este producto de tu carrito.",
        imageUrl: BASE_URL + 'img/cencocalin/cencocalin_preocupado.png',
        imageWidth: 100,
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#2A1B5E',
        confirmButtonText: 'Sí, quitarlo',
        cancelButtonText: 'Cancelar',
        customClass: { popup: 'rounded-4 shadow-lg' }
    }).then((result) => {
        if (result.isConfirmed) {
            // Si dice que sí, disparamos la función que ya hace la magia
            cambiarCantidad(id, 'eliminar');
        }
    });
}