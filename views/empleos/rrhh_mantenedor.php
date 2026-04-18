<div class="container-fluid px-4 py-4 bg-light min-vh-100">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-1"><i class="bi bi-briefcase-fill me-2"></i>Mantenedor de Cargos</h2>
            <p class="text-muted mb-0">Gestiona los perfiles que aparecen en el formulario de postulación.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="<?= BASE_URL ?>empleos/rrhh_dashboard" class="btn btn-outline-secondary fw-bold rounded-pill shadow-sm hover-scale">
                <i class="bi bi-arrow-left me-1"></i>Volver al Hub
            </a>
            <button type="button" class="btn btn-cenco-green text-white fw-bold rounded-pill shadow-sm hover-scale" onclick="abrirModalCargo()">
                <i class="bi bi-plus-circle me-1"></i>Nuevo Cargo
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light border-bottom">
                    <tr>
                        <th class="ps-4 py-3 text-muted small fw-bold">Cargo</th>
                        <th class="py-3 text-muted small fw-bold">Descripción</th>
                        <th class="py-3 text-muted small fw-bold">Entorno</th>
                        <th class="py-3 text-muted small fw-bold text-center">Estado (Visible)</th>
                        <th class="pe-4 py-3 text-end text-muted small fw-bold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cargos)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No hay cargos registrados.</td></tr>
                    <?php else: foreach ($cargos as $c): 
                        $badgeBg = '';
                        $icono = '';
                        if ($c['tipo_sucursal'] === 'tienda') { $badgeBg = 'bg-primary'; $icono = 'bi-shop'; }
                        if ($c['tipo_sucursal'] === 'bodega') { $badgeBg = 'bg-warning text-dark'; $icono = 'bi-boxes'; }
                        if ($c['tipo_sucursal'] === 'casa_matriz') { $badgeBg = 'bg-dark'; $icono = 'bi-building'; }
                    ?>
                        <tr>
                            <td class="ps-4 fw-bold text-cenco-indigo"><?= htmlspecialchars($c['nombre']) ?></td>
                            <td class="text-muted small" style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($c['descripcion']) ?>">
                                <?= htmlspecialchars($c['descripcion']) ?>
                            </td>
                            <td>
                                <span class="badge <?= $badgeBg ?> rounded-pill px-2">
                                    <i class="bi <?= $icono ?> me-1"></i><?= ucfirst(str_replace('_', ' ', $c['tipo_sucursal'])) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input" type="checkbox" role="switch" style="cursor:pointer;" 
                                        <?= $c['activo'] ? 'checked' : '' ?> 
                                        onchange="toggleCargo(<?= $c['id'] ?>, this.checked)">
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-cenco-indigo rounded-circle shadow-sm" title="Editar" 
                                    onclick='abrirModalCargo(<?= json_encode($c) ?>)'>
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCargo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-cenco-indigo text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalCargoTitle"><i class="bi bi-briefcase me-2"></i>Añadir Nuevo Cargo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>empleos/guardarCargo" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="cargo_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Nombre del Cargo</label>
                        <input type="text" name="nombre" id="cargo_nombre" class="form-control" required placeholder="Ej: Operario de Bodega">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Tipo de Sucursal / Entorno</label>
                        <select name="tipo_sucursal" id="cargo_tipo" class="form-select" required>
                            <option value="tienda">Tienda / Local</option>
                            <option value="bodega">Bodega / Centro de Distribución</option>
                            <option value="casa_matriz">Casa Matriz</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Descripción o Requisitos Breves</label>
                        <textarea name="descripcion" id="cargo_desc" class="form-control" rows="3" required placeholder="Describe brevemente las funciones o requisitos del cargo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill fw-bold px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-cenco-green text-white rounded-pill fw-bold px-4">Guardar Cargo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Magia para abrir el Modal y llenarlo si es edición
function abrirModalCargo(cargoData = null) {
    const modal = new bootstrap.Modal(document.getElementById('modalCargo'));
    const form = document.querySelector('#modalCargo form');
    
    if (cargoData) {
        // Modo Edición
        document.getElementById('modalCargoTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Editar Cargo';
        document.getElementById('cargo_id').value = cargoData.id;
        document.getElementById('cargo_nombre').value = cargoData.nombre;
        document.getElementById('cargo_tipo').value = cargoData.tipo_sucursal;
        document.getElementById('cargo_desc').value = cargoData.descripcion;
    } else {
        // Modo Nuevo
        document.getElementById('modalCargoTitle').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Añadir Nuevo Cargo';
        form.reset();
        document.getElementById('cargo_id').value = '';
    }
    
    modal.show();
}

// Magia AJAX para el switch de Activo/Inactivo
function toggleCargo(id, isChecked) {
    const estado = isChecked ? 1 : 0;
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('activo', estado);

    fetch('<?= BASE_URL ?>empleos/toggleCargoAjax', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: isChecked ? 'Cargo Visible en Formulario' : 'Cargo Ocultado',
                showConfirmButton: false,
                timer: 2000
            });
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Hubo un problema de conexión.', 'error');
        // Revertir el switch si falla
        event.target.checked = !isChecked;
    });
}

// Alerta de éxito al guardar
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('msg') === 'exito') {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Cargo guardado correctamente',
            showConfirmButton: false,
            timer: 2000
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>