<style>
    /* Efecto de escala al pasar el mouse por los botones */
    .hover-scale { transition: transform 0.2s ease-in-out; }
    .hover-scale:hover { transform: scale(1.05); }
    
    /* Degradados sutiles para quitar el aspecto "plano" y tosco */
    .bg-gradient-green { background: linear-gradient(135deg, var(--cenco-green) 0%, #5ca332 100%); }
    .bg-gradient-indigo { background: linear-gradient(135deg, var(--cenco-indigo) 0%, #1f1445 100%); }
    
    /* Círculo para contener los iconos elegantemente */
    .icon-circle { 
        width: 80px; 
        height: 80px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        border-radius: 50%; 
    }
</style>

<div class="container-fluid min-vh-100 d-flex flex-column justify-content-center align-items-center bg-light py-5">
    
    <div class="w-100 px-3" style="max-width: 850px;">
        
        <div class="card border-0 rounded-4 shadow-lg mb-4 overflow-hidden bg-gradient-green">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-md-row align-items-center text-center text-md-start">
                <div class="pe-md-5 mb-4 mb-md-0">
                    <div class="bg-white bg-opacity-25 icon-circle mx-auto mx-md-0 shadow-sm">
                        <i class="bi bi-person-vcard fs-1 text-white"></i>
                    </div>
                </div>
                <div class="flex-grow-1 text-white">
                    <h2 class="fw-black mb-2">Únete al Equipo</h2>
                    <p class="fs-6 mb-4 opacity-75">Buscamos talento, compromiso y ganas de crecer. Revisa nuestras vacantes y postula.</p>
                    <a href="<?= BASE_URL ?>empleos/postulante" class="btn btn-light rounded-pill fw-bold text-cenco-green px-4 py-2 hover-scale shadow-sm">
                        Ver Ofertas Laborales
                    </a>
                </div>
            </div>
        </div>

        <div class="card border-0 rounded-4 shadow-lg overflow-hidden bg-gradient-indigo">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-md-row align-items-center text-center text-md-start">
                <div class="pe-md-5 mb-4 mb-md-0">
                    <div class="bg-white bg-opacity-10 icon-circle mx-auto mx-md-0 shadow-sm">
                        <i class="bi bi-shield-lock fs-1 text-white"></i>
                    </div>
                </div>
                <div class="flex-grow-1 text-white">
                    <h2 class="fw-black mb-2">Portal RRHH</h2>
                    <p class="fs-6 mb-4 opacity-75">Acceso exclusivo para el equipo de Selección y Recursos Humanos de Cencocal.</p>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?= BASE_URL ?>empleos/dashboardRRHH" class="btn btn-outline-light rounded-pill fw-bold px-4 py-2 hover-scale">
                            Ingresar al Panel
                        </a>
                    <?php else: ?>
                        <button type="button" data-bs-toggle="modal" data-bs-target="#loginModal" 
                                class="btn btn-outline-light rounded-pill fw-bold px-4 py-2 hover-scale"
                                onclick="document.querySelector('#loginModal form').action = '<?= BASE_URL ?>auth/login?redirect=empleos/dashboardRRHH'">
                            Iniciar Sesión RRHH
                        </button>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>

    </div>
</div>