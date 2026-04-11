<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-header bg-cenco-indigo text-white p-4 border-0 rounded-top-4">
                    <h3 class="fw-black mb-0"><i class="bi bi-briefcase me-2"></i> Postulación Cencocal</h3>
                    <p class="mb-0 mt-1 opacity-75 small">Completa tus datos cuidadosamente.</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form action="<?= BASE_URL ?>empleos/guardar" method="POST" enctype="multipart/form-data" id="formPostulacion">
                        
                        <h5 class="fw-bold text-cenco-green mb-3 border-bottom pb-2">1. ¿A dónde deseas postular?</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small">Sucursal / Instalación</label>
                                <select name="sucursal" id="selectSucursal" class="form-select" required>
                                    <option value="">Seleccione una ubicación...</option>
                                    <option value="Femacal">Sucursal Femacal (La Calera)</option>
                                    <option value="Prat">Sucursal Prat (La Calera)</option>
                                    <option value="Bodega Nogales">Centro de Distribución (Nogales)</option>
                                    <option value="Casa Matriz">Casa Matriz</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small">Cargo de Interés</label>
                                <select name="cargo_id" id="selectCargo" class="form-select" required disabled>
                                    <option value="">Primero seleccione sucursal</option>
                                </select>
                            </div>
                            <div class="col-12" id="infoCargoContainer" style="display:none;">
                                <div class="alert alert-secondary border-0 border-start border-4 border-cenco-indigo small mb-0">
                                    <strong>Acerca del cargo:</strong> <span id="descripcionCargo"></span>
                                </div>
                            </div>
                        </div>

                        <h5 class="fw-bold text-cenco-green mb-3 border-bottom pb-2">2. Datos Personales</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small">Nombres</label>
                                <input type="text" name="nombres" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small">Apellidos</label>
                                <input type="text" name="apellidos" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small">RUT</label>
                                <input type="text" name="rut" id="inputRut" class="form-control" placeholder="99.999.999-9" maxlength="12" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-muted small">Edad</label>
                                <input type="number" name="edad" class="form-control" min="18" max="99" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-muted small">Sexo</label>
                                <select name="sexo" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="Masculino">Masculino</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-muted small">Nacionalidad</label>
                                <select name="nacionalidad_tipo" id="selectNacionalidad" class="form-select" required>
                                    <option value="Chilena">Chilena</option>
                                    <option value="Extranjera">Extranjera</option>
                                </select>
                            </div>
                            
                            <div class="col-md-8" id="divExtranjero" style="display:none;">
                                <div class="row g-2">
                                    <div class="col-sm-6">
                                        <label class="form-label fw-bold text-muted small text-danger">País de Origen</label>
                                        <select name="pais_origen" id="selectPais" class="form-select">
                                            <option value="">Seleccione País...</option>
                                            <option value="Argentina">Argentina</option>
                                            <option value="Bolivia">Bolivia</option>
                                            <option value="Brasil">Brasil</option>
                                            <option value="Colombia">Colombia</option>
                                            <option value="Ecuador">Ecuador</option>
                                            <option value="Paraguay">Paraguay</option>
                                            <option value="Perú">Perú</option>
                                            <option value="Uruguay">Uruguay</option>
                                            <option value="Venezuela">Venezuela</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label fw-bold text-muted small text-danger">Permiso de Trabajo</label>
                                        <select name="permiso_trabajo" id="selectPermiso" class="form-select">
                                            <option value="">Seleccione...</option>
                                            <option value="Si">Sí, vigente</option>
                                            <option value="En tramite">En trámite</option>
                                            <option value="No">No</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="fw-bold text-cenco-green mb-3 border-bottom pb-2">3. Contacto y Experiencia</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small">Comuna de Residencia</label>
                                <select name="comuna" class="form-select" required>
                                    <option value="">Seleccione comuna...</option>
                                    <?php if(!empty($comunas)): ?>
                                        <?php foreach($comunas as $c): ?>
                                            <option value="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="La Calera">La Calera</option>
                                        <option value="La Cruz">La Cruz</option>
                                        <option value="Quillota">Quillota</option>
                                        <option value="Nogales">Nogales</option>
                                        <option value="Hijuelas">Hijuelas</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small">Teléfono Celular</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">+56 9</span>
                                    <input type="text" name="telefono" class="form-control" maxlength="8" placeholder="12345678" required>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-muted small">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold text-muted small">Breve resumen de su última experiencia laboral</label>
                                <textarea name="experiencia" class="form-control" rows="3" required placeholder="Indique empresa, cargo y tiempo trabajado..."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold text-danger small">Adjuntar Curriculum (OBLIGATORIO - PDF/Word)</label>
                                <input type="file" name="cv" class="form-control border-danger" accept=".pdf,.doc,.docx" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-cenco-indigo btn-lg w-100 fw-bold rounded-pill shadow-sm">
                            Enviar Postulación <i class="bi bi-send ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. FORMATEADOR DE RUT DINÁMICO
    const inputRut = document.getElementById('inputRut');
    inputRut.addEventListener('input', function(e) {
        let valor = this.value.replace(/[^0-9kK]/g, ''); // Quitar todo lo que no sea número o K
        if (valor.length > 1) {
            let cuerpo = valor.slice(0, -1);
            let dv = valor.slice(-1).toUpperCase();
            // Poner puntos al cuerpo
            cuerpo = cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            this.value = cuerpo + '-' + dv;
        } else {
            this.value = valor;
        }
    });

    // 2. LÓGICA EXTRANJEROS
    const selectNacionalidad = document.getElementById('selectNacionalidad');
    const divExtranjero = document.getElementById('divExtranjero');
    const selectPais = document.getElementById('selectPais');
    const selectPermiso = document.getElementById('selectPermiso');
    
    selectNacionalidad.addEventListener('change', function() {
        if(this.value === 'Extranjera') {
            divExtranjero.style.display = 'block'; // Mostrar combobox de paises
            selectPais.required = true;
            selectPermiso.required = true;
        } else {
            divExtranjero.style.display = 'none';
            selectPais.required = false;
            selectPermiso.required = false;
            selectPais.value = '';
            selectPermiso.value = '';
        }
    });

    // 3. LÓGICA SUCURSAL -> CARGOS (AJAX)
    const selectSucursal = document.getElementById('selectSucursal');
    const selectCargo = document.getElementById('selectCargo');
    const infoCargoContainer = document.getElementById('infoCargoContainer');
    const descCargo = document.getElementById('descripcionCargo');
    let cargosActuales = [];

    selectSucursal.addEventListener('change', function() {
        const suc = this.value;
        selectCargo.innerHTML = '<option value="">Cargando...</option>';
        selectCargo.disabled = true;
        infoCargoContainer.style.display = 'none';

        if(suc) {
            fetch(`<?= BASE_URL ?>empleos/getCargosAjax?sucursal=${encodeURIComponent(suc)}`)
            .then(res => res.json())
            .then(data => {
                cargosActuales = data;
                selectCargo.innerHTML = '<option value="">Seleccione un cargo...</option>';
                data.forEach(c => {
                    selectCargo.innerHTML += `<option value="${c.id}">${c.nombre}</option>`;
                });
                selectCargo.disabled = false;
            });
        }
    });

    // Mostrar descripción del cargo
    selectCargo.addEventListener('change', function() {
        const id = this.value;
        const cargo = cargosActuales.find(c => c.id == id);
        if(cargo) {
            descCargo.textContent = cargo.descripcion;
            infoCargoContainer.style.display = 'block';
        } else {
            infoCargoContainer.style.display = 'none';
        }
    });

    // ==========================================
    // 4. ALERTAS SWEETALERT2 (CENCOCALIN)
    // ==========================================
    <?php if(isset($_GET['msg']) && $_GET['msg'] === 'exito'): ?>
        Swal.fire({
            title: '¡Postulación Enviada!',
            text: 'Hemos recibido tus datos con éxito. Nuestro equipo de selección los revisará a la brevedad.',
            imageUrl: '<?= BASE_URL ?>img/cencocalin/cencocalin_positivo.png',
            imageWidth: 100,
            imageHeight: 100,
            imageAlt: 'Cencocalin',
            confirmButtonColor: '#2A1B5E'
        }).then(() => {
            window.location.href = '<?= BASE_URL ?>empleos';
        });
    <?php endif; ?>

    <?php if(isset($_GET['msg']) && $_GET['msg'] === 'duplicado'): ?>
        Swal.fire({
            icon: 'warning',
            title: 'Postulación en Curso',
            text: 'Ya hemos recibido vuestra solicitud anteriormente con este RUT. ¡Mantente atento a tu teléfono o correo!',
            confirmButtonColor: '#2A1B5E'
        }).then(() => {
            // Limpiar la URL para que no vuelva a saltar al recargar
            window.history.replaceState(null, null, window.location.pathname);
        });
    <?php endif; ?>

    <?php if(isset($_GET['msg']) && $_GET['msg'] === 'error_cv'): ?>
        Swal.fire({
            icon: 'error',
            title: 'Falta tu Curriculum',
            text: 'Es obligatorio adjuntar tu CV en formato PDF o Word para postular.',
            confirmButtonColor: '#d33'
        });
    <?php endif; ?>
});
</script>