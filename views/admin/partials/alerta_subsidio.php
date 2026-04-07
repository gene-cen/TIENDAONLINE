<div class="alert border-warning bg-warning bg-opacity-10 d-flex align-items-center mb-4">
    <i class="bi bi-info-circle-fill text-warning fs-3 me-3"></i>
    <div>
        <h6 class="fw-bold mb-1 text-dark">Diferencia Asumida por Reemplazo de Stock</h6>
        <p class="mb-0 small text-muted">
            Este pedido contiene productos de reemplazo de mayor valor. Transbank cobró <strong>$<?= number_format($pedido['monto_total'] - $pedido['subsidio_empresa'], 0, ',', '.') ?></strong>. La diferencia de <strong>$<?= number_format($pedido['subsidio_empresa'], 0, ',', '.') ?></strong> debe ser pasada en caja usando el medio de pago "Subsidio Reemplazo Web".
        </p>
    </div>
</div>