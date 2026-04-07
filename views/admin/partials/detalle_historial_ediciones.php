<?php if (!empty($ediciones)): ?>
<div class="card border-0 shadow-sm rounded-4 mt-4 mb-5 animate__animated animate__fadeInUp">
    <div class="card-header bg-white py-3 px-4 border-bottom border-light">
        <h5 class="mb-0 fw-bold text-cenco-indigo"><i class="bi bi-clock-history me-2"></i>Historial de Ediciones</h5>
    </div>
    <div class="card-body p-4">
        <div class="timeline-container">
            <?php foreach ($ediciones as $ed): ?>
                <div class="d-flex mb-4 position-relative">
                    <div class="flex-shrink-0 me-3 mt-1 position-relative" style="z-index: 2;">
                        <div class="bg-white border rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                            <i class="bi bi-pencil-fill text-warning fs-5"></i>
                        </div>
                    </div>
                    
                    <div class="flex-grow-1 border-bottom border-light pb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($ed['admin_nombre'] ?? 'Administrador') ?> realizó un cambio</h6>
                                <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y H:i', strtotime($ed['fecha_edicion'] ?? 'now')) ?> hrs.</small>
                            </div>
                            
                            <?php if (!empty($ed['evidencia_imagen'])): ?>
                                <button type="button" 
                                        class="btn btn-sm btn-primary fw-bold rounded-pill shadow-sm px-3 hover-scale" 
                                        onclick="verEvidencia('<?= BASE_URL . $ed['evidencia_imagen'] ?>')">
                                    <i class="bi bi-camera-fill me-1"></i> Ver Evidencia
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="bg-light p-3 rounded-3 mb-2 border">
                            <p class="small mb-2 text-dark"><strong><i class="bi bi-chat-left-dots me-1 text-muted"></i>Motivo:</strong> <?= htmlspecialchars($ed['motivo_cambio'] ?? 'Cliente lo solicitó') ?></p>
                            <div class="row small g-0">
                                <div class="col-6">
                                    <span class="text-muted">Total ERP Anterior:</span> <span class="text-decoration-line-through text-danger">$<?= number_format((float)($ed['monto_original'] ?? 0), 0, ',', '.') ?></span>
                                </div>
                                <div class="col-6 text-end">
                                    <span class="text-muted fw-bold">Nuevo Total ERP:</span> <span class="fw-bold text-success">$<?= number_format((float)($ed['monto_nuevo'] ?? 0), 0, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($ed['detalles_cambio'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0 align-middle" style="font-size: 0.75rem;">
                                <tbody>
                                    <?php foreach ($ed['detalles_cambio'] as $det):
                                        $esAgregado = ($det['accion'] ?? '') == 'agregado';
                                        $badgeClass = $esAgregado ? 'bg-success text-white' : 'bg-danger text-white';
                                        $iconClass = $esAgregado ? 'bi-plus-circle-fill' : 'bi-dash-circle-fill';
                                        $textClass = $esAgregado ? 'text-success fw-bold' : 'text-danger fw-bold';
                                    ?>
                                        <tr>
                                            <td class="ps-0" style="width: 80px;">
                                                <span class="badge <?= $badgeClass ?> px-2 py-1 text-uppercase w-100" style="font-size: 0.6rem;">
                                                    <i class="bi <?= $iconClass ?> me-1"></i><?= strtoupper($det['accion'] ?? 'Cambio') ?>
                                                </span>
                                            </td>
                                            <td class="<?= $textClass ?>"><?= htmlspecialchars($det['nombre_producto'] ?? 'Producto') ?></td>
                                            <td class="text-center text-muted"><?= $det['cantidad'] ?? 0 ?> un.</td>
                                            <td class="text-end fw-bold text-dark pe-0">$<?= number_format((float)($det['precio_bruto'] ?? 0), 0, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>