<div class="container-fluid px-4 py-4 bg-light min-vh-100">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-1">
                <i class="bi bi-images me-2"></i>Gestión de Banners
            </h2>
            <p class="text-muted mb-0">Administra las imágenes del carrusel principal de la portada.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom border-light">
            <h5 class="mb-0 fw-bold text-cenco-indigo"><i class="bi bi-plus-circle-fill me-2"></i>Añadir Nuevo Banner</h5>
        </div>
        <div class="card-body p-4">
            <form action="<?= BASE_URL ?>admin/banners/guardar" method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label text-muted small fw-bold text-uppercase">Título (Opcional)</label>
                        <input type="text" name="titulo" class="form-control border-light shadow-sm" placeholder="Ej: CyberDay">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted small fw-bold text-uppercase">Orden</label>
                        <input type="number" name="orden" class="form-control border-light shadow-sm text-center fw-bold" value="1" min="1" title="El número 1 aparecerá primero">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label text-muted small fw-bold text-uppercase">Imagen del Banner</label>
                        <input type="file" name="imagen" class="form-control border-light shadow-sm" accept="image/*" required>
                    </div>
                </div>
                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-cenco-indigo shadow-sm fw-bold px-4">
                        <i class="bi bi-cloud-arrow-up-fill me-2"></i>Subir Banner
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 px-4 border-bottom border-light">
            <h6 class="fw-bold text-cenco-indigo mb-0">Banners Publicados</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaBanners">
                    <thead class="bg-light border-bottom">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold text-uppercase border-0">Imagen</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0">Título</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0 text-center">Orden</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0 text-center">Estado</th>
                            <th class="pe-4 py-3 text-muted small fw-bold text-uppercase border-0 text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($banners)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-images fs-1 d-block mb-2 opacity-50"></i>
                                    No hay banners configurados.
                                </td>
                            </tr>
                        <?php else: foreach($banners as $banner): ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="bg-light border rounded-3 overflow-hidden shadow-sm" style="width: 140px; height: 60px;">
                                        <img src="<?= BASE_URL ?><?= htmlspecialchars($banner['ruta_imagen']) ?>" class="w-100 h-100 object-fit-cover" onerror="this.src='<?= BASE_URL ?>img/no-image.png';">
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <?= !empty($banner['titulo']) ? htmlspecialchars($banner['titulo']) : '<span class="text-muted fst-italic small">Sin título</span>' ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-3 py-2 fs-6"><?= $banner['orden'] ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if($banner['estado_activo'] == 1): ?>
                                        <span class="badge rounded-pill bg-success px-3">Activo</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill bg-secondary px-3">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button onclick="toggleBanner(<?= $banner['id'] ?>, this)" class="btn btn-sm btn-light border shadow-sm" title="<?= $banner['estado_activo'] ? 'Desactivar' : 'Activar' ?>">
                                            <i class="bi <?= $banner['estado_activo'] ? 'bi-eye-fill text-success' : 'bi-eye-slash-fill text-muted' ?>"></i>
                                        </button>
                                        
                                        <button onclick="abrirModalEditar(<?= $banner['id'] ?>, '<?= htmlspecialchars($banner['titulo'], ENT_QUOTES) ?>', <?= $banner['orden'] ?>)" class="btn btn-sm btn-light border shadow-sm text-primary" title="Editar">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>

                                        <a href="<?= BASE_URL ?>admin/banners/borrar/<?= $banner['id'] ?>" class="btn btn-sm btn-light border shadow-sm text-danger" onclick="return confirm('¿Seguro que deseas eliminar este banner permanentemente?');" title="Eliminar">
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
</div>

<div class="modal fade" id="editarBannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0 bg-light">
                <h5 class="modal-title fw-bold text-cenco-indigo"><i class="bi bi-pencil-square me-2"></i>Editar Banner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form action="<?= BASE_URL ?>admin/banners/actualizar" method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Título</label>
                        <input type="text" name="titulo" id="edit_titulo" class="form-control border-light shadow-sm">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">Orden</label>
                        <input type="number" name="orden" id="edit_orden" class="form-control border-light shadow-sm fw-bold text-center" min="1" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-cenco-indigo rounded-pill fw-bold shadow-sm">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Función para llenar el modal con los datos actuales
    function abrirModalEditar(id, titulo, orden) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_titulo').value = titulo;
        document.getElementById('edit_orden').value = orden;
        
        var modal = new bootstrap.Modal(document.getElementById('editarBannerModal'));
        modal.show();
    }

    // Función para activar/desactivar en tiempo real (AJAX)
    function toggleBanner(id, btn) {
        btn.disabled = true;
        const icon = btn.querySelector('i');
        const originalClass = icon.className;
        icon.className = 'spinner-border spinner-border-sm text-secondary';

        fetch('<?= BASE_URL ?>admin/banners/toggleAjax', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const row = btn.closest('tr');
                const tdEstado = row.cells[3]; // Columna 3 es el Estado
                
                if (data.nuevo_estado == 1) {
                    icon.className = 'bi bi-eye-fill text-success';
                    btn.title = 'Desactivar';
                    tdEstado.innerHTML = '<span class="badge rounded-pill bg-success px-3">Activo</span>';
                } else {
                    icon.className = 'bi bi-eye-slash-fill text-muted';
                    btn.title = 'Activar';
                    tdEstado.innerHTML = '<span class="badge rounded-pill bg-secondary px-3">Inactivo</span>';
                }
            } else {
                alert('Error al cambiar el estado del banner');
                icon.className = originalClass;
            }
        })
        .catch(err => {
            console.error(err);
            icon.className = originalClass;
        })
        .finally(() => {
            btn.disabled = false;
        });
    }
</script>