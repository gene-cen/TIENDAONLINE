<div class="container-fluid px-4 py-4 bg-light min-vh-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-1"><i class="bi bi-star-fill me-2 text-warning"></i>Mantenedor de Marcas</h2>
            <p class="text-muted mb-0">Gestiona las marcas que aparecen en la grilla principal (Recomendado: 8 marcas).</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form action="<?= BASE_URL ?>admin/marcas/guardar" method="POST" enctype="multipart/form-data">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Nombre de la Marca</label>
                        <input type="text" name="nombre" class="form-control border-light shadow-sm" placeholder="Ej: Coca Cola" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Orden</label>
                        <input type="number" name="orden" class="form-control border-light shadow-sm text-center" value="1" min="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Logo (PNG transparente recomendado)</label>
                        <input type="file" name="imagen" class="form-control border-light shadow-sm" accept="image/*" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-cenco-indigo w-100 fw-bold shadow-sm">Subir Logo</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

   <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Logo</th>
                        <th>Nombre</th>
                        <th class="text-center">Orden</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($marcas)): ?>
                        <tr><td colspan="5" class="text-center py-4">No hay marcas configuradas.</td></tr>
                    <?php else: foreach($marcas as $m): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="bg-white border rounded p-2 d-flex align-items-center justify-content-center" style="width: 80px; height: 50px;">
                                <img src="<?= BASE_URL . $m['ruta_imagen'] ?>" class="img-fluid" style="max-height: 100%;">
                            </div>
                        </td>
                        <td class="fw-bold"><?= htmlspecialchars($m['nombre']) ?></td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border px-3 py-2"><?= $m['orden'] ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill <?= $m['estado_activo'] ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $m['estado_activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <button onclick="abrirModalEditarMarca(<?= $m['id'] ?>, '<?= htmlspecialchars($m['nombre'], ENT_QUOTES) ?>', <?= $m['orden'] ?>)" class="btn btn-sm btn-light border text-primary">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <a href="<?= BASE_URL ?>admin/marcas/borrar/<?= $m['id'] ?>" class="btn btn-sm btn-light border text-danger" onclick="return confirm('¿Eliminar marca?')">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editarMarcaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-cenco-indigo">Editar Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form action="<?= BASE_URL ?>admin/marcas/actualizar" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit_marca_id">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombre</label>
                        <input type="text" name="nombre" id="edit_marca_nombre" class="form-control border-light shadow-sm" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Orden</label>
                        <input type="number" name="orden" id="edit_marca_orden" class="form-control border-light shadow-sm text-center" min="1" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Cambiar Logo (Opcional)</label>
                        <input type="file" name="imagen" class="form-control border-light shadow-sm" accept="image/*">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-cenco-indigo rounded-pill fw-bold">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function abrirModalEditarMarca(id, nombre, orden) {
    document.getElementById('edit_marca_id').value = id;
    document.getElementById('edit_marca_nombre').value = nombre;
    document.getElementById('edit_marca_orden').value = orden;
    new bootstrap.Modal(document.getElementById('editarMarcaModal')).show();
}
</script>