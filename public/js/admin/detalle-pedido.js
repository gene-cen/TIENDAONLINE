/**
 * Motor de Gestión de Pedidos - Cencocal
 * Maneja Estados, Webpay, Anulaciones y Edición Avanzada
 */

document.addEventListener("DOMContentLoaded", function () {

    // 🔥 FIX CRÍTICO: Este bloque permite escribir en SweetAlert cuando el modal de edición está abierto
    document.addEventListener('focusin', (e) => {
        if (e.target.closest('.swal2-container')) {
            e.stopImmediatePropagation();
        }
    });
    const urlParams = new URLSearchParams(window.location.search);

    // 1. Notificaciones de Éxito/Error (Alertas Post-Carga)
    const msgs = {
        'estado_actualizado': { icon: 'success', title: '¡Éxito!', text: 'El estado se actualizó correctamente.' },
        'captura_exitosa': { icon: 'success', title: '¡Pago Capturado!', text: 'El cobro se procesó en Transbank.' },
        'error_captura': { icon: 'error', title: 'Error de Webpay', text: 'No se pudo capturar el pago.' }
    };

    const msgKey = urlParams.get('msg');
    if (msgs[msgKey]) {
        Swal.fire({
            ...msgs[msgKey],
            confirmButtonColor: '#2A1B5E',
            customClass: { popup: 'rounded-4 shadow-lg' }
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    }

    // 2. Listener para Spinner al cambiar estado
    const formEstado = document.getElementById('formCambiarEstado');
    if (formEstado) {
        formEstado.addEventListener('submit', () => {
            Swal.fire({
                title: 'Actualizando...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
        });
    }

    // 3. Inicializar buscador de productos en el modal
    initBuscadorProductos();
});


/**
 * Cambia la cantidad de un producto dentro del modal de edición
 * @param {number} index - Posición en el arreglo carritoEditado
 * @param {number} delta - Cambio (+1 o -1)
 */
function cambiarCantEdicion(index, delta) {
    let item = carritoEditado[index];
    let nuevaCant = parseInt(item.cantidad) + delta;

    // Evitamos que la cantidad sea menor a 1
    if (nuevaCant < 1) {
        eliminarItemEdicion(index);
        return;
    }

    // Actualizamos el objeto en memoria
    carritoEditado[index].cantidad = nuevaCant;

    // Refrescamos la tabla visualmente para que se vean los nuevos subtotales
    renderizarTablaEdicion();
}

/**
 * Marca un producto como eliminado en el modal de edición
 * @param {number} index - Posición en el arreglo
 */
function eliminarItemEdicion(index) {
    // Usamos SweetAlert para confirmar antes de quitar
    Swal.fire({
        title: '¿Quitar producto?',
        text: "Se eliminará de esta edición del pedido.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, quitar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Marcamos como eliminado (el renderizador lo ignorará)
            carritoEditado[index].es_eliminado = true;
            renderizarTablaEdicion();
        }
    });
}

function agregarProductoReemplazo(producto) {
    // Verificamos si ya existe en el carrito editado
    let existente = carritoEditado.find(p => p.cod_producto === producto.cod_producto);

    if (existente) {
        existente.cantidad++;
        existente.es_eliminado = false; // Por si lo había borrado antes
    } else {
        // Agregamos el nuevo objeto con el formato que espera tu tabla
        carritoEditado.push({
            producto_id: producto.id,
            cod_producto: producto.cod_producto,
            nombre_producto: producto.nombre,
            imagen: producto.imagen,
            precio_bruto: parseInt(producto.precio),
            cantidad: 1,
            es_eliminado: false
        });
    }

    // CERRAMOS EL BUSCADOR Y REFRESCAMOS
    document.getElementById('resultadosBusquedaReemplazo').classList.add('d-none');
    document.getElementById('inputBuscarProdReemplazo').value = '';

    renderizarTablaEdicion(); // <--- FUNDAMENTAL
}
/**
 * Lógica de Captura Webpay
 */
function confirmarCapturaWebpay(form, maxMonto) {
    let montoIngresado = parseFloat(form.monto_final.value);
    if (montoIngresado > maxMonto) {
        Swal.fire('Monto Inválido', 'No puedes cobrar más del autorizado.', 'error');
        return;
    }

    Swal.fire({
        title: '¿Confirmar cobro?',
        text: `Se cargará $${montoIngresado.toLocaleString('es-CL')} definitivamente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, procesar cobro',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Conectando con Webpay...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            form.submit();
        }
    });
}

/**
 * Lógica de Anulación y Reembolso
 */
function confirmarAnulacionReembolso() {
    Swal.fire({
        title: '¿Anular y Reembolsar?',
        html: `<p class="text-danger small">Esta acción devolverá el dinero al cliente y restaurará el stock.</p>
               <input type="text" id="swal-motivo-anula" class="form-control" placeholder="Motivo de la anulación">`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: 'Confirmar Reembolso',
        confirmButtonColor: '#d33',
        preConfirm: () => {
            const motivo = document.getElementById('swal-motivo-anula').value.trim();
            if (!motivo) Swal.showValidationMessage('El motivo es obligatorio');
            return motivo;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Procesando Reembolso...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            fetch(`${BASE_URL}admin/pedido/anular_reembolsar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pedido_id: PEDIDO_ID, motivo: result.value })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status) {
                        Swal.fire('¡Anulado!', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
        }
    });
}


let edicionConfirmada = false; // <-- NUEVA VARIABLE: NUESTRO ESCUDO ANTI-BUCLES

document.getElementById('modalEditarPedido')?.addEventListener('show.bs.modal', function (event) {
    if (edicionConfirmada) return;

    event.preventDefault();
    const myModal = this;

    Swal.fire({
        title: '⚠️ Regla de Reemplazos',
        html: 'Recuerda que por ahora <b>la empresa no asume diferencias de precio</b>.<br><br>Asegúrate de que el nuevo total sea <b>igual o menor</b> al pago original.',
        icon: 'info',
        confirmButtonColor: '#2A1B5E',
        confirmButtonText: 'Entendido, ¡a editar!',
        showCancelButton: true,
        cancelButtonText: 'Cancelar',
        cancelButtonColor: '#6c757d',
        customClass: { popup: 'rounded-4 shadow-lg' }
    }).then((result) => {
        if (result.isConfirmed) {
            edicionConfirmada = true;

            // Estandarizamos a 'producto_id'
            carritoEditado = CARRITO_ORIGINAL.map(item => {
                const estaEliminado = [true, 1, 'true', '1'].includes(item.es_eliminado);
                const estaAgregado = [true, 1, 'true', '1'].includes(item.es_agregado);

                return {
                    producto_id: item.producto_id, // Llave estándar
                    cod_producto: item.cod_producto,
                    nombre: item.nombre_producto || item.nombre,
                    imagen: item.imagen,
                    precio_bruto: parseInt(item.precio_bruto) || Math.round(parseFloat(item.precio_neto) * 1.19),
                    cantidad: parseInt(item.cantidad),
                    es_nuevo: estaAgregado,
                    es_eliminado: estaEliminado
                };
            });

            renderizarTablaEdicion();
            const bsModal = bootstrap.Modal.getOrCreateInstance(myModal);
            bsModal.show();
        }
    });
});


// Reiniciamos la bandera cuando cierran el modal, por si quieren volver a entrar
document.getElementById('modalEditarPedido')?.addEventListener('hidden.bs.modal', function () {
    edicionConfirmada = false;
});
let carritoEditado = [];

// 1. Abre el modal al primer clic y clona los datos
function abrirModalEdicion() {
    // Clonamos el carrito original para poder jugar con él sin romper la vista de atrás
    carritoEditado = JSON.parse(JSON.stringify(CARRITO_ORIGINAL));

    // Dibujamos la tabla
    renderizarTablaEdicion();

    // Forzamos la apertura directa del modal (Adiós al doble clic)
    const modalEl = document.getElementById('modalEditarPedido');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

/**
 * Quita la marca de eliminado de un producto
 */
function restaurarItemEdicion(index) {
    carritoEditado[index].es_eliminado = false;
    renderizarTablaEdicion();
}
// 2. Dibuja los productos, el servicio y el despacho en pantalla
function renderizarTablaEdicion() {
    const tbody = document.querySelector('#tablaEdicionPedido tbody');
    tbody.innerHTML = '';
    let subtotalProductos = 0;

    carritoEditado.forEach((item, index) => {
        // --- DETECTAR SI ESTÁ ELIMINADO ---
        const isDeleted = (item.es_eliminado === true || item.es_eliminado === 'true');

        // --- ESTILOS CONDICIONALES ---
        const filaClase = isDeleted ? 'bg-secondary bg-opacity-10' : '';
        const textoClase = isDeleted ? 'text-decoration-line-through text-muted' : 'text-dark';
        const imgEstilo = isDeleted ? 'filter: grayscale(1); opacity: 0.5;' : '';
        const precioClase = isDeleted ? 'text-muted text-decoration-line-through' : 'text-cenco-indigo';

        // --- LÓGICA INTELIGENTE DE IMAGEN ---
        let rutaImg = "";
        if (item.imagen && item.imagen.startsWith('http')) {
            rutaImg = item.imagen;
        } else if (item.imagen) {
            rutaImg = BASE_URL + 'img/productos/' + item.imagen;
        } else {
            rutaImg = BASE_URL + 'img/no-image.png';
        }

        let precio = parseInt(item.precio_bruto || (item.precio_neto * 1.19));
        let cantidad = parseInt(item.cantidad);
        let subtotal = precio * cantidad;

        // --- SUMA MATEMÁTICA: Solo sumamos si NO está eliminado ---
        if (!isDeleted) {
            subtotalProductos += subtotal;
        }

        // --- BOTÓN DINÁMICO: Basurero o Restaurar ---
        const botonAccion = isDeleted
            ? `<button class="btn btn-sm btn-outline-primary border-0" onclick="restaurarItemEdicion(${index})" title="Restaurar"><i class="bi bi-arrow-counterclockwise"></i></button>`
            : `<button class="btn btn-sm btn-outline-danger border-0" onclick="eliminarItemEdicion(${index})" title="Eliminar"><i class="bi bi-trash-fill"></i></button>`;

        tbody.innerHTML += `
        <tr class="${filaClase}">
            <td class="ps-4">
                <div class="d-flex align-items-center">
                    <img src="${rutaImg}" style="width: 40px; height: 40px; object-fit: contain; ${imgEstilo}" 
                         class="rounded border me-2" onerror="this.src='${BASE_URL}img/no-image.png'">
                    <div>
                        <span class="d-block fw-bold small ${textoClase}">${item.nombre_producto || item.nombre}</span>
                        <span class="badge bg-light text-secondary border" style="font-size:0.6rem;">${item.cod_producto}</span>
                    </div>
                </div>
            </td>
            <td class="text-center fw-bold text-muted">$${new Intl.NumberFormat('es-CL').format(precio)}</td>
            <td class="text-center">
                <div class="d-flex justify-content-center align-items-center">
                    <button class="btn btn-sm btn-light border py-0 px-2" onclick="cambiarCantEdicion(${index}, -1)" ${isDeleted ? 'disabled' : ''}>-</button>
                    <span class="mx-2 fw-bold ${isDeleted ? 'text-muted' : ''}">${cantidad}</span>
                    <button class="btn btn-sm btn-light border py-0 px-2" onclick="cambiarCantEdicion(${index}, 1)" ${isDeleted ? 'disabled' : ''}>+</button>
                </div>
            </td>
            <td class="text-end fw-bold ${precioClase}">$${new Intl.NumberFormat('es-CL').format(subtotal)}</td>
            <td class="text-center pe-4">
                ${botonAccion}
            </td>
        </tr>
    `;
    });

    // B. AGREGAMOS LOS COSTOS FIJOS AL FINAL DE LA TABLA
    const costoServicio = 490;
    tbody.innerHTML += `
        <tr class="bg-light">
            <td colspan="3" class="text-end text-muted small"><i class="bi bi-bag-check me-1"></i> Costo por Servicio Fijo:</td>
            <td class="text-end fw-bold text-dark">$${new Intl.NumberFormat('es-CL').format(costoServicio)}</td>
            <td></td>
        </tr>
        <tr class="bg-light">
            <td colspan="3" class="text-end text-muted small"><i class="bi bi-truck me-1"></i> Costo de Despacho Original:</td>
            <td class="text-end fw-bold text-dark">$${new Intl.NumberFormat('es-CL').format(COSTO_ENVIO_FIJO)}</td>
            <td></td>
        </tr>
    `;

    // C. Disparamos la matemática
    recalcularTotal(subtotalProductos);
}

/**
 * Función adicional para que el botón de flecha azul funcione
 */
function restaurarItemEdicion(index) {
    carritoEditado[index].es_eliminado = false;
    renderizarTablaEdicion();
}
function recalcularTotal(subtotalProductos) {
    const costoServicioFijo = 490;
    const nuevoTotal = subtotalProductos + COSTO_ENVIO_FIJO + costoServicioFijo;
    const montoPagado = MONTO_WEBPAY_ORIGINAL;
    const diferencia = montoPagado - nuevoTotal;

    // --- FIX: Validamos que el elemento exista antes de asignarle texto ---
    const elMontoPagado = document.getElementById('txt-monto-pagado');
    const elNuevoTotal = document.getElementById('txt-nuevo-total');
    const elTotalPedido = document.getElementById('nuevo_total_pedido'); // Revisa si el ID es este

    if (elMontoPagado) elMontoPagado.innerText = '$' + new Intl.NumberFormat('es-CL').format(montoPagado);
    if (elNuevoTotal) elNuevoTotal.innerText = '$' + new Intl.NumberFormat('es-CL').format(nuevoTotal);
    if (elTotalPedido) elTotalPedido.innerText = '$' + new Intl.NumberFormat('es-CL').format(nuevoTotal);

    const cajaDif = document.getElementById('caja-diferencia');
    if (cajaDif) {
        if (diferencia > 0) {
            cajaDif.innerHTML = `<span class="fw-bold text-success"><i class="bi bi-lightbulb-fill me-1"></i> Saldo a favor (¡Ofrece algo!):</span>
                                 <strong class="text-success fs-5">+$${new Intl.NumberFormat('es-CL').format(diferencia)}</strong>`;
        } else if (diferencia === 0) {
            cajaDif.innerHTML = `<span class="fw-bold text-secondary">Diferencia:</span>
                                 <strong class="text-secondary fs-5">$0</strong>`;
        } else {
            cajaDif.innerHTML = `<span class="fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Te pasaste por:</span>
                                 <strong class="text-danger fs-5">-$${new Intl.NumberFormat('es-CL').format(Math.abs(diferencia))}</strong>`;
        }
    }

    const btnGuardar = document.getElementById('btnGuardarEdicion');
    if (btnGuardar) btnGuardar.disabled = (diferencia < 0);
}
// Asegúrate de que cuando agregas un producto desde el buscador o cambias la cantidad, llames a:
// renderizarTablaEdicion();
function actualizarCantidadEdicion(index, val) {
    carritoEditado[index].cantidad = Math.max(1, parseInt(val));
    renderizarTablaEdicion();
}

function eliminarItemEdicion(index) {
    if (carritoEditado[index].es_nuevo) carritoEditado.splice(index, 1);
    else carritoEditado[index].es_eliminado = true;
    renderizarTablaEdicion();
}

function restaurarItemEdicion(index) {
    carritoEditado[index].es_eliminado = false;
    renderizarTablaEdicion();
}
/**
 * Buscador de Productos AJAX (Exclusivo para Reemplazos en Pedidos)
 */
function initBuscadorProductos() {
    const input = document.getElementById('inputBuscarProdReemplazo');
    const res = document.getElementById('resultadosBusquedaReemplazo');
    if (!input) return;

    input.addEventListener('keyup', function () {
        const q = this.value.trim();
        if (q.length < 3) {
            res.classList.add('d-none');
            return;
        }

        // 1. Mostrar estado de carga visual (Evita que parezca congelado)
        res.innerHTML = '<div class="p-3 text-center text-muted small"><div class="spinner-border spinner-border-sm me-2"></div>Buscando en bodega...</div>';
        res.classList.remove('d-none');


        // 2. Llamar a la ruta ninja en AdminController
        // Dentro de initBuscadorProductos, cambia la línea del fetch por esta:
        fetch(`${BASE_URL}admin/buscar_reemplazo?q=${encodeURIComponent(q)}&pedido_id=${PEDIDO_ID}&sucursal=${SUCURSAL_PEDIDO}`)
            .then(r => {
                if (!r.ok) throw new Error("Error de ruta HTTP " + r.status);
                return r.text(); // Leemos como texto por si PHP escupe un error raro
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    res.innerHTML = '';

                    // Si no hay stock o la búsqueda no coincide
                    if (!Array.isArray(data) || data.length === 0) {
                        res.innerHTML = '<div class="p-3 text-muted small text-center"><i class="bi bi-inbox-fill fs-4 d-block mb-2"></i>No hay stock disponible para "' + q + '".</div>';
                        return;
                    }

                    // Dibujar la lista de productos
                    data.forEach(prod => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center p-2 border-bottom';

                        const precio = Number(prod.precio) || 0;
                        const stock = Number(prod.stock_disponible) || 0;

                        btn.innerHTML = `
                            <div class="d-flex align-items-center" style="min-width: 0;">
                                <img src="${prod.imagen}" class="me-3 rounded border bg-white" style="width:45px; height:45px; object-fit:contain; flex-shrink: 0;">
                                <div class="text-truncate">
                                    <b class="d-block text-truncate text-dark" style="font-size: 0.85rem;">${prod.nombre}</b>
                                    <span class="text-muted small">${prod.cod_producto} - <strong class="text-cenco-indigo">$${precio.toLocaleString('es-CL')}</strong></span>
                                </div>
                            </div>
                            <span class="badge bg-cenco-green rounded-pill ms-2 shadow-sm" style="font-size: 0.75rem;">
                                Stock: ${stock}
                            </span>
                        `;

                        // Al hacer clic, enviamos la info a tu función existente
                        btn.onclick = (e) => {
                            e.preventDefault();
                            agregarProductoEdicion(prod.id, prod.cod_producto, prod.nombre, prod.imagen, precio);
                            res.classList.add('d-none');
                            input.value = '';
                        };
                        res.appendChild(btn);
                    });

                } catch (e) {
                    console.error("Error leyendo JSON del servidor:", text);
                    res.innerHTML = '<div class="p-3 text-danger small text-center"><i class="bi bi-exclamation-triangle-fill fs-4 d-block mb-2"></i>Error interno. Revisa la consola (F12).</div>';
                }
            })
            .catch(err => {
                console.error("Error de Red:", err);
                res.innerHTML = '<div class="p-3 text-danger small text-center"><i class="bi bi-x-octagon-fill fs-4 d-block mb-2"></i>Ruta no encontrada. Asegúrate de haber guardado el PHP.</div>';
            });
    });
} function agregarProductoEdicion(id, cod, nombre, img, precio) {
    // Buscamos usando la nueva llave estándar
    let itemExistente = carritoEditado.find(item => item.producto_id == id);

    if (itemExistente) {
        if (itemExistente.es_eliminado) {
            itemExistente.es_eliminado = false;
            itemExistente.cantidad = 1;
        } else {
            itemExistente.cantidad += 1;
        }
    } else {
        carritoEditado.push({
            producto_id: id, // Llave estándar
            cod_producto: cod,
            nombre: nombre,
            imagen: img,
            precio_bruto: precio,
            cantidad: 1,
            es_nuevo: true,
            es_eliminado: false
        });
    }

    renderizarTablaEdicion();
}
function guardarEdicionPedido() {
    const limpios = carritoEditado.filter(i => !i.es_eliminado);

    if (limpios.length === 0) {
        return Swal.fire({ title: 'Error', text: 'El pedido no puede estar vacío', icon: 'warning', confirmButtonColor: '#2A1B5E' });
    }

    const nuevoTotalProductos = limpios.reduce((acc, item) => acc + (item.precio_bruto * item.cantidad), 0);
    const nuevoTotalFinal = nuevoTotalProductos + (typeof COSTO_ENVIO_FIJO !== 'undefined' ? COSTO_ENVIO_FIJO : 0);

    if (nuevoTotalFinal > MONTO_WEBPAY_ORIGINAL) {
        const diferencia = nuevoTotalFinal - MONTO_WEBPAY_ORIGINAL;
        return Swal.fire({
            title: 'Acción Bloqueada',
            html: `Monto superior al pagado por el cliente.<br><span class="text-danger fw-bold">Diferencia: $${diferencia.toLocaleString('es-CL')}</span>`,
            icon: 'error', confirmButtonColor: '#2A1B5E'
        });
    }

    Swal.fire({
        title: '¿Confirmar Cambios?',
        html: `
            <p class="small text-muted">El monto es correcto. Indica el motivo y adjunta el respaldo.</p>
            <div class="text-start">
                <label class="small fw-bold text-dark">Motivo de la edición:</label>
                <input type="text" id="swal-motivo" class="form-control mb-3" 
                       placeholder="Ej: Cambio de sabor solicitado"
                       oninput="this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1)">
                
                <label class="small fw-bold text-dark">Imagen de respaldo (Opcional):</label>
                <input type="file" id="swal-foto" class="form-control" accept="image/*">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar Pedido',
        cancelButtonText: 'Volver',
        confirmButtonColor: '#2A1B5E',
        customClass: { popup: 'rounded-4 shadow-lg' },
        // Forzamos el foco al abrirse para asegurar la escritura
        didOpen: () => {
            const input = document.getElementById('swal-motivo');
            if (input) input.focus();
        },
        preConfirm: () => {
            const motivo = document.getElementById('swal-motivo').value.trim();
            const foto = document.getElementById('swal-foto').files[0];
            if (!motivo) {
                Swal.showValidationMessage('El motivo es obligatorio');
                return false;
            }
            return { motivo, foto };
        }
    }).then((res) => {
        if (res.isConfirmed) {
            const fd = new FormData();
            fd.append('pedido_id', PEDIDO_ID);
            fd.append('motivo', res.value.motivo);
            fd.append('carrito_original', JSON.stringify(CARRITO_ORIGINAL));
            fd.append('carrito_editado', JSON.stringify(limpios));
            if (res.value.foto) fd.append('evidencia', res.value.foto);

            Swal.fire({ title: 'Sincronizando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            fetch(`${BASE_URL}admin/pedido/guardar_edicion`, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.status) {
                        Swal.fire('¡Listo!', 'Pedido modificado exitosamente.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                }).catch(() => Swal.fire('Error', 'Error de conexión', 'error'));
        }
    });
}
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Abre el modal y carga la imagen de evidencia
 */
function verEvidencia(rutaImagen) {
    const imgModal = document.getElementById('imgEvidenciaZoom');
    const modal = new bootstrap.Modal(document.getElementById('modalVerEvidencia'));

    // Cambiamos la fuente de la imagen
    imgModal.src = rutaImagen;

    // Mostramos el modal
    modal.show();
}

/**
 * Pasa un pedido de Crédito de Confianza directamente a preparación (Fast-Track)
 */
function pasarAPreparacionDirecto(pedidoId) {
    Swal.fire({
        title: '¿Enviar a Preparación?',
        text: "Confirmas que el pedido de confianza es válido para comenzar a ser armado por bodega.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2A1B5E',
        confirmButtonText: 'Sí, enviar a bodega',
        cancelButtonText: 'Cancelar',
        customClass: { popup: 'rounded-4 shadow-lg' }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('formCambiarEstado');
            if (form) {
                const selectEstado = form.querySelector('select[name="estado_id"]');
                if (selectEstado) {
                    selectEstado.value = "3";
                    form.submit();
                }
            }
        }
    });
}
