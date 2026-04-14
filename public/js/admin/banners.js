// ==========================================
// LÓGICA DE UX: SINCRONIZAR PESTAÑAS Y PANELES
// ==========================================
function sincronizarPestana(valor) {
    let btn = null;
    if (valor == '0') btn = document.getElementById('btn-tab-ambas');
    else if (valor == '29') btn = document.getElementById('btn-tab-calera');
    else if (valor == '10') btn = document.getElementById('btn-tab-villa');
    if (btn) btn.click();
}

function setModo(prefix, modo) {
    const panelA = document.getElementById('panelA_' + prefix);
    const panelB = document.getElementById('panelB_' + prefix);
    const inputsA = panelA.querySelectorAll('input, select, button');
    const inputsB = panelB.querySelectorAll('input, select, button');

    if (modo === 'A') {
        panelA.classList.remove('panel-disabled');
        panelB.classList.add('panel-disabled');
        inputsA.forEach(el => el.disabled = false);
        inputsB.forEach(el => el.disabled = true);
        if (document.getElementById('enlace_' + prefix)) document.getElementById('enlace_' + prefix).value = '';
    } else {
        panelB.classList.remove('panel-disabled');
        panelA.classList.add('panel-disabled');
        inputsB.forEach(el => el.disabled = false);
        inputsA.forEach(el => el.disabled = true);
        if (document.getElementById('palabra_clave_' + prefix)) document.getElementById('palabra_clave_' + prefix).value = '';
        if (document.getElementById('ids_' + prefix)) {
            document.getElementById('ids_' + prefix).value = '';
            if (prefix === 'nuevo') productosNuevo = []; else productosEdit = [];
            renderTabla([], prefix === 'nuevo' ? 'tablaSeleccionadosNuevo' : 'tablaSeleccionadosEdit', document.getElementById('ids_' + prefix));
        }
        let catChecked = document.getElementById('btn_cat_' + prefix).checked;
        setTipoB(prefix, catChecked ? 'categoria' : 'marca');
    }
}

function setTipoB(prefix, tipo) {
    const selCat = document.getElementById('select_cat_' + prefix);
    const selMarca = document.getElementById('select_marca_' + prefix);
    const inpEnlace = document.getElementById('enlace_' + prefix);

    if (tipo === 'categoria') {
        selCat.classList.remove('d-none');
        selMarca.classList.add('d-none');
        inpEnlace.value = selCat.value;
    } else {
        selMarca.classList.remove('d-none');
        selCat.classList.add('d-none');
        inpEnlace.value = selMarca.value;
    }
}

// ==========================================
// INICIALIZAR DRAG AND DROP
// ==========================================
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.sortable-tbody').forEach(tbody => {
        new Sortable(tbody, {
            handle: '.handle',
            animation: 150,
            ghostClass: 'bg-light',
            onEnd: function (evt) {
                let ordenes = [];
                let rows = evt.to.querySelectorAll('tr');
                rows.forEach((row, index) => {
                    let nuevoOrden = index + 1;
                    ordenes.push({ id: row.getAttribute('data-id'), orden: nuevoOrden });
                    row.querySelector('.orden-badge').innerText = nuevoOrden;
                });
                fetch(window.BASE_URL + 'admin/banners/reordenarAjax', {
                    method: 'POST',
                    body: JSON.stringify({ tabla: tbody.getAttribute('data-tabla'), ordenes: ordenes }),
                    headers: { 'Content-Type': 'application/json' }
                });
            }
        });
    });
});

// ==========================================
// LÓGICA DEL BUSCADOR AVANZADO
// ==========================================
let productosNuevo = [];
let productosEdit = [];

function getSucursalForSearch(isEdit) {
    const sel = document.getElementById(isEdit ? 'edit_sucursal' : 'select_sucursal_nuevo');
    return sel ? sel.value : window.MI_SUCURSAL;
}
function setupBuscadorAvanzado(inputBuscadorId, listaResultadosId, inputIdsOcultoId, tbodyTablaId, arrayLocalObj, isEdit) {
    const buscador = document.getElementById(inputBuscadorId);
    if (!buscador) return;

    const lista = document.getElementById(listaResultadosId);
    const inputHidden = document.getElementById(inputIdsOcultoId);

    buscador.addEventListener('input', function () {
        let q = this.value.trim();

        if (q.length < 3) {
            lista.classList.add('d-none');
            lista.innerHTML = '';
            return;
        }

        // Obtenemos los códigos ya seleccionados para que el servidor los excluya (si tu backend lo soporta)
        let excludeStr = arrayLocalObj.get().filter(p => p !== null).map(p => p.cod_producto).join(',');

        fetch(window.BASE_URL + 'admin/banners/buscarParaBannerAjax?q=' + encodeURIComponent(q) + '&excluir=' + encodeURIComponent(excludeStr) + '&sucursal=' + getSucursalForSearch(isEdit))
            .then(res => res.ok ? res.json() : Promise.reject())
            .then(data => {
                lista.innerHTML = '';

                if (data && data.length > 0) {
                    data.forEach(prod => {
                        // 🛡️ ESCUDO: Si el producto es nulo o inválido, lo saltamos
                        if (!prod || !prod.cod_producto) return;

                        let li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action py-3 px-4 cursor-pointer border-0 border-bottom';

                        // Lógica de imagen inteligente
                        const urlImg = (prod.imagen && prod.imagen.startsWith('http'))
                            ? prod.imagen
                            : window.BASE_URL + 'img/productos/' + (prod.imagen || 'no-image.png');

                        li.innerHTML = `
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center overflow-hidden">
                                    <img src="${urlImg}" style="width:45px; height:45px; object-fit:contain;" class="me-3 bg-white border rounded p-1 shadow-sm">
                                    <div class="lh-sm text-truncate">
                                        <span class="d-block fw-bold text-dark text-capitalize text-md mb-1">${prod.nombre.toLowerCase()}</span>
                                        <span class="text-muted small fw-bold">
                                            Cod: ${prod.cod_producto} | 
                                            Stock: <span class="${prod.stock_real > 0 ? 'text-cenco-green' : 'text-danger'}">${prod.stock_real ?? 0}</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <i class="bi bi-plus-circle-fill text-success fs-4 hover-scale"></i>
                                </div>
                            </div>`;

                        li.onclick = () => {
                            // 🛡️ Solo procedemos si 'prod' es un objeto válido y tiene código
                            if (prod && typeof prod === 'object' && prod.cod_producto) {

                                let curr = arrayLocalObj.get();

                                // Limpiamos el array actual de nulos antes de comparar
                                curr = curr.filter(p => p !== null);

                                // Evitamos duplicados
                                if (!curr.some(p => p.cod_producto === prod.cod_producto)) {
                                    curr.push(prod);
                                    arrayLocalObj.set(curr);
                                    renderTabla(curr, tbodyTablaId, inputHidden);
                                }

                                buscador.value = '';
                                lista.classList.add('d-none');
                                buscador.focus();

                            } else {
                                console.error("Se intentó añadir un producto que llegó como NULL desde el buscador.");
                            }
                        };
                        lista.appendChild(li);
                    });
                    lista.classList.remove('d-none');
                } else {
                    lista.innerHTML = '<li class="list-group-item text-secondary py-4 text-center small"><i class="bi bi-info-circle me-2"></i>No se hallaron productos con stock.</li>';
                    lista.classList.remove('d-none');
                }
            })
            .catch(err => {
                console.error("Error búsqueda avanzada:", err);
                lista.innerHTML = '<li class="list-group-item text-danger py-3 text-center small">Error de conexión al servidor.</li>';
                lista.classList.remove('d-none');
            });
    });

    // Cerrar lista al hacer clic afuera
    document.addEventListener('click', e => {
        if (!buscador.contains(e.target) && !lista.contains(e.target)) {
            lista.classList.add('d-none');
        }
    });
} function renderTabla(arrayLocal, tbodyId, inputHidden) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;

    // 🔥 FIX ATÓMICO: Si arrayLocal no es un array o viene corrupto, lo reseteamos.
    if (!Array.isArray(arrayLocal)) {
        console.error("renderTabla: arrayLocal no es un array válido");
        return;
    }

    // Limpiamos CUALQUIER rastro de null, undefined o valores que no sean objetos
    const listaLimpia = arrayLocal.filter(p => p !== null && typeof p === 'object' && (p.id || p.cod_producto));

    tbody.innerHTML = '';

    if (listaLimpia.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted small">No hay productos seleccionados.</td></tr>';
        if (inputHidden) inputHidden.value = '';
        return;
    }

    let codigos = [];

    // Ahora iteramos sobre la lista que GARANTIZAMOS que está limpia
    listaLimpia.forEach((prod) => {
        // Doble escudo: Si por un milagro llega algo null aquí, el 'continue' de JS (en forEach es return) lo ignora
        if (!prod) return; 

        codigos.push(prod.cod_producto);

        const urlImg = (prod.imagen && prod.imagen.startsWith('http')) 
            ? prod.imagen 
            : window.BASE_URL + 'img/productos/' + (prod.imagen || 'no-image.png');

        // 🔥 LÍNEA 229 (Blindada): Usamos opcional chaining (?.) para que si algo falta, no explote
        tbody.innerHTML += `
            <tr class="bg-white align-middle shadow-sm">
                <td class="ps-3"><img src="${urlImg}" style="width:40px; height:45px; object-fit:contain;" class="border rounded bg-light p-1"></td>
                <td class="fw-bold text-secondary small">${prod.cod_producto || 'N/A'}</td>
                <td class="text-dark fw-semibold text-capitalize">${(prod.nombre || 'Sin nombre').toLowerCase()}</td>
                <td class="text-center fw-bold text-cenco-indigo">${prod.stock_real ?? 0}</td>
                <td class="text-end pe-3">
                    <button type="button" class="btn btn-sm btn-light border text-danger" 
                        onclick="quitarProducto('${prod.cod_producto}', '${tbodyId}', '${inputHidden.id}')">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    if (inputHidden) inputHidden.value = codigos.join(',');
}

function quitarProducto(cod_producto, tbodyId, inputHiddenId) {
    if (tbodyId === 'tablaSeleccionadosNuevo') {
        productosNuevo = productosNuevo.filter(p => String(p.cod_producto) !== String(cod_producto));
        renderTabla(productosNuevo, tbodyId, document.getElementById(inputHiddenId));
    } else {
        productosEdit = productosEdit.filter(p => String(p.cod_producto) !== String(cod_producto));
        renderTabla(productosEdit, tbodyId, document.getElementById(inputHiddenId));
    }
}

document.addEventListener("DOMContentLoaded", function () {
    // 🔥 CAMBIO: 'idsNuevo' -> 'ids_nuevo'  y  'edit_ids' -> 'ids_edit'
    setupBuscadorAvanzado('buscadorNuevo', 'listaNuevo', 'ids_nuevo', 'tablaSeleccionadosNuevo', { get: () => productosNuevo, set: (v) => productosNuevo = v }, false);
    setupBuscadorAvanzado('buscadorEdit', 'listaEdit', 'ids_edit', 'tablaSeleccionadosEdit', { get: () => productosEdit, set: (v) => productosEdit = v }, true);
});

// ==========================================
// MODAL EDITAR Y FUNCIONES AJAX
// ==========================================
function formatoFechaLocal(fechaSql) {
    if (!fechaSql) return '';
    return fechaSql.replace(' ', 'T').slice(0, 16);
}

function abrirModalEditar(id, titulo, tipo, enlace, palabra_clave, productos_ids, sucursal_id, fecha_inicio, fecha_fin) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_titulo').value = titulo;
    document.getElementById('edit_tipo').value = tipo;
    if (document.getElementById('edit_sucursal')) document.getElementById('edit_sucursal').value = sucursal_id;

    document.getElementById('edit_fecha_inicio').value = formatoFechaLocal(fecha_inicio);
    document.getElementById('edit_fecha_fin').value = formatoFechaLocal(fecha_fin);

    if (palabra_clave && palabra_clave.trim() !== '') {
        document.getElementById('modoA_edit').checked = true;
        setModo('edit', 'A');
        document.getElementById('palabra_clave_edit').value = palabra_clave;
        document.getElementById('ids_edit').value = productos_ids;
    } else {
        document.getElementById('modoB_edit').checked = true;
        setModo('edit', 'B');
        document.getElementById('enlace_edit').value = enlace;

        if (enlace.includes('categoria=')) {
            document.getElementById('btn_cat_edit').checked = true; setTipoB('edit', 'categoria'); document.getElementById('select_cat_edit').value = enlace;
        } else if (enlace.includes('marca=')) {
            document.getElementById('btn_marca_edit').checked = true; setTipoB('edit', 'marca'); document.getElementById('select_marca_edit').value = enlace;
        } else {
            document.getElementById('btn_cat_edit').checked = true; setTipoB('edit', 'categoria'); document.getElementById('select_cat_edit').value = '';
        }
    }

    productosEdit = [];
    const tbodyEdit = document.getElementById('tablaSeleccionadosEdit');

    if (productos_ids && productos_ids.trim() !== '') {
        tbodyEdit.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-4 text-md"><div class="spinner-border spinner-border-sm text-cenco-green me-2"></div> Cargando...</td></tr>';
        fetch(window.BASE_URL + 'admin/banners/cargarProductosPorCodigosAjax?codigos=' + encodeURIComponent(productos_ids) + '&sucursal=' + sucursal_id)
            .then(res => res.json())
            .then(data => { productosEdit = data; renderTabla(productosEdit, 'tablaSeleccionadosEdit', document.getElementById('ids_edit')); });
    } else {
        renderTabla(productosEdit, 'tablaSeleccionadosEdit', document.getElementById('ids_edit'));
    }

    new bootstrap.Modal(document.getElementById('editarBannerModal')).show();
}

function guardarFechasAjax(btn) {
    let id = document.getElementById('edit_id').value;
    let tipo = document.getElementById('edit_tipo').value;
    let inicio = document.getElementById('edit_fecha_inicio').value;
    let fin = document.getElementById('edit_fecha_fin').value;

    if (!id) { Swal.fire('Error', 'No hay banner seleccionado.', 'error'); return; }

    let originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
    btn.disabled = true;

    fetch(window.BASE_URL + 'admin/banners/actualizarFechasAjax', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, tipo: tipo, inicio: inicio, fin: fin })
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Fechas Guardadas', text: 'Programación actualizada.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            } else {
                Swal.fire('Error', 'No se pudo guardar', 'error');
            }
        })
        .catch(err => { Swal.fire('Error', 'Problema de red.', 'error'); })
        .finally(() => { btn.innerHTML = originalHtml; btn.disabled = false; });
}

function toggleBanner(id, tipo, btn) {
    btn.disabled = true; const icon = btn.querySelector('i'); const originalClass = icon.className;
    icon.className = 'spinner-border spinner-border-sm text-secondary';

    fetch(window.BASE_URL + 'admin/banners/toggleAjax', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id, tipo: tipo })
    }).then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const tdEstado = btn.closest('tr').cells[4];
                if (data.nuevo_estado == 1) {
                    icon.className = 'fs-5 bi bi-eye-fill text-success';
                    tdEstado.innerHTML = '<span class="badge rounded-pill fs-6 px-3 py-2 bg-success">Activo</span>';
                } else {
                    icon.className = 'fs-5 bi bi-eye-slash-fill text-secondary';
                    tdEstado.innerHTML = '<span class="badge rounded-pill fs-6 px-3 py-2 bg-secondary">Inactivo</span>';
                }
            } else alert('Error al cambiar el estado');
        }).finally(() => btn.disabled = false);
}

function eliminarBannerAjax(id, tipo, btn) {
    Swal.fire({
        title: '¿Estás seguro?', text: "El banner se eliminará permanentemente.", icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#E53935', cancelButtonColor: '#6c757d', confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; btn.disabled = true;

            fetch(window.BASE_URL + 'admin/banners/borrarAjax', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id, tipo: tipo })
            }).then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const fila = btn.closest('tr');
                        fila.style.transition = "all 0.4s ease"; fila.style.opacity = "0"; fila.style.transform = "translateX(30px)";
                        setTimeout(() => fila.remove(), 400);
                        Swal.fire({ icon: 'success', title: 'Eliminado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                    } else { Swal.fire('Error', 'No se pudo eliminar', 'error'); btn.innerHTML = originalHtml; btn.disabled = false; }
                }).catch(err => { Swal.fire('Error', 'Problema de conexión.', 'error'); btn.innerHTML = originalHtml; btn.disabled = false; });
        }
    });
}