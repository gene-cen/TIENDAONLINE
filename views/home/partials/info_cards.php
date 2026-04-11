<div class="container-fluid px-3 px-xl-5 my-5">
    <div class="row text-center g-0 bg-white rounded-4 shadow-sm border border-light overflow-hidden">
        <div class="col-md-6 col-lg-3 info-card-item d-flex flex-column align-items-center justify-content-center hover-scale transition-hover">
            <div class="mb-3 text-cenco-indigo"><i class="bi bi-file-earmark-text fs-1"></i></div>
            <h6 class="fw-bold text-cenco-indigo text-uppercase ls-1 mb-2">Atención Personalizada</h6>
            <p class="text-muted small mb-3 px-3">¿Necesitas una cotización especial o ayuda con tu pedido?</p>
            <a href="https://wa.me/56946452516" target="_blank" class="text-decoration-none fw-bold text-cenco-green small">Contáctanos aquí <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="col-md-6 col-lg-3 info-card-item d-flex flex-column align-items-center justify-content-center hover-scale transition-hover">
            <div class="mb-3 text-cenco-indigo"><i class="bi bi-box-seam fs-1"></i></div>
            <h6 class="fw-bold text-cenco-indigo text-uppercase ls-1 mb-2">Seguimiento de Compra</h6>
            <p class="text-muted small mb-3 px-3">Revisa el estado y detalle de tus despachos en tiempo real.</p>
            <a href="<?= BASE_URL ?>perfil" class="text-decoration-none fw-bold text-cenco-green small">Ver estado <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="col-md-6 col-lg-3 info-card-item d-flex flex-column align-items-center justify-content-center hover-scale transition-hover">
            <div class="mb-3 text-cenco-indigo position-relative">
                <i class="bi bi-headset fs-1"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-cenco-red border border-light rounded-circle"></span>
            </div>
            <h6 class="fw-bold text-cenco-indigo text-uppercase ls-1 mb-2">¿Problemas con tu Pedido?</h6>
            <p class="text-muted small mb-3 px-3">Repórtanos cualquier inconveniente para solucionarlo rápido.</p>
            <a href="mailto:ventas@cencocal.cl?subject=Problema con Pedido" class="text-decoration-none fw-bold text-cenco-green small">Reportar ahora <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="col-md-6 col-lg-3 info-card-item d-flex flex-column align-items-center justify-content-center hover-scale transition-hover">
            <div class="mb-3 text-cenco-indigo"><i class="bi bi-shop fs-1"></i></div>
            <h6 class="fw-bold text-cenco-indigo text-uppercase ls-1 mb-2">Locales y Horarios</h6>
            <p class="text-muted small mb-3 px-3">Entérate cuál es la sucursal Cencocal más cerca de ti.</p>
            <a href="<?= BASE_URL ?>home/locales" class="text-decoration-none fw-bold text-cenco-green small">Ver ubicaciones <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</div>

<div class="modal fade" id="localesModal" tabindex="-1" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-cenco-indigo"><i class="bi bi-shop me-2"></i>Nuestras Sucursales</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
        </div>
    </div>
</div>