<div class="container-fluid px-4 py-4 bg-light min-vh-100">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-1">
                <i class="bi bi-receipt-cutoff me-2"></i>Historial de Ventas
            </h2>
            <p class="text-muted mb-0">Gestiona, filtra y revisa la logística de tus pedidos.</p>
        </div>
        
        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a href="<?= BASE_URL ?>admin/exportar_pedidos" class="btn btn-cenco-green text-white shadow-sm fw-bold hover-scale">
                <i class="bi bi-file-earmark-excel me-1"></i> Exportar a Excel
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="<?= BASE_URL ?>admin/pedidos" method="GET" class="row g-2 align-items-center">
                
                <div class="col-md-4 d-flex gap-2">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted small">Desde</span>
                        <input type="date" name="desde" class="form-control border-start-0 ps-0 text-muted" 
                               value="<?= $_GET['desde'] ?? date('Y-m-01') ?>">
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted small">Hasta</span>
                        <input type="date" name="hasta" class="form-control border-start-0 ps-0 text-muted" 
                               value="<?= $_GET['hasta'] ?? date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control border-start-0 ps-0" 
                               placeholder="RUT, Cliente, Folio o Tracking..." 
                               value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <select name="estado" class="form-select text-muted">
                        <option value="">Todos los Estados</option>
                        <option value="pendiente" <?= ($_GET['estado'] ?? '') == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="pagado" <?= ($_GET['estado'] ?? '') == 'pagado' ? 'selected' : '' ?>>Pagado</option>
                        <option value="enviado" <?= ($_GET['estado'] ?? '') == 'enviado' ? 'selected' : '' ?>>Enviado</option>
                        <option value="entregado" <?= ($_GET['estado'] ?? '') == 'entregado' ? 'selected' : '' ?>>Entregado</option>
                        <option value="anulado" <?= ($_GET['estado'] ?? '') == 'anulado' ? 'selected' : '' ?>>Anulado</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-cenco-indigo w-100 fw-bold shadow-sm">Buscar</button>
                    <?php if(!empty($_GET['q']) || !empty($_GET['estado'])): ?>
                        <a href="<?= BASE_URL ?>admin/pedidos" class="btn btn-outline-danger" title="Limpiar">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light border-bottom">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold text-uppercase border-0">Folio</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0">Cliente</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0" style="width: 25%;">Logística de Entrega</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0 text-end">Total</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0 text-center">Estado</th>
                            <th class="pe-4 py-3 text-muted small fw-bold text-uppercase border-0 text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pedidos)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                    No se encontraron pedidos con estos filtros.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pedidos as $p): 
                                // Variables seguras con ?? ''
                                $id = $p['id'] ?? 0;
                                $fechaRaw = $p['fecha_creacion'] ?? null;
                                
                                $nombreCliente = $p['nombre_cliente'] ?? 'Cliente Web';
                                $rutCliente = $p['rut_cliente'] ?? '---';
                                
                                // DATOS LOGÍSTICOS
                                $sucursal = $p['sucursal_codigo'] ?? 'WEB';
                                $fechaEntrega = $p['fecha_entrega_fmt'] ?? null;
                                $rangoHorario = $p['rango_horario'] ?? '';
                                
                                $monto = $p['total_bruto'] ?? $p['monto_total'] ?? 0;
                                $estado = $p['estado'] ?? 'pendiente';

                                // Badge Class
                                $badgeClass = match(strtolower($estado)) {
                                    'pendiente' => 'bg-warning text-dark',
                                    'pagado', 'completado' => 'bg-success text-white',
                                    'enviado', 'en ruta' => 'bg-primary text-white',
                                    'entregado' => 'bg-dark text-white',
                                    'anulado', 'cancelado' => 'bg-danger text-white',
                                    default => 'bg-secondary text-white'
                                };
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-primary">#<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?></span>
                                    <br>
                                    <small class="text-muted" style="font-size: 0.75rem;">
                                        <?= date('d/m/Y', strtotime($fechaRaw)) ?>
                                    </small>
                                </td>

                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($nombreCliente) ?></div>
                                    <div class="small text-muted d-flex align-items-center gap-2">
                                        <span><i class="bi bi-person-vcard"></i> <?= $rutCliente ?></span>
                                    </div>
                                </td>

                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <div>
                                            <span class="badge bg-white text-dark border shadow-sm rounded-pill px-2">
                                                <i class="bi bi-shop me-1 text-secondary"></i> Sucursal <?= $sucursal ?>
                                            </span>
                                        </div>
                                        
                                        <?php if($fechaEntrega): ?>
                                            <div class="d-flex align-items-center mt-1">
                                                <i class="bi bi-truck text-success me-2 fs-6"></i>
                                                <div class="lh-1">
                                                    <span class="fw-bold text-dark small d-block"><?= $fechaEntrega ?></span>
                                                    <span class="text-muted" style="font-size: 0.7rem;"><?= $rangoHorario ?></span>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="small text-muted fst-italic ms-1">Sin fecha programada</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td class="text-end fw-black text-cenco-indigo">
                                    $<?= number_format($monto, 0, ',', '.') ?>
                                </td>

                                <td class="text-center">
                                    <span class="badge rounded-pill <?= $badgeClass ?> px-3 py-2 text-uppercase" style="font-size: 0.7rem;">
                                        <?= $estado ?>
                                    </span>
                                </td>

                                <td class="text-end pe-4">
                                    <a href="<?= BASE_URL ?>admin/pedido/ver/<?= $id ?>" class="btn btn-sm btn-white border shadow-sm text-primary fw-bold hover-scale" title="Ver Detalles y Cambiar Estado">
                                        Ver <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top py-3 text-center text-muted small">
            Mostrando <?= count($pedidos) ?> registros
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.querySelector('input[name="q"]');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                // Lógica visual opcional
            });
        }
    });
</script>