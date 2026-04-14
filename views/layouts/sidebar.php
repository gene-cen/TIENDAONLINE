<?php if (isset($esAdmin) && $esAdmin): ?>
    <?php
    $currentUrl = $_GET['url'] ?? 'admin/dashboard';
    if (!function_exists('isActiveAdmin')) {
        function isActiveAdmin($ruta, $current) {
            return (strpos($current, $ruta) === 0) ? 'bg-cenco-indigo text-white shadow-sm' : 'text-secondary hover-bg-light';
        }
    }
    // Determinar si eres la jefa
    $esSuperAdmin = (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1);
    $isMantenedorActive = (strpos($currentUrl, 'admin/banners') === 0 || strpos($currentUrl, 'admin/marcas') === 0 || strpos($currentUrl, 'admin/usuarios') === 0);
    ?>

    <div class="offcanvas offcanvas-start rounded-end-4" tabindex="-1" id="adminSidebar" style="width: 300px; z-index: 1055;">
        <div class="offcanvas-header border-bottom bg-light">
            <h5 class="offcanvas-title fw-black text-cenco-indigo">
                <i class="bi bi-shield-lock-fill me-2 text-cenco-green"></i>ADMIN PANEL
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body p-0">
            <div class="list-group list-group-flush py-3">
                
                <div class="small fw-bold text-uppercase text-muted px-4 mb-2 ls-1" style="font-size: 0.75rem;">Principal</div>
                
                <a href="<?= BASE_URL ?>admin/dashboard" class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex align-items-center gap-3 fw-bold rounded-end-pill me-2 <?= isActiveAdmin('admin/dashboard', $currentUrl) ?>">
                    <i class="bi bi-speedometer2 fs-5"></i> Dashboard
                </a>

                <?php if ($esSuperAdmin || empty($_SESSION['admin_sucursal'])): ?>
                    <a href="<?= BASE_URL ?>admin/analytics" class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex align-items-center gap-3 fw-bold rounded-end-pill me-2 <?= isActiveAdmin('admin/analytics', $currentUrl) ?>">
                        <i class="bi bi-graph-up-arrow fs-5"></i> Data Analytics
                    </a>
                <?php endif; ?>

                <div class="small fw-bold text-uppercase text-muted px-4 mt-4 mb-2 ls-1 border-top pt-3" style="font-size: 0.75rem;">Gestión de Operaciones</div>
                
                <a href="<?= BASE_URL ?>admin/pedidos" class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex align-items-center gap-3 fw-bold rounded-end-pill me-2 <?= isActiveAdmin('admin/pedidos', $currentUrl) ?>">
                    <i class="bi bi-cart-check fs-5"></i> Pedidos y Ventas
                </a>

                <a href="<?= BASE_URL ?>admin/productos" class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex align-items-center gap-3 fw-bold rounded-end-pill me-2 <?= isActiveAdmin('admin/productos', $currentUrl) ?>">
                    <i class="bi bi-box-seam fs-5"></i> Inventario Total
                </a>

                <a href="<?= BASE_URL ?>admin/productos_nuevos" class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex align-items-center gap-3 fw-bold rounded-end-pill me-2 <?= isActiveAdmin('admin/productos_nuevos', $currentUrl) ?>">
                    <i class="bi bi-magic fs-5"></i> Productos Nuevos
                </a>

                <a href="<?= BASE_URL ?>admin/stock_fantasma" class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex align-items-center gap-3 fw-bold rounded-end-pill me-2 <?= isActiveAdmin('admin/stock_fantasma', $currentUrl) ?>">
                    <i class="bi bi-radar fs-5 text-danger"></i> Stock Fantasma
                </a>

                <div class="small fw-bold text-uppercase text-muted px-4 mt-4 mb-2 ls-1 border-top pt-3" style="font-size: 0.75rem;">Configuración Web</div>

                <a data-bs-toggle="collapse" href="#menuMantenedor" role="button" aria-expanded="<?= $isMantenedorActive ? 'true' : 'false' ?>"
                    class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex justify-content-between align-items-center fw-bold rounded-end-pill me-2 <?= $isMantenedorActive ? 'bg-cenco-indigo text-white shadow-sm' : 'text-secondary hover-bg-light' ?>">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-laptop fs-5"></i> Mantenedor CMS
                    </div>
                    <i class="bi bi-chevron-down small"></i>
                </a>

                <div class="collapse <?= $isMantenedorActive ? 'show' : '' ?>" id="menuMantenedor">
                    <div class="list-group list-group-flush bg-transparent py-1 ps-4">
                        <a href="<?= BASE_URL ?>admin/banners" class="list-group-item list-group-item-action border-0 py-2 mb-1 d-flex align-items-center gap-3 rounded-end-pill me-2 <?= isActiveAdmin('admin/banners', $currentUrl) ?>">
                            <i class="bi bi-images"></i> Gestionar Banners
                        </a>
                        <a href="<?= BASE_URL ?>admin/marcas" class="list-group-item list-group-item-action border-0 py-2 mb-1 d-flex align-items-center gap-3 rounded-end-pill me-2 <?= isActiveAdmin('admin/marcas', $currentUrl) ?>">
                            <i class="bi bi-star-fill text-warning"></i> Gestionar Marcas
                        </a>
                        <?php if ($esSuperAdmin): ?>
                            <a href="<?= BASE_URL ?>admin/usuarios" class="list-group-item list-group-item-action border-0 py-2 mb-1 d-flex align-items-center gap-3 rounded-end-pill me-2 <?= isActiveAdmin('admin/usuarios', $currentUrl) ?>">
                                <i class="bi bi-people-fill text-cenco-green"></i> Clientes y Roles
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="small fw-bold text-uppercase text-muted px-4 mt-4 mb-2 ls-1 border-top pt-3" style="font-size: 0.75rem;">Logística</div>
                <a href="<?= BASE_URL ?>transporte/misEntregas" class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex align-items-center gap-3 fw-bold rounded-end-pill me-2 <?= isActiveAdmin('transporte/misEntregas', $currentUrl) ?>">
                    <i class="bi bi-truck fs-5"></i> Panel Transporte
                </a>
            </div>
        </div>

        <div class="offcanvas-footer p-3 border-top bg-light">
            <a href="<?= BASE_URL ?>home" class="btn btn-outline-cenco-indigo w-100 rounded-pill fw-bold mb-2">
                <i class="bi bi-shop me-2"></i> Salir al Home
            </a>
        </div>
    </div>
<?php endif; ?>