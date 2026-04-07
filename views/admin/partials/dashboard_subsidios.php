<?php if (!empty($kpisSubsidios) && (int)$kpisSubsidios['total_pedidos'] > 0): 
    // Declaración explícita para evitar Warnings de variable indefinida
    $total       = (int)$kpisSubsidios['total_pedidos'];
    $monto       = (float)($kpisSubsidios['monto_total_subsidio'] ?? 0);
    $conSubsidio = (int)($kpisSubsidios['pedidos_con_subsidio'] ?? 0);
    
    // Cálculo de incidencia con protección contra división por cero
    $incidencia  = ($total > 0) ? ($conSubsidio / $total) * 100 : 0;
    
    $sucursalCritica = $kpisSubsidios['sucursal_critica'] ?? '---';
?>
<div class="row g-3 mb-4 animate__animated animate__fadeIn">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 bg-white border-start border-4 border-danger h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="bg-danger bg-opacity-10 p-3 rounded-3 me-3">
                        <i class="bi bi-graph-down-arrow text-danger fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.7rem;">Subsidio Total (Fuga)</small>
                        <h4 class="fw-black text-dark mb-0">$<?= number_format($monto, 0, ',', '.') ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 bg-white border-start border-4 border-warning h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 p-3 rounded-3 me-3">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.7rem;">Tasa de Quiebres</small>
                        <h4 class="fw-black text-dark mb-0"><?= number_format($incidencia, 1, ',', '.') ?>% <small class="fw-normal fs-6 text-muted">de pedidos</small></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 bg-white border-start border-4 border-primary h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="bg-cenco-indigo bg-opacity-10 p-3 rounded-3 me-3">
                        <i class="bi bi-geo-alt-fill text-cenco-indigo fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.7rem;">Sucursal con más Subsidios</small>
                        <h4 class="fw-black text-dark mb-0">Suc. <?= htmlspecialchars($sucursalCritica) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>