<?php
// 🔥 CONTROL DE ROLES (Misma lógica que banners)
$esSuperAdmin = (
    (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') || 
    (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin')
) && empty($_SESSION['admin_sucursal']); 
?>

<style>
    /* 🔥 MEJORAS DE UX Y ACCESIBILIDAD VISUAL */
    .bg-soft-blue { background-color: #f0f8ff !important; border: 1px solid #cfe2ff !important; }
    .text-md { font-size: 0.95rem !important; }
    .text-lg { font-size: 1.05rem !important; }
    .form-label-custom { font-size: 0.9rem; font-weight: 700; color: var(--cenco-indigo); text-transform: uppercase; letter-spacing: 0.5px; }

    /* Drag & Drop UX */
    .drag-handle-pill { cursor: grab; transition: transform 0.2s; padding: 6px 12px; }
    .drag-handle-pill:hover { transform: scale(1.05); background-color: #e9ecef !important; }
    .drag-handle-pill:active { cursor: grabbing; }
    .capitalize-input { text-transform: capitalize; }
</style>

<div class="container-fluid px-4 py-4 bg-light min-vh-100">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-1"><i class="bi bi-award-fill me-2 text-warning"></i>Marcas Destacadas (Partners)</h2>
            <p class="text-secondary text-md mb-0">Gestiona los auspiciadores del Home. Arrastra las filas para ordenar quién aparece primero.</p>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && in_array($_GET['msg'], ['marca_creada', 'marca_actualizada'])): ?>
        <div class="alert bg-white border border-success border-2 shadow-sm rounded-4 d-flex align-items-center mb-4 p-3 animate__animated animate__bounceIn">
            <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_celebrando_compra.png" alt="Éxito" style="width: 65px; height: 65px; object-fit: contain;" class="me-3">
            <div>
                <h5 class="fw-bold text-success mb-1">¡Acción realizada con éxito!</h5>
                <span class="text-dark text-md">La marca se ha configurado correctamente.</span>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <script>window.history.replaceState({}, document.title, window.location.pathname);</script>
    <?php endif; ?>

    <?php if ($esSuperAdmin): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 border-top border-4 border-warning">
            <div class="card-header bg-white py-3 px-4 border-bottom border-light">
                <h4 class="mb-0 fw-bold text-cenco-indigo"><i class="bi bi-cloud-arrow-up-fill me-2"></i>Añadir Nueva Marca Partner</h4>
            </div>
            <div class="card-body p-4 bg-soft-blue">
                <form action="<?= BASE_URL ?>admin/marcas/guardar" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="orden" value="999">

                    <div class="row g-4 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label-custom">Nombre de la Marca</label>
                            <input type="text" name="nombre" class="form-control form-control-lg border-primary border-opacity-25 shadow-sm capitalize-input bg-white" placeholder="Ej: Coca Cola" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label-custom">Logo (PNG transparente recomendado)</label>
                            <input type="file" name="imagen" class="form-control form-control-lg border-primary border-opacity-25 shadow-sm bg-white" accept="image/*" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm rounded-pill"><i class="bi bi-upload me-2"></i>Subir Logo</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4 d-flex align-items-center mb-4 p-4">
            <i class="bi bi-shield-lock-fill fs-1 text-warning me-4"></i>
            <div>
                <h4 class="fw-bold mb-1">Modo Solo Lectura</h4>
                <span class="text-md">Solo el Administrador Mayor puede gestionar las marcas destacadas.</span>
            </div>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-cenco-indigo"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Grilla de Marcas Activas</h5>
            <span class="badge bg-warning text-dark fs-6"><i class="bi bi-info-circle-fill me-1"></i>Solo las primeras 8 marcas Activas se muestran en el Home</span>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-cenco-indigo">
                    <tr style="font-size:0.95rem;">
                        <th class="ps-4 py-3 fw-bold">Logo</th>
                        <th class="py-3 fw-bold">Nombre Marca</th>
                        <th class="py-2 fw-bold text-center" style="line-height: 1.2;">
                            Prioridad<br>
                            <small class="text-secondary fw-normal" style="font-size:0.8rem; text-transform:none;"><i class="bi bi-hand-index-thumb-fill text-cenco-indigo"></i> Arrastra la manito</small>
                        </th>
                        <th class="py-3 fw-bold text-center">Estado</th>
                        <th class="pe-4 py-3 fw-bold text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody class="sortable-tbody" id="tbody-marcas">
                    <?php if (empty($marcas)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-secondary text-md">No hay marcas configuradas.</td></tr>
                    <?php else: foreach ($marcas as $index => $m): 
                        // UX Magia: Si es top 8 y está activo, resalta verde. Si queda fuera del top 8, avisa.
                        $esTop = ($index < 8 && $m['estado_activo'] == 1);
                    ?>
                    <tr data-id="<?= $m['id'] ?>" class="bg-white">
                        <td class="ps-4 py-2">
                            <div class="bg-light border rounded-3 d-flex align-items-center justify-content-center shadow-sm position-relative" style="width: 100px; height: 65px; padding: 5px;">
                                <?php if($esTop): ?>
                                    <span class="position-absolute top-0 start-0 m-1 badge bg-warning text-dark rounded-circle p-1" title="Se muestra en Home"><i class="bi bi-star-fill"></i></span>
                                <?php endif; ?>
                                <img src="<?= str_starts_with($m['ruta_imagen'] ?? '', 'http') ? $m['ruta_imagen'] : BASE_URL . ltrim($m['ruta_imagen'] ?? '', '/') ?>" class="img-fluid" style="max-height: 100%; object-fit: contain;">
                            </div>
                        </td>
                        <td class="py-2">
                            <div class="fw-bold text-dark text-lg capitalize-input"><?= htmlspecialchars($m['nombre']) ?></div>
                            <small class="text-secondary"><i class="bi bi-link-45deg"></i> Redirige a catálogo por marca</small>
                        </td>
                        <td class="text-center py-2">
                            <?php if ($esSuperAdmin): ?>
                                <div class="d-inline-flex align-items-center justify-content-center gap-2 px-3 py-2 bg-light border rounded-pill drag-handle-pill handle text-muted shadow-sm" title="Arrastra para definir el orden">
                                    <i class="bi bi-hand-index-thumb-fill fs-4 text-cenco-indigo"></i>
                                    <span class="badge bg-white text-dark border orden-badge fs-5 shadow-sm px-3"><?= $m['orden'] ?></span>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border orden-badge fs-5 px-3 py-2 shadow-sm"><?= $m['orden'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center py-2">
                            <span class="badge rounded-pill fs-6 px-3 py-2 bg-<?= $m['estado_activo'] ? 'success' : 'secondary' ?>">
                                <?= $m['estado_activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td class="text-end pe-4 py-2">
                            <?php if ($esSuperAdmin): ?>
                                <div class="btn-group shadow-sm">
                                    <button onclick="toggleMarcaAjax(<?= $m['id'] ?>, this)" class="btn btn-light border py-2 px-3"><i class="fs-5 bi <?= $m['estado_activo'] ? 'bi-eye-fill text-success' : 'bi-eye-slash-fill text-secondary' ?>"></i></button>
                                    <button onclick="abrirModalEditarMarca(<?= $m['id'] ?>, '<?= htmlspecialchars($m['nombre'], ENT_QUOTES) ?>')" class="btn btn-light border text-primary py-2 px-3"><i class="fs-5 bi bi-pencil-fill"></i></button>
                                    <button onclick="eliminarMarcaAjax(<?= $m['id'] ?>, this)" class="btn btn-light border text-danger py-2 px-3"><i class="fs-5 bi bi-trash-fill"></i></button>
                                </div>
                            <?php else: ?>
                                <span class="text-secondary text-md"><i class="bi bi-lock-fill"></i> Solo lectura</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editarMarcaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0 pt-4 px-4 bg-white">
                <h4 class="modal-title fw-black text-cenco-indigo"><i class="bi bi-pencil-square me-2"></i>Editar Marca Destacada</h4>
                <button type="button" class="btn-close fs-5" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form action="<?= BASE_URL ?>admin/marcas/actualizar" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit_marca_id">
                    <input type="hidden" name="orden" id="edit_marca_orden"> 
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label-custom">Nombre de la Marca</label>
                            <input type="text" name="nombre" id="edit_marca_nombre" class="form-control form-control-lg border-secondary shadow-sm capitalize-input" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom">Cambiar Logo (Opcional)</label>
                            <input type="file" name="imagen" class="form-control form-control-lg border-secondary shadow-sm" accept="image/*">
                            <small class="text-muted d-block mt-1">Si no subes nada, se mantendrá el logo actual.</small>
                        </div>
                    </div>
                    
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm py-3">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
    // ==========================================
    // INICIALIZAR DRAG AND DROP (SORTABLE.JS)
    // ==========================================
    document.addEventListener("DOMContentLoaded", function() {
        const tbody = document.getElementById('tbody-marcas');
        if(tbody) {
            new Sortable(tbody, {
                handle: '.handle', 
                animation: 150,
                ghostClass: 'bg-light',
                onEnd: function(evt) {
                    let ordenes = [];
                    let rows = evt.to.querySelectorAll('tr');

                    rows.forEach((row, index) => {
                        let nuevoOrden = index + 1;
                        ordenes.push({ id: row.getAttribute('data-id'), orden: nuevoOrden });
                        row.querySelector('.orden-badge').innerText = nuevoOrden;
                    });

                    // Guardado AJAX
                    fetch('<?= BASE_URL ?>admin/marcas/reordenarAjax', {
                        method: 'POST',
                        body: JSON.stringify({ ordenes: ordenes }),
                        headers: { 'Content-Type': 'application/json' }
                    }).then(() => {
                        // Recargamos silenciosamente para que los iconos de "Top 8" se acomoden
                        location.reload();
                    });
                }
            });
        }
    });

    // ==========================================
    // FUNCIONES MODAL, TOGGLE Y BORRAR AJAX
    // ==========================================
    function abrirModalEditarMarca(id, nombre) {
        document.getElementById('edit_marca_id').value = id;
        document.getElementById('edit_marca_nombre').value = nombre;
        // Recuperamos el orden visual de la tabla para no perderlo
        let tr = document.querySelector('tr[data-id="'+id+'"]');
        if(tr) {
            let ordenText = tr.querySelector('.orden-badge').innerText;
            document.getElementById('edit_marca_orden').value = ordenText;
        }
        
        new bootstrap.Modal(document.getElementById('editarMarcaModal')).show();
    }

    function toggleMarcaAjax(id, btn) {
        btn.disabled = true;
        const icon = btn.querySelector('i');
        const originalClass = icon.className;
        icon.className = 'spinner-border spinner-border-sm text-secondary';

        fetch('<?= BASE_URL ?>admin/marcas/toggleAjax', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const tdEstado = btn.closest('tr').cells[3]; 

                if (data.nuevo_estado == 1) {
                    icon.className = 'fs-5 bi bi-eye-fill text-success';
                    tdEstado.innerHTML = '<span class="badge rounded-pill fs-6 px-3 py-2 bg-success">Activo</span>';
                } else {
                    icon.className = 'fs-5 bi bi-eye-slash-fill text-secondary';
                    tdEstado.innerHTML = '<span class="badge rounded-pill fs-6 px-3 py-2 bg-secondary">Inactivo</span>';
                }
            } else {
                Swal.fire('Error', 'No se pudo actualizar', 'error');
                icon.className = originalClass;
            }
        })
        .finally(() => btn.disabled = false);
    }

    function eliminarMarcaAjax(id, btn) {
        Swal.fire({
            title: '¿Eliminar Marca?',
            text: "Se quitará del listado de partners del Home.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#E53935',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                btn.disabled = true;

                fetch('<?= BASE_URL ?>admin/marcas/borrarAjax', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const fila = btn.closest('tr');
                        fila.style.transition = "all 0.4s ease";
                        fila.style.opacity = "0";
                        fila.style.transform = "translateX(30px)";
                        setTimeout(() => fila.remove(), 400);

                        Swal.fire({
                            icon: 'success', title: 'Eliminado', toast: true,
                            position: 'top-end', showConfirmButton: false, timer: 2000
                        });
                    } else {
                        Swal.fire('Error', 'No se pudo eliminar', 'error');
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    }
                });
            }
        });
    }
</script>