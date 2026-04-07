<div class="modal fade" id="modalEditarPedido" tabindex="-1" aria-labelledby="modalEditarPedidoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            
            <div class="modal-header bg-light border-bottom-0 py-3">
                <h5 class="modal-title fw-bold text-cenco-indigo" id="modalEditarPedidoLabel">
                    <i class="bi bi-tools me-2"></i>Edición Avanzada - Pedido #<?= $idPedido ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-light">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body">
                        <label class="form-label fw-bold text-muted small">Buscar producto para agregar:</label>
                        <div class="position-relative">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" id="inputBuscarProdReemplazo" class="form-control border-start-0 ps-0" placeholder="Nombre o código...">
                            </div>
                            <div id="resultadosBusquedaReemplazo" class="list-group position-absolute w-100 shadow mt-1 d-none" style="z-index: 1050; max-height: 250px; overflow-y: auto;"></div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-0">
                        <table class="table align-middle mb-0" id="tablaEdicionPedido">
                            <thead class="bg-white border-bottom">
                                <tr>
                                    <th class="ps-4">Producto</th>
                                    <th class="text-center">Precio</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-end">Subtotal</th>
                                    <th class="text-center pe-4">Acción</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-4 shadow-sm border border-2 border-light">
                    <div class="d-flex justify-content-between mb-2 text-cenco-indigo">
                        <span class="fw-bold"><i class="bi bi-wallet2 me-2"></i>Máximo Permitido (Pagado por Cliente):</span>
                        <strong id="txt-monto-pagado" class="fs-5">$0</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3 text-muted border-bottom pb-3">
                        <span>Nuevo Total Calculado <small>(Prod. + Despacho + Servicio)</small>:</span>
                        <strong id="txt-nuevo-total" class="fs-5">$0</strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-2" id="caja-diferencia">
                        <span class="fw-bold text-secondary">Diferencia:</span>
                        <strong class="text-secondary fs-5">$0</strong>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer bg-light border-top-0 py-3 d-flex justify-content-end">
                <button type="button" class="btn btn-outline-secondary fw-bold me-2 px-4 rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarEdicion" class="btn btn-cenco-green text-white fw-bold shadow-sm px-4 rounded-pill" onclick="guardarEdicionPedido()">
                    <i class="bi bi-check-circle-fill me-2"></i>Guardar Cambios
                </button>
            </div>
            
        </div>
    </div>
</div>