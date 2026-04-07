<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-3 mb-1">
            <h2 class="fw-black text-cenco-indigo mb-0">Pedido #<?= str_pad($idPedido, 6, '0', STR_PAD_LEFT) ?></h2>
            <span class="badge rounded-pill <?= $badgeClass ?? 'bg-secondary' ?> px-3 py-2 text-uppercase ls-1">
                <?= htmlspecialchars($pedido['estado'] ?? $estadoStr ?? 'Pendiente') ?>
            </span>
        </div>
        <p class="text-muted mb-0">
            Realizado el <strong><?= date('d/m/Y', strtotime($pedido['fecha_creacion'] ?? 'now')) ?></strong> a las <?= date('H:i', strtotime($pedido['hora_creacion'] ?? 'now')) ?>
        </p>
    </div>

    <div class="d-flex gap-2 mt-3 mt-md-0">
        <?php if ($estadoIdActual != 6): ?>
            <button class="btn btn-outline-danger shadow-sm fw-bold px-3" onclick="confirmarAnulacionReembolso()">
                <i class="bi bi-x-octagon-fill me-2"></i> Anular y Reembolsar
            </button>
        <?php endif; ?>

        <?php
        // Condición para permitir la edición
        $puedeEditar = in_array($estadoIdActual, [1, 2, 3]) && ($estadoWebpay ?? '') !== 'capturado' && ($estadoWebpay ?? '') !== 'captured';

        if ($puedeEditar): ?>
            <button class="btn btn-cenco-indigo text-white fw-bold shadow-sm px-3"
                onclick="abrirModalEdicion()"
                style="background-color: #2A1B5E !important; border-color: #2A1B5E !important;">
                <i class="bi bi-pencil-square me-2"></i> Editar Pedido
            </button>
        <?php else: ?>
            <button class="btn btn-light border text-muted fw-bold px-3"
                disabled
                title="No se puede editar: El pago ya fue capturado o el pedido está en ruta/entregado.">
                <i class="bi bi-lock-fill me-2"></i> Editar Pedido
            </button>
        <?php endif; ?>

        <button class="btn btn-white border shadow-sm text-cenco-indigo fw-bold px-3" onclick="window.print()">
            <i class="bi bi-printer me-2"></i> Imprimir
        </button>
    </div>
</div>