<?php
// 🔥 CONTROL DE ROLES
$esSuperAdmin = (
    (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') || 
    (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin')
) && empty($_SESSION['admin_sucursal']); 

$miSucursal = $_SESSION['admin_sucursal'] ?? 0;

// 🔥 SEPARAMOS LOS BANNERS
$bannersAmbas_Prin = array_filter($bannersPrincipal, fn($b) => $b['sucursal_id'] == 0);
$bannersCalera_Prin = array_filter($bannersPrincipal, fn($b) => $b['sucursal_id'] == 29);
$bannersVilla_Prin = array_filter($bannersPrincipal, fn($b) => $b['sucursal_id'] == 10);

$bannersAmbas_Sec = array_filter($bannersSecundario, fn($b) => $b['sucursal_id'] == 0);
$bannersCalera_Sec = array_filter($bannersSecundario, fn($b) => $b['sucursal_id'] == 29);
$bannersVilla_Sec = array_filter($bannersSecundario, fn($b) => $b['sucursal_id'] == 10);

// 🔥 OBTENEMOS CATEGORÍAS Y MARCAS PARA EL COMBOBOX
$listaCategoriasForm = [];
$listaMarcasForm = [];
try {
    $listaCategoriasForm = $this->db->query("SELECT nombre FROM web_categorias WHERE activo=1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    $listaMarcasForm = $this->db->query("SELECT nombre FROM marcas WHERE activo=1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>

<style>
    /* 🔥 MEJORAS DE UX Y ACCESIBILIDAD VISUAL */
    .nav-tabs .nav-link { color: #555; font-weight: 700; background-color: #f8f9fa; font-size: 1.05rem; border: 1px solid #dee2e6; border-bottom: none; transition: all 0.3s ease; padding: 12px 20px; }
    .nav-tabs .nav-link:hover { background-color: #e9ecef; }
    .nav-tabs .nav-link.active[data-bs-target="#tab-ambas"] { background-color: var(--cenco-indigo) !important; color: white !important; border-color: var(--cenco-indigo); }
    .nav-tabs .nav-link.active[data-bs-target="#tab-calera"] { background-color: var(--cenco-green) !important; color: white !important; border-color: var(--cenco-green); }
    .nav-tabs .nav-link.active[data-bs-target="#tab-villa"] { background-color: #0dcaf0 !important; color: #000 !important; border-color: #0dcaf0; }

    .bg-soft-mint { background-color: #f2faf5 !important; border: 1px solid #d1e7dd !important; }
    .bg-soft-blue { background-color: #f0f8ff !important; border: 1px solid #cfe2ff !important; }

    .text-md { font-size: 0.95rem !important; }
    .text-lg { font-size: 1.05rem !important; }
    .form-label-custom { font-size: 0.9rem; font-weight: 700; color: var(--cenco-indigo); text-transform: uppercase; letter-spacing: 0.5px; }

    .drag-handle-pill { cursor: grab; transition: transform 0.2s; padding: 6px 12px; }
    .drag-handle-pill:hover { transform: scale(1.05); background-color: #e9ecef !important; }
    .drag-handle-pill:active { cursor: grabbing; }
    .capitalize-input { text-transform: capitalize; }

    .panel-disabled { opacity: 0.4; pointer-events: none; filter: grayscale(80%); transition: all 0.3s ease; }
    .form-check-input-lg { width: 1.8em; height: 1.8em; margin-top: -0.1em; cursor: pointer; }
    .form-check-label-lg { cursor: pointer; padding-top: 0.25rem; font-size: 1.05rem; }
    .inner-card-white { background-color: #ffffff; border-radius: 12px; padding: 15px; border: 1px solid #e9ecef; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
</style>

<div class="container-fluid px-4 py-4 bg-light min-vh-100">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-1"><i class="bi bi-images me-2"></i>Gestión de Banners</h2>
            <p class="text-secondary text-md mb-0">
                <?= $esSuperAdmin ? 'Administra las vitrinas de todas las sucursales. Programa fechas y arrastra filas para ordenar.' : 'Administra la vitrina de tu sucursal. Programa fechas y arrastra filas.' ?>
            </p>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && in_array($_GET['msg'], ['banner_creado', 'banner_actualizado'])): ?>
        <div class="alert bg-white border border-success border-2 shadow-sm rounded-4 d-flex align-items-center mb-4 p-3 animate__animated animate__bounceIn">
            <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_celebrando_compra.png" alt="Éxito" style="width: 65px; height: 65px; object-fit: contain;" class="me-3">
            <div>
                <h5 class="fw-bold text-success mb-1">¡Acción realizada con éxito!</h5>
                <span class="text-dark text-md">Los cambios del banner se han guardado correctamente en el sistema.</span>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <script>window.history.replaceState({}, document.title, window.location.pathname);</script>
    <?php endif; ?>

    <?php if ($esSuperAdmin): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 border-top border-4 border-cenco-green">
            <div class="card-header bg-white py-3 px-4 border-bottom border-light">
                <h4 class="mb-0 fw-bold text-cenco-indigo"><i class="bi bi-cloud-arrow-up-fill me-2"></i>Añadir Nuevo Banner</h4>
            </div>
            <div class="card-body p-4">
                <form action="<?= BASE_URL ?>admin/banners/guardar" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="orden" value="999">

                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <label class="form-label-custom">Sucursal Visible</label>
                            <select name="sucursal_id" id="select_sucursal_nuevo" class="form-select form-select-lg border-cenco-green shadow-sm fw-bold bg-white" required onchange="productosNuevo=[]; renderTabla(productosNuevo, 'tablaSeleccionadosNuevo', document.getElementById('idsNuevo')); sincronizarPestana(this.value);">
                                <option value="0">Ambas Sucursales</option>
                                <option value="29">Solo La Calera</option>
                                <option value="10">Solo Villa Alemana</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-custom">Ubicación</label>
                            <select name="tipo_carrusel" class="form-select form-select-lg border-secondary shadow-sm bg-white" required>
                                <option value="principal">Carrusel Principal (Arriba)</option>
                                <option value="secundario">Carrusel Promocional (Abajo)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-custom">Título Interno</label>
                            <input type="text" name="titulo" class="form-control form-control-lg border-secondary shadow-sm capitalize-input bg-white" placeholder="Ej: Ofertas del Mes">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-custom">Imagen Banner</label>
                            <input type="file" name="imagen" class="form-control form-control-lg border-secondary shadow-sm bg-white" accept="image/*" required>
                        </div>
                    </div>

                    <div class="p-3 bg-soft-blue rounded-4 shadow-sm mb-4 border border-primary border-opacity-25">
                        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-clock-history me-2"></i>Programación Automática (Opcional)</h6>
                        <p class="text-secondary text-md mb-3">Deja esto en blanco si quieres que el banner se muestre de inmediato y para siempre.</p>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label text-dark fw-bold mb-1">Mostrar desde:</label>
                                <input type="datetime-local" name="fecha_inicio" class="form-control border-primary border-opacity-50 shadow-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-dark fw-bold mb-1">Ocultar desde:</label>
                                <input type="datetime-local" name="fecha_fin" class="form-control border-primary border-opacity-50 shadow-sm">
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-soft-mint rounded-4 shadow-sm mb-2">
                        <h5 class="fw-bold text-cenco-indigo mb-3 border-bottom border-success pb-2"><i class="bi bi-cursor-fill text-success me-2"></i>¿Qué debe abrir el banner al hacerle clic?</h5>
                        
                        <div class="row g-4 mb-3 border-bottom border-success pb-4">
                            <div class="col-md-6">
                                <div class="form-check d-flex align-items-start gap-3">
                                    <input class="form-check-input form-check-input-lg border-success shadow-sm mt-1" type="radio" name="modo_destino" id="modoA_nuevo" value="A" checked onchange="setModo('nuevo', 'A')">
                                    <div>
                                        <label class="form-check-label fw-black text-success form-check-label-lg mb-1" for="modoA_nuevo">Opción A: Crear Vitrina de Productos</label>
                                        <p class="text-secondary text-md mb-0 lh-sm">Arma una colección personalizada buscando los productos específicos que quieres mostrar.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check d-flex align-items-start gap-3">
                                    <input class="form-check-input form-check-input-lg border-secondary shadow-sm mt-1" type="radio" name="modo_destino" id="modoB_nuevo" value="B" onchange="setModo('nuevo', 'B')">
                                    <div>
                                        <label class="form-check-label fw-black text-cenco-indigo form-check-label-lg mb-1" for="modoB_nuevo">Opción B: Enlace Rápido</label>
                                        <p class="text-secondary text-md mb-0 lh-sm">Redirige al cliente a una Categoría completa o a una Marca que ya existe.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-7" id="panelA_nuevo">
                                <div class="inner-card-white">
                                    <div class="row g-3 mb-3">
                                        <div class="col-sm-5">
                                            <label class="fw-bold text-dark mb-1">Nombre Vitrina:</label>
                                            <input type="text" name="palabra_clave" id="palabra_clave_nuevo" class="form-control form-control-lg shadow-sm capitalize-input border-success" placeholder="Ej: Proteina">
                                        </div>
                                        <div class="col-sm-7 position-relative">
                                            <label class="fw-bold text-dark mb-1">Buscador de Productos:</label>
                                            <input type="text" id="buscadorNuevo" class="form-control form-control-lg border-success shadow-sm" placeholder="🔍 Escribe código o nombre..." autocomplete="off">
                                            <ul id="listaNuevo" class="list-group position-absolute w-100 shadow-lg z-3 d-none" style="max-height: 250px; overflow-y: auto; font-size: 1rem;"></ul>
                                        </div>
                                    </div>
                                    <div class="table-responsive rounded-3 border shadow-sm">
                                        <table class="table table-hover mb-0 align-middle bg-white">
                                            <thead class="bg-light text-cenco-indigo" style="font-size:0.95rem;">
                                                <tr><th class="ps-3 py-2">Img</th><th>Código</th><th>Nombre</th><th>Stock</th><th class="text-center">Quitar</th></tr>
                                            </thead>
                                            <tbody id="tablaSeleccionadosNuevo" class="text-md">
                                                <tr><td colspan="5" class="text-center text-secondary py-4">No hay productos seleccionados.</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <input type="hidden" name="productos_ids" id="idsNuevo">
                                </div>
                            </div>

                            <div class="col-md-5 panel-disabled" id="panelB_nuevo">
                                <div class="inner-card-white h-100">
                                    <div class="btn-group w-100 mb-4 shadow-sm" role="group">
                                        <input type="radio" class="btn-check" name="tipo_b_nuevo" id="btn_cat_nuevo" autocomplete="off" checked onchange="setTipoB('nuevo', 'categoria')" disabled>
                                        <label class="btn btn-outline-cenco-indigo fw-bold py-2 text-lg" for="btn_cat_nuevo">Por Categoría</label>

                                        <input type="radio" class="btn-check" name="tipo_b_nuevo" id="btn_marca_nuevo" autocomplete="off" onchange="setTipoB('nuevo', 'marca')" disabled>
                                        <label class="btn btn-outline-cenco-indigo fw-bold py-2 text-lg" for="btn_marca_nuevo">Por Marca</label>
                                    </div>

                                    <label class="fw-bold text-dark mb-1">Selecciona el destino:</label>
                                    <select id="select_cat_nuevo" class="form-select form-select-lg border-cenco-indigo shadow-sm mb-3" onchange="document.getElementById('enlace_nuevo').value = this.value" disabled>
                                        <option value="">-- Selecciona Categoría --</option>
                                        <?php foreach ($listaCategoriasForm as $cat): ?>
                                            <option value="home/catalogo?categoria=<?= urlencode($cat['nombre']) ?>"><?= htmlspecialchars(ucfirst($cat['nombre'])) ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <select id="select_marca_nuevo" class="form-select form-select-lg border-cenco-indigo shadow-sm mb-3 d-none" onchange="document.getElementById('enlace_nuevo').value = this.value" disabled>
                                        <option value="">-- Selecciona Marca --</option>
                                        <?php foreach ($listaMarcasForm as $marca): ?>
                                            <option value="home/catalogo?marca=<?= urlencode($marca['nombre']) ?>"><?= htmlspecialchars(ucfirst($marca['nombre'])) ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <input type="hidden" name="enlace" id="enlace_nuevo">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-success btn-lg shadow-sm fw-bold px-5 py-2 rounded-pill">
                            <i class="bi bi-upload me-2"></i>Publicar Banner
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4 d-flex align-items-center mb-4 p-4">
            <i class="bi bi-shield-lock-fill fs-1 text-warning me-4"></i>
            <div>
                <h4 class="fw-bold mb-1">Modo Solo Lectura</h4>
                <span class="text-md">Solo el Administrador Mayor puede subir o modificar banners y colecciones. Puedes ver el orden actual.</span>
            </div>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs nav-fill mb-4 gap-2" id="sucursalTabs" role="tablist">
        <?php if ($esSuperAdmin): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link active shadow-sm" id="btn-tab-ambas" data-bs-toggle="tab" data-bs-target="#tab-ambas" type="button"><i class="bi bi-globe fs-5 me-2"></i>Ambas Sucursales</button>
            </li>
        <?php endif; ?>
        
        <?php if ($esSuperAdmin || $miSucursal == 29): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= (!$esSuperAdmin && $miSucursal == 29) ? 'active' : '' ?> shadow-sm" id="btn-tab-calera" data-bs-toggle="tab" data-bs-target="#tab-calera" type="button"><i class="bi bi-shop fs-5 me-2"></i>La Calera (29)</button>
            </li>
        <?php endif; ?>

        <?php if ($esSuperAdmin || $miSucursal == 10): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= (!$esSuperAdmin && $miSucursal == 10) ? 'active' : '' ?> shadow-sm" id="btn-tab-villa" data-bs-toggle="tab" data-bs-target="#tab-villa" type="button"><i class="bi bi-shop-window fs-5 me-2"></i>Villa Alemana (10)</button>
            </li>
        <?php endif; ?>
    </ul>

    <div class="tab-content" id="sucursalTabsContent">
        
        <?php 
        function renderTablaBanners($banners, $titulo, $tabla_db, $esSuperAdmin)
        {
            echo '<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">';
            echo '<div class="card-header bg-white py-3 border-bottom"><h5 class="mb-0 fw-bold text-cenco-indigo"><i class="bi bi-collection me-2"></i>' . $titulo . '</h5></div>';
            echo '<div class="table-responsive"><table class="table table-hover align-middle mb-0">';
            
            echo '<thead class="bg-light text-cenco-indigo"><tr style="font-size:0.95rem;">
                    <th class="ps-4 py-3 fw-bold">Imagen</th>
                    <th class="py-3 fw-bold">Destino / Colección</th>
                    <th class="py-3 fw-bold text-center">Programación</th>
                    <th class="py-2 fw-bold text-center" style="line-height: 1.2;">
                        Orden<br>
                        <small class="text-secondary fw-normal" style="font-size:0.8rem; text-transform:none;"><i class="bi bi-hand-index-thumb-fill text-cenco-indigo"></i> Arrastra la manito</small>
                    </th>
                    <th class="py-3 fw-bold text-center">Estado</th>
                    <th class="pe-4 py-3 fw-bold text-end">Acciones</th>
                  </tr></thead>';
            echo '<tbody class="sortable-tbody" data-tabla="' . $tabla_db . '">';

            if (empty($banners)) {
                echo '<tr><td colspan="6" class="text-center py-5 text-secondary text-md">No hay banners en esta sección.</td></tr>';
            } else {
                foreach ($banners as $banner) {
                    echo '<tr data-id="' . $banner['id'] . '" class="bg-white">';

                    echo '<td class="ps-4 py-2"><div class="bg-light border rounded-3 overflow-hidden shadow-sm" style="width: 150px; height: 65px;"><img src="' . BASE_URL . htmlspecialchars($banner['ruta_imagen']) . '" class="w-100 h-100 object-fit-cover"></div></td>';

                    echo '<td class="py-2"><div class="fw-bold text-dark text-lg">' . htmlspecialchars(ucfirst($banner['titulo'] ?? 'Sin título')) . '</div>';
                    if (!empty($banner['palabra_clave'])) {
                        echo '<span class="badge bg-cenco-green text-md mt-1 px-3 py-2 shadow-sm"><i class="bi bi-tag-fill me-1"></i> ' . htmlspecialchars(ucfirst($banner['palabra_clave'])) . '</span>';
                    } elseif (!empty($banner['enlace'])) {
                        $linkLimpio = str_replace('home/catalogo?', '', $banner['enlace']);
                        $linkLimpio = urldecode(str_replace(['categoria=', 'marca='], '', $linkLimpio));
                        echo '<span class="badge bg-light text-primary border border-primary text-md mt-1 px-3 py-2 shadow-sm"><i class="bi bi-link-45deg fs-6"></i> Redirige a: ' . htmlspecialchars(ucfirst($linkLimpio)) . '</span>';
                    }
                    echo '</td>';

                    // 🔥 UX: Columna de Programación con estilo ordenado
                    echo '<td class="text-center py-2 align-middle">';
                    if (!empty($banner['fecha_inicio']) || !empty($banner['fecha_fin'])) {
                        $inicioStr = !empty($banner['fecha_inicio']) ? date('d/m/Y H:i', strtotime($banner['fecha_inicio'])) : 'Inmediato';
                        $finStr = !empty($banner['fecha_fin']) ? date('d/m/Y H:i', strtotime($banner['fecha_fin'])) : 'Sin límite';
                        
                        echo '<div class="d-flex flex-column align-items-center gap-1">';
                        echo '<span class="badge bg-soft-blue text-primary border border-primary text-md px-2 py-1 shadow-sm fw-bold" style="width: 140px; text-align: left;"><i class="bi bi-play-circle-fill me-1"></i> ' . $inicioStr . '</span>';
                        echo '<span class="badge bg-soft-mint text-success border border-success text-md px-2 py-1 shadow-sm fw-bold" style="width: 140px; text-align: left;"><i class="bi bi-stop-circle-fill me-1"></i> ' . $finStr . '</span>';
                        echo '</div>';
                    } else {
                        echo '<span class="badge bg-light text-secondary border text-md px-3 py-2 shadow-sm"><i class="bi bi-infinity me-1"></i> Permanente</span>';
                    }
                    echo '</td>';

                    echo '<td class="text-center py-2">';
                    if ($esSuperAdmin) {
                        echo '<div class="d-inline-flex align-items-center justify-content-center gap-2 px-3 py-2 bg-light border rounded-pill drag-handle-pill handle text-muted shadow-sm" title="Arrastra para definir el orden">';
                        echo '<i class="bi bi-hand-index-thumb-fill fs-4 text-cenco-indigo"></i>'; 
                        echo '<span class="badge bg-white text-dark border orden-badge fs-5 shadow-sm px-3">' . $banner['orden'] . '</span>';
                        echo '</div>';
                    } else {
                        echo '<span class="badge bg-light text-dark border orden-badge fs-5 px-3 py-2 shadow-sm">' . $banner['orden'] . '</span>';
                    }
                    echo '</td>';

                    echo '<td class="text-center py-2"><span class="badge rounded-pill fs-6 px-3 py-2 bg-' . ($banner['estado_activo'] ? 'success' : 'secondary') . '">' . ($banner['estado_activo'] ? 'Activo' : 'Inactivo') . '</span></td>';

                    echo '<td class="text-end pe-4 py-2">';
                    if ($esSuperAdmin) {
                        echo '<div class="btn-group shadow-sm">';
                        echo '<button onclick="toggleBanner(' . $banner['id'] . ', \'' . ($tabla_db == 'carrusel_banners' ? 'principal' : 'secundario') . '\', this)" class="btn btn-light border py-2 px-3"><i class="fs-5 bi ' . ($banner['estado_activo'] ? 'bi-eye-fill text-success' : 'bi-eye-slash-fill text-secondary') . '"></i></button>';
                        echo '<button onclick="abrirModalEditar(' . $banner['id'] . ', \'' . htmlspecialchars(ucfirst($banner['titulo'] ?? ''), ENT_QUOTES) . '\', \'' . ($tabla_db == 'carrusel_banners' ? 'principal' : 'secundario') . '\', \'' . htmlspecialchars($banner['enlace'] ?? '', ENT_QUOTES) . '\', \'' . htmlspecialchars(ucfirst($banner['palabra_clave'] ?? ''), ENT_QUOTES) . '\', \'' . htmlspecialchars($banner['productos_ids'] ?? '', ENT_QUOTES) . '\', ' . ($banner['sucursal_id'] ?? 0) . ', \'' . ($banner['fecha_inicio'] ?? '') . '\', \'' . ($banner['fecha_fin'] ?? '') . '\')" class="btn btn-light border text-primary py-2 px-3"><i class="fs-5 bi bi-pencil-fill"></i></button>';
                        echo '<button onclick="eliminarBannerAjax(' . $banner['id'] . ', \'' . ($tabla_db == 'carrusel_banners' ? 'principal' : 'secundario') . '\', this)" class="btn btn-light border text-danger py-2 px-3"><i class="fs-5 bi bi-trash-fill"></i></button>';
                        echo '</div>';
                    } else {
                        echo '<span class="text-secondary text-md"><i class="bi bi-lock-fill"></i> Solo lectura</span>';
                    }
                    echo '</td></tr>';
                }
            }
            echo '</tbody></table></div></div>';
        }
        ?>

        <?php if ($esSuperAdmin): ?>
            <div class="tab-pane fade show active" id="tab-ambas" role="tabpanel">
                <?php renderTablaBanners($bannersAmbas_Prin, 'Carrusel Principal', 'carrusel_banners', $esSuperAdmin); ?>
                <?php renderTablaBanners($bannersAmbas_Sec, 'Carrusel Promocional', 'carrusel_secundario', $esSuperAdmin); ?>
            </div>
        <?php endif; ?>

        <?php if ($esSuperAdmin || $miSucursal == 29): ?>
            <div class="tab-pane fade <?= (!$esSuperAdmin && $miSucursal == 29) ? 'show active' : '' ?>" id="tab-calera" role="tabpanel">
                <?php renderTablaBanners($bannersCalera_Prin, 'Carrusel Principal', 'carrusel_banners', $esSuperAdmin); ?>
                <?php renderTablaBanners($bannersCalera_Sec, 'Carrusel Promocional', 'carrusel_secundario', $esSuperAdmin); ?>
            </div>
        <?php endif; ?>

        <?php if ($esSuperAdmin || $miSucursal == 10): ?>
            <div class="tab-pane fade <?= (!$esSuperAdmin && $miSucursal == 10) ? 'show active' : '' ?>" id="tab-villa" role="tabpanel">
                <?php renderTablaBanners($bannersVilla_Prin, 'Carrusel Principal', 'carrusel_banners', $esSuperAdmin); ?>
                <?php renderTablaBanners($bannersVilla_Sec, 'Carrusel Promocional', 'carrusel_secundario', $esSuperAdmin); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

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

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
    // ==========================================
    // LÓGICA DE UX: SINCRONIZAR PESTAÑAS Y PANELES
    // ==========================================
    function sincronizarPestana(valor) {
        let btn = null;
        if(valor == '0') btn = document.getElementById('btn-tab-ambas');
        else if(valor == '29') btn = document.getElementById('btn-tab-calera');
        else if(valor == '10') btn = document.getElementById('btn-tab-villa');
        if(btn) btn.click();
    }

    function setModo(prefix, modo) {
        const panelA = document.getElementById('panelA_' + prefix);
        const panelB = document.getElementById('panelB_' + prefix);
        const inputsA = panelA.querySelectorAll('input, select, button');
        const inputsB = panelB.querySelectorAll('input, select, button');

        if (modo === 'A') {
            panelA.classList.remove('panel-disabled');
            panelB.classList.add('panel-disabled');
            inputsA.forEach(el => el.disabled = false);
            inputsB.forEach(el => el.disabled = true);
            if(document.getElementById('enlace_' + prefix)) document.getElementById('enlace_' + prefix).value = '';
        } else {
            panelB.classList.remove('panel-disabled');
            panelA.classList.add('panel-disabled');
            inputsB.forEach(el => el.disabled = false);
            inputsA.forEach(el => el.disabled = true);
            if(document.getElementById('palabra_clave_' + prefix)) document.getElementById('palabra_clave_' + prefix).value = '';
            if(document.getElementById('ids_' + prefix)) {
                document.getElementById('ids_' + prefix).value = '';
                if(prefix === 'nuevo') productosNuevo = []; else productosEdit = [];
                renderTabla([], prefix === 'nuevo' ? 'tablaSeleccionadosNuevo' : 'tablaSeleccionadosEdit', document.getElementById('ids_' + prefix));
            }
            let catChecked = document.getElementById('btn_cat_' + prefix).checked;
            setTipoB(prefix, catChecked ? 'categoria' : 'marca');
        }
    }

    function setTipoB(prefix, tipo) {
        const selCat = document.getElementById('select_cat_' + prefix);
        const selMarca = document.getElementById('select_marca_' + prefix);
        const inpEnlace = document.getElementById('enlace_' + prefix);

        if (tipo === 'categoria') {
            selCat.classList.remove('d-none');
            selMarca.classList.add('d-none');
            inpEnlace.value = selCat.value;
        } else {
            selMarca.classList.remove('d-none');
            selCat.classList.add('d-none');
            inpEnlace.value = selMarca.value;
        }
    }

    // ==========================================
    // INICIALIZAR DRAG AND DROP
    // ==========================================
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.sortable-tbody').forEach(tbody => {
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
                    fetch('<?= BASE_URL ?>admin/banners/reordenarAjax', {
                        method: 'POST',
                        body: JSON.stringify({ tabla: tbody.getAttribute('data-tabla'), ordenes: ordenes }),
                        headers: { 'Content-Type': 'application/json' }
                    });
                }
            });
        });
    });

    // ==========================================
    // LÓGICA DEL BUSCADOR AVANZADO
    // ==========================================
    let productosNuevo = [];
    let productosEdit = [];

    function getSucursalForSearch(isEdit) {
        const sel = document.getElementById(isEdit ? 'edit_sucursal' : 'select_sucursal_nuevo');
        return sel ? sel.value : <?= $miSucursal ?>;
    }

    function setupBuscadorAvanzado(inputBuscadorId, listaResultadosId, inputIdsOcultoId, tbodyTablaId, arrayLocalObj, isEdit) {
        const buscador = document.getElementById(inputBuscadorId);
        if (!buscador) return;
        const lista = document.getElementById(listaResultadosId);
        const inputHidden = document.getElementById(inputIdsOcultoId);

        buscador.addEventListener('input', function() {
            let q = this.value.trim();
            if (q.length < 3) { lista.classList.add('d-none'); return; }
            let excludeStr = arrayLocalObj.get().map(p => p.cod_producto).join(',');

            fetch('<?= BASE_URL ?>admin/banners/buscarParaBannerAjax?q=' + encodeURIComponent(q) + '&excluir=' + encodeURIComponent(excludeStr) + '&sucursal=' + getSucursalForSearch(isEdit))
                .then(async res => res.ok ? res.json() : Promise.reject())
                .then(data => {
                    lista.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(prod => {
                            let li = document.createElement('li');
                            li.className = 'list-group-item list-group-item-action py-2 px-3 cursor-pointer';
                            li.innerHTML = `
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <img src="${prod.imagen.startsWith('http') ? prod.imagen : '<?= BASE_URL ?>img/productos/' + prod.imagen}" style="width:40px; height:40px; object-fit:contain;" class="me-3 bg-white border rounded p-1">
                                    <div class="lh-sm">
                                        <span class="d-block fw-bold text-dark text-capitalize text-md">${prod.nombre.toLowerCase()}</span>
                                        <span class="text-secondary" style="font-size:0.85rem;">Cod: ${prod.cod_producto} | Stock: <span class="fw-bold ${prod.stock_real > 0 ? 'text-cenco-indigo' : 'text-danger'}">${prod.stock_real}</span></span>
                                    </div>
                                </div>
                                <i class="bi bi-plus-circle-fill text-success fs-4"></i>
                            </div>`;
                            li.onclick = () => {
                                let curr = arrayLocalObj.get(); curr.push(prod); arrayLocalObj.set(curr);
                                renderTabla(curr, tbodyTablaId, inputHidden);
                                buscador.value = ''; lista.classList.add('d-none'); buscador.focus();
                            };
                            lista.appendChild(li);
                        });
                        lista.classList.remove('d-none');
                    } else {
                        lista.innerHTML = '<li class="list-group-item text-secondary text-md py-3">No hay resultados nuevos con stock.</li>';
                        lista.classList.remove('d-none');
                    }
                }).catch(() => {
                    lista.innerHTML = '<li class="list-group-item text-danger text-md py-3">Error de conexión.</li>';
                    lista.classList.remove('d-none');
                });
        });
        document.addEventListener('click', e => { if (!buscador.contains(e.target) && !lista.contains(e.target)) lista.classList.add('d-none'); });
    }

    function renderTabla(arrayLocal, tbodyId, inputHidden) {
        const tbody = document.getElementById(tbodyId);
        tbody.innerHTML = '';
        if (arrayLocal.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-4 text-md">No hay productos seleccionados.</td></tr>';
            inputHidden.value = '';
            return;
        }
        let codigos = [];
        arrayLocal.forEach(prod => {
            codigos.push(prod.cod_producto);
            tbody.innerHTML += `
                <tr class="bg-white">
                    <td class="ps-3 py-2"><img src="${prod.imagen.startsWith('http') ? prod.imagen : '<?= BASE_URL ?>img/productos/' + prod.imagen}" style="width:40px; height:40px; object-fit:contain;" class="border rounded bg-white p-1"></td>
                    <td class="fw-bold text-secondary text-md align-middle">${prod.cod_producto}</td>
                    <td class="text-truncate text-capitalize text-md align-middle fw-bold text-dark" style="max-width: 250px;" title="${prod.nombre}">${prod.nombre.toLowerCase()}</td>
                    <td class="fw-bold text-cenco-indigo text-md align-middle">${prod.stock_real}</td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-light text-danger border p-2" title="Quitar" onclick="quitarProducto('${prod.cod_producto}', '${tbodyId}', '${inputHidden.id}')"><i class="bi bi-trash-fill fs-5"></i></button>
                    </td>
                </tr>
            `;
        });
        inputHidden.value = codigos.join(',');
    }

    function quitarProducto(cod_producto, tbodyId, inputHiddenId) {
        if (tbodyId === 'tablaSeleccionadosNuevo') {
            productosNuevo = productosNuevo.filter(p => String(p.cod_producto) !== String(cod_producto));
            renderTabla(productosNuevo, tbodyId, document.getElementById(inputHiddenId));
        } else {
            productosEdit = productosEdit.filter(p => String(p.cod_producto) !== String(cod_producto));
            renderTabla(productosEdit, tbodyId, document.getElementById(inputHiddenId));
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        setupBuscadorAvanzado('buscadorNuevo', 'listaNuevo', 'idsNuevo', 'tablaSeleccionadosNuevo', { get: () => productosNuevo, set: (v) => productosNuevo = v }, false);
        setupBuscadorAvanzado('buscadorEdit', 'listaEdit', 'edit_ids', 'tablaSeleccionadosEdit', { get: () => productosEdit, set: (v) => productosEdit = v }, true);
    });

    // ==========================================
    // MODAL EDITAR Y FUNCIONES AJAX
    // ==========================================
    
    // 🔥 FORMATO PARA EL DATETIME-LOCAL DE HTML5
    function formatoFechaLocal(fechaSql) {
        if (!fechaSql) return '';
        return fechaSql.replace(' ', 'T').slice(0, 16); 
    }

    function abrirModalEditar(id, titulo, tipo, enlace, palabra_clave, productos_ids, sucursal_id, fecha_inicio, fecha_fin) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_titulo').value = titulo;
        document.getElementById('edit_tipo').value = tipo;
        if (document.getElementById('edit_sucursal')) document.getElementById('edit_sucursal').value = sucursal_id;
        
        // 🔥 Rellenar calendario
        document.getElementById('edit_fecha_inicio').value = formatoFechaLocal(fecha_inicio);
        document.getElementById('edit_fecha_fin').value = formatoFechaLocal(fecha_fin);

        if (palabra_clave && palabra_clave.trim() !== '') {
            document.getElementById('modoA_edit').checked = true;
            setModo('edit', 'A');
            document.getElementById('palabra_clave_edit').value = palabra_clave;
            document.getElementById('ids_edit').value = productos_ids;
        } else {
            document.getElementById('modoB_edit').checked = true;
            setModo('edit', 'B');
            document.getElementById('enlace_edit').value = enlace;

            if(enlace.includes('categoria=')) {
                document.getElementById('btn_cat_edit').checked = true; setTipoB('edit', 'categoria'); document.getElementById('select_cat_edit').value = enlace;
            } else if (enlace.includes('marca=')) {
                document.getElementById('btn_marca_edit').checked = true; setTipoB('edit', 'marca'); document.getElementById('select_marca_edit').value = enlace;
            } else {
                document.getElementById('btn_cat_edit').checked = true; setTipoB('edit', 'categoria'); document.getElementById('select_cat_edit').value = '';
            }
        }

        productosEdit = [];
        const tbodyEdit = document.getElementById('tablaSeleccionadosEdit');

        if (productos_ids && productos_ids.trim() !== '') {
            tbodyEdit.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-4 text-md"><div class="spinner-border spinner-border-sm text-cenco-green me-2"></div> Cargando...</td></tr>';
            fetch('<?= BASE_URL ?>admin/banners/cargarProductosPorCodigosAjax?codigos=' + encodeURIComponent(productos_ids) + '&sucursal=' + sucursal_id)
                .then(res => res.json())
                .then(data => { productosEdit = data; renderTabla(productosEdit, 'tablaSeleccionadosEdit', document.getElementById('ids_edit')); });
        } else {
            renderTabla(productosEdit, 'tablaSeleccionadosEdit', document.getElementById('ids_edit'));
        }

        new bootstrap.Modal(document.getElementById('editarBannerModal')).show();
    }

    // 🔥 NUEVA FUNCIÓN PARA GUARDAR SOLO LAS FECHAS
    function guardarFechasAjax(btn) {
        let id = document.getElementById('edit_id').value;
        let tipo = document.getElementById('edit_tipo').value;
        let inicio = document.getElementById('edit_fecha_inicio').value;
        let fin = document.getElementById('edit_fecha_fin').value;

        if(!id) {
            Swal.fire('Error', 'No hay banner seleccionado.', 'error');
            return;
        }

        let originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
        btn.disabled = true;

        fetch('<?= BASE_URL ?>admin/banners/actualizarFechasAjax', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, tipo: tipo, inicio: inicio, fin: fin })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Fechas Guardadas', text: 'Programación actualizada.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            } else {
                Swal.fire('Error', 'No se pudo guardar', 'error');
            }
        })
        .catch(err => { Swal.fire('Error', 'Problema de red.', 'error'); })
        .finally(() => { btn.innerHTML = originalHtml; btn.disabled = false; });
    }

    function toggleBanner(id, tipo, btn) {
        btn.disabled = true; const icon = btn.querySelector('i'); const originalClass = icon.className;
        icon.className = 'spinner-border spinner-border-sm text-secondary';

        fetch('<?= BASE_URL ?>admin/banners/toggleAjax', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id, tipo: tipo })
            }).then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const tdEstado = btn.closest('tr').cells[4]; 
                    if (data.nuevo_estado == 1) {
                        icon.className = 'fs-5 bi bi-eye-fill text-success';
                        tdEstado.innerHTML = '<span class="badge rounded-pill fs-6 px-3 py-2 bg-success">Activo</span>';
                    } else {
                        icon.className = 'fs-5 bi bi-eye-slash-fill text-secondary';
                        tdEstado.innerHTML = '<span class="badge rounded-pill fs-6 px-3 py-2 bg-secondary">Inactivo</span>';
                    }
                } else alert('Error al cambiar el estado');
            }).finally(() => btn.disabled = false);
    }

    function eliminarBannerAjax(id, tipo, btn) {
        Swal.fire({
            title: '¿Estás seguro?', text: "El banner se eliminará permanentemente de la plataforma.", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#E53935', cancelButtonColor: '#6c757d', confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; btn.disabled = true;

                fetch('<?= BASE_URL ?>admin/banners/borrarAjax', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id, tipo: tipo })
                }).then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const fila = btn.closest('tr');
                        fila.style.transition = "all 0.4s ease"; fila.style.opacity = "0"; fila.style.transform = "translateX(30px)";
                        setTimeout(() => fila.remove(), 400);
                        Swal.fire({ icon: 'success', title: 'Eliminado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                    } else { Swal.fire('Error', 'No se pudo eliminar', 'error'); btn.innerHTML = originalHtml; btn.disabled = false; }
                }).catch(err => { Swal.fire('Error', 'Problema de conexión.', 'error'); btn.innerHTML = originalHtml; btn.disabled = false; });
            }
        });
    }
</script>