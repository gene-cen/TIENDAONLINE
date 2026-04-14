// =========================================================
// VARIABLES GLOBALES
// =========================================================
let productosNuevo = [];
let productosEdit = [];
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. ALERTAS Y MENSAJES DE ÉXITO
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.get('msg') === 'creado' || urlParams.get('msg') === 'actualizado') {
        const successModalEl = document.getElementById('successModal');
        if (successModalEl) {
            const msgEl = document.getElementById('successMessage');
            if (msgEl) msgEl.innerText = urlParams.get('msg') === 'creado' ? '¡Banner publicado con éxito!' : '¡Banner actualizado con éxito!';
            new bootstrap.Modal(successModalEl).show();
        } else {
            alert('¡Acción realizada con éxito!');
        }
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // 2. BUSCADOR DE PRODUCTOS CON VISUALIZACIÓN DE STOCK TIPO CATÁLOGO
    const buscadorNuevo = document.getElementById('buscadorNuevo');
    const listaNuevo = document.getElementById('listaNuevo');
    
    if (buscadorNuevo) {
        buscadorNuevo.addEventListener('input', function() {
            let q = this.value;
            let sucursal = document.getElementById('select_sucursal_nuevo').value;
            
            if (q.length >= 2) {
                // 🔥 TRUCO ANTI-CACHÉ: Agregamos Date.now() al final para forzar datos en tiempo real
                fetch(`${BASE_URL}admin/banners/buscarParaBannerAjax?q=${q}&sucursal=${sucursal}&_t=${Date.now()}`)
                .then(res => res.json())
                .then(data => {
                    listaNuevo.innerHTML = '';
                    listaNuevo.classList.remove('d-none');
                    
                    if(data.length === 0) {
                        listaNuevo.innerHTML = '<li class="list-group-item text-muted text-center py-3">No se encontraron productos.</li>';
                        return;
                    }

                    data.forEach(prod => {
                        // 🛡️ Seguridad extra: Si stock viene 'undefined', lo forzamos a 0
                        let stockReal = prod.stock !== undefined ? parseInt(prod.stock) : 0;
                        
                        // 🔥 LÓGICA DE BADGES DE STOCK IGUAL AL CATÁLOGO
                        let stockBadge = '';
                        let btnAdd = '';

                        if (stockReal > 10) {
                            stockBadge = `<span class="badge bg-success bg-opacity-10 text-success border border-success px-2 rounded-pill">${stockReal} un.</span>`;
                        } else if (stockReal > 0) {
                            stockBadge = `<span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-2 rounded-pill">${stockReal} un.</span>`;
                        } else {
                            stockBadge = `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 rounded-pill">Agotado</span>`;
                        }

                        // Si no hay stock, el botón se deshabilita
                        if (stockReal > 0) {
                            btnAdd = `<button type="button" class="btn btn-success btn-sm rounded-circle shadow-sm transition-hover" 
                                        onclick="agregarProductoNuevo('${prod.id}', '${prod.cod_producto}', '${prod.nombre.replace(/'/g, "\\'")}', '${prod.imagen}', ${stockReal})">
                                        <i class="bi bi-plus-lg"></i>
                                      </button>`;
                        } else {
                            btnAdd = `<button type="button" class="btn btn-secondary btn-sm rounded-circle shadow-sm" disabled title="Sin stock para añadir">
                                        <i class="bi bi-dash-lg"></i>
                                      </button>`;
                        }

                        listaNuevo.innerHTML += `
                            <li class="list-group-item d-flex justify-content-between align-items-center list-group-item-action">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="${prod.imagen}" style="width:45px; height:45px; object-fit:contain;" class="rounded border shadow-sm bg-white">
                                    <div class="lh-sm">
                                        <div class="fw-bold text-dark" style="font-size:0.9rem;">${prod.nombre}</div>
                                        <div class="small text-muted mt-1">Cod: ${prod.cod_producto} <span class="mx-1">|</span> Stock: ${stockBadge}</div>
                                    </div>
                                </div>
                                ${btnAdd}
                            </li>
                        `;
                    });
                }).catch(err => {
                    console.error("Error en la búsqueda:", err);
                    listaNuevo.innerHTML = '<li class="list-group-item text-danger small">Error de conexión con el servidor.</li>';
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
// 3. AGREGAR A LA LISTA Y RENDERIZAR TABLA
// 3. AGREGAR A LA LISTA Y RENDERIZAR TABLA (VERSIÓN PRO)
function agregarProductoNuevo(id, cod, nombre, imagen, stock) {
    // 🛡️ Seguridad: Si los datos básicos vienen nulos o vacíos, cancelamos
    if (!id || !cod) return;

    // 🕵️ Verificamos si el producto ya está en la lista para no duplicarlo
    const existe = productosNuevo.some(p => p.cod_producto === cod);

    if (!existe) {
        // 🔥 CAMBIO CLAVE: Seteamos 'stock_real' para que renderTabla lo reconozca
        productosNuevo.push({ 
            id: id, 
            cod_producto: cod, 
            nombre: nombre, 
            imagen: imagen, 
            stock_real: stock // Antes era solo 'stock'
        });

        // Renderizamos usando la función robusta que creamos
        renderTabla(productosNuevo, 'tablaSeleccionadosNuevo', document.getElementById('idsNuevo'));
    }

    // --- Limpieza de Interfaz ---
    const inputBuscador = document.getElementById('buscadorNuevo');
    const sugerencias = document.getElementById('listaNuevo');

    if (inputBuscador) inputBuscador.value = '';
    if (sugerencias) sugerencias.classList.add('d-none');
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
        
        // Píldoras de stock también en la tabla
        let stockBadge = '';
        if (prod.stock > 10) stockBadge = `<span class="badge bg-success bg-opacity-10 text-success border border-success px-2 rounded-pill">${prod.stock} un.</span>`;
        else if (prod.stock > 0) stockBadge = `<span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-2 rounded-pill">${prod.stock} un.</span>`;
        else stockBadge = `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 rounded-pill">Agotado</span>`;

        tbody.innerHTML += `
            <tr class="align-middle bg-white">
                <td class="ps-3 py-2">
                    <img src="${prod.imagen}" alt="img" style="width: 45px; height: 45px; object-fit: contain;" class="rounded border shadow-sm bg-white">
                </td>
                <td class="text-muted fw-bold small">${prod.cod_producto}</td>
                <td class="fw-bold text-dark" style="font-size: 0.95rem;">${prod.nombre}</td>
                <td class="text-center">${stockBadge}</td>
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
}

// 4. CAMBIO DE MODALIDAD
function setModo(prefijo, modo) {
    const panelA = document.getElementById('panelA_' + prefijo);
    const panelB = document.getElementById('panelB_' + prefijo);
    const radiosB = document.getElementsByName('tipo_b_' + prefijo);
    const selectCat = document.getElementById('select_cat_' + prefijo);
    const selectMarca = document.getElementById('select_marca_' + prefijo);

    if (modo === 'A') {
        panelA.classList.remove('panel-disabled');
        panelB.classList.add('panel-disabled');
        radiosB.forEach(r => r.disabled = true);
        if(selectCat) selectCat.disabled = true;
        if(selectMarca) selectMarca.disabled = true;
    } else {
        panelA.classList.add('panel-disabled');
        panelB.classList.remove('panel-disabled');
        radiosB.forEach(r => r.disabled = false);
        if(selectCat && document.getElementById('btn_cat_'+prefijo).checked) selectCat.disabled = false;
        if(selectMarca && document.getElementById('btn_marca_'+prefijo).checked) selectMarca.disabled = false;
    }
}