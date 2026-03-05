<?php
// Determinamos si es contra entrega (ID 7) u otra forma (Webpay)
$esContraEntrega = ($pedido['forma_pago_id'] == 7);
$modalidadTexto = $esContraEntrega ? 'Pago Contra Entrega' : 'Webpay Plus';
$modalidadColor = $esContraEntrega ? 'text-cenco-green' : 'text-primary';
?>

<div class="container mb-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow border-0 rounded-4 text-center overflow-hidden">
                <div class="card-body p-5">
                    
                    <div class="mb-4 animate__animated animate__bounceIn">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                    </div>

                    <h2 class="fw-black text-cenco-indigo mb-3">¡Pedido Confirmado!</h2>
                    
                    <p class="text-muted mb-4 fs-5">
                        Hola <strong><?= htmlspecialchars($pedido['nombre_cliente']) ?></strong>, tu orden ha sido ingresada con éxito bajo la modalidad de <strong class="<?= $modalidadColor ?>"><?= $modalidadTexto ?></strong>.
                    </p>

                    <div class="bg-light rounded-3 p-4 mb-4 text-start border shadow-sm">
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <span class="text-muted fw-bold">N° de Orden:</span>
                            <span class="fw-bold fs-5 text-cenco-indigo">#<?= str_pad($pedido['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <span class="text-muted fw-bold">N° de Seguimiento:</span>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($pedido['numero_seguimiento']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <span class="text-muted fw-bold">Fecha Estimada de Entrega:</span>
                            <span class="fw-bold text-dark"><?= date('d/m/Y', strtotime($pedido['fecha_entrega_estimada'])) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <span class="fw-bold text-cenco-indigo fs-5">Total a Pagar:</span>
                            <span class="fw-black text-cenco-red fs-4">$<?= number_format($pedido['monto_total'], 0, ',', '.') ?></span>
                        </div>
                    </div>

                    <?php if ($esContraEntrega): ?>
                        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4 text-start">
                            <i class="bi bi-info-circle-fill fs-3 text-warning me-3"></i>
                            <small class="text-dark">Recuerda que deberás realizar el pago (Efectivo, Transferencia o Transbank) <strong>al momento de recibir tus productos</strong>.</small>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4 text-start bg-opacity-10">
                            <i class="bi bi-shield-check fs-3 text-success me-3"></i>
                            <small class="text-dark">Tu pago ha sido <strong>procesado y aprobado</strong> exitosamente. ¡Pronto comenzaremos a preparar tu pedido!</small>
                        </div>
                    <?php endif; ?>

                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                        <a href="<?= BASE_URL ?>perfil?tab=pedidos" class="btn btn-outline-cenco-indigo px-4 py-2 fw-bold rounded-pill">
                            <i class="bi bi-list-ul me-2"></i>Ver mis pedidos
                        </a>
                        <a href="<?= BASE_URL ?>home" class="btn btn-cenco-green px-4 py-2 fw-bold rounded-pill">
                            <i class="bi bi-house-door me-2"></i>Volver al inicio
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>