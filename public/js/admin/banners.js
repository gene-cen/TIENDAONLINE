// ==========================================
// LÓGICA DE UX: SINCRONIZAR PESTAÑAS Y PANELES
// ==========================================
function sincronizarPestana(valor) {
    let btn = null;
    if(valor == '0') btn = document.getElementById('btn-tab-ambas');
    else if(valor == '29') btn = document.getElementById('btn-tab-calera');
    else if(valor == '10') btn = document.getElementById('btn-tab-villa');
    if(btn) btn.click();
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
        if(document.getElementById('enlace_' + prefix)) document.getElementById('enlace_' + prefix).value = '';
    } else {
        panelB.classList.remove('panel-disabled');
        panelA.classList.add('panel-disabled');
        inputsB.forEach(el => el.disabled = false);
        inputsA.forEach(el => el.disabled = true);
        if(document.getElementById('palabra_clave_' + prefix)) document.getElementById('palabra_clave_' + prefix).value = '';
        if(document.getElementById('ids_' + prefix)) {
            document.getElementById('ids_' + prefix).value = '';
            if(prefix === 'nuevo') productosNuevo = []; else productosEdit = [];
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
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.sortable-tbody').forEach(tbody => {
        new Sortable(tbody, {
            handle: '.handle', 
            animation: 150,
            ghostClass: 'bg-light',
            onEnd: function(evt) {
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

    buscador.addEventListener('input', function() {
        let q = this.value.trim();
        if (q.length < 3) { lista.classList.add('d-none'); return; }
        let excludeStr = arrayLocalObj.get().map(p => p.cod_producto).join(',');

        fetch(window.BASE_URL + 'admin/banners/buscarParaBannerAjax?q=' + encodeURIComponent(q) + '&excluir=' + encodeURIComponent(excludeStr) + '&sucursal=' + getSucursalForSearch(isEdit))
            .then(async res => res.ok ? res.json() : Promise.reject())
            .then(data => {
                lista.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(prod => {
                        let li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action py-2 px-3 cursor-pointer';
                        li.innerHTML = `
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <img src="${prod.imagen.startsWith('http') ? prod.imagen : window.BASE_URL + 'img/productos/' + prod.imagen}" style="width:40px; height:40px; object-fit:contain;" class="me-3 bg-white border rounded p-1">
                                <div class="lh-sm">
                                    <span class="d-block fw-bold text-dark text-capitalize text-md">${prod.nombre.toLowerCase()}</span>
                                    <span class="text-secondary" style="font-size:0.85rem;">Cod: ${prod.cod_producto} | Stock: <span class="fw-bold ${prod.stock_real > 0 ? 'text-cenco-indigo' : 'text-danger'}">${prod.stock_real}</span></span>
                                </div>
                            </div>
                            <i class="bi bi-plus-circle-fill text-success fs-4"></i>
                        </div>`;
                        li.onclick = () => {
                            let curr = arrayLocalObj.get(); curr.push(prod); arrayLocalObj.set(curr);
                            renderTabla(curr, tbodyTablaId, inputHidden);
                            buscador.value = ''; lista.classList.add('d-none'); buscador.focus();
                        };
                        lista.appendChild(li);
                    });
                    lista.classList.remove('d-none');
                } else {
                    lista.innerHTML = '<li class="list-group-item text-secondary text-md py-3">No hay resultados nuevos con stock.</li>';
                    lista.classList.remove('d-none');
                }
            }).catch(() => {
                lista.innerHTML = '<li class="list-group-item text-danger text-md py-3">Error de conexión.</li>';
                lista.classList.remove('d-none');
            });
    });
    document.addEventListener('click', e => { if (!buscador.contains(e.target) && !lista.contains(e.target)) lista.classList.add('d-none'); });
}

function renderTabla(arrayLocal, tbodyId, inputHidden) {
    const tbody = document.getElementById(tbodyId);
    tbody.innerHTML = '';
    if (arrayLocal.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-4 text-md">No hay productos seleccionados.</td></tr>';
        inputHidden.value = '';
        return;
    }
    let codigos = [];
    arrayLocal.forEach(prod => {
        codigos.push(prod.cod_producto);
        tbody.innerHTML += `
            <tr class="bg-white">
                <td class="ps-3 py-2"><img src="${prod.imagen.startsWith('http') ? prod.imagen : window.BASE_URL + 'img/productos/' + prod.imagen}" style="width:40px; height:40px; object-fit:contain;" class="border rounded bg-white p-1"></td>
                <td class="fw-bold text-secondary text-md align-middle">${prod.cod_producto}</td>
                <td class="text-truncate text-capitalize text-md align-middle fw-bold text-dark" style="max-width: 250px;" title="${prod.nombre}">${prod.nombre.toLowerCase()}</td>
                <td class="fw-bold text-cenco-indigo text-md align-middle">${prod.stock_real}</td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-light text-danger border p-2" title="Quitar" onclick="quitarProducto('${prod.cod_producto}', '${tbodyId}', '${inputHidden.id}')"><i class="bi bi-trash-fill fs-5"></i></button>
                </td>
            </tr>
        `;
    });
    inputHidden.value = codigos.join(',');
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

document.addEventListener("DOMContentLoaded", function() {
    setupBuscadorAvanzado('buscadorNuevo', 'listaNuevo', 'idsNuevo', 'tablaSeleccionadosNuevo', { get: () => productosNuevo, set: (v) => productosNuevo = v }, false);
    setupBuscadorAvanzado('buscadorEdit', 'listaEdit', 'edit_ids', 'tablaSeleccionadosEdit', { get: () => productosEdit, set: (v) => productosEdit = v }, true);
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

        if(enlace.includes('categoria=')) {
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

    if(!id) { Swal.fire('Error', 'No hay banner seleccionado.', 'error'); return; }

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