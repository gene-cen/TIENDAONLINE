<div class="container-fluid min-vh-100 d-flex align-items-center bg-light">
    <div class="row w-100 g-0">
        <div class="col-md-6 p-4 p-md-5 d-flex flex-column justify-content-center align-items-center border-end border-2 border-white" style="background-color: var(--cenco-green);">
            <div class="text-center text-white">
                <i class="bi bi-person-vcard display-1 mb-3 d-block opacity-75"></i>
                <h1 class="fw-black display-5 mb-3">Únete al Equipo</h1>
                <p class="fs-5 mb-5 opacity-75">Buscamos talento, compromiso y ganas de crecer. Revisa nuestras vacantes y postula.</p>
                <a href="<?= BASE_URL ?>empleos/postulante" class="btn btn-light btn-lg rounded-pill fw-bold text-cenco-green px-5 hover-scale shadow">
                    Ver Ofertas Laborales
                </a>
            </div>
        </div>

      <div class="col-md-6 p-4 p-md-5 d-flex flex-column justify-content-center align-items-center" style="background-color: var(--cenco-indigo);">
            <div class="text-center text-white">
                <i class="bi bi-shield-lock display-1 mb-3 d-block opacity-75"></i>
                <h1 class="fw-black display-5 mb-3">Portal RRHH</h1>
                <p class="fs-5 mb-5 opacity-75">Acceso exclusivo para el equipo de Selección y Recursos Humanos de Cencocal.</p>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= BASE_URL ?>empleos/dashboardRRHH" class="btn btn-outline-light btn-lg rounded-pill fw-bold px-5 hover-scale">
                        Ingresar al Panel
                    </a>
                <?php else: ?>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#loginModal" 
                            class="btn btn-outline-light btn-lg rounded-pill fw-bold px-5 hover-scale"
                            onclick="document.querySelector('#loginModal form').action = '<?= BASE_URL ?>auth/login?redirect=empleos/dashboardRRHH'">
                        Iniciar Sesión RRHH
                    </button>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>