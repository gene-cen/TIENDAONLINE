<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary">📊 Panel de Administración</h2>

        <div>
            <a href="<?= BASE_URL ?>admin/importar_erp" class="btn btn-primary btn-sm me-2">
                <i class="bi bi-arrow-repeat"></i> Sincronizar Stock (ERP)
            </a>

            <a href="<?= BASE_URL ?>admin/exportar_pedidos" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a ERP
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Pedidos Totales</h5>
                    <p class="card-text fs-2 fw-bold"><?= count($pedidos) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Pendientes</h5>
                    <p class="card-text fs-2 fw-bold">
                        <?= count(array_filter($pedidos, fn($p) => $p->estado == 'pendiente')) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">Últimas Ventas</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Folio ERP</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Sucursal</th>
                            <th>Total Bruto</th>
                            <th>Estado</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $p): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary">#<?= str_pad($p->id, 6, '0', STR_PAD_LEFT) ?></td>
                                <td><?= date('d/m/Y', strtotime($p->fecha_creacion)) ?></td>
                                <td>
                                    <div class="fw-bold"><?= date('d/m/Y', strtotime($p->fecha_creacion)) ?></div>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> <?= date('H:i', strtotime($p->fecha_creacion)) ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= $p->sucursal_codigo ?></span>
                                    <small class="text-muted ms-1"><?= $p->vendedor_codigo ?></small>
                                </td>
                                <td class="fw-bold text-success">$<?= number_format($p->total_bruto, 0, ',', '.') ?></td>
                                <td>
                                    <?php if ($p->estado == 'pendiente'): ?>
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Completado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="<?= BASE_URL ?>admin/pedido/ver/<?= $p->id ?>" class="btn btn-sm btn-outline-primary" title="Ver Detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>