/**
 * Lógica para el Perfil de Usuario (Mapas, Direcciones, Pedidos, AJAX)
 */

let map = null;
let marker = null;
let mapEdit = null;
let markerEdit = null;

document.addEventListener('DOMContentLoaded', function () {

    // --- 1. MANTENER PESTAÑA ACTIVA ---
    const params = new URLSearchParams(window.location.search);
    const tabName = params.get('tab');
    if (tabName) {
        const tabButton = document.querySelector(`button[data-bs-target="#${tabName}"]`);
        if (tabButton) {
            new bootstrap.Tab(tabButton).show();
        }
    }

    // --- 2. INICIALIZAR MAPA (CREAR DIRECCIÓN) ---
    const modalDireccion = document.getElementById('modalNuevaDireccion');
    if (modalDireccion) {
        modalDireccion.addEventListener('shown.bs.modal', function () {
            if (!map) {
                // Coordenadas iniciales (Centro Santiago/Chile)
                map = L.map('mapaSelect').setView([-33.4489, -70.6693], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(map);

                marker = L.marker([-33.4489, -70.6693], { draggable: true }).addTo(map);

                marker.on('dragend', function (e) {
                    const coord = e.target.getLatLng();
                    guardarCoordenadas(coord.lat, coord.lng);
                });
            }
            setTimeout(() => { map.invalidateSize(); }, 10);
            // 🔥 AÑADE ESTO: Cargar las comunas de la región seleccionada por defecto
            const regionSelect = document.getElementById('selectRegionNueva');
            if (regionSelect && regionSelect.value) {
                cargarComunas(regionSelect.value);
            }
        });
    }
});

// ============================================
// FUNCIONES UI (TOASTS, ETC)
// ============================================

function mostrarToast(mensaje, tipo = 'success') {
    const toastEl = document.getElementById('liveToast');
    const toastBody = document.getElementById('toastMessage');
    toastEl.className = `toast align-items-center text-white border-0 bg-${tipo}`;
    toastBody.innerText = mensaje;
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
}

// ============================================
// LÓGICA DE DETALLE PEDIDO (MODAL)
// ============================================

// ============================================
// LÓGICA DE MAPAS Y DIRECCIONES
// ============================================

function guardarCoordenadas(lat, lng) {
    document.getElementById('inputLat').value = lat;
    document.getElementById('inputLng').value = lng;
}

// BUSCADOR MAPA (CREAR)
function buscarEnMapa() {
    const inputCalle = document.getElementById('inputDireccion');
    const selectComuna = document.getElementById('selectComuna');
    const btnBuscar = document.getElementById('btnBuscarMapa');

    realizarBusquedaMapa(inputCalle, selectComuna, btnBuscar, map, marker, (lat, lng) => {
        guardarCoordenadas(lat, lng);
    });
}

// BUSCADOR MAPA (EDITAR)
function buscarEnMapaEdit() {
    const inputCalle = document.getElementById('edit_direccion');
    const selectComuna = document.getElementById('edit_comuna');
    const btnBuscar = document.getElementById('btnBuscarMapaEdit');

    realizarBusquedaMapa(inputCalle, selectComuna, btnBuscar, mapEdit, markerEdit, (lat, lng) => {
        document.getElementById('edit_lat').value = lat;
        document.getElementById('edit_lng').value = lng;
    });
}

// BUSCADOR GENÉRICO (NOMINATIM API)
function realizarBusquedaMapa(inputCalle, selectComuna, btnBuscar, mapaObj, markerObj, callbackCoords) {
    const calle = inputCalle.value.trim();

    if (!selectComuna.value) {
        alert("⚠️ Por favor selecciona una Comuna antes de buscar.");
        selectComuna.focus();
        return;
    }
    if (calle.length < 3) {
        alert("⚠️ Por favor escribe una dirección válida.");
        inputCalle.focus();
        return;
    }

    const comunaTexto = selectComuna.options[selectComuna.selectedIndex].text;
    const iconoOriginal = btnBuscar.innerHTML;
    btnBuscar.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    btnBuscar.disabled = true;

    const query = `${calle}, ${comunaTexto}, Chile`;
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lon = parseFloat(data[0].lon);

                if (mapaObj) {
                    const nuevaPos = new L.LatLng(lat, lon);
                    mapaObj.setView(nuevaPos, 16);
                    markerObj.setLatLng(nuevaPos);
                    callbackCoords(lat, lon);
                }
            } else {
                alert("❌ No encontramos esa dirección exacta. Mueve el pin manualmente.");
            }
        })
        .catch(e => {
            console.error(e);
            alert("Error de conexión al buscar.");
        })
        .finally(() => {
            btnBuscar.innerHTML = iconoOriginal;
            btnBuscar.disabled = false;
        });
}

// CARGAR COMUNAS (CREAR)
function cargarComunas(regionId) {
    const comunaSelect = document.getElementById('selectComuna');
    comunaSelect.innerHTML = '<option>Cargando...</option>';
    comunaSelect.disabled = true;

    if (!regionId) {
        comunaSelect.innerHTML = '<option value="">Elige Región primero</option>';
        return;
    }

    fetch(BASE_URL + 'perfil/obtenerComunas?region_id=' + regionId)
        .then(r => r.json())
        .then(data => {
            comunaSelect.innerHTML = '<option value="">Selecciona</option>';
            if (data.length > 0) {
                data.forEach(c => {
                    comunaSelect.innerHTML += `<option value="${c.id}">${c.nombre}</option>`;
                });
                comunaSelect.disabled = false;
            } else {
                comunaSelect.innerHTML = '<option value="">No hay comunas</option>';
            }
        })
        .catch(e => { comunaSelect.innerHTML = '<option>Error</option>'; });
}

// CARGAR COMUNAS (EDITAR)
function cargarComunasEdicion(regionId, comunaSeleccionadaId = null) {
    const select = document.getElementById('edit_comuna');
    select.innerHTML = '<option>Cargando...</option>';

    fetch(BASE_URL + 'perfil/obtenerComunas?region_id=' + regionId)
        .then(r => r.json())
        .then(data => {
            select.innerHTML = '<option value="">Selecciona</option>';
            data.forEach(c => {
                const selected = (c.id == comunaSeleccionadaId) ? 'selected' : '';
                select.innerHTML += `<option value="${c.id}" ${selected}>${c.nombre}</option>`;
            });
        });
}

// INICIALIZAR EDICIÓN (MODAL)
function cargarDatosEdicion(id) {
    fetch(BASE_URL + 'perfil/obtenerDireccionPorId?id=' + id)
        .then(r => r.json())
        .then(data => {
            if (!data) return;

            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_etiqueta').value = data.nombre_etiqueta;
            document.getElementById('edit_direccion').value = data.direccion;
            document.getElementById('edit_lat').value = data.latitud;
            document.getElementById('edit_lng').value = data.longitud;
            document.getElementById('edit_region').value = data.region_id;

            cargarComunasEdicion(data.region_id, data.comuna_id);

            const modal = new bootstrap.Modal(document.getElementById('modalEditarDireccion'));
            modal.show();

            setTimeout(() => {
                initMapaEdicion(data.latitud, data.longitud);
            }, 500);
        });
}

function initMapaEdicion(lat, lng) {
    const latInit = lat || -33.4489;
    const lngInit = lng || -70.6693;

    if (!mapEdit) {
        mapEdit = L.map('mapaEditar').setView([latInit, lngInit], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(mapEdit);
        markerEdit = L.marker([latInit, lngInit], { draggable: true }).addTo(mapEdit);

        markerEdit.on('dragend', function (e) {
            const coord = e.target.getLatLng();
            document.getElementById('edit_lat').value = coord.lat;
            document.getElementById('edit_lng').value = coord.lng;
        });
    } else {
        const nuevaPos = new L.LatLng(latInit, lngInit);
        mapEdit.setView(nuevaPos, 15);
        markerEdit.setLatLng(nuevaPos);
        mapEdit.invalidateSize();
    }
}

// ============================================
// ACCIONES AJAX (ELIMINAR, FAVORITO, GUARDAR)
// ============================================

// GUARDAR DIRECCIÓN (AJAX)
function guardarDireccionAjax(e, form, endpointName) {
    e.preventDefault();
    const formData = new FormData(form);
    const btn = form.querySelector('button[type="submit"]');
    const txtOriginal = btn.innerText;

    btn.innerText = 'Guardando...';
    btn.disabled = true;

    fetch(BASE_URL + 'perfil/' + endpointName, { method: 'POST', body: formData })
        .then(response => {
            // Recargar lista completa
            fetch(window.location.href)
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    document.getElementById('contenedor-direcciones').innerHTML = doc.getElementById('contenedor-direcciones').innerHTML;
                });

            const modalEl = form.closest('.modal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            modalInstance.hide();

            mostrarToast('Dirección guardada con éxito');
            form.reset();
        })
        .catch(err => {
            console.error(err);
            mostrarToast('Error al guardar', 'danger');
        })
        .finally(() => {
            btn.innerText = txtOriginal;
            btn.disabled = false;
        });
}

// ELIMINAR DIRECCIÓN
function confirmarBorrarAjax(id) {
    if (!confirm('¿Estás seguro de eliminar esta dirección?')) return;

    const formData = new FormData();
    formData.append('id', id);

    fetch(BASE_URL + 'perfil/eliminarDireccionAjax', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                const card = document.getElementById('card-direccion-' + id);
                if (card) {
                    card.style.transition = 'all 0.5s';
                    card.style.opacity = '0';
                    setTimeout(() => { card.remove(); }, 500);
                }
                mostrarToast('Dirección eliminada');
            } else {
                alert('Error: ' + data.message);
            }
        });
}

// MARCAR FAVORITO
function confirmarFavoritoAjax(id, etiqueta) {
    if (!confirm(`¿Definir "${etiqueta}" como principal?`)) return;

    const formData = new FormData();
    formData.append('id', id);

    fetch(BASE_URL + 'perfil/hacerPrincipalAjax', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                // Recarga silenciosa
                fetch(window.location.href)
                    .then(r => r.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        document.getElementById('contenedor-direcciones').innerHTML = doc.getElementById('contenedor-direcciones').innerHTML;
                    });
                mostrarToast('Dirección principal actualizada');
            }
        });
}


function verDetallePedido(idPedido, fecha, rango) {
    document.getElementById('detalleIdPedido').innerText = '#' + idPedido;

    // Resetear contenido y mostrar spinner
    const lista = document.getElementById('listaDetallePedido');
    const timeline = document.getElementById('timelineContainer');

    lista.innerHTML = '<tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-cenco-green"></div></td></tr>';
    timeline.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-cenco-indigo"></div></div>';

    // Resetear pestaña activa a "Productos"
    const tabBtn = document.querySelector('#tab-productos');
    const tabIns = new bootstrap.Tab(tabBtn);
    tabIns.show();

    fetch(`${BASE_URL}perfil/obtenerDetallePedido?id=${idPedido}`)
        .then(response => response.json())
        .then(data => {

            // 1. RENDERIZAR PRODUCTOS
            let htmlProd = '';
            let total = 0;
            const productos = data.productos || [];

            if (productos.length === 0) {
                htmlProd = '<tr><td colspan="4" class="text-center py-4">No hay información de productos.</td></tr>';
            } else {
                productos.forEach(item => {
                    const precio = parseInt(item.precio_unitario);
                    const subtotal = precio * item.cantidad;
                    total += subtotal;
                    // Lógica imagen
                    const img = item.imagen ? (item.imagen.startsWith('http') ? item.imagen : `${BASE_URL}img/productos/${item.imagen}`) : `${BASE_URL}img/no-image.png`;

                    htmlProd += `
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center">
                                    <img src="${img}" class="rounded border p-1 me-3" style="width: 50px; height: 50px; object-fit: contain;">
                                    <div>
                                        <div class="fw-bold small text-dark">${item.nombre}</div>
                                        <div class="small text-muted">COD: ${item.cod_producto}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center fw-bold text-secondary">x${item.cantidad}</td>
                            <td class="text-end text-muted small">$${precio.toLocaleString('es-CL')}</td>
                            <td class="text-end pe-4 fw-bold text-cenco-indigo">$${subtotal.toLocaleString('es-CL')}</td>
                        </tr>`;
                });

                // Footer
                htmlProd += `
                    <tr class="bg-light">
                        <td colspan="4" class="py-3 ps-4">
                            <small class="text-muted fw-bold d-block">ESTIMACIÓN DE ENTREGA:</small>
                            <span class="text-success fw-bold"><i class="bi bi-truck me-1"></i> ${fecha || 'Por confirmar'}</span>
                            <span class="text-muted small ms-2">(${rango || ''})</span>
                        </td>
                    </tr>
                    <tr class="bg-white border-top">
                        <td colspan="3" class="text-end fw-black py-3">TOTAL PAGADO:</td>
                        <td class="text-end pe-4 fw-black text-cenco-green fs-5 py-3">$${total.toLocaleString('es-CL')}</td>
                    </tr>
                `;
            }
            lista.innerHTML = htmlProd;

            // 2. RENDERIZAR LÍNEA DE TIEMPO
            let htmlTime = '';
            const historial = data.historial || [];

            if (historial.length === 0) {
                htmlTime = `
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-clock-history fs-1 opacity-25"></i>
                        <p class="mt-3 small">Historial de movimientos no disponible.</p>
                    </div>`;
            } else {
                htmlTime += `<div style="position: absolute; left: 24px; top: 10px; bottom: 10px; width: 2px; background: #e9ecef;"></div>`;

                historial.forEach((h, index) => {
                    const isLatest = index === 0;
                    const iconColor = isLatest ? 'bg-cenco-green text-white shadow-sm' : 'bg-white border text-muted';
                    const textColor = isLatest ? 'text-dark' : 'text-muted';

                    htmlTime += `
                    <div class="d-flex mb-4 position-relative">
                        <div class="${iconColor} rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 48px; height: 48px; z-index: 2; flex-shrink: 0; border: 2px solid #fff;">
                            <i class="bi bi-check-lg fs-5"></i>
                        </div>
                        <div class="ms-3 pt-1">
                            <h6 class="fw-bold mb-0 ${textColor}">${h.nombre_estado}</h6>
                            <small class="text-muted d-block mb-1">
                                <i class="bi bi-calendar3 me-1"></i> ${h.fecha} 
                                <i class="bi bi-clock ms-2 me-1"></i> ${h.hora}
                            </small>
                            ${h.comentario ? `<div class="p-2 bg-light rounded small text-secondary mt-1 border">${h.comentario}</div>` : ''}
                        </div>
                    </div>`;
                });
            }
            timeline.innerHTML = htmlTime;

        })
        .catch(err => {
            console.error(err);
            lista.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-4">Error al cargar información.</td></tr>';
        });
}

// Función para abrir el modal de edición con los datos cargados
function abrirEditarTelefono(id, alias, numero) {
    document.getElementById('edit_tel_id').value = id;
    document.getElementById('edit_tel_alias').value = alias;
    document.getElementById('edit_tel_numero').value = numero;

    new bootstrap.Modal(document.getElementById('modalEditarTelefono')).show();
}

// Función para confirmar eliminación
function confirmarEliminarTelefono(id) {
    Swal.fire({
        title: '¿Eliminar contacto?',
        text: "Este número dejará de aparecer en tu agenda de checkout.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = BASE_URL + 'perfil/eliminarTelefono?id=' + id;
        }
    })
}