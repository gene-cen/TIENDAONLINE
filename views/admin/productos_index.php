<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary">📦 Gestión de Productos</h2>
        <div>
            <a href="<?= BASE_URL ?>admin/importar_erp" class="btn btn-primary btn-sm me-2">
                <i class="bi bi-arrow-repeat"></i> Sincronizar ERP
            </a>
            <a href="<?= BASE_URL ?>admin/producto/crear" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Nuevo
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Imagen</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Stock</th>

                            <th>Estado</th>

                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $prod): ?>
                            <tr>
                                <td class="ps-4">
                                    <?php
                                    if (empty($prod->imagen)) {
                                        $rutaImagen = BASE_URL . 'img/no-photo_small.png';
                                    } elseif (strpos($prod->imagen, 'http') === 0) {
                                        $rutaImagen = $prod->imagen;
                                    } else {
                                        $rutaImagen = BASE_URL . 'img/productos/' . $prod->imagen;
                                    }
                                    ?>
                                    <img src="<?= $rutaImagen ?>" class="rounded border" style="width: 50px; height: 50px; object-fit: cover;">
                                </td>

                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($prod->nombre) ?></div>
                                    <small class="text-muted text-truncate d-block" style="max-width: 250px;">
                                        <?= htmlspecialchars($prod->categoria ?? '') ?>
                                    </small>
                                </td>

                                <td class="fw-bold text-success">
                                    $<?= number_format($prod->precio, 0, ',', '.') ?>
                                </td>

                                <td>
                                    <?php if (($prod->stock ?? 0) > 10): ?>
                                        <span class="badge bg-success"><?= $prod->stock ?? 0 ?> un.</span>
                                    <?php elseif (($prod->stock ?? 0) > 0): ?>
                                        <span class="badge bg-warning text-dark"><?= $prod->stock ?? 0 ?> un.</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Sin Stock</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($prod->activo): ?>
                                        <span class="badge bg-success">Visible</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Oculto</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end pe-4">

                                    <a href="<?= BASE_URL ?>admin/producto/toggle/<?= $prod->id ?>"
                                        class="btn btn-sm me-1 <?= $prod->activo ? 'btn-outline-success' : 'btn-outline-secondary' ?>"
                                        title="<?= $prod->activo ? 'Ocultar en web' : 'Mostrar en web' ?>">
                                        <i class="bi <?= $prod->activo ? 'bi-eye-fill' : 'bi-eye-slash-fill' ?>"></i>
                                    </a>

                                    <a href="<?= BASE_URL ?>admin/producto/editar/<?= $prod->id ?>"
                                        class="btn btn-sm btn-outline-primary me-1" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <button onclick="confirmarEliminar(<?= $prod->id ?>)"
                                        class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div> <?php if ($total_paginas > 1): ?>
                <nav aria-label="Navegación de productos" class="mt-4">
                    <ul class="pagination justify-content-center">

                        <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= BASE_URL ?>admin/productos?page=<?= $pagina_actual - 1 ?>" tabindex="-1">Anterior</a>
                        </li>

                        <?php
                        $rango = 2; // Cuántos números mostrar alrededor de la página actual
                        for ($i = 1; $i <= $total_paginas; $i++):
                            if ($i == 1 || $i == $total_paginas || ($i >= $pagina_actual - $rango && $i <= $pagina_actual + $rango)):
                        ?>
                                <li class="page-item <?= ($pagina_actual == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= BASE_URL ?>admin/productos?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php elseif ($i == $pagina_actual - $rango - 1 || $i == $pagina_actual + $rango + 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif;
                        endfor; ?>

                        <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= BASE_URL ?>admin/productos?page=<?= $pagina_actual + 1 ?>">Siguiente</a>
                        </li>
                    </ul>
                </nav>

                <div class="text-center text-muted small mt-2">
                    Mostrando página <?= $pagina_actual ?> de <?= $total_paginas ?> (Total: <?= $total_registros ?> productos)
                </div>
            <?php endif; ?>

        </div>
    </div>

</div>
</div>
</div>

<script>
    function confirmarEliminar(id) {
        if (confirm('¿Estás segura de que deseas eliminar este producto?')) {
            window.location.href = '<?= BASE_URL ?>admin/producto/eliminar/' + id;
        }
    }
</script>