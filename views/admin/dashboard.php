<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid px-4 py-4 bg-light min-vh-100">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-1">
                <i class="bi bi-speedometer2 me-2"></i>Panel de Control
            </h2>
            <p class="text-muted mb-0">Resumen en tiempo real de tu negocio.</p>
        </div>
        
        <div class="d-flex gap-2 align-items-center">
            <form action="" method="GET" class="d-flex gap-2">
                <input type="date" name="desde" class="form-control form-control-sm shadow-sm border-0" value="<?= $desde ?>">
                <input type="date" name="hasta" class="form-control form-control-sm shadow-sm border-0" value="<?= $hasta ?>">
                <button type="submit" class="btn btn-sm btn-cenco-indigo shadow-sm"><i class="bi bi-filter"></i></button>
            </form>
            
            <a href="<?= BASE_URL ?>admin/importar_erp" class="btn btn-white text-primary border shadow-sm fw-bold hover-scale btn-sm ms-2">
                <i class="bi bi-arrow-repeat me-1"></i> ERP
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-cenco-indigo text-white h-100 overflow-hidden position-relative">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-uppercase text-white-50 fw-bold small mb-1 ls-1">Ventas (Periodo)</p>
                            <h2 class="fw-black mb-0 display-6">$<?= number_format($ventaPeriodo, 0, ',', '.') ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-10 p-3 rounded-circle text-white">
                            <i class="bi bi-currency-dollar fs-4"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="badge bg-white bg-opacity-25 text-white fw-normal">
                            <i class="bi bi-calendar3 me-1"></i> <?= date('d/m', strtotime($desde)) ?> - <?= date('d/m', strtotime($hasta)) ?>
                        </span>
                    </div>
                    <i class="bi bi-graph-up-arrow position-absolute bottom-0 end-0 me-n3 mb-n3 text-white opacity-10" style="font-size: 8rem;"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-uppercase text-muted fw-bold small mb-1 ls-1">Pedidos Pendientes</p>
                            <h2 class="fw-black text-warning mb-0 display-6"><?= $pendientes ?></h2>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                            <i class="bi bi-hourglass-split fs-4"></i>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">Requieren atención inmediata.</p>
                    <a href="<?= BASE_URL ?>admin/pedidos?estado=pendiente" class="btn btn-sm btn-outline-warning mt-3 rounded-pill fw-bold">Gestionar</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="text-uppercase text-muted fw-bold small mb-0 ls-1">Alertas de Stock</p>
                        <span class="badge bg-danger rounded-pill"><?= count($stockCritico) ?> Críticos</span>
                    </div>
                    
                    <div class="list-group list-group-flush">
                        <?php if(empty($stockCritico)): ?>
                            <div class="text-center text-muted small py-3">Inventario saludable <i class="bi bi-check-circle text-success"></i></div>
                        <?php else: ?>
                            <?php foreach($stockCritico as $prod): ?>
                                <div class="list-group-item px-0 py-2 border-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-exclamation-triangle-fill text-danger me-2 small"></i>
                                        <span class="small text-dark fw-bold text-truncate" style="max-width: 150px;"><?= $prod['nombre'] ?></span>
                                    </div>
                                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill"><?= $prod['stock'] ?> un.</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-cenco-indigo mb-0">Tendencia de Ventas (Últimos 7 días)</h6>
                </div>
                <div class="card-body">
                    

[Image of Sales Trend Chart]

                    <canvas id="ventasChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold text-cenco-indigo mb-0">Top 5 Más Vendidos</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <tbody>
                                <?php if(empty($topProductos)): ?>
                                    <tr><td class="text-center text-muted p-4">Sin datos en este periodo.</td></tr>
                                <?php else: ?>
                                    <?php $pos = 1; foreach($topProductos as $tp): ?>
                                        <tr>
                                            <td class="ps-4" style="width: 50px;">
                                                <?php if($pos == 1): ?>
                                                    <span class="badge bg-warning text-dark rounded-circle p-2">🥇</span>
                                                <?php elseif($pos == 2): ?>
                                                    <span class="badge bg-secondary bg-opacity-50 text-white rounded-circle p-2">🥈</span>
                                                <?php elseif($pos == 3): ?>
                                                    <span class="badge bg-secondary bg-opacity-25 text-dark rounded-circle p-2">🥉</span>
                                                <?php else: ?>
                                                    <span class="text-muted fw-bold ps-2"><?= $pos ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="d-block fw-bold text-dark text-truncate" style="max-width: 180px;">
                                                    <?= $tp['nombre'] ?>
                                                </small>
                                            </td>
                                            <td class="text-end pe-4">
                                                <span class="badge bg-success bg-opacity-10 text-success fw-bold">
                                                    <?= $tp['vendidos'] ?> un.
                                                </span>
                                            </td>
                                        </tr>
                                    <?php $pos++; endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 px-4 border-bottom border-light d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-cenco-indigo mb-0">Últimos Pedidos Recibidos</h6>
            <a href="<?= BASE_URL ?>admin/pedidos" class="btn btn-sm btn-light text-primary fw-bold">Ver Todos</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 text-muted small fw-bold">Folio</th>
                            <th class="text-muted small fw-bold">Cliente</th>
                            <th class="text-muted small fw-bold text-center">Estado</th>
                            <th class="text-muted small fw-bold text-end">Total</th>
                            <th class="text-muted small fw-bold text-end pe-4">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($ultimosPedidos as $p): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary">#<?= str_pad($p['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div class="fw-bold text-dark small"><?= $p['nombre_cliente'] ?></div>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($p['fecha_creacion'].' '.$p['hora_creacion'])) ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-<?= $p['color_estado'] ?? 'secondary' ?> px-2">
                                        <?= $p['estado'] ?>
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-dark">$<?= number_format($p['monto_total'], 0, ',', '.') ?></td>
                                <td class="text-end pe-4">
                                    <a href="<?= BASE_URL ?>admin/pedido/ver/<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-circle">
                                        <i class="bi bi-eye-fill"></i>
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

<script>
    const ctx = document.getElementById('ventasChart').getContext('2d');
    
    // Datos pasados desde PHP
    const fechas = <?= json_encode(array_column($datosGrafico, 'fecha')) ?>;
    const totales = <?= json_encode(array_column($datosGrafico, 'total')) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: fechas,
            datasets: [{
                label: 'Ventas ($)',
                data: totales,
                borderColor: '#004481', // Cencocal Indigo
                backgroundColor: 'rgba(0, 68, 129, 0.1)',
                borderWidth: 2,
                tension: 0.4, // Curvas suaves
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#004481',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [2, 4] }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
</script>