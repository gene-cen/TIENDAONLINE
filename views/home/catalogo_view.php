<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>css/shop/catalogo.css">

<div class="container-fluid container-fluid-catalogo py-4">

    <button class="btn btn-cenco-indigo d-lg-none w-100 mb-3 fw-bold" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasFiltros">
        <i class="bi bi-funnel-fill me-2"></i> Filtrar Productos
    </button>

    <div class="row g-4">
        <?php include __DIR__ . '/partials/catalogo_sidebar.php'; ?>

<main class="col-lg-9 col-xl-10">
    <?php if (isset($_GET['categoria']) && !empty($_GET['categoria'])): 
        $catActual = $_GET['categoria'];
        // Limpiamos el nombre para buscar la imagen física
        $imgName = strtolower(str_replace([' ', 'á','é','í','ó','ú'], ['_','a','e','i','o','u'], trim($catActual)));
        $urlBannerCat = BASE_URL . "img/banners_categorias/" . $imgName . ".jpg";
    ?>
        <div class="mb-4 position-relative rounded-4 overflow-hidden shadow-sm d-flex align-items-center" style="height: 180px; background-color: var(--cenco-indigo);">
            <img src="<?= $urlBannerCat ?>" 
                 class="position-absolute w-100 h-100 object-fit-cover z-1" 
                 alt="<?= htmlspecialchars($catActual) ?>"
                 onerror="this.style.display='none'; document.getElementById('fallback-banner-<?= $imgName ?>').classList.replace('d-none', 'd-flex');">
            
            <div id="fallback-banner-<?= $imgName ?>" 
                 class="position-absolute w-100 h-100 top-0 start-0 z-0 d-none flex-column justify-content-center px-4 px-md-5" 
                 style="background: linear-gradient(135deg, var(--cenco-indigo) 0%, #3e2b85 100%);">
                <h2 class="fw-black text-white mb-1 ls-1 text-uppercase" style="font-size: 2.2rem;"><?= htmlspecialchars($catActual) ?></h2>
                <p class="text-white opacity-75 mb-0 fw-bold">Explora nuestra mejor selección de productos.</p>
            </div>
        </div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm border border-light">
        <div class="text-center text-md-start mb-3 mb-md-0">
            <h4 class="fw-black text-cenco-indigo mb-0 ls-1" style="font-size: 1.2rem;">
                <?= htmlspecialchars($titulo) ?>
            </h4>
            <span class="text-muted small">
                Mostrando <strong><?= count($productos) ?></strong> de <strong><?= $total_registros ?></strong> productos encontrados
            </span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <label class="small fw-bold text-muted text-nowrap d-none d-sm-block">Ordenar por:</label>
            <select class="form-select form-select-sm border-0 bg-light fw-bold text-cenco-indigo rounded-pill px-3" 
                    style="width: 210px; cursor: pointer;" 
                    onchange="location = this.value;">
                <?php 
                    $urlOrder = $_GET; 
                    unset($urlOrder['orden']); 
                    $base = BASE_URL . 'home/catalogo?' . http_build_query($urlOrder) . '&orden='; 
                ?>
                <option value="<?= $base ?>relevancia" <?= ($orden == 'relevancia') ? 'selected' : '' ?>>✨ Relevancia</option>
                <option value="<?= $base ?>precio_asc" <?= ($orden == 'precio_asc') ? 'selected' : '' ?>>💰 Precio: Menor a Mayor</option>
                <option value="<?= $base ?>precio_desc" <?= ($orden == 'precio_desc') ? 'selected' : '' ?>>💰 Precio: Mayor a Menor</option>
                <option value="<?= $base ?>nombre_asc" <?= ($orden == 'nombre_asc') ? 'selected' : '' ?>>🔤 Nombre (A-Z)</option>
            </select>
        </div>
    </div>

    <?php if (empty($productos)): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm border border-light">
            <div class="mb-3">
                <i class="bi bi-search text-muted opacity-25" style="font-size: 5rem;"></i>
            </div>
            <h3 class="fw-bold text-cenco-indigo">¡Ups! No hay resultados</h3>
            <p class="text-muted">Intenta ajustando los filtros o buscando otro término.</p>
            <a href="<?= BASE_URL ?>home/catalogo" class="btn btn-cenco-green text-white rounded-pill px-5 mt-2 fw-bold shadow-sm">
                Ver todo el catálogo
            </a>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 g-3">
            <?php foreach ($productos as $p) { 
                include __DIR__ . '/partials/tarjeta_producto.php'; 
            } ?>
        </div>

        <?php if ($total_paginas > 1): ?>
            <div class="mt-5">
                <?php include __DIR__ . '/partials/catalogo_paginacion.php'; ?> 
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.js"></script>
<script>
    // Inyectamos las variables de PHP al JS
    window.CatalogoConfig = {
        minGlobal: <?= $rangoPrecio['min'] ?? 0 ?>,
        maxGlobal: <?= $rangoPrecio['max'] ?? 1000000 ?>,
        currentMin: <?= $_GET['min_price'] ?? $rangoPrecio['min'] ?? 0 ?>,
        currentMax: <?= $_GET['max_price'] ?? $rangoPrecio['max'] ?? 1000000 ?>
    };
</script>
<script src="<?= BASE_URL ?>js/shop/catalogo.js"></script>