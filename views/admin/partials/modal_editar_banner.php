<div class="modal fade" id="editarBannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0 bg-white pt-4 px-4">
                <h4 class="modal-title fw-black text-cenco-indigo"><i class="bi bi-pencil-square me-2"></i>Editar Configuración del Banner</h4>
                <button type="button" class="btn-close fs-5" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form action="<?= BASE_URL ?>admin/banners/actualizar" method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="tipo_carrusel_edit" id="edit_tipo">
                    <input type="hidden" name="orden" id="edit_orden">

                    <div class="row g-4 mb-4">
                        <div class="col-md-5">
                            <label class="form-label-custom">Sucursal Asignada</label>
                            <select name="sucursal_id" id="edit_sucursal" class="form-select form-select-lg border-secondary shadow-sm fw-bold bg-white" required onchange="productosEdit=[]; renderTabla(productosEdit, 'tablaSeleccionadosEdit', document.getElementById('ids_edit'));">
                                <option value="0">Ambas</option>
                                <option value="29">La Calera (29)</option>
                                <option value="10">Villa Alemana (10)</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label-custom">Título Interno</label>
                            <input type="text" name="titulo" id="edit_titulo" class="form-control form-control-lg border-secondary shadow-sm capitalize-input bg-white">
                        </div>
                    </div>

                    <div class="p-3 bg-soft-blue rounded-4 shadow-sm mb-4 border border-primary border-opacity-25">
                        <div class="d-flex justify-content-between align-items-center border-bottom border-primary border-opacity-25 pb-2 mb-3">
                            <h6 class="fw-bold text-primary mb-0"><i class="bi bi-clock-history me-2"></i>Programación Automática</h6>
                            <button type="button" class="btn btn-sm btn-primary fw-bold shadow-sm rounded-pill px-3" onclick="guardarFechasAjax(this)">
                                <i class="bi bi-save me-1"></i> Guardar Fechas
                            </button>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label text-dark fw-bold mb-1">Mostrar desde:</label>
                                <input type="datetime-local" name="fecha_inicio" id="edit_fecha_inicio" class="form-control border-primary border-opacity-50 shadow-sm">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label text-dark fw-bold mb-1">Ocultar desde:</label>
                                <input type="datetime-local" name="fecha_fin" id="edit_fecha_fin" class="form-control border-primary border-opacity-50 shadow-sm">
                            </div>
                            <div class="col-md-2 d-flex align-items-end justify-content-end">
                                <button type="button" class="btn btn-outline-danger btn-sm fw-bold w-100" onclick="document.getElementById('edit_fecha_inicio').value=''; document.getElementById('edit_fecha_fin').value='';"><i class="bi bi-eraser-fill me-1"></i>Limpiar</button>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-soft-mint rounded-4 shadow-sm mb-2">
                        <h5 class="fw-bold text-cenco-indigo mb-3 border-bottom border-success pb-2"><i class="bi bi-cursor-fill text-success me-2"></i>¿Qué debe abrir el banner al hacerle clic?</h5>

                        <div class="row g-4 mb-3 border-bottom border-success pb-4">
                            <div class="col-md-6">
                                <div class="form-check d-flex align-items-start gap-3">
                                    <input class="form-check-input form-check-input-lg border-success shadow-sm mt-1" type="radio" name="modo_destino_edit" id="modoA_edit" value="A" onchange="setModo('edit', 'A')">
                                    <div>
                                        <label class="form-check-label fw-black text-success form-check-label-lg mb-1" for="modoA_edit">Opción A: Vitrina</label>
                                        <p class="text-secondary text-md mb-0 lh-sm">Arma una colección personalizada buscando los productos específicos.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check d-flex align-items-start gap-3">
                                    <input class="form-check-input form-check-input-lg border-secondary shadow-sm mt-1" type="radio" name="modo_destino_edit" id="modoB_edit" value="B" onchange="setModo('edit', 'B')">
                                    <div>
                                        <label class="form-check-label fw-black text-cenco-indigo form-check-label-lg mb-1" for="modoB_edit">Opción B: Enlace Rápido</label>
                                        <p class="text-secondary text-md mb-0 lh-sm">Redirige a una Categoría o Marca ya existente en el catálogo.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-7" id="panelA_edit">
                                <div class="inner-card-white">
                                    <div class="row g-3 mb-3">
                                        <div class="col-sm-5">
                                            <label class="fw-bold text-dark mb-1">Nombre Vitrina:</label>
                                            <input type="text" name="palabra_clave" id="palabra_clave_edit" class="form-control form-control-lg border-success shadow-sm capitalize-input" placeholder="Palabra clave">
                                        </div>
                                        <div class="col-sm-7 position-relative">
                                            <label class="fw-bold text-dark mb-1">Buscar Producto:</label>
                                            <input type="text" id="buscadorEdit" class="form-control form-control-lg border-success shadow-sm" placeholder="🔍 Añadir código o nombre..." autocomplete="off">
                                            <ul id="listaEdit" class="list-group position-absolute w-100 shadow-lg z-3 d-none" style="max-height: 250px; overflow-y: auto; font-size: 1rem;"></ul>
                                        </div>
                                    </div>
                                    <div class="table-responsive rounded-3 border shadow-sm mt-2">
                                        <table class="table table-hover mb-0 align-middle bg-white">
                                            <thead class="bg-light text-cenco-indigo" style="font-size:0.95rem;">
                                                <tr><th class="ps-3 py-2">Img</th><th>Código</th><th>Nombre</th><th>Stock</th><th class="text-center">Quitar</th></tr>
                                            </thead>
                                            <tbody id="tablaSeleccionadosEdit" class="text-md">
                                                <tr><td colspan="5" class="text-center text-secondary py-4">No hay productos.</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <input type="hidden" name="productos_ids" id="ids_edit">
                                </div>
                            </div>

                            <div class="col-md-5 panel-disabled" id="panelB_edit">
                                <div class="inner-card-white h-100">
                                    <div class="btn-group w-100 mb-4 shadow-sm" role="group">
                                        <input type="radio" class="btn-check" name="tipo_b_edit" id="btn_cat_edit" autocomplete="off" checked onchange="setTipoB('edit', 'categoria')" disabled>
                                        <label class="btn btn-outline-cenco-indigo fw-bold py-2 text-lg" for="btn_cat_edit">Por Categoría</label>

                                        <input type="radio" class="btn-check" name="tipo_b_edit" id="btn_marca_edit" autocomplete="off" onchange="setTipoB('edit', 'marca')" disabled>
                                        <label class="btn btn-outline-cenco-indigo fw-bold py-2 text-lg" for="btn_marca_edit">Por Marca</label>
                                    </div>

                                    <label class="fw-bold text-dark mb-1">Destino Actual:</label>
                                    <select id="select_cat_edit" class="form-select form-select-lg border-cenco-indigo shadow-sm mb-3" onchange="document.getElementById('enlace_edit').value = this.value" disabled>
                                        <option value="">-- Selecciona Categoría --</option>
                                        <?php foreach ($listaCategoriasForm as $cat): ?>
                                            <option value="home/catalogo?categoria=<?= urlencode($cat['nombre']) ?>"><?= htmlspecialchars(ucfirst($cat['nombre'])) ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <select id="select_marca_edit" class="form-select form-select-lg border-cenco-indigo shadow-sm mb-3 d-none" onchange="document.getElementById('enlace_edit').value = this.value" disabled>
                                        <option value="">-- Selecciona Marca --</option>
                                        <?php foreach ($listaMarcasForm as $marca): ?>
                                            <option value="home/catalogo?marca=<?= urlencode($marca['nombre']) ?>"><?= htmlspecialchars(ucfirst($marca['nombre'])) ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <input type="hidden" name="enlace" id="enlace_edit">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm py-3 text-lg">Guardar Configuración General</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>