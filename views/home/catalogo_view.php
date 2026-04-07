<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.js"></script>



<style>
    /* --- ESTILOS VISUALES --- */

    .container-fluid-catalogo {

        padding-left: 2rem;

        padding-right: 2rem;

    }



    .filter-sidebar {

        background-color: #fff;

        border-right: 1px solid #eee;

        height: 100%;

        padding-right: 20px;

    }



    .filter-title {

        font-weight: 800;

        color: var(--cenco-indigo);

        margin-bottom: 12px;

        font-size: 0.9rem;

        text-transform: uppercase;

        letter-spacing: 0.5px;

    }



    .custom-scrollbar {

        scrollbar-width: thin;

        scrollbar-color: var(--cenco-green) #f1f1f1;

    }



    .custom-scrollbar::-webkit-scrollbar {

        width: 6px;

    }



    .custom-scrollbar::-webkit-scrollbar-thumb {

        background-color: var(--cenco-green);

        border-radius: 10px;

    }



    .filter-link {

        color: #666;

        text-decoration: none;

        display: block;

        padding: 4px 0;

        transition: all 0.2s;

        font-size: 0.95rem;

    }



    .filter-link:hover,

    .filter-link.active {

        color: var(--cenco-green);

        font-weight: bold;

        padding-left: 5px;

    }



    /* Slider */

    .noUi-connect {

        background: var(--cenco-indigo);

    }



    .noUi-horizontal {

        height: 6px;

        border: none;

        background: #e0e0e0;

        border-radius: 5px;

    }



    .noUi-handle {

        width: 18px;

        height: 18px;

        right: -9px;

        top: -7px;

        border-radius: 50%;

        border: 2px solid var(--cenco-indigo);

        background: var(--cenco-green);

        box-shadow: none;

        cursor: pointer;

    }



    .noUi-handle::before,

    .noUi-handle::after {

        display: none;

    }



    .noUi-handle:hover {

        transform: scale(1.2);

        transition: transform 0.2s;

    }



    /* Cards */

    .card-prod {

        border: 1px solid #f0f0f0;

        transition: all 0.3s ease;

    }



    .card-prod:hover {

        border-color: var(--cenco-green);

        transform: translateY(-5px);

        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);

        z-index: 2;

    }



    .card-img-wrapper {

        height: 280px;

        padding: 20px;

        display: flex;

        align-items: center;

        justify-content: center;

    }



    .card-img-wrapper img {

        max-height: 100%;

        max-width: 100%;

        object-fit: contain;

        transition: transform 0.3s;

    }



    .card-prod:hover .card-img-wrapper img {

        transform: scale(1.05);

    }



    .page-link {

        color: var(--cenco-indigo);

        border: none;

        margin: 0 3px;

        border-radius: 8px;

        font-weight: bold;

    }



    .page-item.active .page-link {

        background-color: var(--cenco-green);

        color: white;

    }
</style>



<div class="container-fluid container-fluid-catalogo py-4">



    <button class="btn btn-cenco-indigo d-lg-none w-100 mb-3 fw-bold" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasFiltros">

        <i class="bi bi-funnel-fill me-2"></i> Filtrar Productos

    </button>



    <div class="row g-4">



        <aside class="col-lg-3 col-xl-2 d-none d-lg-block">

            <div class="filter-sidebar sticky-top" style="top: 100px; z-index: 1;">



                <?php if (!empty($_GET)): ?>

                    <a href="<?= BASE_URL ?>home/catalogo" class="btn btn-outline-danger btn-sm w-100 mb-4 fw-bold">

                        <i class="bi bi-trash3-fill me-2"></i> Limpiar Filtros

                    </a>

                <?php endif; ?>



                <div class="mb-4">

                    <h6 class="filter-title">Buscar Producto</h6>

                    <form action="" method="GET">

                        <?php foreach ($_GET as $k => $v): if ($k != 'q' && $k != 'p') : ?><input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($v) ?>"><?php endif;

                                                                                                                                                                endforeach; ?>



                        <div class="input-group">

                            <input type="text" name="q" class="form-control form-control-sm border-end-0" placeholder="Ej: Arroz..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">

                            <button class="btn btn-sm btn-outline-secondary border-start-0" type="submit"><i class="bi bi-search"></i></button>

                        </div>

                    </form>

                </div>



                <div class="mb-5">

                    <h6 class="filter-title d-flex justify-content-between">Precio <i class="bi bi-chevron-up small"></i></h6>

                    <div id="price-slider" class="mt-4 mb-3 mx-2"></div>

                    <form action="" method="GET" id="formPrecio">

                        <?php foreach ($_GET as $k => $v): if (!in_array($k, ['min_price', 'max_price', 'p'])) : ?><input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($v) ?>"><?php endif;

                                                                                                                                                                                        endforeach; ?>

                        <input type="hidden" name="min_price" id="input-min">

                        <input type="hidden" name="max_price" id="input-max">

                        <div class="d-flex justify-content-between small fw-bold text-cenco-indigo mb-2">

                            <span id="label-min">$0</span>

                            <span id="label-max">$1.000.000</span>

                        </div>

                        <button type="submit" class="btn btn-sm btn-cenco-indigo w-100 rounded-pill">Aplicar Precio</button>

                    </form>

                </div>



                <div class="mb-4">

                    <h6 class="filter-title">Categorías</h6>

                    <div style="max-height: 300px; overflow-y: auto;" class="custom-scrollbar pe-2">

                        <a href="<?= BASE_URL ?>home/catalogo" class="filter-link <?= !isset($_GET['categoria']) ? 'active' : '' ?>">

                            <i class="bi bi-grid-fill me-2 small"></i> Todas

                        </a>

                        <?php foreach ($categorias as $cat):

                            $isActive = (isset($_GET['categoria']) && $_GET['categoria'] === $cat['nombre']) ? 'active' : '';



                            // --- AQUÍ ESTÁ EL CAMBIO IMPORTANTE ---

                            // 1. Tomamos los parámetros actuales

                            $params = $_GET;



                            // 2. BORRAMOS 'marca' y 'q' para reiniciar la búsqueda al cambiar de categoría

                            unset($params['marca']);

                            unset($params['q']);



                            // 3. Asignamos la nueva categoría y reseteamos página

                            $params['categoria'] = $cat['nombre'];

                            $params['p'] = 1;



                            $url = '?' . http_build_query($params);

                        ?>

                            <a href="<?= $url ?>" class="filter-link <?= $isActive ?>">

                                <?= htmlspecialchars($cat['nombre']) ?>

                            </a>

                        <?php endforeach; ?>

                    </div>

                </div>



                <div class="mb-4">

                    <h6 class="filter-title">Marcas</h6>

                    <div style="max-height: 250px; overflow-y: auto;" class="custom-scrollbar pe-2">

                        <?php if (empty($marcasList)): ?>

                            <small class="text-muted fst-italic">Selecciona una categoría para ver marcas.</small>

                        <?php else: ?>

                            <?php foreach ($marcasList as $m):

                                $isActive = (isset($_GET['marca']) && $_GET['marca'] === $m['nombre']) ? 'active' : '';



                                // Las marcas SÍ mantienen la categoría actual

                                $params = $_GET;

                                $params['marca'] = $m['nombre'];

                                $params['p'] = 1;

                                $url = '?' . http_build_query($params);

                            ?>

                                <a href="<?= $url ?>" class="filter-link <?= $isActive ?>">

                                    <?= htmlspecialchars($m['nombre']) ?>

                                </a>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                </div>



            </div>

        </aside>



        <main class="col-lg-9 col-xl-10">

            <?php if (isset($_GET['categoria']) && !empty($_GET['categoria'])): 
                $catActual = $_GET['categoria'];
                // Convertimos "Cuidado Bebé" a "cuidado_bebe" para que busque el archivo fácilmente
                $imgName = strtolower(str_replace([' ', 'á','é','í','ó','ú'], ['_','a','e','i','o','u'], trim($catActual)));
                
                // La ruta donde Eliseo debe subir las fotos (ej: public/img/banners_categorias/cuidado_bebe.jpg)
                $urlBannerCat = BASE_URL . "img/banners_categorias/" . $imgName . ".jpg";
            ?>
                <div class="mb-4 position-relative rounded-4 overflow-hidden shadow-sm d-flex align-items-center" style="height: 160px; background-color: var(--cenco-indigo);">
                    
                    <img src="<?= $urlBannerCat ?>" class="position-absolute w-100 h-100 object-fit-cover z-1" alt="Banner <?= htmlspecialchars($catActual) ?>" 
                         onerror="this.style.display='none'; document.getElementById('fallback-banner-<?= $imgName ?>').classList.remove('d-none'); document.getElementById('fallback-banner-<?= $imgName ?>').classList.add('d-flex');">
                    
                    <div id="fallback-banner-<?= $imgName ?>" class="position-absolute w-100 h-100 top-0 start-0 z-0 d-none flex-column justify-content-center px-4 px-md-5" style="background: linear-gradient(135deg, var(--cenco-indigo) 0%, var(--cenco-green) 100%);">
                        <h2 class="fw-black text-white mb-1 ls-1 text-uppercase" style="font-size: 2rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);"><?= htmlspecialchars($catActual) ?></h2>
                        <p class="text-white opacity-75 mb-0 fw-bold">Explora nuestra selección especial y descubre los mejores precios.</p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 bg-white p-3 rounded-3 shadow-sm border">
                <div>
                    <h4 class="fw-black text-cenco-indigo mb-0 ls-1"><?= htmlspecialchars($titulo) ?></h4>
                    <span class="text-muted small">Mostrando <strong><?= count($productos) ?></strong> de <?= $total_registros ?> resultados</span>
                </div>

                <div class="d-flex align-items-center gap-3 mt-3 mt-md-0">
                    <label class="small fw-bold text-muted text-nowrap">Ordenar por:</label>
                    <select class="form-select form-select-sm border-secondary-subtle fw-bold text-cenco-indigo bg-light" style="width: 200px;" onchange="location = this.value;">
                        <?php
                        $urlOrder = $_GET;
                        unset($urlOrder['orden']);
                        $base = BASE_URL . 'home/catalogo?' . http_build_query($urlOrder) . '&orden=';
                        ?>
                        <option value="<?= $base ?>relevancia" <?= ($orden == 'relevancia') ? 'selected' : '' ?>>Relevancia</option>
                        <option value="<?= $base ?>precio_asc" <?= ($orden == 'precio_asc') ? 'selected' : '' ?>>Precio: Menor a Mayor</option>
                        <option value="<?= $base ?>precio_desc" <?= ($orden == 'precio_desc') ? 'selected' : '' ?>>Precio: Mayor a Menor</option>
                        <option value="<?= $base ?>nombre_asc" <?= ($orden == 'nombre_asc') ? 'selected' : '' ?>>Nombre (A-Z)</option>
                    </select>
                </div>
            </div>

            <?php if (empty($productos)): ?>
                <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                    <div class="mb-3"><i class="bi bi-search text-muted opacity-25" style="font-size: 4rem;"></i></div>
                    <h3 class="fw-bold text-cenco-indigo">No encontramos resultados</h3>
                    <p class="text-muted">Intenta con otra búsqueda o ajusta los filtros.</p>
                    <a href="<?= BASE_URL ?>home/catalogo" class="btn btn-cenco-green rounded-pill px-5 fw-bold">Ver Todo</a>
                </div>
            <?php else: ?>

                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 g-3">
                    <?php foreach ($productos as $p):
                        $id = $p['id'];
                        $nombre = !empty($p['nombre_web']) ? $p['nombre_web'] : $p['nombre'];
                        $img = !empty($p['imagen']) ? $p['imagen'] : '';
                        $imgSrc = !empty($img) ? (strpos($img, 'http') === 0 ? $img : BASE_URL . 'img/productos/' . $img) : BASE_URL . 'img/no-image.png';
                        $enCarro = isset($_SESSION['carrito'][$id]);
                        $cant = $enCarro ? $_SESSION['carrito'][$id]['cantidad'] : 0;
                        $border = $enCarro ? 'border-cenco-green shadow' : 'card-prod';
                        
                        $esObjeto = is_object($p);
                        $stockDisponible = $esObjeto ? (int)($p->stock_web ?? $p->stock ?? 0) : (int)($p['stock_web'] ?? $p['stock'] ?? 0);
                        $estaAgotado = ($stockDisponible <= 0);
                    ?>

                        <div class="col">
                            <div class="card h-100 rounded-4 overflow-hidden <?= $border ?> <?= $estaAgotado ? 'opacity-75 bg-light' : '' ?>" id="card-prod-<?= $id ?>">

                                <a href="<?= BASE_URL ?>home/producto?id=<?= $id ?>" class="text-decoration-none">
                                    <div class="position-relative bg-white" id="img-container-<?= $id ?>">
                                        <div class="card-img-wrapper" style="<?= $estaAgotado ? 'filter: grayscale(100%);' : '' ?>">
                                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($nombre) ?>">
                                        </div>
                                        <?php if ($estaAgotado): ?>
                                            <span class="position-absolute top-50 start-50 translate-middle badge bg-danger fs-6 shadow">AGOTADO</span>
                                        <?php endif; ?>
                                    </div>
                                </a>

                                <div class="card-body d-flex flex-column p-3 bg-white border-top border-light">
                                    <small class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.7rem;"><?= htmlspecialchars($p['marca'] ?? '') ?></small>

                                    <a href="<?= BASE_URL ?>home/producto?id=<?= $id ?>" class="text-decoration-none">
                                        <h6 class="card-title fw-bold text-dark lh-sm mb-2 text-truncate-2" style="font-size: 0.95rem; height: 40px; overflow: hidden;" title="<?= htmlspecialchars($nombre) ?>">
                                            <?= htmlspecialchars($nombre) ?>
                                        </h6>
                                    </a>

                                    <div class="mb-2" style="font-size: 0.75rem;">
                                        <?php if ($estaAgotado): ?>
                                            <span class="text-danger fw-bold"><i class="bi bi-x-circle me-1"></i>Agotado</span>
                                        <?php else: ?>
                                            <span class="<?= $stockDisponible <= 7 ? 'text-danger fw-black' : 'text-success' ?>">
                                                <i class="bi bi-box-seam me-1"></i>Stock: <?= $stockDisponible ?> un.
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-auto pt-2 d-flex justify-content-between align-items-center border-top">
                                        <div class="d-flex flex-column">
                                            <span class="fw-black text-cenco-red fs-5">$<?= number_format($p['precio'], 0, ',', '.') ?></span>

                                            <?php if (!empty($p['precio_unidad_medida'])): ?>
                                                <small class="text-muted fw-normal" style="font-size: 0.65rem; margin-top: -3px;">
                                                    <?= htmlspecialchars($p['precio_unidad_medida']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>

                                        <div style="min-width: 90px; text-align: right;">
                                            <?php if ($estaAgotado): ?>
                                                <button class="btn btn-secondary rounded-circle shadow-sm d-inline-flex align-items-center justify-content-center" style="width:38px;height:38px;" disabled>
                                                    <i class="bi bi-cart-x fs-6"></i>
                                                </button>
                                            <?php else: ?>
                                                <form id="form-add-<?= $id ?>" class="<?= $enCarro ? 'd-none' : 'd-block' ?>" onsubmit="agregarAlCarrito(event, this, <?= $id ?>)">
                                                    <input type="hidden" name="id" value="<?= $id ?>">
                                                    <input type="hidden" name="nombre" value="<?= htmlspecialchars($nombre) ?>">
                                                    <input type="hidden" name="precio" value="<?= $p['precio'] ?>">
                                                    <input type="hidden" name="imagen" value="<?= $img ?>">
                                                    <button <?= $p['stock'] <= 0 ? 'disabled' : '' ?> class="btn btn-cenco-green rounded-circle shadow-sm d-inline-flex align-items-center justify-content-center" style="width:38px;height:38px;">
                                                        <i class="bi bi-plus-lg fs-5"></i>
                                                    </button>
                                                </form>

                                                <div id="controls-<?= $id ?>" class="align-items-center justify-content-end gap-1 <?= $enCarro ? 'd-flex' : 'd-none' ?>">
                                                    <button class="btn btn-outline-danger rounded-circle p-0 d-flex align-items-center justify-content-center" style="width:32px;height:32px;" onclick="gestionarClickTarjeta(<?= $id ?>, 'bajar')"><i class="bi bi-dash fs-5"></i></button>
                                                    <span class="fw-bold text-cenco-indigo px-1 fs-6" id="card-count-<?= $id ?>"><?= $cant ?></span>
                                                    <button class="btn btn-cenco-green rounded-circle p-0 d-flex align-items-center justify-content-center" style="width:32px;height:32px;" onclick="gestionarClickTarjeta(<?= $id ?>, 'subir')"><i class="bi bi-plus fs-5"></i></button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($total_paginas > 1): ?>
                        <div class="col-12 w-100">
                            <nav class="mt-5 border-top pt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= ($pagina <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= BASE_URL ?>home/catalogo?<?= http_build_query(array_merge($_GET, ['p' => $pagina - 1])) ?>"><i class="bi bi-chevron-left"></i></a>
                                    </li>
                                    <?php
                                    $rango = 2;
                                    $queryParams = $_GET;
                                    if ($pagina > $rango + 1) {
                                        $queryParams['p'] = 1;
                                        echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($queryParams) . '">1</a></li>';
                                        if ($pagina > $rango + 2) echo '<li class="page-item disabled"><span class="page-link border-0">...</span></li>';
                                    }
                                    for ($i = max(1, $pagina - $rango); $i <= min($total_paginas, $pagina + $rango); $i++) {
                                        $queryParams['p'] = $i;
                                        $active = ($i == $pagina) ? 'active' : '';
                                        echo '<li class="page-item ' . $active . '"><a class="page-link" href="?' . http_build_query($queryParams) . '">' . $i . '</a></li>';
                                    }
                                    if ($pagina < $total_paginas - $rango) {
                                        if ($pagina < $total_paginas - $rango - 1) echo '<li class="page-item disabled"><span class="page-link border-0">...</span></li>';
                                        $queryParams['p'] = $total_paginas;
                                        echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($queryParams) . '">' . $total_paginas . '</a></li>';
                                    }
                                    ?>
                                    <li class="page-item <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= BASE_URL ?>home/catalogo?<?= http_build_query(array_merge($_GET, ['p' => $pagina + 1])) ?>"><i class="bi bi-chevron-right"></i></a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>
        </main>

    </div>

</div>



<script>
    // Configuración del Slider (IGUAL AL ANTERIOR)

    document.addEventListener("DOMContentLoaded", function() {

        var slider = document.getElementById('price-slider');

        var minGlobal = <?= $rangoPrecio['min'] ?? 0 ?>;

        var maxGlobal = <?= $rangoPrecio['max'] ?? 1000000 ?>;

        var currentMin = <?= $_GET['min_price'] ?? $rangoPrecio['min'] ?? 0 ?>;

        var currentMax = <?= $_GET['max_price'] ?? $rangoPrecio['max'] ?? 1000000 ?>;



        noUiSlider.create(slider, {

            start: [currentMin, currentMax],

            connect: true,

            range: {

                'min': minGlobal,

                'max': maxGlobal

            },

            step: 100,

            format: {

                to: function(v) {

                    return Math.round(v)

                },

                from: function(v) {

                    return Number(v)

                }

            }

        });



        var inputMin = document.getElementById('input-min');

        var inputMax = document.getElementById('input-max');

        var labelMin = document.getElementById('label-min');

        var labelMax = document.getElementById('label-max');

        const formatter = new Intl.NumberFormat('es-CL', {

            style: 'currency',

            currency: 'CLP'

        });



        slider.noUiSlider.on('update', function(values, handle) {

            var value = values[handle];

            if (handle) {

                inputMax.value = value;

                labelMax.innerHTML = formatter.format(value);

            } else {

                inputMin.value = value;

                labelMin.innerHTML = formatter.format(value);

            }

        });

    });
</script>