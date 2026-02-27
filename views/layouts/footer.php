<style>
    /* === TIPOGRAFÍA PREMIUM (Light & Clean) === */
    .footer-heading {
        font-weight: 600;
        color: var(--cenco-green);
        margin-bottom: 1.2rem;
        font-size: 1.1rem;
        letter-spacing: 0.5px;
    }

    .footer-text {
        font-size: 0.95rem;
        font-weight: 300;
        line-height: 1.6;
        color: rgba(255, 255, 255, 0.8);
    }

    .footer-link {
        font-size: 0.95rem;
        font-weight: 300;
        text-decoration: none;
        color: rgba(255, 255, 255, 0.7);
        transition: all 0.3s ease;
        display: block;
        margin-bottom: 8px;
    }

    .footer-link:hover {
        color: white !important;
        transform: translateX(5px);
    }

    /* === BOTÓN TRABAJA CON NOSOTROS === */
    .trabaja-card {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        padding: 15px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        text-align: center;
        transition: transform 0.3s;
    }

    .trabaja-card:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-5px);
    }

    .btn-trabaja {
        background: var(--cenco-green);
        color: white;
        border-radius: 50px;
        padding: 8px 20px;
        font-size: 0.9rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        margin-top: 10px;
        width: 100%;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.3s;
    }

    .btn-trabaja:hover {
        background: white;
        color: var(--cenco-green);
    }

    /* === REDES SOCIALES === */
    .social-btn-clean {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border-radius: 50%;
        transition: all 0.3s ease;
        text-decoration: none;
        font-size: 1.2rem;
    }

    .social-btn-clean:hover {
        background: var(--cenco-green);
        color: white;
        transform: scale(1.1);
    }

    /* === LOGOS DE PAGO Y ALIANZA === */
    .trust-badge {
        background: white;
        padding: 8px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
        height: 50px;
        width: 100%;
        transition: transform 0.3s;
    }

    .trust-badge:hover {
        transform: scale(1.02);
    }

    .trust-badge img {
        max-height: 100%;
        max-width: 100%;
    }

    /* === BOTÓN FLOTANTE ACCESIBILIDAD (NUEVO) === */
    .btn-accessibility {
        width: 55px;
        height: 55px;
        border: 2px solid rgba(255, 255, 255, 0.8);
        background-color: var(--cenco-indigo);
        color: white;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        z-index: 2000;
        /* Por encima de casi todo */
    }

    .btn-accessibility:hover {
        transform: scale(1.1) rotate(10deg);
        background-color: var(--cenco-green);
        border-color: white;
        color: white;
    }
</style>

<footer class="bg-cenco-indigo text-white mt-auto position-relative overflow-hidden" style="border-top: 6px solid var(--cenco-green);">

    <div class="position-absolute top-0 end-0 opacity-10 pe-0 mt-5" style="pointer-events: none;">
        <i class="bi bi-box-seam-fill" style="font-size: 25rem; transform: rotate(-15deg); margin-right: -8rem; opacity: 0.05;"></i>
    </div>

    <div class="container py-5 position-relative z-1">

        <div class="row g-4 justify-content-between">

            <div class="col-lg-3 col-md-6">
                <img src="<?= BASE_URL ?>img/logo.png" alt="Cencocal S.A." height="50" class="d-block bg-white rounded-3 p-2 mb-3 shadow-sm" style="width: auto;">
                <p class="footer-text mb-4">
                    Transformamos la forma de comprar y vender, integrando tecnología, logística y experiencia en una nueva unidad de negocios digital.
                </p>

                <h6 class="text-uppercase text-white opacity-50 mb-3 small fw-bold ls-1">Síguenos</h6>
                <div class="d-flex gap-2">
                    <a href="https://www.facebook.com/cencocalValpo/?locale=es_LA" target="_blank" class="social-btn-clean"><i class="bi bi-facebook"></i></a>
                    <a href="https://www.instagram.com/cencocal.cl/?hl=es" target="_blank" class="social-btn-clean"><i class="bi bi-instagram"></i></a>
                    <a href="https://www.linkedin.com/company/cencocal-s.a/?originalSubdomain=cl" target="_blank" class="social-btn-clean"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h5 class="footer-heading">Explora</h5>
                <ul class="list-unstyled">
                    <li><a href="<?= BASE_URL ?>home" class="footer-link">Inicio</a></li>
                    <li><a href="#" class="footer-link" data-bs-toggle="modal" data-bs-target="#nosotrosModal">Nosotros</a></li>
                    <li><a href="<?= BASE_URL ?>home/catalogo" class="footer-link">Catálogo</a></li>
                    <li><a href="<?= BASE_URL ?>home/locales" class="footer-link">Sucursales</a></li>
                    <li><a href="<?= BASE_URL ?>home/terminos" class="footer-link">Términos Legales</a></li>
                </ul>
            </div>

            <div class="col-lg-2 col-md-6">
                <h5 class="footer-heading">Trabaja con nosotros</h5>
                <div class="trabaja-card">
                    <i class="bi bi-people-fill fs-1 text-white opacity-50 mb-2"></i>
                    <p class="small mb-2 lh-sm text-white opacity-75">¿Buscas nuevos desafíos?</p>
                    <a href="https://forms.gle/eMtt81QhDws5PSn7A" target="_blank" class="btn-trabaja">
                        Postular Aquí
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5 class="footer-heading">Contacto</h5>
                <ul class="list-unstyled d-flex flex-column gap-3">
                    <li class="d-flex align-items-start gap-3">
                        <i class="bi bi-geo-alt text-cenco-green fs-5 mt-1"></i>
                        <div>
                            <span class="d-block fw-semibold text-white">Oficinas de Negocios & Transformación Digital</span>
                            <span class="footer-text small">Santiago 884, Villa Alemana<br>Región de Valparaíso.</span>
                        </div>
                    </li>
                    <li class="d-flex align-items-center gap-3">
                        <i class="bi bi-telephone text-cenco-green fs-5"></i>
                        <span class="footer-text small">+56 9 8137 2374</span>
                    </li>
                    <li class="d-flex align-items-center gap-3">
                        <i class="bi bi-envelope text-cenco-green fs-5"></i>
                        <span class="footer-text small">tiendaonline@cencocal.cl * hacer el ajuste del correo</span>
                    </li>
                </ul>
            </div>

            <div class="col-lg-2 col-md-12">
                <h5 class="footer-heading">Respaldo</h5>

                <div class="mb-3">
                    <span class="d-block text-white opacity-50 small mb-1 text-uppercase" style="font-size: 0.7rem;">Pagos Seguros</span>
                    <div class="trust-badge">
                        <img src="<?= BASE_URL ?>img/webpay-logo.png" alt="Webpay Plus">
                    </div>
                </div>

                <div>
                    <span class="d-block text-white opacity-50 small mb-1 text-uppercase" style="font-size: 0.7rem;">Alianza</span>
                    <div class="trust-badge">
                        <img src="<?= BASE_URL ?>img/soysociocrcp2025.png" alt="CRCP Valparaíso">
                    </div>

                    <div class="trust-badge">
                        <img src="<?= BASE_URL ?>img/chep.jpg" alt="Smart Movement">
                    </div>
                    <div class="trust-badge">
                        <img src="<?= BASE_URL ?>img/almasend.jpg" alt="Smart Movement">
                    </div>
                </div>






            </div>

        </div>
    </div>

    <div class="border-top border-white border-opacity-10 py-4 mt-2">
        <div class="container text-center">
            <p class="mb-0 small text-white opacity-50 font-monospace">
                &copy; <?= date('Y') ?> CENCOCAL S.A. | Todos los derechos reservados.
            </p>
        </div>
    </div>
</footer>



<div class="modal fade" id="nosotrosModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">

            <div class="position-relative">
                <img src="<?= BASE_URL ?>img/banner/banner Cencocal.png" class="w-100 object-fit-cover" style="height: 200px; filter: brightness(0.7);" alt="Equipo Cencocal">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-light">

                <ul class="nav nav-pills nav-fill mb-4 p-1 bg-white rounded-pill shadow-sm" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill fw-bold" id="pills-objetivo-tab" data-bs-toggle="pill" data-bs-target="#pills-objetivo" type="button">Objetivo</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-bold" id="pills-mision-tab" data-bs-toggle="pill" data-bs-target="#pills-mision" type="button">Misión</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-bold" id="pills-vision-tab" data-bs-toggle="pill" data-bs-target="#pills-vision" type="button">Visión</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-bold" id="pills-valores-tab" data-bs-toggle="pill" data-bs-target="#pills-valores" type="button">Valores</button>
                    </li>
                </ul>

                <div class="tab-content" id="pills-tabContent">

                    <div class="tab-pane fade show active" id="pills-objetivo" role="tabpanel">
                        <div class="bg-white p-4 rounded-4 shadow-sm border-start border-5 border-cenco-green h-100">
                            <h5 class="fw-bold text-cenco-indigo mb-3"><i class="bi bi-bullseye me-2 text-cenco-green"></i>Nuestro Objetivo</h5>
                            <p class="text-muted text-justify mb-0">
                                Mantenernos como la mejor distribuidora de abarrotes de la zona norte y centro de nuestro país,
                                con altas expectativas de crecimiento geográfico en base a la <strong>conveniencia y confianza</strong> consolidada con nuestros clientes.
                                <br><br>
                                Nos basamos en el <strong>compromiso y agilidad</strong> en la entrega de nuestro amplio portafolio, asegurando una total satisfacción y experiencia de servicio.
                            </p>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pills-mision" role="tabpanel">
                        <div class="bg-white p-4 rounded-4 shadow-sm border-start border-5 border-primary h-100">
                            <h5 class="fw-bold text-cenco-indigo mb-3"><i class="bi bi-flag-fill me-2 text-primary"></i>Nuestra Misión</h5>
                            <p class="text-muted text-justify mb-0">
                                Convertirse en la compañía de distribución más conveniente y confiable a lo largo de nuestro país.
                                <br><br>
                                Lograremos esto a través del <strong>compromiso, ética y desarrollo</strong> de nuestros colaboradores,
                                satisfaciendo las necesidades de nuestros clientes y creando valor real en cada producto entregado.
                            </p>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pills-vision" role="tabpanel">
                        <div class="bg-white p-4 rounded-4 shadow-sm border-start border-5 border-warning h-100">
                            <h5 class="fw-bold text-cenco-indigo mb-3"><i class="bi bi-eye-fill me-2 text-warning"></i>Nuestra Visión</h5>
                            <p class="text-muted text-justify mb-0">
                                Ser la <strong>distribuidora líder en el mercado nacional</strong>, alcanzando una expansión geográfica exponencial
                                junto con un crecimiento financiero sólido.
                                <br><br>
                                Buscamos estar siempre a la <strong>vanguardia</strong> de los cambios que exigen nuestros clientes y fortalecer nuestras alianzas estratégicas.
                            </p>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pills-valores" role="tabpanel">
                        <div class="bg-white p-4 rounded-4 shadow-sm h-100">
                            <h5 class="fw-bold text-cenco-indigo mb-3 text-center"><i class="bi bi-gem me-2 text-cenco-red"></i>Nuestros Valores</h5>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 h-100">
                                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Ética y Compromiso</h6>
                                        <p class="small text-muted mb-0">Actuamos con integridad profesional, generando confianza en cada relación comercial.</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 h-100">
                                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-lightbulb-fill text-warning me-2"></i>Iniciativa e Innovación</h6>
                                        <p class="small text-muted mb-0">Buscamos siempre nuevas formas de mejorar y crecer junto a nuestros clientes.</p>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="p-3 bg-light rounded-3">
                                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-people-fill text-primary me-2"></i>Orientación al Cliente y Equipo</h6>
                                        <p class="small text-muted mb-0">Nuestro pilar son nuestros colaboradores. Fomentamos un liderazgo participativo y un ambiente de crecimiento personal y laboral.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>



            <div class="modal-footer border-0 bg-light p-0 position-relative overflow-hidden d-flex align-items-center justify-content-center" style="height: 70px;">
                <img src="<?= BASE_URL ?>img/logo.png"
                    alt="Cencocal"
                    class="img-fluid w-100 opacity-25"
                    style="height: 100%; object-fit: contain; filter: grayscale(100%); mix-blend-mode: multiply; transform: scale(0.8);">
            </div>

        </div>

    </div>
</div>