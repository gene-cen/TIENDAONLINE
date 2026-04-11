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
                <?php foreach ($_GET as $k => $v): if ($k != 'q' && $k != 'p') : ?><input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($v) ?>"><?php endif; endforeach; ?>
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
                <?php foreach ($_GET as $k => $v): if (!in_array($k, ['min_price', 'max_price', 'p'])) : ?><input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($v) ?>"><?php endif; endforeach; ?>
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
                <a href="<?= BASE_URL ?>home/catalogo" class="filter-link <?= !isset($_GET['categoria']) ? 'active' : '' ?>"><i class="bi bi-grid-fill me-2 small"></i> Todas</a>
                <?php foreach ($categorias as $cat):
                    $isActive = (isset($_GET['categoria']) && $_GET['categoria'] === $cat['nombre']) ? 'active' : '';
                    $params = $_GET; unset($params['marca'], $params['q']);
                    $params['categoria'] = $cat['nombre']; $params['p'] = 1;
                ?>
                    <a href="?<?= http_build_query($params) ?>" class="filter-link <?= $isActive ?>"><?= htmlspecialchars($cat['nombre']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mb-4">
            <h6 class="filter-title">Marcas</h6>
            <div style="max-height: 250px; overflow-y: auto;" class="custom-scrollbar pe-2">
                <?php if (empty($marcasList)): ?>
                    <small class="text-muted fst-italic">Selecciona categoría para ver marcas.</small>
                <?php else: foreach ($marcasList as $m):
                        $isActive = (isset($_GET['marca']) && $_GET['marca'] === $m['nombre']) ? 'active' : '';
                        $params = $_GET; $params['marca'] = $m['nombre']; $params['p'] = 1;
                ?>
                    <a href="?<?= http_build_query($params) ?>" class="filter-link <?= $isActive ?>"><?= htmlspecialchars($m['nombre']) ?></a>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</aside>