<div class="container py-4">
    
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>home">Inicio</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($nombre_categoria) ?></li>
        </ol>
    </nav>

    <h2 class="mb-4"><?= htmlspecialchars($nombre_categoria) ?> <small class="text-muted fs-6">(<?= $total_productos ?> productos)</small></h2>

    <?php if (empty($productos)): ?>
        <div class="alert alert-warning text-center">
            No hay productos en esta categoría por el momento.
        </div>
    <?php else: ?>
        
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-4">
            <?php foreach ($productos as $prod): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-0 producto-card">
                        <img src="<?= !empty($prod->imagen) ? BASE_URL . 'img/productos/' . $prod->imagen : 'https://via.placeholder.com/300' ?>" 
                             class="card-img-top p-3" 
                             alt="<?= htmlspecialchars($prod->nombre) ?>"
                             style="height: 200px; object-fit: contain;">
                        
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title text-truncate" title="<?= htmlspecialchars($prod->nombre) ?>">
                                <?= htmlspecialchars($prod->nombre) ?>
                            </h6>
                            <p class="card-text fw-bold text-primary mb-3">
                                $<?= number_format($prod->precio, 0, ',', '.') ?>
                            </p>
                            
                            <a href="<?= BASE_URL ?>producto/ver/<?= $prod->id ?>" class="btn btn-outline-primary btn-sm mt-auto w-100">
                                Ver Detalle
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_paginas > 1): ?>
            <nav class="mt-5">
                <ul class="pagination justify-content-center">
                    
                    <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= BASE_URL ?>categoria/<?= $id ?>?p=<?= $pagina_actual - 1 ?>">Anterior</a>
                    </li>

                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?= ($i == $pagina_actual) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= BASE_URL ?>categoria/<?= $id ?>?p=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= BASE_URL ?>categoria/<?= $id ?>?p=<?= $pagina_actual + 1 ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    <?php endif; ?>
</div>

<style>
    /* Efecto hover suave para las tarjetas */
    .producto-card:hover {
        transform: translateY(-5px);
        transition: transform 0.2s;
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
</style>