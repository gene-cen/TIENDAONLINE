<div class="container py-4">
    <div class="mb-4">
        <a href="<?= BASE_URL ?>admin/dashboard" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Volver al Panel
        </a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary mb-0">Pedido #<?= str_pad($pedido->id, 6, '0', STR_PAD_LEFT) ?></h2>
            <p class="text-muted small mb-0">
                <i class="bi bi-calendar-event"></i> <?= date('d/m/Y', strtotime($pedido->fecha_creacion)) ?> &nbsp;|&nbsp;
                <i class="bi bi-clock"></i> <?= date('H:i', strtotime($pedido->fecha_creacion)) ?> hrs
            </p>
        </div>
        <div>
            <?php if ($pedido->estado == 'pendiente'): ?>
                <span class="badge bg-warning text-dark fs-6 px-3 py-2">Pendiente</span>
            <?php else: ?>
                <span class="badge bg-success fs-6 px-3 py-2">Completado</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-light fw-bold">
                    <i class="bi bi-person"></i> Datos del Cliente
                </div>
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($pedido->nombre_cliente) ?></h5>
                    <p class="card-text mb-1"><strong>RUT:</strong> <?= $pedido->rut_cliente ?></p>
                    <p class="card-text mb-1"><strong>Email:</strong> <?= $pedido->email_cliente ?></p>
                    <p class="card-text"><strong>Dirección:</strong> <?= htmlspecialchars($pedido->direccion_envio) ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-light fw-bold">
                    <i class="bi bi-gear"></i> Gestión del Pedido
                </div>
                <div class="card-body">

                    <form action="<?= BASE_URL ?>admin/pedido/cambiar_estado" method="POST" class="mb-3">
                        <input type="hidden" name="pedido_id" value="<?= $pedido->id ?>">

                        <label class="form-label fw-bold small text-muted">Cambiar Estado:</label>
                        <div class="input-group">
                            <select name="nuevo_estado" class="form-select">
                                <option value="pendiente" <?= $pedido->estado == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                <option value="pagado" <?= $pedido->estado == 'pagado' ? 'selected' : '' ?>>Pagado</option>
                                <option value="enviado" <?= $pedido->estado == 'enviado' ? 'selected' : '' ?>>Enviado</option>
                                <option value="completado" <?= $pedido->estado == 'completado' ? 'selected' : '' ?>>Completado</option>
                                <option value="cancelado" <?= $pedido->estado == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                            </select>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Guardar
                            </button>
                        </div>
                    </form>

                    <hr>

                    <p class="mb-2">
                        <strong>Sucursal Origen:</strong>
                        <span class="badge bg-secondary"><?= $pedido->sucursal_codigo ?? 'Casa Matriz' ?></span>
                    </p>
                    <p class="mb-2"><strong>Vendedor:</strong> <?= $pedido->vendedor_codigo ?? 'Web' ?></p>

                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-outline-dark btn-sm" onclick="window.print()">
                            <i class="bi bi-printer"></i> Imprimir Comprobante
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0 fs-6"><i class="bi bi-cart"></i> Detalle de Productos</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Código</th>
                        <th>Producto</th>
                        <th class="text-center">Cant.</th>
                        <th class="text-end">Precio Neto</th>
                        <th class="text-end pe-4">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $d): ?>
                        <tr>
                            <td class="ps-4 font-monospace small"><?= $d->cod_producto ?></td>
                            <td>
                                <span class="fw-bold"><?= htmlspecialchars($d->nombre_producto ?? 'Producto Generico') ?></span>
                                <br>
                                <small class="text-muted">Unidad: <?= $d->unidad_medida ?></small>
                            </td>
                            <td class="text-center"><?= $d->cantidad ?></td>
                            <td class="text-end">$<?= number_format($d->precio_neto, 0, ',', '.') ?></td>
                            <td class="text-end pe-4 fw-bold">
                                $<?= number_format($d->precio_neto * $d->cantidad, 0, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-white">
                    <tr>
                        <td colspan="4" class="text-end pt-3"><strong>Subtotal Neto:</strong></td>
                        <td class="text-end pt-3 pe-4">$<?= number_format($pedido->total_neto, 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end text-muted">IVA (19%):</td>
                        <td class="text-end text-muted pe-4">$<?= number_format($pedido->total_bruto - $pedido->total_neto, 0, ',', '.') ?></td>
                    </tr>
                    <tr class="fs-5">
                        <td colspan="4" class="text-end fw-bold text-primary">TOTAL A PAGAR:</td>
                        <td class="text-end fw-bold text-primary pe-4">$<?= number_format($pedido->total_bruto, 0, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>