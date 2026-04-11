// =========================================================
// VARIABLES GLOBALES (Para almacenar los productos seleccionados)
// =========================================================
let productosNuevo = [];
let productosEdit = [];

document.addEventListener('DOMContentLoaded', function() {
    
    // =========================================================
    // 1. ALERTAS Y MENSAJES DE ÉXITO (URL PARSER)
    // =========================================================
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.get('msg') === 'creado' || urlParams.get('msg') === 'actualizado') {
        const successModalEl = document.getElementById('successModal');
        
        if (successModalEl) {
            const msgEl = document.getElementById('successMessage');
            if (msgEl) msgEl.innerText = urlParams.get('msg') === 'creado' ? '¡Banner publicado con éxito!' : '¡Banner actualizado con éxito!';
            
            const successModal = new bootstrap.Modal(successModalEl);
            successModal.show();
        } else {
            alert('¡Acción realizada con éxito!');
        }

        // Limpiamos la URL para no repetir la alerta al actualizar la página
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // =========================================================
    // 2. BUSCADOR DE PRODUCTOS (El que llama a buscarParaBannerAjax)
    // =========================================================
    const buscadorNuevo = document.getElementById('buscadorNuevo');
    const listaNuevo = document.getElementById('listaNuevo');
    
    if (buscadorNuevo) {
        buscadorNuevo.addEventListener('input', function() {
            let q = this.value;
            let sucursal = document.getElementById('select_sucursal_nuevo').value;
            
            if (q.length >= 2) {
                // Buscamos los datos en el servidor
                fetch(`${BASE_URL}admin/banners/buscarParaBannerAjax?q=${q}&sucursal=${sucursal}`)
                .then(res => res.json())
                .then(data => {
                    listaNuevo.innerHTML = '';
                    listaNuevo.classList.remove('d-none');
                    
                    if(data.length === 0) {
                        listaNuevo.innerHTML = '<li class="list-group-item text-muted text-center py-3">No se encontraron productos.</li>';
                        return;
                    }

                    // Armamos la lista de resultados
                    data.forEach(prod => {
                        // Aseguramos de enviar prod.stock en el onclick
                        listaNuevo.innerHTML += `
                            <li class="list-group-item d-flex justify-content-between align-items-center list-group-item-action">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="${prod.imagen}" style="width:40px; height:40px; object-fit:cover;" class="rounded border">
                                    <div class="lh-sm">
                                        <div class="fw-bold text-dark" style="font-size:0.9rem;">${prod.nombre}</div>
                                        <div class="small text-muted">Cod: ${prod.cod_producto} | Stock: <span class="${prod.stock > 0 ? 'text-success' : 'text-danger'} fw-bold">${prod.stock}</span></div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-success btn-sm rounded-circle shadow-sm" 
                                    onclick="agregarProductoNuevo('${prod.id}', '${prod.cod_producto}', '${prod.nombre.replace(/'/g, "\\'")}', '${prod.imagen}', ${prod.stock})">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </li>
                        `;
                    });
                }).catch(err => {
                    listaNuevo.innerHTML = '<li class="list-group-item text-danger small">Error de conexión.</li>';
                });
            } else {
                listaNuevo.classList.add('d-none');
            }
        });

        // Ocultar la lista al hacer clic afuera
        document.addEventListener('click', function(e) {
            if (!buscadorNuevo.contains(e.target) && !listaNuevo.contains(e.target)) {
                listaNuevo.classList.add('d-none');
            }
        });
    }
});

// =========================================================
// 3. AGREGAR A LA LISTA Y RENDERIZAR TABLA (Sin "undefined")
// =========================================================

// La función DEBE recibir la variable 'stock' al final
function agregarProductoNuevo(id, cod, nombre, imagen, stock) {
    // Evitamos duplicados en la tabla
    if (!productosNuevo.some(p => p.cod_producto === cod)) {
        productosNuevo.push({
            id: id,
            cod_producto: cod,
            nombre: nombre,
            imagen: imagen,
            stock: stock // <--- GUARDAMOS EL STOCK AQUÍ
        });
        
        // Redibujamos la tabla
        renderTabla(productosNuevo, 'tablaSeleccionadosNuevo', document.getElementById('idsNuevo'));
    }
    
    // Limpiamos el buscador después de agregar
    document.getElementById('buscadorNuevo').value = '';
    document.getElementById('listaNuevo').classList.add('d-none');
}

function renderTabla(lista, tbodyId, inputHidden) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;

    if (lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-4">No hay productos seleccionados.</td></tr>';
        inputHidden.value = '';
        return;
    }

    tbody.innerHTML = '';
    let ids = [];

    lista.forEach((prod, index) => {
        ids.push(prod.cod_producto);
        
        // Color según stock
        const colorStock = prod.stock > 0 ? 'text-success' : 'text-danger';

        tbody.innerHTML += `
            <tr class="align-middle bg-white">
                <td class="ps-3 py-2">
                    <img src="${prod.imagen}" alt="img" style="width: 45px; height: 45px; object-fit: cover;" class="rounded border shadow-sm">
                </td>
                <td class="text-muted fw-bold small">${prod.cod_producto}</td>
                <td class="fw-bold text-dark" style="font-size: 0.95rem;">${prod.nombre}</td>
                <td class="fw-black ${colorStock} fs-6">${prod.stock}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-light border text-danger shadow-sm transition-hover" onclick="quitarProducto('${tbodyId}', ${index})">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    inputHidden.value = ids.join(',');
}

function quitarProducto(tbodyId, index) {
    if (tbodyId === 'tablaSeleccionadosNuevo') {
        productosNuevo.splice(index, 1);
        renderTabla(productosNuevo, 'tablaSeleccionadosNuevo', document.getElementById('idsNuevo'));
    }
    // Si tienes lógica para editar, la agregas aquí (productosEdit)
}

// =========================================================
// 4. CAMBIO DE MODALIDAD (A: Vitrina vs B: Enlace Rápido)
// =========================================================
function setModo(prefijo, modo) {
    const panelA = document.getElementById('panelA_' + prefijo);
    const panelB = document.getElementById('panelB_' + prefijo);
    
    // Si tienes inputs/botones de opción B, los habilitas o deshabilitas
    const radiosB = document.getElementsByName('tipo_b_' + prefijo);
    const selectCat = document.getElementById('select_cat_' + prefijo);
    const selectMarca = document.getElementById('select_marca_' + prefijo);

    if (modo === 'A') {
        panelA.classList.remove('panel-disabled');
        panelB.classList.add('panel-disabled');
        // Deshabilitar los campos de la opción B
        radiosB.forEach(r => r.disabled = true);
        if(selectCat) selectCat.disabled = true;
        if(selectMarca) selectMarca.disabled = true;
    } else {
        panelA.classList.add('panel-disabled');
        panelB.classList.remove('panel-disabled');
        // Habilitar los campos de la opción B
        radiosB.forEach(r => r.disabled = false);
        if(selectCat && document.getElementById('btn_cat_'+prefijo).checked) selectCat.disabled = false;
        if(selectMarca && document.getElementById('btn_marca_'+prefijo).checked) selectMarca.disabled = false;
    }
}