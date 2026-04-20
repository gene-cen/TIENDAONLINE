<link rel="stylesheet" href="<?= BASE_URL ?>css/shop/footer.css">

<footer class="bg-cenco-indigo text-white mt-auto position-relative overflow-hidden w-100" style="border-top: 5px solid var(--cenco-green);">

    <div class="position-absolute top-0 end-0 pe-0 mt-5" style="pointer-events: none; z-index: 0;">
        <i class="bi bi-box-seam-fill" style="font-size: 30rem; transform: rotate(-15deg); margin-right: -10rem; opacity: 0.03; color: white;"></i>
    </div>

    <div class="container-fluid px-4 px-xl-5 py-5 position-relative z-1">
        <div class="row g-5 justify-content-between">

            <div class="col-lg-3 col-md-6">
                <img src="<?= BASE_URL ?>img/logo_blanco.png" alt="Cencocal S.A." class="d-block mb-4" style="max-width: 220px; height: auto; object-fit: contain; margin-top: -12px;">

                <p class="mb-4 opacity-75 lh-base" style="font-size: 0.95rem;">
                    Transformamos la forma de comprar y vender, integrando tecnología, logística y experiencia en una nueva unidad de negocios digital para toda la región.
                </p>

                <h6 class="text-uppercase text-white opacity-50 mb-3 small fw-bold" style="letter-spacing: 1px;">SÍGUENOS EN REDES</h6>
                <div class="d-flex gap-3">
                    <a href="https://www.facebook.com/cencocalValpo/" target="_blank" class="social-btn-clean"><i class="bi bi-facebook fs-5"></i></a>
                    <a href="https://www.instagram.com/cencocal.cl/" target="_blank" class="social-btn-clean"><i class="bi bi-instagram fs-5"></i></a>
                    <a href="https://www.linkedin.com/company/cencocal-s.a/" target="_blank" class="social-btn-clean"><i class="bi bi-linkedin fs-5"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h5 class="footer-heading">Explora Cencocal</h5>
                <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                    <li><a href="<?= BASE_URL ?>home" class="footer-link"><i class="bi bi-chevron-right me-2 small"></i> Inicio</a></li>
                    <li><a href="#" class="footer-link" data-bs-toggle="modal" data-bs-target="#nosotrosModal"><i class="bi bi-chevron-right me-2 small"></i> Nosotros</a></li>
                    <li><a href="<?= BASE_URL ?>home/catalogo" class="footer-link"><i class="bi bi-chevron-right me-2 small"></i> Catálogo</a></li>
                    <li><a href="<?= BASE_URL ?>home/locales" class="footer-link"><i class="bi bi-chevron-right me-2 small"></i> Locales y Horarios</a></li>
                    <li><a href="<?= BASE_URL ?>home/terminos" class="footer-link"><i class="bi bi-chevron-right me-2 small"></i> Términos Legales</a></li>
                </ul>
            </div>

            <div class="col-lg-2 col-md-6">
                <h5 class="footer-heading">Talento y Equipo</h5>
                <div class="d-flex flex-column gap-3">
                    <div class="trabaja-card p-4 rounded-4" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);">
                        <i class="bi bi-people-fill fs-3 text-white opacity-50 d-block mb-2 text-center"></i>
                        <p class="small text-center mb-3 lh-sm opacity-75">¿Buscas nuevos desafíos?</p>
                        <a href="<?= BASE_URL ?>empleos/postulante" class="btn btn-sm btn-cenco-green text-white w-100 rounded-pill fw-bold border-0 shadow-sm">Únete al Equipo</a>
                    </div>
                    <div class="intranet-card p-3 rounded-4" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15);">
                        <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                            <i class="bi bi-shield-lock-fill" style="color: var(--cenco-green);"></i>
                            <span class="fw-bold small text-white">Portal Interno</span>
                        </div>
                        <a href="<?= BASE_URL ?>intranet" class="btn btn-sm btn-outline-light w-100 rounded-pill fw-bold" style="font-size: 0.8rem;">Acceso Colaboradores</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h5 class="footer-heading">Atención y Soporte</h5>
                <ul class="list-unstyled d-flex flex-column gap-4 mb-0">
                    <li class="d-flex align-items-start gap-3">
                        <div class="bg-white bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="min-width: 36px; height: 36px;">
                            <i class="bi bi-geo-alt text-cenco-green"></i>
                        </div>
                        <div>
                            <span class="d-block fw-bold text-white mb-1">Oficinas Digitales</span>
                            <span class="opacity-75 small lh-sm d-block">Santiago 884, Villa Alemana<br>Región de Valparaíso, Chile.</span>
                        </div>
                    </li>
                    <li class="d-flex align-items-center gap-3">
                        <div class="bg-white bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="min-width: 36px; height: 36px;">
                            <i class="bi bi-telephone text-cenco-green"></i>
                        </div>
                        <span class="opacity-75 small">+56 9 8137 2374</span>
                    </li>
                    <li class="d-flex align-items-center gap-3">
                        <div class="bg-white bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="min-width: 36px; height: 36px;">
                            <i class="bi bi-envelope text-cenco-green"></i>
                        </div>
                        <a href="mailto:ecommerce@tiendacencocal.com" class="opacity-75 small text-decoration-none text-white transition">ecommerce@tiendacencocal.com</a>
                    </li>
                </ul>
            </div>

            <div class="col-lg-2 col-md-12">
                <h5 class="footer-heading">Respaldo Seguro</h5>

                <div class="mb-4">
                    <span class="d-block text-white opacity-50 small mb-2 text-uppercase fw-bold" style="letter-spacing: 0.5px; font-size: 0.7rem;">Medios de Pago</span>
                    <div class="trust-badge shadow-sm">
                        <img src="<?= BASE_URL ?>img/webpay-logo.png" alt="Webpay Plus">
                    </div>
                </div>
<div>
                    <span class="d-block text-white opacity-50 small mb-2 text-uppercase fw-bold" style="letter-spacing: 0.5px; font-size: 0.7rem;">Nuestras Alianzas</span>
                    <div class="row g-2">
                        <div class="col-12">
                            <div class="trust-badge shadow-sm">
                                <img src="<?= BASE_URL ?>img/soysociocrcp2025.png" alt="Socio CRCP">
                            </div>
                        </div>
                        <div class="col-6">
                            <a href="https://www.chep.com/cl/es-419/why-chep" target="_blank" class="text-decoration-none">
                                <div class="trust-badge shadow-sm" title="CHEP">
                                    <img src="<?= BASE_URL ?>img/chep.jpg" alt="CHEP">
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="https://almasend.app/" target="_blank" class="text-decoration-none">
                                <div class="trust-badge shadow-sm" title="Almasend">
                                    <img src="<?= BASE_URL ?>img/almasend.jpg" alt="Almasend">
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="container-fluid text-center py-3" style="background: rgba(0,0,0,0.2);">
        <p class="small opacity-50 mb-0 fw-medium">
            &copy; <?= date('Y') ?> Cencocal S.A. | Todos los derechos reservados.
        </p>
    </div>
</footer>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'usa_intranet'): ?>
            Swal.fire({
                icon: 'info',
                title: 'Acceso Restringido',
                html: 'Tu cuenta pertenece al equipo de colaboradores Cencocal.<br><br>Por favor, inicia sesión a través del <b>Portal de Intranet</b>.',
                showCancelButton: true,
                confirmButtonText: 'Ir a la Intranet <i class="bi bi-arrow-right"></i>',
                cancelButtonText: 'Cerrar',
                confirmButtonColor: '#2A1B5E',
                cancelButtonColor: '#6c757d',
                backdrop: `rgba(42, 27, 94, 0.4)` /* Toque extra de estilo al alert */
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?= BASE_URL ?>intranet';
                } else {
                    window.history.replaceState(null, null, window.location.pathname);
                }
            });
        <?php endif; ?>
    });
</script>