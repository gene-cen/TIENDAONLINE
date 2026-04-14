<aside class="col-lg-3 col-xl-2 d-none d-lg-block">
    <div class="filter-sidebar sticky-top" style="top: 120px; z-index: 10;">

        <?php if (!empty($_GET)): ?>
            <a href="<?= BASE_URL ?>home/catalogo" class="btn btn-cenco-red-outline btn-sm w-100 mb-4 fw-bold rounded-pill shadow-sm transition-hover">
                <i class="bi bi-trash3-fill me-2"></i> Limpiar Filtros
            </a>
        <?php endif; ?>

        <div class="mb-4">
            <h6 class="filter-title-custom">¿Qué buscas hoy?</h6>
            <form action="" method="GET">
                <?php foreach ($_GET as $k => $v): if ($k != 'q' && $k != 'p') : ?><input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($v) ?>"><?php endif; endforeach; ?>
                <div class="input-group input-group-sm shadow-sm rounded-pill overflow-hidden border">
                    <input type="text" name="q" class="form-control border-0 ps-3" placeholder="Ej: Arroz, Aceite..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    <button class="btn btn-white border-0 text-primary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>

        <div class="mb-4">
            <h6 class="filter-title-custom">Categorías</h6>
            <div class="category-menu custom-scrollbar pe-2" style="max-height: 350px; overflow-y: auto;">
                <a href="<?= BASE_URL ?>home/catalogo" class="cat-item <?= !isset($_GET['categoria']) ? 'active' : '' ?>">
                    <i class="bi bi-grid-fill"></i> Todo el Catálogo
                </a>
                <?php foreach ($categorias as $cat):
                    $catNombre = is_object($cat) ? $cat->nombre : $cat['nombre'];
                    $isActive = (isset($_GET['categoria']) && $_GET['categoria'] === $catNombre);
                    $params = $_GET; unset($params['marca'], $params['q'], $params['p']);
                    $params['categoria'] = $catNombre;
                ?>
                    <a href="?<?= http_build_query($params) ?>" class="cat-item <?= $isActive ? 'active' : '' ?>">
                        <i class="bi bi-chevron-right small"></i> <?= htmlspecialchars($catNombre) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mb-4">
            <h6 class="filter-title-custom">Precio</h6>
            <div id="price-slider" class="mt-4 mb-3 mx-2"></div>
            <form action="" method="GET" id="formPrecio">
                <?php foreach ($_GET as $k => $v): if (!in_array($k, ['min_price', 'max_price', 'p'])) : ?><input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($v) ?>"><?php endif; endforeach; ?>
                <input type="hidden" name="min_price" id="input-min">
                <input type="hidden" name="max_price" id="input-max">
                <div class="d-flex justify-content-between mb-3 x-small fw-bold">
                    <span id="label-min" class="badge bg-light text-dark border p-2">$0</span>
                    <span id="label-max" class="badge bg-light text-dark border p-2">$1M</span>
                </div>
                <button type="submit" class="btn btn-sm btn-cenco-indigo w-100 rounded-pill fw-bold">Filtrar por Precio</button>
            </form>
        </div>

        <div class="mb-4">
            <h6 class="filter-title-custom">Marcas destacadas</h6>
            <div class="d-flex flex-wrap gap-2 custom-scrollbar pe-2" style="max-height: 250px; overflow-y: auto;">
                <?php if (empty($marcasList)): ?>
                    <small class="text-muted fst-italic">Selecciona una categoría primero.</small>
                <?php else: foreach ($marcasList as $m):
                    $mNombre = is_object($m) ? $m->nombre : $m['nombre'];
                    $isActive = (isset($_GET['marca']) && $_GET['marca'] === $mNombre);
                    $params = $_GET; $params['marca'] = $mNombre; $params['p'] = 1;
                ?>
                    <a href="?<?= http_build_query($params) ?>" class="brand-pill <?= $isActive ? 'active' : '' ?>">
                        <?php if($isActive): ?><i class="bi bi-check-circle-fill me-1"></i><?php endif; ?>
                        <?= htmlspecialchars($mNombre) ?>
                    </a>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</aside>