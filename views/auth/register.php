<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Completo - CENCOCAL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .input-group-text { background-color: #e9ecef; }
        /* Ocultar el mapa por defecto hasta que marquen el check */
        #mapa-container { display: none; margin-top: 10px; }
    </style>
</head>
<body class="d-flex align-items-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                
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
                                <label class="form-label small fw-bold">RUT (Empresa o Persona)</label>
                                <input type="text" name="rut" id="rut" class="form-control" placeholder="12.345.678-9" required oninput="checkRut(this)">
                                <small class="text-muted" id="rut-feedback"></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Nombre o Razón Social</label>
                                <input type="text" name="nombre" id="nombre" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control" required>
                                <div class="form-text">Te enviaremos un correo de validación.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Celular</label>
                                <div class="input-group">
                                    <span class="input-group-text">+569</span>
                                    <input type="tel" name="telefono" class="form-control" placeholder="12345678" pattern="[0-9]{8}" maxlength="8" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Contraseña</label>
                                <input type="password" name="password" class="form-control" minlength="6" required>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="checkDireccion" onchange="toggleDireccion()">
                                <label class="form-check-label small" for="checkDireccion">Quiero agregar mi dirección de despacho</label>
                            </div>

                            <div id="direccion-box" style="display:none;">
                                <div class="mb-3">
                                    <input type="text" name="direccion" id="direccion-input" class="form-control" placeholder="Escribe tu dirección...">
                                </div>
                                </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold">
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

    <div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4">
                <img src="<?= BASE_URL ?>img/mascota.png" class="img-fluid mx-auto mb-3" style="max-height: 120px;">
                <h3 class="text-success fw-bold">¡Casi listo!</h3>
                <p>Hemos enviado un correo de confirmación. Por favor revísalo para activar tu cuenta.</p>
                <div class="alert alert-warning small">
                    (Modo Prueba: Revisa el archivo <b>public/simulacion_email.txt</b>)
                </div>
                <a href="<?= BASE_URL ?>auth/login" class="btn btn-outline-primary w-100">Volver al Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // 1. Formatear RUT y "Simular" API SII
        function checkRut(rutInput) {
            // Limpiar formato actual
            var valor = rutInput.value.replace(/\./g, '').replace(/-/g, '');
            
            // Lógica simple de formato (Puntos y guión)
            if (valor.length > 1) {
                var cuerpo = valor.slice(0, -1);
                var dv = valor.slice(-1).toUpperCase();
                rutInput.value = cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, ".") + "-" + dv;

                // SIMULACIÓN DE API SII (Solo para demo)
                // Si el RUT es de empresa (> 50 millones), rellenamos datos
                if(parseInt(cuerpo) > 50000000) {
                    document.getElementById('nombre').value = "EMPRESA DEMO S.A.";
                    document.getElementById('rut-feedback').innerText = "✅ Empresa encontrada en SII (Datos Simulados)";
                    document.getElementById('rut-feedback').classList.add('text-success');
                } else {
                    document.getElementById('rut-feedback').innerText = "";
                }
            }
        }

        // 2. Mostrar/Ocultar Dirección
        function toggleDireccion() {
            var check = document.getElementById('checkDireccion');
            var box = document.getElementById('direccion-box');
            if(check.checked) {
                box.style.display = 'block';
                // Aquí llamarías a initMap() si tuvieras la API Key
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