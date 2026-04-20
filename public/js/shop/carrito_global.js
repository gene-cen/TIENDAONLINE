/**
 * ARCHIVO: carrito_global.js
 * Descripción: Maneja toda la lógica del carrito flotante (offcanvas),
 * agregar productos desde tarjetas y sincronización de subtotales globales.
 */

// =========================================================
// 🔥 CONTROLADOR VISUAL DE TARJETAS DE PRODUCTO 🔥
// =========================================================
window.actualizarEstadoTarjeta = function(id, cantidad) {
    const card = document.getElementById(`card-prod-${id}`);
    if (!card) return; // Si la tarjeta no está en esta pantalla, no hacemos nada

    const formAdd = document.getElementById(`form-add-${id}`);
    const controlsDiv = document.getElementById(`controls-${id}`);
    const counts = document.querySelectorAll(`#card-count-${id}, #count-${id}`);
    const partesInternas = card.querySelectorAll('.fondo-dinamico-imagen, .fondo-dinamico-cuerpo');

    // Actualizamos los números de los contadores instantáneamente
    counts.forEach(el => el.innerText = cantidad);

    if (cantidad > 0) {
        // 1. ESTADO ACTIVO (Producto en Carrito)
        card.classList.remove('bg-white', 'border-light');
        card.classList.add('tarjeta-seleccionada', 'shadow', 'border-cenco-green');
        partesInternas.forEach(p => { p.classList.remove('bg-white'); p.classList.add('bg-transparent'); });

        if (formAdd) { formAdd.classList.remove('d-block'); formAdd.classList.add('d-none'); }
        if (controlsDiv) { controlsDiv.classList.remove('d-none'); controlsDiv.classList.add('d-flex'); }
    } else {
        // 2. ESTADO INACTIVO (Producto removido o en 0)
        card.classList.remove('tarjeta-seleccionada', 'shadow', 'border-cenco-green');
        card.classList.add('bg-white', 'border-light');
        partesInternas.forEach(p => { p.classList.remove('bg-transparent'); p.classList.add('bg-white'); });

        if (formAdd) { formAdd.classList.remove('d-none'); formAdd.classList.add('d-block'); }
        if (controlsDiv) { controlsDiv.classList.remove('d-flex'); controlsDiv.classList.add('d-none'); }
    }
};

// =========================================================
// 1. AÑADIR AL CARRO (Desde Cero)
// =========================================================
function agregarAlCarrito(e, form, id, stockMax) {
    e.preventDefault();

    if (stockMax <= 0) {
        Swal.fire({
            title: '¡Agotado!', text: 'Lo sentimos, este producto ya no tiene stock disponible.',
            imageUrl: window.BASE_URL + 'img/cencocalin/cencocalin_algo_fallo.png',
            imageWidth: 120, confirmButtonColor: '#2A1B5E', customClass: { popup: 'rounded-4 shadow-lg' }
        });
        return;
    }

    const formData = new FormData(form);

    fetch(window.BASE_URL + 'carrito/agregarAjax', {
        method: 'POST', body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            
            // 🔥 Ejecutamos la transformación visual inmediatamente
            window.actualizarEstadoTarjeta(id, data.cantidadItem);
            actualizarCarritoGlobal(data.totalCantidad, data.totalMonto);

            // Sincronización silenciosa en Checkout
            if (window.location.pathname.includes('checkout')) {
                fetch(window.location.href)
                    .then(res => res.text())
                    .then(html => {
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const selectors = ['.col-lg-5 .card-body', 'label[for="opcion_despacho"]', '#alerta-envio-gratis'];
                        selectors.forEach(sel => {
                            const actual = document.querySelector(sel);
                            const nuevo = doc.querySelector(sel);
                            if (actual && nuevo) {
                                actual.innerHTML = nuevo.innerHTML;
                                if(sel === '#alerta-envio-gratis') actual.className = nuevo.className;
                            }
                        });
                    });
                return;
            }

            // Abrir offcanvas automático
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

// =========================================================
// 2. PUENTE: Botones "+" y "-" de las tarjetas
// =========================================================
function gestionarClickTarjeta(id, accion, stockMax) {
    if (accion === 'subir') {
        const countSpan = document.getElementById(`card-count-${id}`);
        const cantidadActual = countSpan ? parseInt(countSpan.innerText) : 0;

        if (cantidadActual >= stockMax) {
            Swal.fire({
                title: '¡Límite alcanzado!',
                text: `Lo sentimos, solo tenemos ${stockMax} unidades disponibles de este producto por ahora.`,
                imageUrl: window.BASE_URL + 'img/cencocalin/cencocalin_preocupado.png',
                imageWidth: 120, confirmButtonColor: '#2A1B5E', confirmButtonText: 'Entendido',
                customClass: { popup: 'rounded-4 shadow-lg border-0' }
            });
            return; 
        }
    }
    cambiarCantidad(id, accion);
}

// =========================================================
// 3. LA CALCULADORA MAESTRA (Modifica cantidades)
// =========================================================
function cambiarCantidad(id, accion) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('accion', accion);

    fetch(window.BASE_URL + 'carrito/modificarAjax', {
        method: 'POST', body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            
            // 🔥 Ejecutamos la actualización visual para la tarjeta principal
            window.actualizarEstadoTarjeta(id, data.cantidadItem);

            // Stock Dinámico
            const spanStock = document.getElementById('stock-num-' + id);
            const contenedorStatus = document.getElementById('status-stock-' + id);
            if (spanStock && data.stockDisponible !== undefined) {
                spanStock.innerText = data.stockDisponible;
                if (data.stockDisponible <= 0) {
                    contenedorStatus.innerHTML = `<span class="text-danger small fw-bold"><i class="bi bi-x-circle"></i> Sin stock disponible</span>`;
                } else if (data.stockDisponible < 5) {
                    contenedorStatus.innerHTML = `
                        <span class="badge bg-light text-dark border fw-normal" style="font-size: 0.75rem;">Stock disponible: <strong id="stock-num-${id}">${data.stockDisponible}</strong></span><br>
                        <span class="text-danger extra-small fw-bold animate__animated animate__flash animate__infinite"><i class="bi bi-exclamation-triangle"></i> ¡Últimas unidades!</span>`;
                }
            }

            // Cantidades y Subtotales (Vista Carrito interna)
            const inputCant = document.getElementById('input-cant-' + id);
            if (inputCant) {
                inputCant.value = data.cantidadItem;
                const precio = parseInt(inputCant.getAttribute('data-precio')) || 0;
                const subtotalFmt = new Intl.NumberFormat('es-CL').format(precio * data.cantidadItem);
                const tdSubtotal = document.getElementById('subtotal-item-' + id);
                if (tdSubtotal) tdSubtotal.innerText = '$' + subtotalFmt;
            }

            // Manejo de Eliminación (Offcanvas o vista carrito)
            if (data.cantidadItem === 0) {
                const filaCarrito = document.getElementById('fila-carrito-' + id);
                if (filaCarrito) filaCarrito.remove();

                if (data.totalCantidad === 0 && window.location.href.includes('carrito')) {
                    location.reload(); return;
                }
            }

            // Actualizar Resumen (Vista Carrito interna)
            const resumenTotal = document.getElementById('resumen-total');
            if (resumenTotal) {
                const montoFmt = new Intl.NumberFormat('es-CL').format(data.totalMonto);
                const ivaFmt = new Intl.NumberFormat('es-CL').format(data.totalMonto * 0.19);
                document.getElementById('resumen-subtotal').innerText = '$' + montoFmt;
                document.getElementById('resumen-iva').innerText = '$' + ivaFmt;
                resumenTotal.innerText = '$' + montoFmt;
            }

            actualizarCarritoGlobal(data.totalCantidad, data.totalMonto);

            // Botón Subir Limitador
            const btnSubirLocal = document.getElementById('btn-subir-' + id);
            if (btnSubirLocal) {
                if (data.stockDisponible <= 0) {
                    btnSubirLocal.disabled = true; btnSubirLocal.classList.replace('text-success', 'text-muted');
                } else {
                    btnSubirLocal.disabled = false; btnSubirLocal.classList.replace('text-muted', 'text-success');
                }
            }

            // Sincronización silenciosa en Checkout
            if (window.location.pathname.includes('checkout')) {
                if (data.totalCantidad === 0) { window.location.reload(); return; }
                fetch(window.location.href).then(res => res.text()).then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const selectors = ['.col-lg-5 .card-body', 'label[for="opcion_despacho"]', '#alerta-envio-gratis'];
                    selectors.forEach(sel => {
                        const actual = document.querySelector(sel), nuevo = doc.querySelector(sel);
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

// =========================================================
// 4. FUNCIONES GLOBALES DEL CARRITO (Visuales)
// =========================================================
function actualizarCarritoGlobal(cantidad, monto) {
    const montoFmt = new Intl.NumberFormat('es-CL').format(monto);
    document.querySelectorAll('.badge-carrito, #badge-carrito-navbar, #contador-carrito').forEach(el => {
        el.innerText = cantidad; el.style.display = cantidad > 0 ? 'inline-block' : 'none';
    });
    document.querySelectorAll('.monto-carrito, #monto-carrito-navbar, #total-monto-navbar').forEach(el => {
        el.innerText = '$' + montoFmt;
    });
    actualizarCarritoLateral();
}

function actualizarCarritoLateral() {
    fetch(window.BASE_URL + 'carrito/obtenerHtml')
        .then(res => res.json())
        .then(data => {
            const contenedorLista = document.getElementById('contenedor-carrito-lista');
            const contenedorTotal = document.getElementById('contenedor-carrito-total');
            if (contenedorLista) contenedorLista.innerHTML = data.html;
            if (contenedorTotal) contenedorTotal.innerText = data.total;
        })
        .catch(err => console.error('Error recargando offcanvas:', err));
}

function confirmarEliminarCarrito(id) {
    Swal.fire({
        title: '¿Estás seguro?', text: "Sacarás este producto de tu carrito.",
        imageUrl: window.BASE_URL + 'img/cencocalin/cencocalin_preocupado.png', imageWidth: 100,
        showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#2A1B5E',
        confirmButtonText: 'Sí, quitarlo', cancelButtonText: 'Cancelar', customClass: { popup: 'rounded-4 shadow-lg' }
    }).then((result) => {
        if (result.isConfirmed) cambiarCantidad(id, 'eliminar');
    });
}

function actualizarBotonesStock(id, bloquear) {
    const btnMas = document.querySelector(`#controls-${id} .btn-cenco-green`);
    if (btnMas) {
        if (bloquear) btnMas.classList.add('opacity-50');
        else btnMas.classList.remove('opacity-50');
    }
}