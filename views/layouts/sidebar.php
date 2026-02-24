<?php if (isset($esAdmin) && $esAdmin): ?>

    <?php
    // Lógica para detectar página activa
    $currentUrl = $_GET['url'] ?? 'admin/dashboard';

    // Función helper interna para este bloque
    if (!function_exists('isActiveAdmin')) {
        function isActiveAdmin($ruta, $current)
        {
            return (strpos($current, $ruta) === 0)
                ? 'bg-cenco-indigo text-white shadow-sm' // Estilo Activo
                : 'text-secondary hover-bg-light';       // Estilo Inactivo
        }
    }

    // Validamos si alguna ruta del mantenedor está activa para dejar el menú desplegado
    $isMantenedorActive = (strpos($currentUrl, 'admin/banners') === 0 || strpos($currentUrl, 'admin/marcas') === 0);
    ?>

    <div class="offcanvas offcanvas-start rounded-end-4" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel" style="width: 300px; z-index: 1055;">

        <div class="offcanvas-header border-bottom bg-light">
            <h5 class="offcanvas-title fw-black text-cenco-indigo" id="adminSidebarLabel">
                <i class="bi bi-shield-lock-fill me-2 text-cenco-green"></i>ADMIN PANEL
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body p-0">
            <div class="list-group list-group-flush py-3">

                <div class="small fw-bold text-uppercase text-muted px-4 mb-2 ls-1" style="font-size: 0.75rem;">Principal</div>

                <a href="<?= BASE_URL ?>admin/dashboard"
                    class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex align-items-center gap-3 fw-bold rounded-end-pill me-2 <?= isActiveAdmin('admin/dashboard', $currentUrl) ?>">
                    <i class="bi bi-speedometer2 fs-5"></i> Dashboard
                </a>

                <div class="small fw-bold text-uppercase text-muted px-4 mt-4 mb-2 ls-1 border-top pt-3" style="font-size: 0.75rem;">Gestión</div>

                <a href="<?= BASE_URL ?>admin/productos"
                    class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex align-items-center gap-3 fw-bold rounded-end-pill me-2 <?= isActiveAdmin('admin/productos', $currentUrl) ?>">
                    <i class="bi bi-box-seam fs-5"></i> Inventario
                </a>

                <a href="<?= BASE_URL ?>admin/pedidos"
                    class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex align-items-center gap-3 fw-bold rounded-end-pill me-2 <?= isActiveAdmin('admin/pedidos', $currentUrl) ?>">
                    <i class="bi bi-cart-check fs-5"></i> Pedidos y Ventas
                </a>

                <div class="small fw-bold text-uppercase text-muted px-4 mt-4 mb-2 ls-1 border-top pt-3" style="font-size: 0.75rem;">Herramientas</div>

                <a href="<?= BASE_URL ?>admin/importar_erp"
                    class="list-group-item list-group-item-action border-0 px-4 py-2 mb-1 d-flex align-items-center gap-3 rounded-end-pill me-2 <?= isActiveAdmin('admin/importar_erp', $currentUrl) ?>">
                    <i class="bi bi-arrow-repeat"></i> Sincronizar ERP
                </a>

                <a href="<?= BASE_URL ?>admin/exportar_pedidos"
                    class="list-group-item list-group-item-action border-0 px-4 py-2 mb-1 d-flex align-items-center gap-3 rounded-end-pill me-2 <?= isActiveAdmin('admin/exportar_pedidos', $currentUrl) ?>">
                    <i class="bi bi-file-earmark-excel"></i> Reportes Excel
                </a>



                <div class="small fw-bold text-uppercase text-muted px-4 mt-4 mb-2 ls-1 border-top pt-3" style="font-size: 0.75rem;">Plataforma</div>

                <a data-bs-toggle="collapse" href="#menuMantenedor" role="button" aria-expanded="<?= $isMantenedorActive ? 'true' : 'false' ?>"
                    class="list-group-item list-group-item-action border-0 px-4 py-3 mb-1 d-flex justify-content-between align-items-center fw-bold rounded-end-pill me-2 <?= $isMantenedorActive ? 'bg-cenco-indigo text-white shadow-sm' : 'text-secondary hover-bg-light' ?>">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-laptop fs-5"></i> Mantenedor Web
                    </div>
                    <i class="bi bi-chevron-down small"></i>
                </a>

                <div class="collapse <?= $isMantenedorActive ? 'show' : '' ?>" id="menuMantenedor">
                    <div class="list-group list-group-flush bg-transparent py-1 ps-4">
                        <a href="<?= BASE_URL ?>admin/banners"
                            class="list-group-item list-group-item-action border-0 py-2 mb-1 d-flex align-items-center gap-3 rounded-end-pill me-2 <?= isActiveAdmin('admin/banners', $currentUrl) ?>" style="font-size: 0.9rem;">
                            <i class="bi bi-images"></i> Cambiar Banners
                        </a>

                        <a href="<?= BASE_URL ?>admin/marcas"
                            class="list-group-item list-group-item-action border-0 py-2 mb-1 d-flex align-items-center gap-3 rounded-end-pill me-2 <?= isActiveAdmin('admin/marcas', $currentUrl) ?>" style="font-size: 0.9rem;">
                            <i class="bi bi-star-fill text-warning"></i> Marcas Destacadas
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="offcanvas-footer p-3 border-top bg-light">
            <a href="<?= BASE_URL ?>home" class="btn btn-outline-cenco-indigo w-100 rounded-pill fw-bold mb-2">
                <i class="bi bi-shop-window me-2"></i> Ver Tienda
            </a>
            <a href="<?= BASE_URL ?>auth/logout" class="btn btn-cenco-red w-100 rounded-pill fw-bold text-white">
                <i class="bi bi-box-arrow-left me-2"></i> Salir
            </a>
        </div>

    </div>
<?php endif; ?>