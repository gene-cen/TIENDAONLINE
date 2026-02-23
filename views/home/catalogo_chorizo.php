<div class="container-fluid px-3 py-4 bg-light min-vh-100">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 sticky-top bg-light py-3 border-bottom shadow-sm" style="top: 80px; z-index: 900;">
        <div class="mb-3 mb-md-0">
            <h4 class="fw-black text-cenco-indigo mb-0">
                <i class="bi bi-images me-2"></i>Catálogo Visual
            </h4>
            <small class="text-muted">
                <?= number_format($total_registros, 0, ',', '.') ?> fotos listadas
            </small>
        </div>
        
        <form class="d-flex shadow-sm rounded-pill overflow-hidden" action="<?= BASE_URL ?>home/catalogo_chorizo" method="GET" style="max-width: 400px; width: 100%;">
            <input class="form-control border-0 px-4" type="search" name="q" placeholder="Buscar por nombre..." value="<?= htmlspecialchars($busqueda) ?>">
            <button class="btn btn-cenco-indigo px-4" type="submit"><i class="bi bi-search text-white"></i></button>
        </form>
    </div>

    <div class="row g-2 g-md-3 row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 row-cols-xl-6">
        <?php foreach ($productos as $prod): 
            // Lógica para detectar si la imagen es URL externa (cencocal.cl) o local
            $img = !empty($prod->imagen) 
                ? (strpos($prod->imagen, 'http') === 0 ? $prod->imagen : BASE_URL.'img/productos/'.$prod->imagen) 
                : BASE_URL.'img/no-photo_small.png';
            
            $nombre = $prod->nombre_mostrar ?? $prod->nombre;
        ?>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm">
                
                <div class="ratio ratio-1x1 bg-white rounded-top">
                    <img src="<?= $img ?>" 
                         class="card-img-top object-fit-contain p-2" 
                         loading="lazy" 
                         alt="<?= htmlspecialchars($nombre) ?>" 
                         onerror="this.src='<?= BASE_URL ?>img/no-image.png'">
                </div>

                <div class="card-body p-2 text-center d-flex flex-column justify-content-center">
                    <small class="text-muted opacity-50" style="font-size: 0.65rem;"><?= $prod->cod_producto ?></small>
                    
                    <h6 class="card-title fw-bold text-dark mb-0 mt-1" style="font-size: 0.85rem; line-height: 1.3;">
                        <?= htmlspecialchars($nombre) ?>
                    </h6>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-center py-5 text-muted small">
        --- Fin del listado ---
    </div>

</div>