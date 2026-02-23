<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Completo - CENCOCAL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    
    <style>
        body { background-color: #f4f7f6; }
        .input-group-text { background-color: #e9ecef; }
        
        /* Aseguramos que el contenedor tenga altura aunque esté oculto */
        #mapa-container { 
            height: 300px; 
            width: 100%; 
            border-radius: 8px; 
            margin-top: 10px; 
            z-index: 0; /* Evita que tape el autocompletado del navegador */
        }
    </style>
</head>
<body class="d-flex align-items-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                             <img src="<?= BASE_URL ?>img/logo.png" alt="CENCOCAL" style="max-width: 120px;">
                             <h4 class="mt-3 fw-bold text-primary">Crear Cuenta</h4>
                        </div>

                        <?php if (isset($error) && $error): ?>
                            <div class="alert alert-danger small"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form action="<?= BASE_URL ?>auth/register" method="POST" id="formRegistro">
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Contraseña</label>
                                <input type="password" name="password" class="form-control" minlength="6" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Celular</label>
                                <div class="input-group">
                                    <span class="input-group-text">+569</span>
                                    <input type="tel" name="telefono" class="form-control" placeholder="12345678" pattern="[0-9]{8}" maxlength="8" required>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-3">
                                <label class="form-label small fw-bold">RUT (Empresa o Persona)</label>
                                <div class="input-group">
                                    <input type="text" name="rut" id="rut" class="form-control" placeholder="12.345.678-9" required oninput="formatearRut(this)">
                                    <button class="btn btn-outline-primary" type="button" id="btnBuscarRut">
                                        <i class="bi bi-search"></i> Buscar Datos
                                    </button>
                                </div>
                                <small class="text-muted" id="rut-feedback">Ingresa RUT y presiona buscar para autocompletar.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Nombre o Razón Social</label>
                                <input type="text" name="nombre" id="nombre" class="form-control" required>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Giro Comercial</label>
                                    <input type="text" name="giro" id="giro" class="form-control" placeholder="Ej: Venta de repuestos...">
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="checkDireccion" onchange="toggleDireccion()">
                                <label class="form-check-label small" for="checkDireccion">Quiero agregar mi dirección de despacho ahora</label>
                            </div>

                            <div id="direccion-box" style="display:none;" class="card bg-light p-3 mb-3 border-0">
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">Dirección de Despacho</label>
                                    
                                    <div class="input-group">
                                        <input type="text" name="direccion" id="direccion-input" class="form-control" placeholder="Ej: Av. Libertador 1234, Santiago" autocomplete="off">
                                        <button class="btn btn-primary" type="button" id="btnBuscarDireccion" onclick="buscarEnMapa()">
                                            <i class="bi bi-geo-alt"></i> Ubicar
                                        </button>
                                    </div>
                                    <div class="form-text small">Escribe tu dirección y presiona <b>Ubicar</b>, o mueve el pin manualmente.</div>
                                </div>

                                <div id="mapa-container"></div>
                                
                                <input type="hidden" name="latitud" id="latitud">
                                <input type="hidden" name="longitud" id="longitud">
                                
                                <div class="mt-2 text-muted small">
                                    <i class="bi bi-info-circle-fill text-primary"></i> Lat: <span id="lbl-lat">-</span>, Lon: <span id="lbl-lon">-</span>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold mt-2">
                                Registrarme
                            </button>
                        </form>

                        <div class="text-center mt-3">
                            <small>¿Ya tienes cuenta? <a href="<?= BASE_URL ?>auth/login">Ingresa aquí</a></small>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>

    <script>
        const BASE_URL = "<?= BASE_URL ?>"; 

        // --- LÓGICA MAPA OPENSTREETMAP ---
        let map;
        let marker;

        function initMap() {
            // Coordenadas iniciales (Santiago)
            const lat = -33.4489;
            const lng = -70.6693;

            // Si el mapa ya existe, no lo recreamos
            if(map) return;

            // Crear el mapa
            map = L.map('mapa-container').setView([lat, lng], 13);

            // Cargar las capas visuales (OpenStreetMap)
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            // Crear el Pin Arrastrable
            marker = L.marker([lat, lng], {draggable: true}).addTo(map);

            // EVENTO: Al terminar de arrastrar el pin
            marker.on('dragend', function(event) {
                const position = marker.getLatLng();
                actualizarCoords(position.lat, position.lng);
            });

            // EVENTO: Al hacer clic en el mapa
            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                actualizarCoords(e.latlng.lat, e.latlng.lng);
            });

            actualizarCoords(lat, lng);
        }

        async function buscarEnMapa() {
            const direccion = document.getElementById('direccion-input').value;
            const btn = document.getElementById('btnBuscarDireccion');

            if(direccion.length < 4) {
                alert("Por favor escribe una dirección más completa (Calle, Número, Comuna).");
                return;
            }

            // UI: Cargando
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Buscando...';

            try {
                // CAMBIO CLAVE: Llamamos a TU backend, no a osm.org directamente
                const url = BASE_URL + 'auth/geolocalizar?direccion=' + encodeURIComponent(direccion);
                
                const response = await fetch(url);
                const data = await response.json();

                if(data && data.length > 0) {
                    // ¡ÉXITO! Encontramos la calle
                    const lat = data[0].lat;
                    const lon = data[0].lon;
                    
                    // Convertir a números para Leaflet
                    const nuevaPos = new L.LatLng(lat, lon);
                    
                    // Mover mapa y pin
                    map.setView(nuevaPos, 16);
                    marker.setLatLng(nuevaPos);
                    
                    // Guardar en inputs ocultos
                    actualizarCoords(lat, lon);
                } else {
                    alert("No encontramos esa dirección exacta. Prueba agregando la comuna (ej: 'Calle 123, La Calera') o mueve el pin manualmente.");
                }

            } catch (e) {
                console.error(e);
                alert("Hubo un error al buscar. Por favor usa el pin manual.");
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-geo-alt"></i> Ubicar';
            }
        }
        function actualizarCoords(lat, lng) {
            document.getElementById('latitud').value = lat;
            document.getElementById('longitud').value = lng;
            // Visual para el usuario
            document.getElementById('lbl-lat').innerText = parseFloat(lat).toFixed(5);
            document.getElementById('lbl-lon').innerText = parseFloat(lng).toFixed(5);
        }

        // --- LÓGICA RUT ---
        // (Tu código de RUT existente sigue aquí igual que antes)
        function formatearRut(rutInput) {
            var valor = rutInput.value.replace(/\./g, '').replace(/-/g, '');
            if (valor.length > 1) {
                var cuerpo = valor.slice(0, -1);
                var dv = valor.slice(-1).toUpperCase();
                rutInput.value = cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, ".") + "-" + dv;
            }
        }
        document.getElementById('btnBuscarRut').addEventListener('click', async function() {
            // (Mismo código de siempre para el RUT...)
            const rutInput = document.getElementById('rut');
            const rut = rutInput.value.trim();
            const btn = this;
            const feedback = document.getElementById('rut-feedback');

            if(rut.length < 8) return; // Validación simple

            btn.disabled = true;
            btn.innerHTML = '...';
            
            try {
                const response = await fetch(BASE_URL + 'auth/consultar_sii?rut=' + rut);
                const data = await response.json();
                if(data.success) {
                    document.getElementById('nombre').value = data.razon_social || '';
                    document.getElementById('giro').value = data.giro || '';
                    feedback.innerText = "✅ OK";
                    feedback.className = "text-success small";
                } else {
                    feedback.innerText = "⚠️ No encontrado";
                }
            } catch (e) {
                // Fallback
                feedback.innerText = "⚠️ Error red";
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-search"></i> Buscar Datos';
            }
        });

        // Mostrar/Ocultar Mapa
        function toggleDireccion() {
            var check = document.getElementById('checkDireccion');
            var box = document.getElementById('direccion-box');
            
            if(check.checked) {
                box.style.display = 'block';
                // Inicializar mapa si no existe
                if(!map) {
                    initMap();
                }
                // HACK CRUCIAL: Forzar renderizado después de que el div es visible
                setTimeout(function(){ 
                    map.invalidateSize(); 
                }, 200);
            } else {
                box.style.display = 'none';
            }
        }
    </script>

    <?php if (isset($success) && $success): ?>
    <script>
        new bootstrap.Modal(document.getElementById('successModal')).show();
    </script>
    <?php endif; ?>
</body>
</html>