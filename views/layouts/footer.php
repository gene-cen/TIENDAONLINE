<link rel="stylesheet" href="<?= BASE_URL ?>css/shop/footer.css">

<footer class="bg-cenco-indigo text-white mt-auto position-relative overflow-hidden w-100" style="border-top: 6px solid var(--cenco-green);">

    <div class="position-absolute top-0 end-0 opacity-10 pe-0 mt-5" style="pointer-events: none;">
        <i class="bi bi-box-seam-fill" style="font-size: 25rem; transform: rotate(-15deg); margin-right: -8rem; opacity: 0.05;"></i>
    </div>

    <div class="container-fluid px-3 px-xl-5 py-5 position-relative z-1">
        <div class="row g-4 justify-content-between">
            
            <div class="col-lg-3 col-md-6">
                <img src="<?= BASE_URL ?>img/logo_blanco.png" alt="Cencocal S.A." class="d-block mb-4" style="max-width: 200px; height: auto; object-fit: contain;">

                <p class="footer-text mb-4 pe-lg-3">
                    Transformamos la forma de comprar y vender, integrando tecnología, logística y experiencia en una nueva unidad de negocios digital para toda la región.
                </p>

                <h6 class="text-uppercase text-white opacity-50 mb-3 small fw-bold ls-1">SÍGUENOS EN REDES</h6>
                <div class="d-flex gap-3">
                    <a href="https://www.facebook.com/cencocalValpo/" target="_blank" class="social-btn-clean"><i class="bi bi-facebook"></i></a>
                    <a href="https://www.instagram.com/cencocal.cl/" target="_blank" class="social-btn-clean"><i class="bi bi-instagram"></i></a>
                    <a href="https://www.linkedin.com/company/cencocal-s.a/" target="_blank" class="social-btn-clean"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h5 class="footer-heading">Explora Cencocal</h5>
                <ul class="list-unstyled">
                    <li><a href="<?= BASE_URL ?>home" class="footer-link"><i class="bi bi-chevron-right me-1 small"></i> Inicio</a></li>
                    <li><a href="#" class="footer-link" data-bs-toggle="modal" data-bs-target="#nosotrosModal"><i class="bi bi-chevron-right me-1 small"></i> Nosotros</a></li>
                    <li><a href="<?= BASE_URL ?>home/catalogo" class="footer-link"><i class="bi bi-chevron-right me-1 small"></i> Catálogo</a></li>
                    <li><a href="<?= BASE_URL ?>home/locales" class="footer-link"><i class="bi bi-chevron-right me-1 small"></i> Locales y Horarios</a></li>
                    <li><a href="<?= BASE_URL ?>home/terminos" class="footer-link"><i class="bi bi-chevron-right me-1 small"></i> Términos Legales</a></li>
                </ul>
            </div>

            <div class="col-lg-2 col-md-6">
                <h5 class="footer-heading">Talento y Equipo</h5>
                <div class="d-flex flex-column gap-3">
                    <div class="trabaja-card shadow-sm p-3 rounded-4" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                        <i class="bi bi-people-fill fs-2 text-white opacity-25 d-block mb-2 text-center"></i>
                        <p class="small text-center mb-2 lh-sm opacity-75">¿Buscas nuevos desafíos?</p>
                        <a href="<?= BASE_URL ?>empleos/postulante" class="btn btn-sm btn-cenco-green text-white w-100 rounded-pill fw-bold">Únete al Equipo</a>
                    </div>
                    <div class="intranet-card shadow-sm p-3 rounded-4" style="background: rgba(255,255,255,0.1); border: 1px solid var(--cenco-green);">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-shield-lock-fill text-warning"></i>
                            <span class="fw-bold small">Portal Interno</span>
                        </div>
                        <a href="<?= BASE_URL ?>intranet" class="btn btn-sm btn-outline-light w-100 rounded-pill fw-bold" style="font-size: 0.75rem;">Acceso Colaboradores</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5 class="footer-heading">Atención y Soporte</h5>
                <ul class="list-unstyled d-flex flex-column gap-3">
                    <li class="d-flex align-items-start gap-3">
                        <i class="bi bi-geo-alt text-cenco-green fs-5 mt-1"></i>
                        <div>
                            <span class="d-block fw-semibold text-white">Oficinas Digitales</span>
                            <span class="footer-text small">Santiago 884, Villa Alemana<br>Región de Valparaíso, Chile.</span>
                        </div>
                    </li>
                    <li class="d-flex align-items-center gap-3">
                        <i class="bi bi-telephone text-cenco-green fs-5"></i>
                        <span class="footer-text small">+56 9 8137 2374</span>
                    </li>
                    <li class="d-flex align-items-center gap-3">
                        <i class="bi bi-envelope text-cenco-green fs-5"></i>
                        <a href="mailto:ecommerce@tiendacencocal.com" class="footer-text small text-decoration-none text-white">ecommerce@tiendacencocal.com</a>
                    </li>
                </ul>
            </div>

            <div class="col-lg-2 col-md-12">
                <h5 class="footer-heading">Respaldo y Seguridad</h5>
                <div class="mb-3">
                    <span class="d-block text-white opacity-50 small mb-2 text-uppercase fw-bold" style="font-size: 0.65rem;">Medios de Pago Seguros</span>
                    <div class="trust-badge shadow-sm bg-white p-1 rounded-2"><img src="<?= BASE_URL ?>img/webpay-logo.png" class="img-fluid" alt="Webpay"></div>
                </div>
                <div>
                    <span class="d-block text-white opacity-50 small mb-2 text-uppercase fw-bold" style="font-size: 0.65rem;">Nuestras Alianzas</span>
                    <div class="row g-2">
                        <div class="col-12">
                            <div class="trust-badge shadow-sm bg-white p-1 rounded-2"><img src="<?= BASE_URL ?>img/soysociocrcp2025.png" class="img-fluid" alt="Socio CRCP"></div>
                        </div>
                        <div class="col-6">
                            <div class="trust-badge shadow-sm bg-white p-2 rounded-2" title="CHEP"><img src="<?= BASE_URL ?>img/chep.jpg" class="img-fluid" alt="CHEP"></div>
                        </div>
                        <div class="col-6">
                            <div class="trust-badge shadow-sm bg-white p-2 rounded-2" title="Almasend"><img src="<?= BASE_URL ?>img/almasend.jpg" class="img-fluid" alt="Almasend"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="container text-center mt-4 py-3 border-top border-secondary border-opacity-25">
        <p class="small text-secondary mb-0">
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
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Si hace clic en el botón, lo mandamos a la intranet
                    window.location.href = '<?= BASE_URL ?>intranet';
                } else {
                    // Si cierra la alerta, simplemente limpiamos la URL
                    window.history.replaceState(null, null, window.location.pathname);
                }
            });
        <?php endif; ?>
    });
</script>