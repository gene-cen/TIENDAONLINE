<div class="container floating-info-cards">
    <div class="bg-white rounded-4 shadow-lg py-5 px-4 border border-light">
        <div class="row text-center g-4 justify-content-center align-items-start">

            <div class="col-md-4 info-card-item px-md-4">
                <div class="mb-3 text-cenco-indigo">
                    <i class="bi bi-box-seam" style="font-size: 2.8rem;"></i>
                </div>
                <h6 class="fw-black text-cenco-indigo text-uppercase mb-2" style="letter-spacing: 0.5px;">Seguimiento de Compra</h6>
                <p class="text-muted small mb-3">Revisa el estado y detalle de tus despachos en tiempo real.</p>
                <a href="#" data-bs-toggle="modal" data-bs-target="#modalRastreo" class="text-decoration-none fw-bold text-cenco-green small">Rastrear pedido <i class="bi bi-search"></i></a>
            </div>

            <div class="col-md-4 info-card-item px-md-4 border-start border-end border-light">
                <div class="mb-3 text-cenco-indigo position-relative d-inline-block">
                    <i class="bi bi-headset" style="font-size: 2.8rem;"></i>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-cenco-red border border-white rounded-circle" style="margin-top: 10px;"></span>
                </div>
                <h6 class="fw-black text-cenco-indigo text-uppercase mb-2" style="letter-spacing: 0.5px;">¿Problemas con tu Pedido?</h6>
                <p class="text-muted small mb-3">Repórtanos cualquier inconveniente para solucionarlo rápido.</p>
                <a href="https://wa.me/56946452516?text=Hola,%20tengo%20un%20problema%20con%20mi%20pedido" target="_blank" class="text-decoration-none fw-bold text-cenco-green small">Reportar por WhatsApp <i class="bi bi-whatsapp"></i></a>
            </div>

            <div class="col-md-4 info-card-item px-md-4">
                <div class="mb-3 text-cenco-indigo">
                    <i class="bi bi-shop" style="font-size: 2.8rem;"></i>
                </div>
                <h6 class="fw-black text-cenco-indigo text-uppercase mb-2" style="letter-spacing: 0.5px;">Locales y Horarios</h6>
                <p class="text-muted small mb-3">Entérate cuál es la sucursal Cencocal más cerca de ti.</p>
                <a href="<?= BASE_URL ?>home/locales" class="text-decoration-none fw-bold text-cenco-green small">Ver ubicaciones <i class="bi bi-arrow-right"></i></a>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalRastreo" tabindex="-1" aria-labelledby="modalRastreoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-lg border-0">
            
            <div class="modal-header bg-cenco-indigo text-white rounded-top-4 py-3">
                <h5 class="modal-title fw-bold" id="modalRastreoLabel">
                    <i class="bi bi-box-seam me-2"></i>Rastrea tu Pedido
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                
                <div id="cajaRastreo">
                    <p class="text-muted small mb-3">Ingresa el código de seguimiento (Ej: 2926041700000301).</p>

                    <div class="input-group mb-4 shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-cenco-indigo">
                            <i class="bi bi-search"></i>
                        </span>
                        
                        <input type="text" id="inputTracking" class="form-control border-start-0 ps-0 fw-bold text-uppercase" placeholder="Ingresa tu código..." onkeypress="if(event.key === 'Enter') ejecutarRastreo()">

                        <button class="btn btn-primary fw-bold px-4" type="button" id="btnBuscarRastreo" onclick="ejecutarRastreo()">Buscar</button>
                    </div>
                </div>

                <div id="resultadoRastreo" class="mt-4" style="display: none;">
                    </div>
                
            </div>

        </div>
    </div>
</div>

<script>
async function ejecutarRastreo() {
   
    
    const input = document.getElementById('inputTracking');
    const btn = document.getElementById('btnBuscarRastreo');
    const resultadoDiv = document.getElementById('resultadoRastreo');
    
    if (!input || !btn || !resultadoDiv) {
        console.error("No se encontraron los elementos del DOM");
        return;
    }
    
    const codigo = input.value.trim().toUpperCase();
    if (!codigo) {
        input.focus();
        return;
    }

    // Bloqueamos el botón y mostramos el loader
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    resultadoDiv.style.display = 'none';

    try {
        // Aseguramos que usamos la variable global BASE_URL
        const urlRastreo = typeof BASE_URL !== 'undefined' ? BASE_URL + 'home/rastrearPedido' : '/home/rastrearPedido';
        
        const response = await fetch(urlRastreo, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tracking: codigo })
        });

        const res = await response.json();

        if (res.status === 'error') {
            resultadoDiv.innerHTML = `<div class="alert alert-danger border-0 shadow-sm rounded-3"><i class="bi bi-exclamation-circle-fill me-2"></i>${res.msg}</div>`;
        } else {
            // RENDERIZAR LA LÍNEA DE TIEMPO
            const p = res.data;
            const estado = p.estado_id;
            const esRetiro = p.tipo_entrega === 2;

            const textoPaso4 = esRetiro ? 'Listo para Retiro' : 'En Ruta';
            const iconoPaso4 = esRetiro ? 'bi-shop' : 'bi-truck';

            const getStepHtml = (num, icono, titulo, activo) => `
                <div class="d-flex mb-3 align-items-center opacity-${activo ? '100' : '50'}">
                    <div class="bg-${activo ? 'success' : 'secondary'} text-white rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 45px; height: 45px;">
                        <i class="bi ${icono} fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold ${activo ? 'text-dark' : 'text-muted'}">${titulo}</h6>
                        ${activo && num === estado && estado < 5 ? `<small class="text-success fw-bold d-block mt-1"><i class="bi bi-arrow-return-right me-1"></i>Estado actual</small>` : ''}
                    </div>
                </div>
            `;

            let html = `
                <div class="bg-light p-3 rounded-4 border mb-4 text-center shadow-sm">
                    <h6 class="fw-bold mb-1 text-muted small">Fecha Estimada de Entrega</h6>
                    <span class="text-cenco-indigo fw-black fs-4">${p.fecha_estimada}</span>
                </div>
                <div class="position-relative ms-2">
            `;

            if (estado === 6) {
                html += `<div class="alert alert-danger text-center fw-bold rounded-3"><i class="bi bi-x-circle-fill me-2"></i>Este pedido ha sido anulado.</div>`;
            } else {
                html += getStepHtml(1, 'bi-receipt', 'Pedido Recibido', estado >= 1);
                html += getStepHtml(2, 'bi-credit-card-check', 'Pago Confirmado', estado >= 2);
                html += getStepHtml(3, 'bi-box-seam', 'En Preparación', estado >= 3);
                html += getStepHtml(4, iconoPaso4, textoPaso4, estado >= 4);
                html += getStepHtml(5, 'bi-house-check', 'Entregado', estado === 5);
            }
            
            html += `</div>`;
            resultadoDiv.innerHTML = html;
        }
    } catch (err) {
        console.error("Error en la petición:", err);
        resultadoDiv.innerHTML = `<div class="alert alert-danger rounded-3"><i class="bi bi-wifi-off me-2"></i>Error al conectar con el servidor. Verifica la consola.</div>`;
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Buscar';
        resultadoDiv.style.display = 'block';
    }
}
</script>