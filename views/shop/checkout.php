<div class="container mb-5">
    <h2 class="mb-4 fw-bold text-primary">Finalizar Compra</h2>

    <div class="row">
        <div class="col-md-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-truck me-2"></i>Datos de Despacho</h5>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>checkout/procesar" method="POST" id="formCheckout">
                        
                        <div class="mb-3">
                            <label class="fw-bold text-muted small">Cliente</label>
                            <input type="text" class="form-control bg-light" value="<?= $usuario->nombre ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold text-muted small">RUT (Facturación)</label>
                            <input type="text" class="form-control bg-light" value="<?= $usuario->rut ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold small">Dirección de Envío</label>
                            <input type="text" name="direccion" class="form-control" value="<?= $usuario->direccion ?>" required>
                            <div class="form-text">Puedes editar la dirección para este pedido.</div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold small">Método de Pago</label>
                            <div class="border rounded p-3 d-flex align-items-center bg-light">
                                <i class="bi bi-bank fs-4 me-3 text-primary"></i>
                                <div>
                                    <strong>Transferencia / Orden de Compra</strong>
                                    <div class="small text-muted">Te enviaremos los datos bancarios al confirmar.</div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Resumen del Pedido</h5>

                    <?php 
                    $total = 0;
                    foreach($_SESSION['carrito'] as $item): 
                        $subtotal = $item['precio'] * $item['cantidad'];
                        $total += $subtotal;
                    ?>
                        <div class="d-flex justify-content-between mb-2 small">
                            <span><?= $item['cantidad'] ?>x <?= substr($item['nombre'], 0, 20) ?>...</span>
                            <span class="fw-bold">$<?= number_format($subtotal, 0, ',', '.') ?></span>
                        </div>
                    <?php endforeach; ?>

                    <hr>

                    <div class="d-flex justify-content-between mb-2 text-muted">
                        <span>Neto</span>
                        <span>$<?= number_format(round($total / 1.19), 0, ',', '.') ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-muted">
                        <span>IVA (19%)</span>
                        <span>$<?= number_format($total - round($total / 1.19), 0, ',', '.') ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-4 fs-4 fw-bold text-primary">
                        <span>Total</span>
                        <span>$<?= number_format($total, 0, ',', '.') ?></span>
                    </div>

                    <button type="submit" form="formCheckout" class="btn btn-primary w-100 py-3 fw-bold shadow">
                        CONFIRMAR COMPRA <i class="bi bi-check-circle ms-2"></i>
                    </button>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted"><i class="bi bi-lock"></i> Compra segura</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>