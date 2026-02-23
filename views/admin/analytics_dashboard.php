<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<div class="container-fluid py-4">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-0">Visión General</h2>
            <p class="text-muted small">Métricas de comportamiento de usuarios</p>
        </div>
        
        <form action="<?= BASE_URL ?>admin/analytics" method="GET" class="d-flex flex-wrap gap-2 mt-3 mt-md-0 bg-white p-2 rounded-pill shadow-sm border">
            
            <div class="input-group input-group-sm" style="width: auto;">
                <input type="date" name="desde" class="form-control border-0 bg-light rounded-start-pill ps-3" 
                       value="<?= $_GET['desde'] ?? date('Y-m-01') ?>" title="Desde">
                <span class="input-group-text bg-light border-0 text-muted">-</span>
                <input type="date" name="hasta" class="form-control border-0 bg-light rounded-end-pill pe-3" 
                       value="<?= $_GET['hasta'] ?? date('Y-m-d') ?>" title="Hasta">
            </div>

            <div class="input-group input-group-sm" style="width: 200px;">
                <span class="input-group-text bg-white border-0 ps-3"><i class="bi bi-person text-cenco-indigo"></i></span>
                <input type="text" name="q" class="form-control border-0" 
                       placeholder="Filtrar por Cliente..." 
                       value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </div>

            <button type="submit" class="btn btn-cenco-indigo rounded-pill px-3 fw-bold btn-sm">
                <i class="bi bi-funnel-fill"></i>
            </button>
            
            <?php if(!empty($_GET['q']) || isset($_GET['desde'])): ?>
                <a href="<?= BASE_URL ?>admin/analytics" class="btn btn-light text-danger rounded-circle btn-sm d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Limpiar Filtros">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-body position-relative">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 me-2">
                            <i class="bi bi-door-open-fill"></i>
                        </div>
                        <span class="text-muted small fw-bold text-uppercase ls-1">Tasa de Rebote</span>
                    </div>
                    <h3 class="fw-black text-cenco-indigo mb-0"><?= $kpis['rebote'] ?? 0 ?>%</h3>
                    <small class="text-muted" style="font-size: 0.75rem;">Visitas de una sola página</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-body position-relative">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-2">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <span class="text-muted small fw-bold text-uppercase ls-1">Tiempo Medio</span>
                    </div>
                    <h3 class="fw-black text-cenco-indigo mb-0"><?= $kpis['duracion_promedio'] ?? 0 ?> <span class="fs-6 text-muted fw-normal">min</span></h3>
                    <small class="text-muted" style="font-size: 0.75rem;">Duración de sesión</small>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body py-3">
                    <h6 class="fw-bold text-cenco-indigo mb-3 small text-uppercase">🔥 Interacciones (Top Clics)</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if(empty($clicsTop)): ?>
                            <span class="text-muted small">Sin datos para este periodo.</span>
                        <?php else: foreach($clicsTop as $clic): ?>
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
                <h5 class="fw-bold text-cenco-indigo mb-4">Tráfico (Sesiones)</h5>
                <div style="position: relative; height: 300px;">
                    <canvas id="trafficChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold text-cenco-indigo mb-0">📄 Páginas Más Vistas</h6>
                </div>
                <div class="list-group list-group-flush overflow-auto" style="max-height: 340px;">
                    <?php if(empty($paginasTop)): ?>
                        <div class="p-4 text-center text-muted small">Sin datos aún.</div>
                    <?php else: foreach($paginasTop as $index => $pag): ?>
                    <div class="list-group-item border-0 d-flex justify-content-between align-items-center px-4 py-3 hover-bg-light">
                        <div class="d-flex align-items-center overflow-hidden">
                            <span class="fw-bold text-muted me-3 opacity-50">#<?= $index + 1 ?></span>
                            <span class="text-truncate text-dark fw-semibold small" style="max-width: 180px;" title="/<?= $pag['url'] ?>">
                                /<?= htmlspecialchars($pag['url']) ?>
                            </span>
                        </div>
                        <span class="badge bg-cenco-indigo text-white rounded-pill px-3 shadow-sm">
                            <?= $pag['visitas'] ?>
                        </span>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
        <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-cenco-indigo mb-0"><i class="bi bi-geo-alt-fill me-2 text-danger"></i>Origen de Visitas</h5>
            <span class="badge bg-light text-muted border">Top Comunas</span>
        </div>
        <div id="analyticsMap" style="height: 450px; width: 100%;"></div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. GRÁFICO (Chart.js)
        const ctx = document.getElementById('trafficChart').getContext('2d');
        let gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(40, 53, 147, 0.2)'); 
        gradient.addColorStop(1, 'rgba(40, 53, 147, 0)');

        new Chart(ctx, {
            type: 'line', // Cambiamos a línea suave para mejor visualización de tiempo
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
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#283593'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 2. MAPA (Leaflet) - Mantenemos tu lógica existente
        if (typeof L !== 'undefined') {
            var map = L.map('analyticsMap').setView([-32.90, -71.4], 9); 
            
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap',
                subdomains: 'abcd',
                maxZoom: 19
            }).addTo(map);

            const coordsComunas = {
                "Santiago": [-33.4489, -70.6693],
                "Providencia": [-33.4314, -70.6093],
                "Las Condes": [-33.4117, -70.5826],
                "Viña del Mar": [-33.0245, -71.5518],
                "Valparaíso": [-33.0458, -71.6197],
                "Valparaiso": [-33.0458, -71.6197],
                "Quilpué": [-33.0498, -71.4390],
                "Quilpue": [-33.0498, -71.4390],
                "Villa Alemana": [-33.0445, -71.3734],
                "Limache": [-32.9989, -71.2669],
                "Olmué": [-32.9967, -71.1844],
                "Quillota": [-32.8794, -71.2464],
                "La Calera": [-32.7833, -71.2000],
                "Nogales": [-32.7333, -71.2000],
                "Hijuelas": [-32.7975, -71.1469],
                "La Cruz": [-32.8272, -71.2294],
                "Concón": [-32.9234, -71.5178],
                "Concon": [-32.9234, -71.5178],
                "San Felipe": [-32.7507, -70.7251],
                "Los Andes": [-32.8338, -70.5977]
            };

            const datosMapa = <?= json_encode($visitasMapa ?? []) ?>;

            if (datosMapa && datosMapa.length > 0) {
                datosMapa.forEach(item => {
                    let nombre = item.comuna;
                    if(!nombre) return;
                    let coord = coordsComunas[nombre] || coordsComunas[Object.keys(coordsComunas).find(key => key.toLowerCase() === nombre.toLowerCase())];

                    if (coord) {
                        let radio = Math.min(Math.max(item.visitas * 300, 800), 6000);
                        L.circle(coord, {
                            color: '#e63946',
                            fillColor: '#e63946',
                            fillOpacity: 0.5,
                            radius: radio,
                            weight: 1
                        }).addTo(map)
                        .bindPopup(`<div class="text-center"><strong class="text-cenco-indigo">${item.comuna}</strong><br><span class="badge bg-cenco-green text-white shadow-sm mt-1 fs-6">${item.visitas} Visitas</span></div>`);
                    }
                });
            }
        }
    });
</script>