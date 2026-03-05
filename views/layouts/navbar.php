<style>
    /* GARANTIZAR NAVBAR FIJO Y VISIBLE */
    nav.navbar {
        position: fixed !important;
        top: 0;
        width: 100%;
        z-index: 1080 !important;
    }

    /* COMPENSACIÓN DE ALTURA */
    body {
        padding-top: 110px;
    }

    /* OFFCANVAS DEBAJO DEL NAVBAR */
    #offcanvasCategorias,
    #offcanvasCarrito {
        top: 110px !important;
        height: calc(100vh - 110px) !important;
        z-index: 1070;
    }

    .offcanvas-backdrop {
        top: 110px !important;
        z-index: 1060;
    }

    /* Animación del Punto de Ubicación */
    @keyframes ping {
        0% {
            transform: scale(1);
            opacity: 1;
        }

        70% {
            transform: scale(2);
            opacity: 0;
        }

        100% {
            transform: scale(1);
            opacity: 0;
        }
    }

    .animate-ping {
        animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
    }

    /* Hover effects */
    .hover-scale {
        transition: transform 0.2s;
    }

    .hover-scale:hover {
        transform: scale(1.05);
    }

    .text-decoration-underline-hover:hover {
        text-decoration: underline !important;
    }
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

            <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin'): ?>
                <button class="btn btn-warning text-dark fw-bold rounded-pill px-3 d-flex align-items-center gap-2 shadow-sm hover-scale"
                    type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span class="d-none d-md-inline">Admin</span>
                </button>
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
                        <li>
                            <hr class="dropdown-divider">
                        </li>
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
                    <span id="contador-carrito" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-cenco-green border border-2 border-cenco-indigo fw-bold">
                        <?= isset($_SESSION['carrito']) ? array_sum(array_column($_SESSION['carrito'], 'cantidad')) : 0 ?>
                    </span>
                </div>
                <div class="d-none d-md-block text-start lh-1">
                    <span class="d-block small opacity-75" style="font-size: 0.7rem;">Mi Carro</span>
                    <span id="total-monto-navbar" class="fw-bold text-white" style="font-size: 0.9rem;">
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
                        <p class="fw-bold text-cenco-indigo small mb-2 border-bottom pb-1">Zona Interior</p>
                    </div>
                    <?php
                    $interior = ['La Calera', 'La Cruz', 'Quillota', 'Nogales', 'Hijuelas', 'Limache'];
                    foreach ($interior as $nombre): ?>
                        <div class="col-6 col-md-4">
                            <button onclick="cambiarComunaRapida('<?= $nombre ?>')" class="btn btn-outline-secondary w-100 text-start py-2 px-3 small"><?= $nombre ?></button>
                        </div>
                    <?php endforeach; ?>

                    <div class="col-12 mt-3">
                        <p class="fw-bold text-cenco-green small mb-2 border-bottom pb-1">Zona Costa</p>
                    </div>
                    <?php
                    $costa = ['Villa Alemana', 'Quilpué', 'Peñablanca', 'Viña del Mar', 'Valparaíso', 'Concón'];
                    foreach ($costa as $nombre): ?>
                        <div class="col-6 col-md-4">
                            <button onclick="cambiarComunaRapida('<?= $nombre ?>')" class="btn btn-outline-success w-100 text-start py-2 px-3 small"><?= $nombre ?></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Verificamos si ya tenemos una comuna en la sesión de PHP (pasada desde el servidor)
        const comunaCargada = <?= isset($_SESSION['comuna_id']) ? 'true' : 'false' ?>;

        // 2. Verificamos si ya intentamos geolocalizar en esta visita (Freno de mano)
        const yaIntentoGeoloc = sessionStorage.getItem('geoloc_intentada');

        if (!comunaCargada && !yaIntentoGeoloc) {
            // Marcamos que ya lo vamos a intentar para que la próxima recarga no entre aquí
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
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `lat=${pos.coords.latitude}&lng=${pos.coords.longitude}`
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                // Guardamos y recargamos solo una vez
                                cambiarComunaDirecto(data.comuna_id, data.comuna_nombre);
                            }
                        })
                        .catch(err => console.error("Error detectando:", err));
                },
                (error) => {
                    console.warn("GPS denegado, asignando La Calera por defecto.");
                    cambiarComunaDirecto(63, 'La Calera'); // Forzamos el default para romper el bucle
                }
            );
        } else {
            cambiarComunaDirecto(63, 'La Calera');
        }
    }

    // Función auxiliar para no repetir código
    function cambiarComunaDirecto(id, nombre) {
        fetch('<?= BASE_URL ?>location/actualizar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `comuna_id=${id}&nombre=${nombre}`
            })
            .then(() => {
                window.location.reload();
            });
    }

    // Agregamos un segundo parámetro opcional 'confirmado'
    function cambiarComunaRapida(nombre, confirmado = 0) {
        const formData = new FormData();
        formData.append('nombre', nombre);
        formData.append('confirmado', confirmado); // Enviamos el estado de confirmación

        fetch(BASE_URL + 'location/actualizar_por_nombre', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                // SI EL BACKEND NOS PIDE PERMISO (Simulacro encontró problemas)
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
                            // El usuario aceptó las consecuencias, llamamos de nuevo pero forzando la acción
                            cambiarComunaRapida(nombre, 1);
                        }
                    });
                }
                // SI TODO ESTÁ OK (O el usuario ya confirmó)
                else if (data.status === 'success') {
                    location.reload(); // Recargamos la página para ver la nueva zona
                } else {
                    Swal.fire('Error', 'No pudimos cambiar la comuna', 'error');
                }
            })
            .catch(err => console.error("Error cambiando comuna:", err));
    }
</script>