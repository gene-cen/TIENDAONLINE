<?php
// Determinamos si es venta asistida
$esAsistido = ($pedido['tipo_cliente'] ?? '') === 'asistido';

// Determinamos si es contra entrega (ID 7 u 8) u otra forma (Webpay)
$esContraEntrega = ($pedido['forma_pago_id'] == 7 || $pedido['forma_pago_id'] == 8);

if ($esAsistido) {
    $modalidadTexto = 'Venta Presencial en Tienda';
    $modalidadColor = 'text-primary';
} else {
    $modalidadTexto = $esContraEntrega ? 'Pago Presencial / Contra Entrega' : 'Webpay Plus';
    $modalidadColor = $esContraEntrega ? 'text-cenco-green' : 'text-primary';
}

// Determinamos la modalidad de entrega
$esRetiro = (isset($pedido['tipo_entrega_id']) && $pedido['tipo_entrega_id'] == 2);

// --- MATEMÁTICA PARA EL DESGLOSE ---
$costoEnvio = (int)($pedido['costo_envio'] ?? 0);
$costoServicio = 490;
$subtotalProductos = (int)$pedido['monto_total'] - $costoEnvio - $costoServicio;
?>

<div class="container mb-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow border-0 rounded-4 text-center overflow-hidden">
                <div class="card-body p-5">

                    <div class="mb-4 animate__animated animate__bounceIn">
                        <i class="bi <?= $esAsistido ? 'bi-person-check-fill text-primary' : 'bi-check-circle-fill text-success' ?>" style="font-size: 5rem;"></i>
                    </div>

                    <h2 class="fw-black text-cenco-indigo mb-3">
                        <?= $esAsistido ? '¡Venta Registrada!' : '¡Pedido Confirmado!' ?>
                    </h2>

                    <p class="text-muted mb-4 fs-5">
                        <?php if ($esAsistido): ?>
                            Has registrado exitosamente la compra para <strong><?= htmlspecialchars($pedido['nombre_destinatario'] ?? 'el cliente') ?></strong> bajo la modalidad de <strong class="<?= $modalidadColor ?>"><?= $modalidadTexto ?></strong>.
                        <?php else: ?>
                            Hola <strong><?= htmlspecialchars($pedido['nombre_cliente'] ?? $pedido['nombre_destinatario'] ?? 'Invitado') ?></strong>, tu orden ha sido ingresada con éxito bajo la modalidad de <strong class="<?= $modalidadColor ?>"><?= $modalidadTexto ?></strong>.
                        <?php endif; ?>
                    </p>

                    <div class="bg-light rounded-3 p-4 mb-4 text-start border shadow-sm">
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <span class="text-muted fw-bold">N° de Orden:</span>
                            <span class="fw-bold fs-5 text-cenco-indigo">#<?= str_pad($pedido['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        </div>

                        <?php if (!$esAsistido): ?>
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <span class="text-muted fw-bold">N° de Seguimiento:</span>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($pedido['numero_seguimiento'] ?? 'Pendiente') ?></span>
                        </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <span class="text-muted fw-bold">Modalidad:</span>
                            <span class="fw-bold text-dark"><?= $esRetiro ? 'Retiro en Tienda' : 'Despacho a Domicilio' ?></span>
                        </div>

                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2 mt-4">
                            <span class="text-muted fw-bold">Subtotal Productos:</span>
                            <span class="fw-bold text-dark">$<?= number_format($subtotalProductos, 0, ',', '.') ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <span class="text-muted fw-bold">Costo por Servicio:</span>
                            <span class="fw-bold text-dark">$<?= number_format($costoServicio, 0, ',', '.') ?></span>
                        </div>
                        
                        <?php if ($costoEnvio > 0): ?>
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <span class="text-muted fw-bold">Despacho a Domicilio:</span>
                            <span class="fw-bold text-dark">$<?= number_format($costoEnvio, 0, ',', '.') ?></span>
                        </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between mt-3 bg-white p-2 border rounded">
                            <span class="fw-bold text-cenco-indigo fs-5">Total Pagado:</span>
                            <span class="fw-black text-cenco-red fs-4">$<?= number_format($pedido['monto_total'], 0, ',', '.') ?></span>
                        </div>
                    </div>

                    <?php 
                    // Fecha exacta que se calculó en el Checkout (puede ser el mismo día si fue asistida)
                    $fechaTexto = date('d/m/Y H:i', strtotime($pedido['fecha_entrega_estimada'])); 
                    ?>

                    <div class="alert <?= $esRetiro ? 'alert-info' : 'alert-success' ?> p-3 mt-3 mb-4 text-center border-0 shadow-sm" style="font-size: 0.9rem;">
                        <?php if ($esRetiro): ?>
                            <i class="bi bi-shop fs-5 d-block mb-1 text-info"></i>
                            El pedido estará listo para retiro en la sucursal <strong>a partir de las <?= date('H:i', strtotime($pedido['fecha_entrega_estimada'])) ?> hrs del <?= date('d/m/Y', strtotime($pedido['fecha_entrega_estimada'])) ?></strong>.
                        <?php else: ?>
                            <i class="bi bi-truck fs-5 d-block mb-1 text-success"></i>
                            El camión realizará la entrega a domicilio <strong>antes de las <?= date('H:i', strtotime($pedido['fecha_entrega_estimada'])) ?> hrs del <?= date('d/m/Y', strtotime($pedido['fecha_entrega_estimada'])) ?></strong>.
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($esAsistido): ?>
                        <div class="alert alert-primary border-0 shadow-sm d-flex align-items-center mb-4 text-start bg-primary bg-opacity-10">
                            <i class="bi bi-camera-fill fs-3 text-primary me-3"></i>
                            <small class="text-dark"><strong>¡Importante!</strong> Ve al detalle de este pedido para adjuntar la foto de la boleta o voucher de Transbank.</small>
                        </div>
                    <?php elseif ($esContraEntrega): ?>
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
                        <?php if ($esAsistido): ?>
                            <a href="<?= BASE_URL ?>admin/pedido/ver/<?= $pedido['id'] ?>" class="btn btn-primary px-4 py-2 fw-bold rounded-pill shadow-sm">
                                <i class="bi bi-cloud-arrow-up-fill me-2"></i>Subir Comprobante
                            </a>
                            <a href="<?= BASE_URL ?>home/catalogo" class="btn btn-outline-cenco-indigo px-4 py-2 fw-bold rounded-pill">
                                <i class="bi bi-cart-plus me-2"></i>Nueva Venta
                            </a>
                        <?php else: ?>
                            <?php if (!empty($_SESSION['user_id'])): ?>
                                <a href="<?= BASE_URL ?>perfil?tab=pedidos" class="btn btn-outline-cenco-indigo px-4 py-2 fw-bold rounded-pill">
                                    <i class="bi bi-list-ul me-2"></i>Ver mis pedidos
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-cenco-indigo px-4 py-2 fw-bold rounded-pill" onclick="window.print()">
                                    <i class="bi bi-printer-fill me-2"></i>Imprimir Comprobante
                                </button>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>home" class="btn btn-cenco-green px-4 py-2 fw-bold rounded-pill">
                                <i class="bi bi-house-door me-2"></i>Volver al inicio
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>