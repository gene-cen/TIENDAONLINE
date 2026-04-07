<div class="container-fluid px-4 py-4 bg-light min-vh-100">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-1">
                <i class="bi bi-box-seam me-2"></i>Gestión de Productos
            </h2>
            <p class="text-muted mb-0">Administra tu catálogo, precios y stock.</p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
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

                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="inputBusqueda" class="form-control border-start-0 ps-0"
                            placeholder="Escribe para buscar (ej: aceite, 1020...)"
                            value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" autocomplete="off">
                    </div>
                </div>

                <div class="col-md-4">
                    <select id="selectCategoria" class="form-select text-muted">
                        <option value="">Todas las Categorías</option>
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

                <div class="col-md-3 d-flex gap-2">
                    <button type="button" id="btnLimpiar" class="btn btn-outline-danger w-100" title="Borrar filtros">
                        <i class="bi bi-eraser-fill me-2"></i>Borrar
                    </button>

                    <div id="loadingSpinner" class="spinner-border text-cenco-indigo spinner-border-sm ms-2 d-none align-self-center" role="status">
                        <span class="visually-hidden">Cargando...</span>
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
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0">Producto</th>
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
                                    No se encontraron productos.
                                </td>
                            </tr>
                            <?php else: foreach ($productos as $prod):
                                $nombre = $prod->nombre_mostrar ?? $prod->nombre ?? 'Sin Nombre';
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
                            ?>
                                <tr>
                                    <td class="ps-4 py-2">
                                        <div class="bg-white border rounded-3 p-1 shadow-sm position-relative" style="width: 50px; height: 50px;">
                                            <img src="<?= $rutaImagen ?>" class="w-100 h-100 object-fit-contain" onerror="this.src='<?= BASE_URL ?>img/no-image.png';">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 300px;" title="<?= htmlspecialchars($nombre) ?>">
                                            <?= htmlspecialchars($nombre) ?>
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
                                        <?php if (!empty($prod->precio_unidad_medida)): ?>
                                            <div class="text-muted" style="font-size: 0.7rem;">
                                                <i class="bi bi-tag-fill small"></i> <?= htmlspecialchars($prod->precio_unidad_medida) ?>
                                            </div>
                                        <?php endif; ?>
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

            <div id="paginacionContainer" class="card-footer bg-white border-top py-4" style="display: <?= (!empty($_GET['q'])) ? 'none' : 'block' ?>;">
                <?php if (isset($total_paginas) && $total_paginas > 1): ?>
                    <nav aria-label="Navegación de productos">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-start-pill border-end-0" href="<?= BASE_URL ?>admin/productos?page=<?= $pagina_actual - 1 ?>"><i class="bi bi-chevron-left small"></i></a>
                            </li>

                            <?php
                            $rango = 2;
                            for ($i = 1; $i <= $total_paginas; $i++):
                                if ($i == 1 || $i == $total_paginas || ($i >= $pagina_actual - $rango && $i <= $pagina_actual + $rango)):
                            ?>
                                    <li class="page-item <?= ($pagina_actual == $i) ? 'active' : '' ?>">
                                        <a class="page-link <?= ($pagina_actual == $i) ? 'bg-cenco-indigo border-cenco-indigo' : 'text-muted' ?>" href="<?= BASE_URL ?>admin/productos?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php elseif ($i == $pagina_actual - $rango - 1 || $i == $pagina_actual + $rango + 1): ?>
                                    <li class="page-item disabled"><span class="page-link border-0">...</span></li>
                            <?php endif;
                            endfor; ?>

                            <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-end-pill border-start-0" href="<?= BASE_URL ?>admin/productos?page=<?= $pagina_actual + 1 ?>"><i class="bi bi-chevron-right small"></i></a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
    function confirmarEliminar(id) {
        if (confirm('¿Estás seguro de que deseas eliminar este producto?')) {
            window.location.href = '<?= BASE_URL ?>admin/producto/eliminar/' + id;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const inputBusqueda = document.getElementById('inputBusqueda');
        const selectCategoria = document.getElementById('selectCategoria');
        const btnLimpiar = document.getElementById('btnLimpiar'); // Capturamos el botón
        const tablaResultados = document.getElementById('tablaResultados');
        const spinner = document.getElementById('loadingSpinner');
        const paginacion = document.getElementById('paginacionContainer');
        let timeout = null;

        // 1. FUNCIÓN DE BÚSQUEDA
        function realizarBusqueda() {
            const q = inputBusqueda.value.trim();
            const cat = selectCategoria.value;

            if (spinner) spinner.classList.remove('d-none');

            // AJAX
            const url = `<?= BASE_URL ?>admin/productos/ajax?q=${encodeURIComponent(q)}&categoria=${encodeURIComponent(cat)}`;

            fetch(url)
                .then(response => response.text())
                .then(html => {
                    tablaResultados.innerHTML = html;

                    // LÓGICA DE PAGINACIÓN (RECUPERAR BOTONES)
                    if (q.length > 0 || cat.length > 0) {
                        // Si hay filtros activos, ocultamos la paginación normal
                        if (paginacion) paginacion.style.display = 'none';
                    } else {
                        // Si NO hay filtros (está vacío), mostramos la paginación original
                        if (paginacion) paginacion.style.display = 'block';
                    }
                })
                .catch(error => console.error('Error:', error))
                .finally(() => {
                    if (spinner) spinner.classList.add('d-none');
                });
        }

        // 2. EVENTO: ESCRIBIR EN INPUT
        inputBusqueda.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(realizarBusqueda, 300);
        });

        // 3. EVENTO: CAMBIAR CATEGORÍA
        selectCategoria.addEventListener('change', function() {
            realizarBusqueda();
        });

        // 4. EVENTO: BOTÓN LIMPIAR (GOMITA)
        if (btnLimpiar) {
            btnLimpiar.addEventListener('click', function() {
                // Borramos los valores
                inputBusqueda.value = '';
                selectCategoria.value = '';

                // Disparamos la búsqueda vacía para resetear la tabla
                realizarBusqueda();

                // Opcional: Si quieres reiniciar totalmente la URL (quitar ?q= de la barra)
                // window.history.pushState({}, document.title, window.location.pathname);
            });
        }
    });

    // FUNCION TOGGLE ESTADO (OJITO) CORREGIDA
    function toggleProducto(id, btn) {
        btn.disabled = true;
        const icon = btn.querySelector('i');
        const originalClass = icon.className;
        icon.className = 'spinner-border spinner-border-sm text-secondary';

        // Usamos FormData para que PHP lo lea perfectamente con $_POST['id']
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
</script>