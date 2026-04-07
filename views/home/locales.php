<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
<style>
    .border-end-lg {
        border-right: 1px solid #eee;
    }

    @media (max-width: 991px) {
        .border-end-lg {
            border-right: none;
            border-bottom: 1px solid #eee;
        }
    }

    .object-fit-cover {
        object-fit: cover;
    }

    .spacing-list li {
        padding: 8px 0;
        border-bottom: 1px dashed #f0f0f0;
    }

    .spacing-list li:last-child {
        border-bottom: none;
    }

    .transition-hover {
        transition: transform 0.5s ease;
    }

    .card:hover .transition-hover {
        transform: scale(1.05);
    }

    /* NUEVO: Ajuste de altura del mapa para celulares vs escritorio */
    .map-container {
        min-height: 300px;
        height: 100%;
        width: 100%;
    }

    @media (min-width: 992px) {
        .map-container {
            min-height: 500px;
        }
    }

    /* Agrega esto al final de tu bloque <style> en la vista */
    @media (max-width: 991px) {
        .map-container {
            pointer-events: none;
            /* Desactiva el mapa por defecto en móvil */
        }

        .card:active .map-container,
        .card:focus .map-container {
            pointer-events: auto;
            /* Se activa al tocar la tarjeta */
        }
    }
</style>

<div class="container py-5">

    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>home" class="text-decoration-none text-muted">Inicio</a></li>
            <li class="breadcrumb-item active text-cenco-indigo fw-bold" aria-current="page">Nuestros Locales</li>
        </ol>
    </nav>

    <h2 class="fw-black text-cenco-indigo mb-2 ls-1"><i class="bi bi-geo-alt-fill me-2 text-cenco-green"></i>Nuestros Locales y Horarios</h2>
    <p class="text-muted mb-5">Visítanos en nuestras sucursales. Estamos listos para atenderte.</p>

    <div class="row g-4">
        <?php foreach ($sucursales as $index => $local): ?>
            <?php
            // Si por alguna razón el nombre viene vacío, nos saltamos esta iteración
            if (empty($local['nombre'])) continue;

            $imgSrc = !empty($local['imagen'])
                ? BASE_URL . 'img/salas/' . $local['imagen']
                : BASE_URL . 'img/no-image.png';
            ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
                    <div class="row g-0">

                        <div class="col-lg-5 bg-white border-end-lg d-flex flex-column">

                            <div class="position-relative" style="height: 250px; overflow: hidden;">
                                <img src="<?= $imgSrc ?>"
                                    alt="<?= htmlspecialchars($local['nombre'] ?? 'Sucursal') ?>"
                                    class="w-100 h-100 object-fit-cover transition-hover"
                                    onerror="this.src='<?= BASE_URL ?>img/logo.png'; this.style.objectFit='contain'; this.style.padding='20px';">

                                <div class="position-absolute bottom-0 start-0 w-100 p-3" style="background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);">
                                    <span class="badge bg-cenco-green shadow-sm text-uppercase ls-1">
                                        <i class="bi bi-shop me-1"></i> Sucursal Abierta
                                    </span>
                                </div>
                            </div>

                            <div class="p-4 flex-grow-1 d-flex flex-column justify-content-center">
                                <h4 class="fw-bold text-cenco-indigo mb-3"><?= htmlspecialchars($local['nombre'] ?? 'Sin nombre') ?></h4>

                                <ul class="list-unstyled text-muted small spacing-list mb-0">
                                    <li class="mb-2 d-flex">
                                        <i class="bi bi-pin-map-fill text-cenco-red fs-5 me-3"></i>
                                        <div>
                                            <strong class="d-block text-dark">Ubicación:</strong>
                                            <span class="d-block"><?= htmlspecialchars($local['direccion'] ?? 'Dirección no disponible') ?></span>

                                            <?php if (!empty($local['nombre_comuna']) || !empty($local['nombre_region'])): ?>
                                                <small class="text-cenco-indigo fw-bold">
                                                    <?= htmlspecialchars($local['nombre_comuna'] ?? '') ?><?= !empty($local['nombre_region']) ? ', ' . htmlspecialchars($local['nombre_region']) : '' ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </li>

                                    <li class="mb-2 d-flex">
                                        <i class="bi bi-clock-fill text-cenco-green fs-5 me-3"></i>
                                        <div>
                                            <strong class="d-block text-dark">Horario:</strong>
                                            <?= htmlspecialchars($local['horario'] ?? 'No especificado') ?>
                                        </div>
                                    </li>

                                    <li class="mb-2 d-flex">
                                        <i class="bi bi-person-badge-fill text-cenco-indigo fs-5 me-3"></i>
                                        <div>
                                            <strong class="d-block text-dark">Encargado de Local:</strong>
                                            <?= htmlspecialchars($local['nombre_encargado'] ?? 'Por asignar') ?>
                                        </div>
                                    </li>

                                    <li class="mb-2 d-flex">
                                        <i class="bi bi-whatsapp text-success fs-5 me-3"></i>
                                        <div>
                                            <strong class="d-block text-dark">Fono / WhatsApp:</strong>
                                            <?= htmlspecialchars($local['fono'] ?? 'No disponible') ?>
                                        </div>
                                    </li>

                                    <?php if (!empty($local['email'])): ?>
                                        <li class="d-flex">
                                            <i class="bi bi-envelope-at-fill text-secondary fs-5 me-3"></i>
                                            <div>
                                                <strong class="d-block text-dark">Email:</strong>
                                                <a href="mailto:<?= htmlspecialchars($local['email']) ?>" class="text-decoration-none text-cenco-indigo fw-bold">
                                                    <?= htmlspecialchars($local['email']) ?>
                                                </a>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div id="map-<?= $index ?>" class="bg-light map-container"></div>
                        </div>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const locales = <?= json_encode($sucursales) ?>;

        locales.forEach((local, index) => {
            const lat = parseFloat(local.latitud) || -33.4489;
            const lng = parseFloat(local.longitud) || -70.6693;

            const mapId = 'map-' + index;

            if (document.getElementById(mapId)) {
                // Configuramos zoomControl en false si estamos en móvil para evitar toques accidentales (Opcional, pero recomendado UX)
                const isMobile = window.innerWidth < 768;
                const map = L.map(mapId, {
                    zoomControl: !isMobile,
                    scrollWheelZoom: false // Evita que la página haga scroll al pasar el dedo/mouse sobre el mapa
                }).setView([lat, lng], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                const redIcon = new L.Icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                });

                // Sanitización básica en JS para evitar romper el mapa si hay comillas raras en la BD
                const nombreLimpio = local.nombre ? local.nombre.replace(/</g, "&lt;").replace(/>/g, "&gt;") : 'Sucursal';
                const direccionLimpia = local.direccion ? local.direccion.replace(/</g, "&lt;").replace(/>/g, "&gt;") : '';

                L.marker([lat, lng], {
                        icon: redIcon
                    })
                    .addTo(map)
                    .bindPopup(`<b>${nombreLimpio}</b><br>${direccionLimpia}`)
                    .openPopup();

                // Fix visual para que cargue bien al renderizar
                setTimeout(() => {
                    map.invalidateSize();
                }, 500);
            }
        });


    });
</script>