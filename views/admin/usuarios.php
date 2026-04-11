<style>
    .bg-soft-blue { background-color: #f0f8ff !important; border: 1px solid #cfe2ff !important; }
    .form-label-custom { font-size: 0.85rem; font-weight: 700; color: var(--cenco-indigo); text-transform: uppercase; letter-spacing: 0.5px; }
    .table-vertical-center td { vertical-align: middle; }
</style>

<div class="container-fluid px-4 py-4 bg-light min-vh-100">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-1"><i class="bi bi-people-fill me-2 text-primary"></i>Gestión de Usuarios</h2>
            <p class="text-secondary mb-0">Administra los roles y los accesos a créditos de confianza.</p>
        </div>
        <div class="mt-3 mt-md-0">
            <span class="badge bg-primary text-white fs-6 px-3 py-2 shadow-sm"><i class="bi bi-person-lines-fill me-1"></i> <?= count($usuarios_lista) ?> Usuarios Registrados</span>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 border-top border-4 border-primary">
        <div class="table-responsive">
            <table class="table table-hover table-vertical-center mb-0 bg-white">
                <thead class="bg-light text-cenco-indigo">
                    <tr style="font-size:0.9rem;">
                        <th class="ps-4 py-3">Usuario / Email</th>
                        <th class="py-3">RUT</th>
                        <th class="py-3">Rol</th>
                        <th class="py-3 text-center">Crédito VIP</th>
                        <th class="py-3 text-end pe-4">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios_lista)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No hay usuarios registrados.</td></tr>
                    <?php else: foreach ($usuarios_lista as $u): ?>
                    <tr id="row-<?= $u['id'] ?>">
                        <td class="ps-4 py-3">
                            <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?= htmlspecialchars($u['nombre'] ?? 'Sin Nombre') ?></div>
                            <div class="text-muted small"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($u['email']) ?></div>
                        </td>
                        
                        <td class="py-3 font-monospace small">
                            <?= !empty($u['rut']) ? htmlspecialchars($u['rut']) : '<span class="text-muted fst-italic">No ingresado</span>' ?>
                        </td>

                        <td class="py-3">
                            <?php if($u['rol'] === 'admin'): ?>
                                <span class="badge bg-danger shadow-sm"><i class="bi bi-shield-lock-fill me-1"></i> Admin</span>
                            <?php elseif($u['rol'] === 'transportista'): ?>
                                <span class="badge bg-info text-white shadow-sm"><i class="bi bi-truck me-1"></i> Transporte</span>
                            <?php else: ?>
                                <span class="badge bg-secondary shadow-sm"><i class="bi bi-person-fill me-1"></i> Cliente</span>
                            <?php endif; ?>
                        </td>

                        <td class="text-center py-3">
                            <?php if ($u['es_cliente_confianza'] == 1): ?>
                                <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill shadow-sm">
                                    <i class="bi bi-star-fill text-warning me-1"></i> Habilitado
                                </span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border px-3 py-2 rounded-pill">
                                    <i class="bi bi-dash-circle me-1"></i> Normal
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="text-end pe-4 py-3">
                            <button class="btn btn-light border text-primary px-3 py-2 shadow-sm transition-hover" onclick="abrirModalUsuario(<?= $u['id'] ?>)" title="Editar Usuario">
                                <i class="bi bi-pencil-square me-1"></i> Editar
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0 pt-4 px-4 bg-white">
                <h5 class="modal-title fw-black text-cenco-indigo"><i class="bi bi-person-gear me-2 text-primary"></i>Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                
                <form id="formUsuario">
                    <input type="hidden" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label-custom mb-1 text-muted">Correo Electrónico <i class="bi bi-lock-fill ms-1"></i></label>
                        <input type="email" id="edit_email" class="form-control bg-light text-muted" disabled title="El correo no se puede modificar por seguridad.">
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom mb-1">Nombre Completo *</label>
                        <input type="text" id="edit_nombre" class="form-control border-primary shadow-sm text-capitalize" required>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label-custom mb-1">RUT</label>
                            <input type="text" id="edit_rut" class="form-control border-secondary shadow-sm" placeholder="Ej: 11.111.111-1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom mb-1">Rol de Sistema</label>
                            <select id="edit_rol" class="form-select border-secondary shadow-sm fw-bold text-cenco-indigo">
                                <option value="cliente">Cliente Web</option>
                                <option value="transportista">Transportista</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-success-subtle p-3 rounded-3 border border-success mb-4 shadow-sm">
                        <div class="form-check form-switch d-flex align-items-center justify-content-between p-0">
                            <label class="form-check-label fw-bold text-success" for="edit_confianza">
                                <i class="bi bi-star-fill text-warning me-1"></i> Cliente de Confianza
                                <span class="d-block small fw-normal text-dark opacity-75 mt-1" style="font-size: 0.75rem;">Le permite realizar pedidos con pago contra entrega o crédito.</span>
                            </label>
                            <input class="form-check-input fs-3 ms-3" type="checkbox" id="edit_confianza" style="cursor:pointer;">
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="button" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm" id="btnGuardarUsuario" onclick="guardarUsuario()">
                            Guardar Cambios <i class="bi bi-check2-circle ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // 1. Traer datos y abrir Modal
    function abrirModalUsuario(id) {
        // Mostramos un loader rápido
        Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });

        fetch('<?= BASE_URL ?>admin/usuarios/get', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(res => res.json())
        .then(data => {
            Swal.close();
            if(data.status === 'success') {
                const u = data.data;
                document.getElementById('edit_id').value = u.id;
                document.getElementById('edit_email').value = u.email;
                document.getElementById('edit_nombre').value = u.nombre;
                document.getElementById('edit_rut').value = u.rut;
                document.getElementById('edit_rol').value = u.rol;
                document.getElementById('edit_confianza').checked = (u.es_cliente_confianza == 1);

                new bootstrap.Modal(document.getElementById('modalUsuario')).show();
            } else {
                Swal.fire('Error', data.msg, 'error');
            }
        })
        .catch(() => Swal.fire('Error', 'Fallo de conexión al servidor.', 'error'));
    }

    // 2. Guardar Datos por AJAX
    function guardarUsuario() {
        const id = document.getElementById('edit_id').value;
        const nombre = document.getElementById('edit_nombre').value;
        const rut = document.getElementById('edit_rut').value;
        const rol = document.getElementById('edit_rol').value;
        const confianza = document.getElementById('edit_confianza').checked ? 1 : 0;

        if (nombre.trim() === '') {
            Swal.fire('Atención', 'El nombre no puede estar vacío.', 'warning');
            return;
        }

        const btn = document.getElementById('btnGuardarUsuario');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
        btn.disabled = true;

        fetch('<?= BASE_URL ?>admin/usuarios/update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: id,
                nombre: nombre,
                rut: rut,
                rol: rol,
                es_cliente_confianza: confianza
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                Swal.fire({icon: 'success', title: '¡Guardado!', text: 'El usuario se actualizó correctamente.', timer: 1500, showConfirmButton: false});
                setTimeout(() => location.reload(), 1500); 
            } else {
                Swal.fire('Error', data.msg, 'error');
            }
        })
        .catch(() => Swal.fire('Error', 'Fallo al guardar los datos.', 'error'))
        .finally(() => { btn.innerHTML = originalText; btn.disabled = false; });
    }
</script>