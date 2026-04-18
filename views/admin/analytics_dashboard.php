<?php
/**
 * VISTA: analytics_dashboard.php
 * Ubicación: views/admin/
 * Descripción: Dashboard de métricas de comportamiento y tráfico.
 */
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="container-fluid py-4">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-0">Visión General</h2>
            <p class="text-muted small">Métricas de comportamiento de usuarios y tráfico web</p>
        </div>

        <form action="<?= BASE_URL ?>admin/analytics" method="GET" class="d-flex flex-wrap gap-2 bg-white p-2 rounded-pill shadow-sm border">
            <div class="input-group input-group-sm" style="width: auto;">
                <input type="date" name="desde" class="form-control border-0 bg-light rounded-start-pill ps-3"
                    value="<?= htmlspecialchars($_GET['desde'] ?? date('Y-m-01')) ?>">
                <span class="input-group-text bg-light border-0 text-muted">-</span>
                <input type="date" name="hasta" class="form-control border-0 bg-light rounded-end-pill pe-3"
                    value="<?= htmlspecialchars($_GET['hasta'] ?? date('Y-m-d')) ?>">
            </div>

            <div class="input-group input-group-sm" style="width: 200px;">
                <span class="input-group-text bg-white border-0 ps-3"><i class="bi bi-person text-cenco-indigo"></i></span>
                <input type="text" name="q" class="form-control border-0" placeholder="Filtrar cliente..."
                    value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </div>

            <button type="submit" class="btn btn-cenco-indigo rounded-pill px-3 fw-bold btn-sm">
                <i class="bi bi-funnel-fill"></i>
            </button>

            <?php if (!empty($_GET['q']) || isset($_GET['desde'])): ?>
                <a href="<?= BASE_URL ?>admin/analytics" class="btn btn-light text-danger rounded-circle btn-sm d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 me-2">
                            <i class="bi bi-door-open-fill"></i>
                        </div>
                        <span class="text-muted small fw-bold text-uppercase ls-1">Tasa de Rebote</span>
                    </div>
                    <h3 class="fw-black text-cenco-indigo mb-0"><?= number_format($kpis['rebote'] ?? 0, 1) ?>%</h3>
                    <small class="text-muted opacity-75">Sesiones de una sola página</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-2">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <span class="text-muted small fw-bold text-uppercase ls-1">Tiempo Medio</span>
                    </div>
                    <h3 class="fw-black text-cenco-indigo mb-0"><?= $kpis['duracion_promedio'] ?? 0 ?> <small class="fs-6 text-muted fw-normal">min</small></h3>
                    <small class="text-muted opacity-75">Duración promedio de sesión</small>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body py-3">
                    <h6 class="fw-bold text-cenco-indigo mb-3 small text-uppercase opacity-75">🔥 Interacciones Populares (Clics)</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (empty($clicsTop)): ?>
                            <span class="text-muted small italic">Sin datos de clics registrados.</span>
                        <?php else: foreach ($clicsTop as $clic): ?>
                            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill d-flex align-items-center gap-2">
                                <?= htmlspecialchars($clic['etiqueta']) ?>
                                <span class="badge bg-cenco-green text-white rounded-pill shadow-sm"><?= $clic['total'] ?></span>
                            </span>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h5 class="fw-bold text-cenco-indigo mb-4">Tráfico Temporal (Sesiones Únicas)</h5>
                <div style="position: relative; height: 320px;">
                    <canvas id="trafficChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold text-cenco-indigo mb-0">📄 Top 10 Páginas más visitadas</h6>
                </div>
                <div class="list-group list-group-flush overflow-auto" style="max-height: 340px;">
                    <?php if (empty($paginasTop)): ?>
                        <div class="p-4 text-center text-muted small">Sin registros en el periodo.</div>
                    <?php else: foreach ($paginasTop as $index => $pag): ?>
                        <div class="list-group-item border-0 d-flex justify-content-between align-items-center px-4 py-3 hover-bg-light transition-all">
                            <div class="d-flex align-items-center overflow-hidden">
                                <span class="fw-bold text-muted me-3 opacity-50">#<?= $index + 1 ?></span>
                                <span class="text-truncate text-dark fw-semibold small" style="max-width: 180px;" title="<?= htmlspecialchars($pag['url']) ?>">
                                    /<?= htmlspecialchars($pag['url']) ?>
                                </span>
                            </div>
                            <span class="badge bg-cenco-indigo text-white rounded-pill px-3 shadow-sm">
                                <?= number_format($pag['visitas']) ?>
                            </span>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
        <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-cenco-indigo mb-0"><i class="bi bi-geo-alt-fill me-2 text-danger"></i>Concentración de Visitas por Comuna</h5>
            <span class="badge bg-light text-muted border px-3">Datos basados en perfiles de usuario</span>
        </div>
        <div id="analyticsMap" style="height: 480px; width: 100%;" class="bg-light"></div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // 1. CONFIGURACIÓN DEL GRÁFICO (Chart.js)
    const trafficCanvas = document.getElementById('trafficChart');
    if (trafficCanvas) {
        const ctx = trafficCanvas.getContext('2d');
        let gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(40, 53, 147, 0.3)');
        gradient.addColorStop(1, 'rgba(40, 53, 147, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartLabels ?? []) ?>,
                datasets: [{
                    label: 'Sesiones',
                    data: <?= json_encode($chartData ?? []) ?>,
                    borderColor: '#283593',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#283593',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { padding: 12, cornerRadius: 8 } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#f0f0f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 2. CONFIGURACIÓN DEL MAPA MUNDIAL (Leaflet)
    if (typeof L !== 'undefined' && document.getElementById('analyticsMap')) {
        
        // 🗺️ Centramos en la Región de Valparaíso por defecto (Zoom 9)
        const map = L.map('analyticsMap').setView([-32.88, -71.25], 9); 

        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CartoDB',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);

        // Recibimos la nueva data global
        const datosMapa = <?= json_encode($visitasMapaGlobal ?? []) ?>;
        console.log("🗺️ Datos Globales del Mapa:", datosMapa);

        const bounds = [];

        datosMapa.forEach(item => {
            if (item.lat && item.lng) {
                const coord = [parseFloat(item.lat), parseFloat(item.lng)];
                const nVisitas = parseInt(item.visitas || 0);
                
                // 🎨 ARTE DEL MAPA: Ajustamos el tamaño de la burbuja.
                // Minimo 2000 metros (2km) para que cubra la comuna, máximo 15km si hay muchísimas visitas.
                const radioFinal = Math.min(Math.max(nVisitas * 800, 2000), 15000); 

                L.circle(coord, {
                    color: '#e63946',
                    fillColor: '#e63946',
                    fillOpacity: 0.6, // Un poco más oscuro para que resalte
                    radius: radioFinal,
                    weight: 2
                }).addTo(map)
                .bindPopup(`
                    <div class="text-center p-1">
                        <strong class="text-cenco-indigo d-block text-uppercase">${item.ciudad}</strong>
                        <small class="text-muted d-block mb-1">${item.pais}</small>
                        <span class="badge bg-cenco-green text-white px-2 py-1">${nVisitas} Visitas</span>
                    </div>
                `);

                bounds.push(coord);
            }
        });

        // 🔭 CÁMARA INTELIGENTE: Si hay datos, encuadramos la cámara para que todos se vean.
        // Si solo hay datos de La Calera, maxZoom en 11 evita que se acerque "hasta la calle".
        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [50, 50], maxZoom: 11 });
        }
    }
});
</script>