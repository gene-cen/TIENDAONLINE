<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-0"><i class="bi bi-radar text-danger me-2"></i>Radar de Stock Fantasma</h2>
            <p class="text-muted small mt-1">Productos que fueron cobrados pero no estaban físicamente en bodega.</p>
        </div>
    </div>

    <?php if (empty($alertas)): ?>
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <div class="card-body">
                <i class="bi bi-shield-check display-1 text-success opacity-50 mb-3"></i>
                <h4 class="fw-bold text-cenco-indigo">¡Inventario Sano!</h4>
                <p class="text-muted">No hay alertas de stock fantasma pendientes de revisión.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light border-bottom">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold text-uppercase border-0">Fecha / Pedido</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0">Producto Afectado</th>
                            <th class="text-center py-3 text-muted small fw-bold text-uppercase border-0">Faltaron</th>
                            <th class="text-center py-3 text-muted small fw-bold text-uppercase border-0">Stock Actual ERP</th>
                            <th class="text-end pe-4 py-3 text-muted small fw-bold text-uppercase border-0">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alertas as $alerta): 
                            // Si el sistema todavía dice que hay stock, es un foco rojo
                            $alertaCritica = ($alerta['stock_sistema'] > 0);
                        ?>
                            <tr id="alerta-<?= $alerta['id'] ?>">
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= date('d/m/Y H:i', strtotime($alerta['fecha'])) ?></div>
                                    <a href="<?= BASE_URL ?>admin/pedido/ver/<?= $alerta['pedido_id'] ?>" class="text-decoration-none small fw-bold text-cenco-indigo">
                                        Ver Pedido #<?= str_pad($alerta['pedido_id'], 6, '0', STR_PAD_LEFT) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-bold text-cenco-indigo"><?= htmlspecialchars($alerta['nombre_producto']) ?></div>
                                    <small class="text-muted">COD: <?= htmlspecialchars($alerta['cod_producto']) ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger rounded-pill fs-6 px-3">-<?= $alerta['cantidad_faltante'] ?> un.</span>
                                </td>
                                <td class="text-center">
                                    <?php if ($alertaCritica): ?>
                                        <span class="badge bg-warning text-dark border border-warning shadow-sm animate__animated animate__pulse animate__infinite">
                                            ¡ERP marca <?= $alerta['stock_sistema'] ?> un!
                                        </span>
                                    <?php else: ?>
                                        <span class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Ya está en 0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-success fw-bold rounded-pill" onclick="resolverAlerta(<?= $alerta['id'] ?>)">
                                        <i class="bi bi-check2-all me-1"></i> Resuelto
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function resolverAlerta(id) {
    const fd = new FormData();
    fd.append('id', id);

    fetch('<?= BASE_URL ?>admin/stock_fantasma/resolver', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            const fila = document.getElementById('alerta-' + id);
            fila.classList.add('opacity-25', 'bg-light'); // Efecto visual de atenuado
            setTimeout(() => fila.remove(), 500);
            
            // Recargar si ya no quedan alertas
            if (document.querySelectorAll('tbody tr').length <= 1) {
                setTimeout(() => location.reload(), 600);
            }
        }
    });
}
</script>