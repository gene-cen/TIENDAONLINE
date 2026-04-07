<style>
    .bg-soft-blue { background-color: #f0f8ff !important; border: 1px solid #cfe2ff !important; }
    .bg-soft-mint { background-color: #f2faf5 !important; border: 1px solid #d1e7dd !important; }
    .form-label-custom { font-size: 0.85rem; font-weight: 700; color: var(--cenco-indigo); text-transform: uppercase; letter-spacing: 0.5px; }
    .capitalize-input { text-transform: capitalize; }
    .table-vertical-center td { vertical-align: middle; }
</style>

<div class="container-fluid px-4 py-4 bg-light min-vh-100">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-1"><i class="bi bi-box-seam-fill me-2 text-primary"></i>Homologación de Productos Nuevos</h2>
            <p class="text-secondary mb-0">Gestiona los nombres comerciales, marcas y categorías antes de publicarlos en la web.</p>
        </div>
        <div class="mt-3 mt-md-0">
            <span class="badge bg-warning text-dark fs-6 px-3 py-2 shadow-sm"><i class="bi bi-info-circle-fill me-1"></i> <?= count($productos) ?> Pendientes de revisión</span>
        </div>
    </div>

    <div class="alert bg-soft-blue border-primary border-opacity-25 rounded-4 d-flex align-items-center mb-4 p-3 shadow-sm">
        <i class="bi bi-shield-lock-fill fs-2 text-primary me-3"></i>
        <div>
            <h6 class="fw-bold text-primary mb-1">Reglas de Publicación</h6>
            <span class="text-dark small">Un producto no puede ser activado para el E-commerce hasta que cumpla 2 requisitos: <br>
            <strong>1.</strong> Tener un Nombre Web Comercial asignado. <br>
            <strong>2.</strong> Tener la fotografía cargada en sistema (Si falta, solicitar a Eliseo).</span>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 border-top border-4 border-primary">
        <div class="table-responsive">
            <table class="table table-hover table-vertical-center mb-0 bg-white">
                <thead class="bg-light text-cenco-indigo">
                    <tr style="font-size:0.9rem;">
                        <th class="ps-4 py-3">Cód / ERP</th>
                        <th class="py-3">Info Comercial (Web)</th>
                        <th class="py-3 text-center">Estado Fotografía</th>
                        <th class="py-3 text-end pe-4">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr><td colspan="4" class="text-center py-5 text-success fw-bold"><i class="bi bi-check-circle-fill fs-2 d-block mb-2"></i>¡Excelente! No hay productos pendientes.</td></tr>
                    <?php else: foreach ($productos as $p): 
                        $faltaImagen = (empty($p['imagen']) || strpos($p['imagen'], 'no-image') !== false);
                        $faltaNombre = empty($p['nombre_web']);
                    ?>
                    <tr id="row-<?= $p['cod_producto'] ?>">
                        <td class="ps-4 py-3">
                            <span class="badge bg-light text-secondary border mb-1 font-monospace"><?= htmlspecialchars($p['cod_producto']) ?></span>
                            <div class="fw-bold text-dark" style="font-size: 0.85rem; max-width: 250px; line-height: 1.2;"><?= htmlspecialchars($p['nombre_erp']) ?></div>
                        </td>
                        
                        <td class="py-3">
                            <?php if($faltaNombre): ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1 mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Nombre Pendiente</span>
                            <?php else: ?>
                                <div class="fw-black text-cenco-indigo capitalize-input mb-1" style="font-size: 0.95rem;"><?= htmlspecialchars($p['nombre_web']) ?></div>
                            <?php endif; ?>
                            
                            <div class="d-flex gap-1 flex-wrap">
                                <?php if(!empty($p['marca_nombre'])): ?>
                                    <span class="badge bg-light text-primary border border-primary opacity-75" style="font-size:0.7rem;"><i class="bi bi-tag-fill"></i> <?= htmlspecialchars($p['marca_nombre']) ?></span>
                                <?php endif; ?>
                                <?php if(!empty($p['categoria_nombre'])): ?>
                                    <span class="badge bg-light text-success border border-success opacity-75" style="font-size:0.7rem;"><i class="bi bi-grid-fill"></i> <?= htmlspecialchars($p['categoria_nombre']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <td class="text-center py-3">
                            <?php if ($faltaImagen): ?>
                                <div class="badge bg-danger text-white px-3 py-2 shadow-sm d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-camera-fill fs-5"></i> 
                                    <div class="text-start lh-1">
                                        <span class="d-block fw-bold" style="font-size:0.7rem;">SIN FOTO</span>
                                        <span class="d-block fw-light" style="font-size:0.65rem;">Avisar a Eliseo</span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="badge bg-success text-white px-3 py-2 shadow-sm"><i class="bi bi-check-circle-fill me-1"></i> Foto OK</div>
                            <?php endif; ?>
                        </td>

                        <td class="text-end pe-4 py-3">
                            <div class="btn-group shadow-sm">
                                <button class="btn btn-light border text-primary px-3 py-2" onclick="abrirModalHomologar('<?= $p['cod_producto'] ?>', '<?= htmlspecialchars($p['nombre_erp'], ENT_QUOTES) ?>', '<?= htmlspecialchars($p['nombre_web'] ?? '', ENT_QUOTES) ?>', '<?= $p['marca_id'] ?? '' ?>', '<?= $p['web_categoria_id'] ?? '' ?>')" title="Editar Info Web">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <button class="btn btn-light border text-success px-3 py-2 fw-bold" onclick="intentarActivar('<?= $p['cod_producto'] ?>', this)" title="Publicar en Ecommerce">
                                    <i class="bi bi-cloud-upload-fill me-1"></i> Publicar
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHomologar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0 pt-4 px-4 bg-white">
                <h5 class="modal-title fw-black text-cenco-indigo"><i class="bi bi-magic me-2 text-warning"></i>Homologar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                
                <div class="bg-light p-3 rounded-3 mb-4 border border-secondary border-opacity-25">
                    <span class="text-muted small fw-bold d-block mb-1">CÓDIGO: <span id="modal_cod_producto_txt" class="text-dark"></span></span>
                    <span class="text-danger small fw-bold d-block mb-1">NOMBRE ERP (Original):</span>
                    <div id="modal_nombre_erp" class="fw-bold text-dark font-monospace" style="font-size:0.9rem;"></div>
                </div>

                <form id="formHomologar">
                    <input type="hidden" id="cod_producto_edit">
                    
                    <div class="mb-4">
                        <label class="form-label-custom mb-2">Nombre Comercial Web (Lindo)</label>
                        <input type="text" id="nombre_web_edit" class="form-control form-control-lg border-primary shadow-sm capitalize-input" placeholder="Ej: Café Nescafé Tradición 170g" required>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label-custom mb-2">Asignar Marca</label>
                            <select id="marca_id_edit" class="form-select border-secondary shadow-sm">
                                <option value="">-- Sin Marca --</option>
                                <?php foreach($marcas as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom mb-2">Asignar Categoría</label>
                            <select id="categoria_id_edit" class="form-select border-secondary shadow-sm">
                                <option value="">-- Sin Categoría --</option>
                                <?php foreach($categorias as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="button" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm" onclick="guardarHomologacion(this)">Guardar Información Web</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // 1. Abrir Modal y rellenar datos
    function abrirModalHomologar(cod, nombre_erp, nombre_web, marca_id, cat_id) {
        document.getElementById('cod_producto_edit').value = cod;
        document.getElementById('modal_cod_producto_txt').innerText = cod;
        document.getElementById('modal_nombre_erp').innerText = nombre_erp;
        
        // Si no hay nombre web, le sugerimos el del ERP para que lo edite más fácil
        document.getElementById('nombre_web_edit').value = nombre_web ? nombre_web : nombre_erp.toLowerCase();
        
        document.getElementById('marca_id_edit').value = marca_id;
        document.getElementById('categoria_id_edit').value = cat_id;

        new bootstrap.Modal(document.getElementById('modalHomologar')).show();
    }

    // 2. Guardar Datos por AJAX
    function guardarHomologacion(btn) {
        const cod = document.getElementById('cod_producto_edit').value;
        const nombre_web = document.getElementById('nombre_web_edit').value;
        const marca_id = document.getElementById('marca_id_edit').value;
        const categoria_id = document.getElementById('categoria_id_edit').value;

        if (nombre_web.trim() === '') {
            Swal.fire('Atención', 'El Nombre Web es obligatorio', 'warning');
            return;
        }

        const originalText = btn.innerText;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
        btn.disabled = true;

        fetch('<?= BASE_URL ?>admin/productos_nuevos/guardarAjax', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                cod_producto: cod,
                nombre_web: nombre_web,
                marca_id: marca_id,
                categoria_id: categoria_id
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                Swal.fire({icon: 'success', title: 'Guardado', text: 'Información web actualizada', timer: 1500, showConfirmButton: false});
                setTimeout(() => location.reload(), 1500); // Recargamos para ver los cambios aplicados en la tabla
            } else {
                Swal.fire('Error', data.msg, 'error');
            }
        })
        .catch(() => Swal.fire('Error', 'Problema de conexión', 'error'))
        .finally(() => { btn.innerHTML = originalText; btn.disabled = false; });
    }

    // 3. Intentar Publicar (Validar Reglas)
    function intentarActivar(cod, btn) {
        Swal.fire({
            title: '¿Publicar Producto?',
            text: "Se activará y será visible en el catálogo web.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, publicar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                btn.disabled = true;

                fetch('<?= BASE_URL ?>admin/productos_nuevos/activarAjax', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cod_producto: cod })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        // Animación bonita: Eliminamos la fila de la vista de pendientes
                        const fila = document.getElementById('row-' + cod);
                        fila.style.transition = "all 0.5s ease";
                        fila.style.opacity = "0";
                        fila.style.transform = "translateX(50px)";
                        setTimeout(() => fila.remove(), 500);

                        Swal.fire({icon: 'success', title: '¡Publicado!', text: 'El producto ya está en vivo.', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false});
                    } else {
                        // ¡Saltó la regla de negocio! (Falta foto o nombre)
                        Swal.fire({icon: 'error', title: 'No se puede publicar', text: data.msg});
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    }
                })
                .catch(() => {
                    Swal.fire('Error', 'Problema de conexión', 'error');
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                });
            }
        });
    }
</script>