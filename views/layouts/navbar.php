<style>
    /* 1. NAVBAR: CAPA SUPREMA */
    nav.navbar {
        position: fixed !important;
        top: 0;
        width: 100%;
        z-index: 1200 !important;
        height: 110px;
        background-color: var(--cenco-indigo) !important;
    }

    /* 2. COMPENSACIÓN PARA EL CUERPO */
    body {
        padding-top: 110px;
        overflow-x: hidden;
    }

    /* 3. MENÚS LATERALES */
    #offcanvasCategorias,
    #offcanvasCarrito,
    #adminSidebar {
        top: 0 !important;
        height: 100vh !important;
        z-index: 1100 !important;
        padding-top: 110px;
        border-top: none !important;
        box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
    }

    /* 4. EL FONDO OSCURO */
    .offcanvas-backdrop {
        z-index: 1050 !important;
        opacity: 0.5 !important;
    }
    nav.navbar { z-index: 1200 !important; }

    /* 5. FIX SCROLLBAR */
    body.modal-open,
    body.offcanvas-active { padding-right: 0 !important; }

    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: var(--cenco-indigo); border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #303f9f; }

    /* Animaciones */
    @keyframes ping {
        0% { transform: scale(1); opacity: 1; }
        70% { transform: scale(2); opacity: 0; }
        100% { transform: scale(1); opacity: 0; }
    }
    .animate-ping { animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite; }
    .hover-scale { transition: transform 0.2s; }
    .hover-scale:hover { transform: scale(1.05); }
    .text-decoration-underline-hover:hover { text-decoration: underline !important; }
</style>

<nav class="navbar navbar-expand-lg navbar-dark bg-cenco-indigo shadow-sm py-2 border-bottom border-3" style="border-color: var(--cenco-green) !important;">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-3 py-0" href="<?= BASE_URL ?>home">
            <div class="bg-white rounded-4 d-flex align-items-center justify-content-center shadow-sm transition-hover overflow-hidden"
                style="height: 75px; min-width: 180px; padding: 0;">
                <img src="<?= BASE_URL ?>img/logo.png" alt="Cencocal" class="img-fluid" style="height: 100%; width: 100%; object-fit: contain;">
            </div>
        </a>

        <button class="btn text-white p-1 me-2 hover-scale transition-hover d-flex align-items-center gap-2"
            type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCategorias">
            <i class="bi bi-list" style="font-size: 2rem;"></i>
            <span class="fw-bold d-none d-sm-inline text-uppercase" style="font-size: 0.85rem; letter-spacing: 1px;">Categorías</span>
        </button>

        <div class="d-flex align-items-center me-auto ms-2">
            <button class="btn btn-outline-light border-0 d-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow-sm transition-hover"
                type="button" data-bs-toggle="modal" data-bs-target="#modalComuna"
                style="background: rgba(255,255,255,0.1);">
                <div class="position-relative">
                    <i class="bi bi-geo-alt-fill text-cenco-green fs-4"></i>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle animate-ping"></span>
                </div>
                <div class="text-start lh-1">
                    <span class="d-block opacity-75" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Despacho en:</span>
                    <span id="nombreComunaNav" class="fw-bold text-white" style="font-size: 0.95rem;">
                        <?= $_SESSION['comuna_nombre'] ?? 'La Calera' ?>
                    </span>
                </div>
                <i class="bi bi-chevron-down small opacity-50 ms-1"></i>
            </button>
        </div>

        <div class="ms-auto d-flex align-items-center gap-3 gap-lg-4">
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                <button class="btn btn-warning text-dark fw-bold rounded-pill px-3 d-flex align-items-center gap-2 shadow-sm hover-scale"
                    type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span class="d-none d-md-inline">Admin</span>
                </button>
            <?php endif; ?>

            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'transportista'): ?>
                <a href="<?= BASE_URL ?>transporte/misEntregas" class="btn btn-info text-white fw-bold rounded-pill px-3 d-flex align-items-center gap-2 shadow-sm hover-scale">
                    <i class="bi bi-truck-flatbed"></i>
                    <span class="d-none d-md-inline">Mis Entregas</span>
                </a>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="dropdown">
                    <a class="text-decoration-none text-white d-flex align-items-center gap-2 dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center overflow-hidden border border-2 border-cenco-green shadow-sm" style="width: 42px; height: 42px;">
                            <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_positivo.png" class="img-fluid" style="transform: scale(1.1) translateY(2px);">
                        </div>
                        <div class="d-none d-md-block lh-1">
                            <span class="d-block small opacity-75">Hola, <?= explode(' ', $_SESSION['user_nombre'])[0] ?></span>
                            <span class="fw-bold">Mi Cuenta</span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>perfil"><i class="bi bi-person-gear me-2 text-cenco-green"></i> Mi Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-cenco-red fw-bold" href="<?= BASE_URL ?>auth/logout"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <button type="button" class="btn text-white d-flex align-items-center gap-2 p-0 border-0 bg-transparent" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <div class="bg-white rounded-circle d-flex align-items-center justify-content-center overflow-hidden border border-2 border-white shadow-sm" style="width: 42px; height: 42px;">
                        <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_positivo.png" class="img-fluid" style="transform: scale(1.1) translateY(2px);">
                    </div>
                    <div class="d-none d-md-block lh-1 text-start">
                        <span class="d-block small opacity-75">¡Bienvenido/a!</span>
                        <span class="fw-bold text-decoration-underline-hover">Inicia Sesión</span>
                    </div>
                </button>
            <?php endif; ?>

            <button type="button" class="btn position-relative text-white border-0 p-1 d-flex align-items-center gap-2" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCarrito" onclick="actualizarCarritoLateral()">
                <div class="position-relative">
                    <i class="bi bi-cart-fill fs-3"></i>
                    <span id="badge-carrito-navbar" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-cenco-green border border-2 border-cenco-indigo fw-bold badge-carrito" style="<?= empty($_SESSION['carrito']) ? 'display:none;' : '' ?>">
                        <?= isset($_SESSION['carrito']) ? array_sum(array_column($_SESSION['carrito'], 'cantidad')) : 0 ?>
                    </span>
                </div>
                <div class="d-none d-md-block text-start lh-1">
                    <span class="d-block small opacity-75" style="font-size: 0.7rem;">Mi Carro</span>
                    <span id="monto-carrito-navbar" class="fw-bold text-white monto-carrito" style="font-size: 0.9rem;">
                        $<?= number_format(isset($_SESSION['carrito']) ? array_sum(array_map(function ($it) {
                                return $it['precio'] * $it['cantidad'];
                            }, $_SESSION['carrito'])) : 0, 0, ',', '.') ?>
                    </span>
                </div>
            </button>
        </div>
    </div>
</nav>

<div class="modal fade" id="modalComuna" tabindex="-1" aria-labelledby="modalComunaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4 pb-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-cenco-indigo p-3 rounded-circle text-white shadow-sm">
                        <i class="bi bi-geo-alt-fill fs-3"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-black text-cenco-indigo mb-0">¿Dónde entregamos?</h5>
                        <p class="mb-0 mt-1" style="font-size: 0.85rem; color: #555;">
                            Estás viendo el stock en la
                            <span class="fw-bold text-cenco-green">
                                <?php
                                $sucursalActual = $_SESSION['sucursal_activa'] ?? 29;
                                echo ($sucursalActual == 29) ? 'Sucursal Prat de La Calera' : 'Sucursal Villa Alemana';
                                ?>
                            </span>
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-2">
                    <div class="col-12">
                        <p class="fw-bold text-cenco-indigo small mb-2 border-bottom pb-1">Sucursal Prat - La Calera</p>
                    </div>

                    <div class="col-12 mb-2">
                        <button onclick="cambiarComunaRapida('La Calera', 29)" class="btn btn-outline-secondary w-100 text-center py-3 px-3 small fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm transition-hover">
                            <i class="bi bi-shop fs-5 text-cenco-indigo"></i> La Calera (Solo Retiro en Tienda)
                        </button>
                    </div>

                    <div class="col-12 mt-4">
                        <p class="fw-bold text-cenco-green small mb-2 border-bottom pb-1">Sucursal Villa Alemana</p>
                    </div>
                    <?php
                    // Las comunas de la costa sí mantienen su despacho normal
                    $costa = ['Villa Alemana', 'Quilpué', 'Peñablanca', 'Viña del Mar', 'Valparaíso', 'Concón'];
                    foreach ($costa as $nombre): ?>
                        <div class="col-6 col-md-4">
                            <button onclick="cambiarComunaRapida('<?= $nombre ?>', 10)" class="btn btn-outline-success w-100 text-start py-2 px-3 small transition-hover"><?= $nombre ?></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const comunaCargada = <?= isset($_SESSION['comuna_id']) ? 'true' : 'false' ?>;
        const yaIntentoGeoloc = sessionStorage.getItem('geoloc_intentada');

        if (!comunaCargada && !yaIntentoGeoloc) {
            sessionStorage.setItem('geoloc_intentada', 'true');
            solicitarGeolocalizacion();
        }
    });

    function solicitarGeolocalizacion() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    fetch('<?= BASE_URL ?>location/detectar', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `lat=${pos.coords.latitude}&lng=${pos.coords.longitude}`
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                // Aquí podrías deducir si es 10 o 29 según la comuna, pero si falla:
                                cambiarComunaDirecto(data.comuna_id, data.comuna_nombre, 29); 
                            }
                        })
                        .catch(err => console.error("Error detectando:", err));
                },
                (error) => {
                    console.warn("GPS denegado, asignando La Calera por defecto.");
                    cambiarComunaDirecto(63, 'La Calera', 29); 
                }
            );
        } else {
            cambiarComunaDirecto(63, 'La Calera', 29);
        }
    }

    function cambiarComunaDirecto(id, nombre, sucursal) {
        fetch('<?= BASE_URL ?>location/actualizar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `comuna_id=${id}&nombre=${nombre}&sucursal_id=${sucursal}`
            })
            .then(() => { window.location.reload(); });
    }

    // MODIFICADO: Agregamos el parámetro sucursal a la función js
    function cambiarComunaRapida(nombre, sucursal, confirmado = 0) {
        const formData = new FormData();
        formData.append('nombre', nombre);
        formData.append('sucursal_id', sucursal); // <--- ESTO ES LA CLAVE
        formData.append('confirmado', confirmado); 

        fetch(BASE_URL + 'location/actualizar_por_nombre', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'requiere_confirmacion') {
                    Swal.fire({
                        icon: 'warning',
                        title: data.titulo,
                        html: data.html,
                        showCancelButton: true,
                        confirmButtonColor: '#2A1B5E',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, cambiar y ajustar',
                        cancelButtonText: 'No, me quedo donde estoy',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            cambiarComunaRapida(nombre, sucursal, 1);
                        }
                    });
                }
                else if (data.status === 'success') {
                    location.reload(); 
                } else {
                    Swal.fire('Error', 'No pudimos cambiar la comuna', 'error');
                }
            })
            .catch(err => console.error("Error cambiando comuna:", err));
    }
</script>