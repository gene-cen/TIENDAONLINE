<style>
    /* Efecto de elevación elegante para las tarjetas */
    .hover-elevate {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .hover-elevate:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(42, 27, 94, 0.1) !important;
        border-color: rgba(97, 166, 14, 0.3);
    }

    .link-modulo {
        transition: 0.2s;
        font-weight: 500;
        border-radius: 8px;
    }

    .link-modulo:hover {
        color: #61A60E !important;
        background-color: rgba(97, 166, 14, 0.05);
        padding-left: 10px !important;
    }
</style>

<div class="container-fluid py-4 px-lg-5" style="max-width: 1400px; margin: 0 auto;">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-1" style="letter-spacing: -0.5px;">Centro de Operaciones</h2>
            <p class="text-muted mb-0 fs-6">Selecciona un módulo para gestionar la plataforma.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-cenco-indigo border px-3 py-2 rounded-pill shadow-sm d-flex align-items-center">
                <i class="bi bi-person-circle me-2 fs-6"></i>
                <?= htmlspecialchars($_SESSION['user_nombre'] ?? 'Administrador') ?>
                <span class="ms-2 opacity-50">| Rol <?= $_SESSION['rol_id'] ?? '?' ?></span>
            </span>
        </div>
    </div>

    <div class="row g-4">

        <div class="col-md-6 col-xl-4">
            <div class="card bg-white shadow-sm rounded-4 h-100 hover-elevate overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-4 pb-2 border-bottom">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-tools fs-4"></i>
                        </div>
                        <h5 class="fw-bold text-cenco-indigo mb-0">Mantenedores</h5>
                    </div>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                        <li>
                            <a href="<?= BASE_URL ?>admin/banners" class="text-decoration-none text-secondary link-modulo d-flex align-items-center p-2">
                                <i class="bi bi-images me-3 text-muted opacity-50"></i> Gestionar Banners
                            </a>
                        </li>

                        <?php if (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1): ?>
                            <li>
                                <a href="<?= BASE_URL ?>admin/marcas" class="text-decoration-none text-secondary link-modulo d-flex align-items-center p-2">
                                    <i class="bi bi-star-fill me-3 text-warning opacity-75"></i> Gestionar Marcas
                                </a>
                            </li>
                        <?php endif; ?>

                        <li>
                            <a href="<?= BASE_URL ?>admin/productos_nuevos" class="text-decoration-none text-secondary link-modulo d-flex align-items-center p-2">
                                <i class="bi bi-magic me-3 text-muted opacity-50"></i> Productos Nuevos
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card bg-white shadow-sm rounded-4 h-100 hover-elevate overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-4 pb-2 border-bottom">
                        <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-boxes fs-4"></i>
                        </div>
                        <h5 class="fw-bold text-cenco-indigo mb-0">Inventario</h5>
                    </div>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                        <li>
                            <a href="<?= BASE_URL ?>admin/productos" class="text-decoration-none text-secondary link-modulo d-flex align-items-center p-2">
                                <i class="bi bi-box-seam me-3 text-muted opacity-50"></i> Inventario Total
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>admin/stock_fantasma" class="text-decoration-none text-secondary link-modulo d-flex align-items-center p-2">
                                <i class="bi bi-radar me-3 text-danger opacity-75"></i> Detección Stock Fantasma
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="card bg-white shadow-sm rounded-4 h-100 hover-elevate overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-4 pb-2 border-bottom">
                        <div class="bg-success bg-opacity-10 text-success rounded-3 p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-bag-check-fill fs-4"></i>
                        </div>
                        <h5 class="fw-bold text-cenco-indigo mb-0">Logística y Pedidos</h5>
                    </div>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                        <li>
                            <a href="<?= BASE_URL ?>admin/pedidos" class="text-decoration-none text-secondary link-modulo d-flex align-items-center p-2">
                                <i class="bi bi-cart-check me-3 text-muted opacity-50"></i> Pedidos y Ventas
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>transporte/misEntregas" class="text-decoration-none text-secondary link-modulo d-flex align-items-center p-2">
                                <i class="bi bi-truck me-3 text-muted opacity-50"></i> Panel Transporte
                            </a>
                        </li>

                        <?php if (isset($_SESSION['rol_id']) && in_array($_SESSION['rol_id'], [1, 2])): ?>
                            <li class="border-top mt-2 pt-2">
                                <a href="<?= BASE_URL ?>admin/ventas_sucursal" class="text-decoration-none text-secondary link-modulo d-flex align-items-center p-2">
                                    <i class="bi bi-graph-up-arrow me-3 text-primary opacity-75"></i> Dashboard Ventas Sucursal
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1): ?>

            <div class="col-md-6 col-xl-4">
                <div class="card bg-white shadow-sm rounded-4 h-100 hover-elevate overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4 pb-2 border-bottom">
                            <div class="bg-info bg-opacity-10 text-info rounded-3 p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-people-fill fs-4"></i>
                            </div>
                            <h5 class="fw-bold text-cenco-indigo mb-0">Gestión de Usuarios</h5>
                        </div>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                            <li>
                                <a href="<?= BASE_URL ?>admin/usuarios" class="text-decoration-none text-secondary link-modulo d-flex align-items-center p-2">
                                    <i class="bi bi-people me-3 text-cenco-green opacity-75"></i> Clientes y Roles
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="card bg-white shadow-sm rounded-4 h-100 hover-elevate overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4 pb-2 border-bottom">
                            <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-graph-up-arrow fs-4"></i>
                            </div>
                            <h5 class="fw-bold text-cenco-indigo mb-0">Reportes</h5>
                        </div>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                            <li>
                                <a href="<?= BASE_URL ?>admin/analytics" class="text-decoration-none text-secondary link-modulo d-flex align-items-center p-2">
                                    <i class="bi bi-geo-alt-fill me-3 text-muted opacity-50"></i> Data Analytics (Mapa)
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>