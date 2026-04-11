<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
<link rel="stylesheet" href="<?= BASE_URL ?>css/shop/locales.css">

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
            $imgSrc = !empty($local['imagen']) ? BASE_URL . 'img/salas/' . $local['imagen'] : BASE_URL . 'img/no-image.png';
            ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
                    <div class="row g-0">
                        <div class="col-lg-5 bg-white border-end-lg d-flex flex-column">
                            <div class="position-relative" style="height: 250px; overflow: hidden;">
                                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($local['nombre'] ?? 'Sucursal') ?>" class="w-100 h-100 object-fit-cover transition-hover" onerror="this.src='<?= BASE_URL ?>img/logo.png'; this.style.objectFit='contain'; this.style.padding='20px';">
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
    // Pasamos el array PHP a JS
    window.LocalesData = <?= json_encode($sucursales) ?>;
</script>
<script src="<?= BASE_URL ?>js/shop/locales.js"></script>