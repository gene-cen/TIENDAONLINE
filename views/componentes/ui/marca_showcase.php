<?php
// views/componentes/ui/marca_showcase.php

if (!function_exists('renderMarcaShowcase')) {
    function renderMarcaShowcase($marca)
    {
        if (empty($marca) || empty($marca['productos'])) return '';

        $urlImgMarca = str_starts_with($marca['ruta_imagen'], 'http') ? $marca['ruta_imagen'] : BASE_URL . ltrim($marca['ruta_imagen'], '/');
        ob_start();
?>
        <div class="container-fluid px-3 px-xl-5 mb-5 mt-5">
            <div class="row g-0 rounded-4 shadow-sm overflow-hidden border border-light">

                <div class="col-lg-4 col-md-4 d-flex flex-column align-items-center justify-content-center p-4 p-lg-5 position-relative" style="background: linear-gradient(135deg, var(--cenco-indigo) 0%, var(--cenco-green) 100%);">
                    
                    <div class="mb-3 hover-scale transition-hover" style="width: 100%; max-width: 320px; height: 220px; display:flex; align-items:center; justify-content:center;"> 
                        <img src="<?= $urlImgMarca ?>" 
                             alt="<?= htmlspecialchars($marca['nombre']) ?>" 
                             class="img-fluid" 
                             style="max-height: 100%; width: auto; object-fit: contain; filter: drop-shadow(0 10px 15px rgba(0,0,0,0.15));" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        
                        <span class="fw-black text-white" style="display:none; font-size:1.8rem; text-align:center;"><?= htmlspecialchars($marca['nombre']) ?></span>
                    </div>

       

                    <a href="<?= BASE_URL ?>home/catalogo?marca=<?= urlencode($marca['nombre']) ?>" class="btn btn-warning rounded-pill fw-bold px-4 shadow-sm hover-scale transition-hover text-dark" style="font-size: 0.95rem;">
                        Ver todo <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>

                <div class="col-lg-8 col-md-8 p-4 position-relative" style="background-color: rgba(13, 202, 240, 0.08);">
                    <div class="row row-cols-2 row-cols-md-2 row-cols-xl-4 g-3">
                        <?php foreach (array_slice($marca['productos'], 0, 4) as $p) {
                            include __DIR__ . '/../../home/partials/tarjeta_producto.php';
                        } ?>
                    </div>
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
?>