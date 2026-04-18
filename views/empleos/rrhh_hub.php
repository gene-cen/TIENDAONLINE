<style>
    /* Efectos de elevación para las tarjetas */
    .hover-elevate { 
        transition: transform 0.3s ease, box-shadow 0.3s ease; 
        border: 1px solid rgba(0,0,0,0.05); 
    }
    .hover-elevate:hover { 
        transform: translateY(-8px); 
        box-shadow: 0 15px 30px rgba(42, 27, 94, 0.1) !important; 
        border-color: var(--cenco-indigo); 
    }
    .icon-circle {
        width: 90px;
        height: 90px;
        transition: transform 0.3s ease;
    }
    .hover-elevate:hover .icon-circle {
        transform: scale(1.1);
    }
    .btn-module {
        transition: all 0.3s ease;
    }
    .hover-elevate:hover .btn-module {
        background-color: var(--cenco-indigo);
        color: white !important;
        border-color: var(--cenco-indigo);
    }
</style>

<div class="container-fluid py-5 px-lg-5 min-vh-100 bg-light" style="max-width: 1200px; margin: 0 auto;">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5 gap-3">
        <div class="text-center text-md-start">
            <h2 class="fw-black text-cenco-indigo mb-1" style="letter-spacing: -0.5px;">Centro de Recursos Humanos</h2>
            <p class="text-muted mb-0 fs-6">Selecciona un módulo para gestionar el personal y las postulaciones.</p>
        </div>
        <div class="d-flex align-items-center">
            <span class="badge bg-white text-cenco-indigo border px-4 py-2 rounded-pill shadow-sm d-flex align-items-center fs-6">
                <i class="bi bi-person-badge fs-5 me-2 text-cenco-green"></i> 
                <?= htmlspecialchars($_SESSION['user_nombre'] ?? 'Equipo RRHH') ?>
            </span>
        </div>
    </div>

    <div class="row g-4 justify-content-center mt-2">

        <div class="col-md-6 col-lg-5">
            <a href="<?= BASE_URL ?>empleos/rrhh_reclutamiento" class="text-decoration-none">
                <div class="card bg-white shadow-sm rounded-4 h-100 hover-elevate border-0 text-center p-4 p-lg-5">
                    <div class="d-flex justify-content-center mb-4">
                        <div class="icon-circle bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-file-earmark-person fs-1"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold text-cenco-indigo mb-3">Reclutamiento y Selección</h4>
                    <p class="text-secondary mb-4 px-xl-3" style="line-height: 1.6;">
                        Revisa, filtra y gestiona los currículums y postulaciones recibidas a través del portal de empleos público.
                    </p>
                    <div class="mt-auto pt-3 border-top border-light">
                        <span class="btn btn-outline-primary btn-module rounded-pill fw-bold px-4 py-2 w-100">
                            Ingresar al Módulo <i class="bi bi-arrow-right ms-2"></i>
                        </span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-5">
            <a href="<?= BASE_URL ?>empleos/rrhh_mantenedor" class="text-decoration-none">
                <div class="card bg-white shadow-sm rounded-4 h-100 hover-elevate border-0 text-center p-4 p-lg-5">
                    <div class="d-flex justify-content-center mb-4">
                        <div class="icon-circle bg-success bg-opacity-10 text-cenco-green rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-briefcase-fill fs-1"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold text-cenco-indigo mb-3">Mantenedor de Cargos</h4>
                    <p class="text-secondary mb-4 px-xl-3" style="line-height: 1.6;">
                        Crea nuevas ofertas laborales, edita los requisitos o pausa los cargos que ya no están disponibles para postulación.
                    </p>
                    <div class="mt-auto pt-3 border-top border-light">
                        <span class="btn btn-outline-success btn-module rounded-pill fw-bold px-4 py-2 w-100" style="border-color: var(--cenco-green); color: var(--cenco-green);">
                            Gestionar Cargos <i class="bi bi-arrow-right ms-2"></i>
                        </span>
                    </div>
                </div>
            </a>
        </div>

    </div>
</div>