<div class="offcanvas offcanvas-start rounded-end-4 shadow" tabindex="-1" id="offcanvasCategorias" aria-labelledby="offcanvasCategoriasLabel">
    
    <div class="offcanvas-header bg-cenco-indigo text-white border-bottom border-3" style="border-color: var(--cenco-green) !important;">
        <h5 class="offcanvas-title fw-black ls-1" id="offcanvasCategoriasLabel">
            <i class="bi bi-grid-3x3-gap-fill text-cenco-green me-2"></i> CATEGORÍAS
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-0 bg-light custom-scrollbar">
        <div class="list-group list-group-flush border-0 mt-2">
            
            <a href="<?= BASE_URL ?>home/catalogo" 
               class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white <?= !isset($_GET['categoria']) ? 'bg-white shadow-sm border-start border-4 border-cenco-indigo' : '' ?>">
                <div class="bg-white p-2 rounded-circle shadow-sm me-3 d-flex align-items-center justify-content-center text-cenco-green" style="width:40px;height:40px;">
                    <i class="bi bi-grid-fill fs-5"></i>
                </div>
                Ver Todo el Catálogo
            </a>

            <div class="px-4 py-3 text-uppercase text-muted fw-black" style="font-size: 0.65rem; letter-spacing: 2px; opacity: 0.7;">
                Nuestros Departamentos
            </div>

            <?php 
            // Si en este punto la variable se llama $categoriasMenu, cámbiala por $categorias 
            // para que usen la misma fuente de datos.
            $listaAMostrar = !empty($categorias) ? $categorias : (!empty($categoriasMenu) ? $categoriasMenu : []);
            
            if (!empty($listaAMostrar)): 
                foreach ($listaAMostrar as $cat):
                    $catNombre = is_object($cat) ? $cat->nombre : $cat['nombre'];
                    
                    // Detectar si esta categoría es la que el cliente está viendo
                    $isActive = (isset($_GET['categoria']) && $_GET['categoria'] === $catNombre);
                    
                    // Ícono dinámico
                    $icono = function_exists('obtenerIconoCategoria') ? obtenerIconoCategoria($catNombre) : 'bi bi-tag';
            ?>
                <a href="<?= BASE_URL ?>home/catalogo?categoria=<?= urlencode($catNombre) ?>" 
                   class="list-group-item list-group-item-action py-3 px-4 border-0 bg-transparent d-flex align-items-center justify-content-between transition-hover hover-bg-white group-link <?= $isActive ? 'bg-white fw-bold text-cenco-indigo shadow-sm border-start border-4 border-cenco-green' : '' ?>">
                    
                    <span class="d-flex align-items-center">
                        <span class="d-flex align-items-center justify-content-center me-3 <?= $isActive ? 'text-cenco-green' : 'text-secondary opacity-50' ?>" style="width: 25px;">
                            <i class="<?= $icono ?> fs-4"></i>
                        </span>
                        <span class="text-truncate" style="max-width: 200px;">
                            <?= htmlspecialchars($catNombre) ?>
                        </span>
                    </span>

                    <?php if($isActive): ?>
                        <i class="bi bi-check-circle-fill text-cenco-green"></i>
                    <?php else: ?>
                        <i class="bi bi-chevron-right text-muted opacity-25 small"></i>
                    <?php endif; ?>
                </a>
            <?php 
                endforeach; 
            endif; 
            ?>
        </div>
    </div>

    <div class="offcanvas-footer p-4 bg-white border-top shadow-sm">
        <div class="d-grid">
            <a href="<?= BASE_URL ?>ayuda" class="btn btn-outline-cenco-indigo rounded-pill fw-bold py-2 transition-hover">
                <i class="bi bi-headset me-2"></i> Centro de Ayuda
            </a>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end shadow" tabindex="-1" id="offcanvasCarrito" aria-labelledby="offcanvasCarritoLabel">
    <div class="offcanvas-header bg-cenco-indigo text-white border-0">
        <h5 class="offcanvas-title fw-bold" id="offcanvasCarritoLabel"><i class="bi bi-cart3 me-2"></i> Tu Carrito</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-0 custom-scrollbar" id="contenedor-carrito-lista">
        <div class="text-center py-5 mt-5">
            <div class="spinner-border text-cenco-indigo mb-3" role="status"></div>
            <h5 class="text-muted fw-bold">Cargando carrito...</h5>
        </div>
    </div>

    <div class="offcanvas-footer border-top bg-light p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted fw-bold fs-5">Total a Pagar:</span>
            <span class="fs-3 fw-black text-cenco-red monto-carrito" id="contenedor-carrito-total">$0</span>
        </div>
        <div class="d-grid gap-2">
            <button type="button" onclick="procesarIrAPagar()" class="btn btn-cenco-green fw-bold py-3 shadow-sm rounded-pill hover-scale text-white">
                Ir a Pagar <i class="bi bi-arrow-right ms-2"></i>
            </button>
            <a href="<?= BASE_URL ?>carrito/ver" class="btn btn-outline-secondary fw-bold py-2 rounded-pill">Ver Carrito Completo</a>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-start rounded-end-4 shadow" tabindex="-1" id="adminSidebar">
    <div class="offcanvas-header bg-cenco-indigo text-white border-bottom border-3" style="border-color: var(--cenco-green) !important;">
        <h5 class="offcanvas-title fw-black ls-1 d-flex align-items-center gap-2">
            <i class="bi bi-shield-lock-fill text-cenco-green fs-4"></i> ADMINISTRACIÓN
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body p-0 bg-light overflow-hidden">
        <div class="bg-white p-3 border-bottom text-center">
            <span class="d-block text-muted x-small text-uppercase fw-bold mb-1">Operando en:</span>
            <span class="badge bg-cenco-green text-white fs-6 shadow-sm rounded-pill px-3">
                <?= ($_SESSION['admin_sucursal'] ?? 29) == 29 ? 'Sucursal Prat (La Calera)' : 'Sucursal Villa Alemana' ?>
            </span>
        </div>
        <div class="list-group list-group-flush border-0 mt-2">
            <a href="<?= BASE_URL ?>admin/dashboard" class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white">
                <i class="bi bi-graph-up-arrow fs-5 me-3 text-cenco-green"></i> Dashboard
            </a>

            <a href="<?= BASE_URL ?>admin/pedidos" class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white">
                <i class="bi bi-box-seam fs-5 me-3 text-cenco-green"></i> Gestión de Pedidos
            </a>

            <a href="<?= BASE_URL ?>admin/productos" class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white">
                <i class="bi bi-tags fs-5 me-3 text-cenco-green"></i> Control de Inventario
            </a>

            <a href="<?= BASE_URL ?>admin/productos_nuevos" class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white">
                <i class="bi bi-magic fs-5 me-3 text-cenco-green"></i> Productos Nuevos
            </a>

            <?php if (empty($_SESSION['admin_sucursal'])): ?>
                <a href="<?= BASE_URL ?>admin/analytics" class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white">
                    <i class="bi bi-pie-chart-fill fs-5 me-3 text-cenco-green"></i> Analítica Web
                </a>
                <a href="<?= BASE_URL ?>admin/marcas" class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white">
                    <i class="bi bi-award fs-5 me-3 text-cenco-green"></i> Marcas Destacadas
                </a>
                <a href="<?= BASE_URL ?>admin/banners" class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white">
                    <i class="bi bi-images fs-5 me-3 text-cenco-green"></i> Banners Publicitarios
                </a>
                <a href="<?= BASE_URL ?>admin/usuarios" class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex align-items-center gap-3 fw-bold rounded-end-pill me-2 <?= isActiveAdmin('admin/usuarios') ?>">
                    <i class="bi bi-people fs-5 text-cenco-green"></i> Clientes y Roles
                </a>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>transporte/misEntregas" class="list-group-item list-group-item-action py-3 px-4 fw-bold text-cenco-indigo bg-transparent border-0 d-flex align-items-center transition-hover hover-bg-white border-top mt-2">
                <i class="bi bi-truck-flatbed fs-5 me-3 text-cenco-green"></i> Panel Transporte
            </a>

            <a href="<?= BASE_URL ?>admin/stock_fantasma" class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center gap-3">
                <i class="bi bi-radar text-danger fs-4"></i>
                <div>
                    <span class="d-block fw-bold">Stock Fantasma</span>
                    <small class="text-muted">Ajustes de inventario pendientes</small>
                </div>
            </a>
        </div>
    </div>

    <div class="offcanvas-footer p-4 bg-white border-top shadow-sm">
        <div class="d-grid">
            <a href="<?= BASE_URL ?>home" class="btn btn-outline-cenco-indigo rounded-pill fw-bold py-2 shadow-sm transition-hover">
                <i class="bi bi-shop me-2"></i> Ir a la Tienda
            </a>
        </div>
    </div>
</div>