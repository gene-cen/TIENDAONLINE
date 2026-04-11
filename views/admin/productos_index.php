<div class="container-fluid px-4 py-4 bg-light min-vh-100">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-1">
                <i class="bi bi-box-seam me-2"></i>Gestión de Productos
            </h2>
            <p class="text-muted mb-0">Administra tu catálogo, precios y stock.</p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <button type="button" id="btnExportar" class="btn btn-outline-success border shadow-sm fw-bold hover-scale">
                <i class="bi bi-file-earmark-excel me-1"></i> Exportar
            </button>
            <a href="<?= BASE_URL ?>admin/importar_erp" class="btn btn-white text-cenco-indigo border shadow-sm fw-bold hover-scale">
                <i class="bi bi-arrow-repeat me-1 text-primary"></i> Sincronizar ERP
            </a>
            <a href="<?= BASE_URL ?>admin/producto/crear" class="btn btn-cenco-green text-white shadow-sm fw-bold hover-scale">
                <i class="bi bi-plus-lg me-1"></i> Nuevo Producto
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form id="formFiltros" class="row g-3 align-items-center" onsubmit="return false;">
                
                <div class="col-md-2">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="inputBusqueda" class="form-control border-start-0 ps-0"
                            placeholder="Buscar..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" autocomplete="off">
                    </div>
                </div>

                <div class="col-md-2">
                    <select id="selectCategoria" class="form-select text-muted">
                        <option value="">Categorías (Todas)</option>
                        <?php if (!empty($listaCategorias)): ?>
                            <?php foreach ($listaCategorias as $cat): ?>
                                <?php
                                $catId = is_array($cat) ? $cat['id'] : $cat;
                                $catNom = is_array($cat) ? $cat['nombre'] : $cat;
                                ?>
                                <option value="<?= $catId ?>" <?= (isset($_GET['categoria']) && $_GET['categoria'] == $catId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($catNom) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <select id="selectMarca" class="form-select text-muted">
                        <option value="">Marcas (Todas)</option>
                        <?php if (!empty($listaMarcas)): ?>
                            <?php foreach ($listaMarcas as $marca): ?>
                                <option value="<?= $marca['id'] ?>" <?= (isset($_GET['marca']) && $_GET['marca'] == $marca['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($marca['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <select id="selectFiltroStock" class="form-select text-muted">
                        <option value="">Stock (Todos)</option>
                        <option value="agotados" <?= (isset($_GET['filtro_stock']) && $_GET['filtro_stock'] === 'agotados') ? 'selected' : '' ?>>Agotados (0)</option>
                        <option value="0_50" <?= (isset($_GET['filtro_stock']) && $_GET['filtro_stock'] === '0_50') ? 'selected' : '' ?>>De 1 a 50 un.</option>
                        <option value="51_100" <?= (isset($_GET['filtro_stock']) && $_GET['filtro_stock'] === '51_100') ? 'selected' : '' ?>>De 51 a 100 un.</option>
                        <option value="100_mas" <?= (isset($_GET['filtro_stock']) && $_GET['filtro_stock'] === '100_mas') ? 'selected' : '' ?>>Más de 100 un.</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select id="selectOrdenStock" class="form-select text-muted">
                        <option value="">Ordenar por...</option>
                        <optgroup label="Por Stock">
                            <option value="desc" <?= (isset($_GET['orden']) && $_GET['orden'] === 'desc') ? 'selected' : '' ?>>Mayor a Menor Stock</option>
                            <option value="asc" <?= (isset($_GET['orden']) && $_GET['orden'] === 'asc') ? 'selected' : '' ?>>Menor a Mayor Stock</option>
                        </optgroup>
                        <optgroup label="Por Precio">
                            <option value="precio_desc" <?= (isset($_GET['orden']) && $_GET['orden'] === 'precio_desc') ? 'selected' : '' ?>>Mayor a Menor Precio</option>
                            <option value="precio_asc" <?= (isset($_GET['orden']) && $_GET['orden'] === 'precio_asc') ? 'selected' : '' ?>>Menor a Mayor Precio</option>
                        </optgroup>
                        <optgroup label="Por Nombre">
                            <option value="nombre_asc" <?= (isset($_GET['orden']) && $_GET['orden'] === 'nombre_asc') ? 'selected' : '' ?>>A - Z</option>
                            <option value="nombre_desc" <?= (isset($_GET['orden']) && $_GET['orden'] === 'nombre_desc') ? 'selected' : '' ?>>Z - A</option>
                        </optgroup>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="button" id="btnLimpiar" class="btn btn-outline-danger w-100" title="Borrar filtros">
                        <i class="bi bi-eraser-fill me-1"></i>Limpiar
                    </button>
                    <div id="loadingSpinner" class="spinner-border text-cenco-indigo spinner-border-sm ms-2 d-none align-self-center" role="status">
                        <span class="visually-hidden">...</span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light border-bottom">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold text-uppercase border-0">Imagen</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0">Producto (Web / ERP)</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0">Precio</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0 text-center">Stock</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0 text-center">Estado</th>
                            <th class="pe-4 py-3 text-muted small fw-bold text-uppercase border-0 text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaResultados">
                        <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-search fs-1 d-block mb-2 opacity-50"></i>
                                    No se encontraron productos con esos filtros.
                                </td>
                            </tr>
                            <?php else: foreach ($productos as $prod):
                                $nombreERP = $prod->nombre ?? 'Sin Nombre';
                                
                                // 🔥 DETECCIÓN DE PRODUCTO DESCONTINUADO
                                $esDescontinuado = (strpos(trim($nombreERP), '#') === 0);
                                $claseFila = $esDescontinuado ? 'table-warning' : '';
                                
                                $nombreWeb = $prod->nombre_web ?? '<span class="text-danger small">No asignado</span>';
                                $codigo = $prod->cod_producto ?? '---';
                                $catNombre = $prod->categoria_nombre ?? 'General';
                                $id = $prod->id;
                                $precio = $prod->precio ?? 0;
                                $stock = $prod->stock ?? 0;
                                $activo = $prod->activo ?? 0;
                                $imgRaw = $prod->imagen ?? '';

                                if (empty($imgRaw)) $rutaImagen = BASE_URL . 'img/no-photo_small.png';
                                elseif (strpos($imgRaw, 'http') === 0) $rutaImagen = $imgRaw;
                                else $rutaImagen = BASE_URL . 'img/productos/' . $imgRaw;

                                $dataModal = htmlspecialchars(json_encode([
                                    'imagen' => $rutaImagen,
                                    'nombreWeb' => strip_tags($nombreWeb),
                                    'nombreERP' => $nombreERP,
                                    'precio' => number_format($precio, 0, ',', '.'),
                                    'stock' => $stock,
                                    'estado' => $activo ? 'Visible' : 'Oculto'
                                ]), ENT_QUOTES, 'UTF-8');
                            ?>
                                <tr class="<?= $claseFila ?>">
                                    <td class="ps-4 py-2">
                                        <button class="btn p-0 border-0 bg-transparent hover-scale" onclick="abrirModalResumen(this)" data-producto="<?= $dataModal ?>">
                                            <div class="bg-white border rounded-3 p-1 shadow-sm position-relative" style="width: 50px; height: 50px;">
                                                <img src="<?= $rutaImagen ?>" class="w-100 h-100 object-fit-contain" onerror="this.src='<?= BASE_URL ?>img/no-image.png';">
                                            </div>
                                        </button>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 250px;" title="<?= strip_tags($nombreWeb) ?>">
                                            <?= $nombreWeb ?>
                                        </div>
                                        <div class="text-secondary small text-truncate" style="max-width: 250px;" title="ERP: <?= htmlspecialchars($nombreERP) ?>">
                                            <i class="bi bi-box me-1"></i><?= htmlspecialchars($nombreERP) ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="badge bg-light text-secondary border fw-normal" style="font-size: 0.7rem;">
                                                <i class="bi bi-upc-scan me-1"></i><?= htmlspecialchars($codigo) ?>
                                            </span>
                                            <span class="badge bg-cenco-indigo bg-opacity-10 text-cenco-indigo fw-bold" style="font-size: 0.7rem;">
                                                <?= htmlspecialchars($catNombre) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-cenco-indigo">$<?= number_format($precio, 0, ',', '.') ?></div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($stock > 10): ?><span class="badge bg-success bg-opacity-10 text-success border border-success px-2 rounded-pill"><?= $stock ?> un.</span>
                                        <?php elseif ($stock > 0): ?><span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-2 rounded-pill"><?= $stock ?> un.</span>
                                        <?php else: ?><span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 rounded-pill">Agotado</span><?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?= $activo ? '<span class="badge rounded-pill bg-success px-3">Visible</span>' : '<span class="badge rounded-pill bg-secondary px-3">Oculto</span>' ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <button onclick="toggleProducto(<?= $id ?>, this)" class="btn btn-sm btn-light border shadow-sm" title="<?= $activo ? 'Ocultar' : 'Mostrar' ?>">
                                                <i class="bi <?= $activo ? 'bi-eye-fill text-success' : 'bi-eye-slash-fill text-muted' ?>"></i>
                                            </button>
                                            <a href="<?= BASE_URL ?>admin/producto/editar/<?= $id ?>" class="btn btn-sm btn-light border shadow-sm text-primary"><i class="bi bi-pencil-fill"></i></a>
                                            <button onclick="confirmarEliminar(<?= $id ?>)" class="btn btn-sm btn-light border shadow-sm text-danger"><i class="bi bi-trash-fill"></i></button>
                                        </div>
                                    </td>
                                </tr>
                        <?php endforeach;
                        endif; ?>
                   
                    </tbody>
                </table>
            </div>

            <div id="paginacionContainer" class="card-footer bg-white border-top py-4">
                <?php 
                $urlParams = '';
                if (!empty($_GET['q'])) $urlParams .= '&q=' . urlencode($_GET['q']);
                if (!empty($_GET['categoria'])) $urlParams .= '&categoria=' . urlencode($_GET['categoria']);
                if (!empty($_GET['marca'])) $urlParams .= '&marca=' . urlencode($_GET['marca']);
                if (!empty($_GET['orden'])) $urlParams .= '&orden=' . urlencode($_GET['orden']);
                if (!empty($_GET['filtro_stock'])) $urlParams .= '&filtro_stock=' . urlencode($_GET['filtro_stock']);
                
                if (isset($total_paginas) && $total_paginas > 1): ?>
                    <nav aria-label="Navegación de productos">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-start-pill px-3 <?= ($pagina_actual <= 1) ? 'text-muted bg-light' : 'text-cenco-indigo' ?>" 
                                   href="<?= BASE_URL ?>admin/productos?page=<?= $pagina_actual - 1 ?><?= $urlParams ?>">
                                    <i class="bi bi-chevron-left small me-1"></i> Anterior
                                </a>
                            </li>

                            <?php
                            $rango = 2;
                            for ($i = 1; $i <= $total_paginas; $i++):
                                if ($i == 1 || $i == $total_paginas || ($i >= $pagina_actual - $rango && $i <= $pagina_actual + $rango)):
                            ?>
                                    <li class="page-item <?= ($pagina_actual == $i) ? 'active' : '' ?>">
                                        <a class="page-link <?= ($pagina_actual == $i) ? 'bg-cenco-indigo border-cenco-indigo' : 'text-muted' ?>" 
                                           href="<?= BASE_URL ?>admin/productos?page=<?= $i ?><?= $urlParams ?>"><?= $i ?></a>
                                    </li>
                                <?php elseif ($i == $pagina_actual - $rango - 1 || $i == $pagina_actual + $rango + 1): ?>
                                    <li class="page-item disabled"><span class="page-link border-0 text-muted">...</span></li>
                            <?php endif;
                            endfor; ?>

                            <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-end-pill px-3 <?= ($pagina_actual >= $total_paginas) ? 'text-muted bg-light' : 'text-cenco-indigo' ?>" 
                                   href="<?= BASE_URL ?>admin/productos?page=<?= $pagina_actual + 1 ?><?= $urlParams ?>">
                                    Siguiente <i class="bi bi-chevron-right small ms-1"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalResumenProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pt-0 px-4 pb-4">
                <div class="bg-light rounded-4 p-3 mb-3 d-inline-block shadow-sm">
                    <img id="modalProdImagen" src="" alt="Producto" class="img-fluid object-fit-contain" style="max-height: 200px; max-width: 250px;">
                </div>
                
                <h5 id="modalProdWeb" class="fw-bold text-dark mb-1">Nombre Web</h5>
                <p id="modalProdERP" class="text-muted small mb-3"><i class="bi bi-box me-1"></i> Nombre ERP</p>
                
                <div class="row g-2 justify-content-center">
                    <div class="col-4">
                        <div class="p-2 border rounded-3 bg-white">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">PRECIO</small>
                            <strong id="modalProdPrecio" class="text-cenco-indigo fs-5">$0</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 border rounded-3 bg-white">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">STOCK</small>
                            <strong id="modalProdStock" class="text-dark fs-5">0</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 border rounded-3 bg-white">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">ESTADO</small>
                            <strong id="modalProdEstado" class="text-success fs-6 mt-1 d-block">Visible</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let modalInstance = null;

    document.addEventListener('DOMContentLoaded', function() {
        modalInstance = new bootstrap.Modal(document.getElementById('modalResumenProducto'));

        const inputBusqueda = document.getElementById('inputBusqueda');
        const selectCategoria = document.getElementById('selectCategoria');
        const selectMarca = document.getElementById('selectMarca'); // Nuevo
        const selectFiltroStock = document.getElementById('selectFiltroStock'); 
        const selectOrdenStock = document.getElementById('selectOrdenStock'); 
        const btnLimpiar = document.getElementById('btnLimpiar');
        const btnExportar = document.getElementById('btnExportar'); 
        const spinner = document.getElementById('loadingSpinner');
        let timeout = null;

        function aplicarFiltros() {
            const q = inputBusqueda.value.trim();
            const cat = selectCategoria.value;
            const marca = selectMarca.value; // Capturamos marca
            const stock = selectFiltroStock.value;
            const orden = selectOrdenStock.value;

            if (spinner) spinner.classList.remove('d-none');

            window.location.href = `<?= BASE_URL ?>admin/productos?q=${encodeURIComponent(q)}&categoria=${encodeURIComponent(cat)}&marca=${encodeURIComponent(marca)}&filtro_stock=${encodeURIComponent(stock)}&orden=${encodeURIComponent(orden)}`;
        }

        inputBusqueda.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(aplicarFiltros, 800);
        });

        inputBusqueda.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(timeout);
                aplicarFiltros();
            }
        });

        selectCategoria.addEventListener('change', aplicarFiltros);
        selectMarca.addEventListener('change', aplicarFiltros); // Escuchador marca
        selectFiltroStock.addEventListener('change', aplicarFiltros);
        selectOrdenStock.addEventListener('change', aplicarFiltros);

        if (btnLimpiar) {
            btnLimpiar.addEventListener('click', function() {
                inputBusqueda.value = '';
                selectCategoria.value = '';
                selectMarca.value = '';
                selectFiltroStock.value = '';
                selectOrdenStock.value = '';
                aplicarFiltros(); 
            });
        }

        if(btnExportar) {
            btnExportar.addEventListener('click', function() {
                const q = inputBusqueda.value.trim();
                const cat = selectCategoria.value;
                const marca = selectMarca.value;
                const stock = selectFiltroStock.value;
                const orden = selectOrdenStock.value;
                
                window.location.href = `<?= BASE_URL ?>admin/exportarProductosExcel?q=${encodeURIComponent(q)}&categoria=${encodeURIComponent(cat)}&marca=${encodeURIComponent(marca)}&filtro_stock=${encodeURIComponent(stock)}&orden=${encodeURIComponent(orden)}`;
            });
        }
    });

    // ABRIR MODAL (Asignación inversa ERP/WEB aplicada)
    function abrirModalResumen(btn) {
        const data = JSON.parse(btn.getAttribute('data-producto'));
        
        document.getElementById('modalProdImagen').src = data.imagen;
        document.getElementById('modalProdWeb').innerHTML = data.nombreWeb; 
        document.getElementById('modalProdERP').innerHTML = '<i class="bi bi-box me-1"></i>' + data.nombreERP; 
        document.getElementById('modalProdPrecio').textContent = '$' + data.precio;
        document.getElementById('modalProdStock').textContent = data.stock;
        
        const elEstado = document.getElementById('modalProdEstado');
        elEstado.textContent = data.estado;
        if(data.estado === 'Visible') {
            elEstado.className = 'text-success fs-6 mt-1 d-block';
        } else {
            elEstado.className = 'text-secondary fs-6 mt-1 d-block';
        }

        modalInstance.show();
    }

    function toggleProducto(id, btn) {
        btn.disabled = true;
        const icon = btn.querySelector('i');
        const originalClass = icon.className;
        icon.className = 'spinner-border spinner-border-sm text-secondary';

        const formData = new FormData();
        formData.append('id', id);

        fetch('<?= BASE_URL ?>admin/producto/toggleAjax', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.nuevo_estado == 1) {
                        icon.className = 'bi bi-eye-fill text-success';
                        btn.title = 'Ocultar';
                        actualizarBadgeEstado(btn, true);
                    } else {
                        icon.className = 'bi bi-eye-slash-fill text-muted';
                        btn.title = 'Mostrar';
                        actualizarBadgeEstado(btn, false);
                    }
                } else {
                    Swal.fire('Error', data.message || 'Error al cambiar estado', 'error');
                    icon.className = originalClass;
                }
            })
            .catch(err => {
                console.error('Error de red:', err);
                Swal.fire('Error', 'Hubo un problema de conexión con el servidor', 'error');
                icon.className = originalClass;
            })
            .finally(() => {
                btn.disabled = false;
            });
    }

    function actualizarBadgeEstado(btn, activo) {
        const row = btn.closest('tr');
        const cell = row.cells[4]; 
        if (activo) {
            cell.innerHTML = '<span class="badge rounded-pill bg-success px-3">Visible</span>';
        } else {
            cell.innerHTML = '<span class="badge rounded-pill bg-secondary px-3">Oculto</span>';
        }
    }

    function confirmarEliminar(id) {
        if (confirm('¿Estás seguro de que deseas eliminar este producto?')) {
            window.location.href = '<?= BASE_URL ?>admin/producto/eliminar/' + id;
        }
    }
</script>