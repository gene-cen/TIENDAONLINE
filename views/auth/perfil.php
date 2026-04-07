<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    /* Estilos personalizados para que la paginación combine con tu diseño Cencocal */
    .pagination .page-item.active .page-link {
        background-color: var(--cenco-indigo, #2A1B5E);
        border-color: var(--cenco-indigo, #2A1B5E);
        color: white;
    }

    .pagination .page-link {
        color: var(--cenco-indigo, #2A1B5E);
        cursor: pointer;
    }

    .pagination .page-link:focus {
        box-shadow: 0 0 0 0.25rem rgba(42, 27, 94, 0.25);
    }
</style>

<div class="position-fixed top-0 end-0 p-3" style="z-index: 1055">
    <div id="liveToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">Acción realizada con éxito.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<div class="container py-5">

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] == 'pass_ok'): ?>
            <div class="alert alert-success rounded-3 shadow-sm mb-4"><i class="bi bi-check-circle me-2"></i>Contraseña actualizada correctamente.</div>
        <?php elseif ($_GET['msg'] == 'datos_ok'): ?>
            <div class="alert alert-success rounded-3 shadow-sm mb-4"><i class="bi bi-person-check me-2"></i>Datos personales actualizados.</div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden text-center h-100">
                <div class="card-body p-5">
                    <div class="position-relative d-inline-block mb-3">
                        <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_positivo.png" class="rounded-circle bg-light p-3 border" style="width: 140px; height: 140px; object-fit: contain;">
                        <span class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle p-2"></span>
                    </div>
                    <h4 class="fw-bold text-cenco-indigo mb-1"><?= htmlspecialchars($usuario->nombre) ?></h4>
                    <p class="text-muted mb-3"><?= htmlspecialchars($usuario->email) ?></p>
                    <span class="badge bg-cenco-indigo rounded-pill px-3 py-2"><?= strtoupper($usuario->rol) ?></span>
                    <hr class="my-4">
                    <div class="text-start">
                        <small class="text-uppercase text-muted fw-bold ls-1 d-block mb-2">Datos de Cuenta</small>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-dark"><i class="bi bi-card-heading me-2 text-cenco-green"></i>RUT</span>
                            <span class="fw-bold"><?= $usuario->rut ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-dark"><i class="bi bi-calendar3 me-2 text-cenco-green"></i>Registro</span>
                            <span><?= date('d/m/Y', strtotime($usuario->created_at ?? 'now')) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom border-light p-0">
                    <ul class="nav nav-tabs nav-fill card-header-tabs m-0 border-0" id="perfilTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active py-3 fw-bold border-0" data-bs-toggle="tab" data-bs-target="#datos">
                                <i class="bi bi-person-gear me-2"></i>Mis Datos
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-3 fw-bold border-0" data-bs-toggle="tab" data-bs-target="#pedidos">
                                <i class="bi bi-bag-check-fill me-2"></i>Mis Pedidos
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-3 fw-bold border-0" data-bs-toggle="tab" data-bs-target="#direcciones">
                                <i class="bi bi-geo-alt me-2"></i>Mis Direcciones
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-3 fw-bold border-0" data-bs-toggle="tab" data-bs-target="#seguridad">
                                <i class="bi bi-shield-lock me-2"></i>Seguridad
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-4 p-lg-5">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="datos">
                            <h5 class="fw-bold text-cenco-indigo mb-4">Información Personal</h5>
                            <form action="<?= BASE_URL ?>perfil/guardar" method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small">Nombre Completo</label>
                                        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario->nombre) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small">Teléfono Titular (Principal)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-success"><i class="bi bi-person-check-fill"></i></span>
                                            <input type="text" name="telefono" class="form-control fw-bold" value="<?= htmlspecialchars($usuario->telefono_principal ?? '') ?>" required>
                                        </div>
                                        <small class="text-muted">Este número aparecerá por defecto en tus compras.</small>
                                    </div>
                                </div>

                                <div class="text-end mb-4">
                                    <button type="submit" class="btn btn-cenco-green text-white fw-bold px-4 rounded-pill">
                                        Actualizar Datos Básicos
                                    </button>
                                </div>
                            </form>

                            <hr class="my-4 border-light">

                            <div class="mt-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold text-cenco-indigo mb-0"><i class="bi bi-journal-bookmark me-2"></i>Agenda de Contactos Alternativos</h6>
                                    <button class="btn btn-sm btn-outline-cenco-green rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalNuevoTelefono">
                                        <i class="bi bi-plus-lg me-1"></i> Nuevo Contacto
                                    </button>
                                </div>

                                <div class="row g-3">
                                    <?php
                                    $hayAlternativos = false;
                                    foreach ($telefonos as $tel):
                                        if (!$tel->es_principal):
                                            $hayAlternativos = true;
                                    ?>
                                            <div class="col-md-6">
                                                <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <span class="d-block fw-bold text-dark small text-uppercase ls-1"><?= htmlspecialchars($tel->alias) ?></span>
                                                        <span class="text-cenco-indigo"><?= htmlspecialchars($tel->numero) ?></span>
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                            <li>
                                                                <a class="dropdown-item small" href="javascript:void(0)"
                                                                    onclick="abrirEditarTelefono(<?= $tel->id ?>, '<?= htmlspecialchars($tel->alias) ?>', '<?= htmlspecialchars($tel->numero) ?>')">
                                                                    <i class="bi bi-pencil me-2"></i>Editar
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <hr class="dropdown-divider">
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item small text-danger" href="javascript:void(0)"
                                                                    onclick="confirmarEliminarTelefono(<?= $tel->id ?>)">
                                                                    <i class="bi bi-trash me-2"></i>Eliminar
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php
                                        endif;
                                    endforeach;

                                    if (!$hayAlternativos):
                                        ?>
                                        <div class="col-12">
                                            <div class="alert alert-light border text-center py-3">
                                                <small class="text-muted">No tienes contactos alternativos guardados. Se agregarán automáticamente cuando realices una compra con un número nuevo.</small>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="pedidos">
                            <h5 class="fw-bold text-cenco-indigo mb-4">Mis Pedidos</h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="small fw-bold text-muted ps-4">Pedido</th>
                                            <th class="small fw-bold text-muted">Estado</th>
                                            <th class="small fw-bold text-muted">Entrega Estimada</th>
                                            <th class="small fw-bold text-muted text-end">Total</th>
                                            <th class="small fw-bold text-muted text-center">Ver</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($misPedidos)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">Aún no tienes pedidos.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($misPedidos as $pedido):
                                                $id = $pedido['id'];
                                                $tracking = $pedido['numero_seguimiento'] ?? '---';

                                                // Lógica visual de entrega
                                                $entrega = $pedido['fecha_entrega'] ?
                                                    '<span class="d-block fw-bold text-dark"><i class="bi bi-truck me-2 text-success"></i>' . $pedido['fecha_entrega'] . '</span><small class="text-muted ps-4">' . $pedido['rango_horario'] . '</small>' :
                                                    '<span class="text-muted">Pendiente</span>';
                                            ?>
                                                <tr class="fila-pedido" style="display: none;">
                                                    <td class="ps-4">
                                                        <span class="fw-bold text-cenco-indigo">#<?= $id ?></span>
                                                        <div class="small text-muted d-flex align-items-center" style="font-size: 0.75rem;">
                                                            <i class="bi bi-upc-scan me-1"></i> <?= $tracking ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge rounded-pill bg-<?= htmlspecialchars($pedido['color_estado']) ?>">
                                                            <?= htmlspecialchars($pedido['estado']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="small lh-sm">
                                                        <?= $entrega ?>
                                                    </td>
                                                    <td class="text-end fw-bold">$<?= number_format($pedido['total'], 0, ',', '.') ?></td>
                                                    <td class="text-center">
                                                        <button class="btn btn-sm btn-outline-cenco-indigo rounded-circle shadow-sm"
                                                            onclick="verDetallePedido(<?= $id ?>, '<?= $pedido['fecha_entrega'] ?>', '<?= $pedido['rango_horario'] ?>')"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#modalDetallePedido">
                                                            <i class="bi bi-eye-fill"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <nav aria-label="Navegación de pedidos" class="mt-4" id="nav-paginacion-pedidos" style="display: none;">
                                <ul class="pagination justify-content-center mb-0" id="paginacion-pedidos">
                                </ul>
                            </nav>

                        </div>

                        <div class="tab-pane fade" id="direcciones">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-bold text-cenco-indigo mb-0">Mis Lugares de Entrega</h5>
                                <button class="btn btn-sm btn-outline-cenco-indigo rounded-pill" data-bs-toggle="modal" data-bs-target="#modalNuevaDireccion">
                                    <i class="bi bi-plus-lg me-1"></i> Nueva Dirección
                                </button>
                            </div>

                            <div class="row g-3" id="contenedor-direcciones">
                                <?php if (empty($misDirecciones)): ?>
                                    <div class="col-12 text-center py-5 text-muted">
                                        <i class="bi bi-map fs-1 d-block mb-2"></i> No tienes direcciones guardadas aún.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($misDirecciones as $dir): ?>
                                        <div class="col-md-6" id="card-direccion-<?= $dir->id ?>">
                                            <div class="card h-100 border <?= $dir->es_principal ? 'border-cenco-green shadow-sm' : 'border-light' ?> rounded-4 position-relative">
                                                <div class="position-absolute top-0 end-0 p-3" id="star-container-<?= $dir->id ?>">
                                                    <?php if ($dir->es_principal): ?>
                                                        <span class="text-cenco-green fs-4" title="Dirección Principal"><i class="bi bi-star-fill"></i></span>
                                                    <?php else: ?>
                                                        <span onclick="confirmarFavoritoAjax(<?= $dir->id ?>, '<?= htmlspecialchars($dir->nombre_etiqueta) ?>')" class="text-muted fs-4 hover-star cursor-pointer" title="Marcar como Principal">
                                                            <i class="bi bi-star"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span class="badge <?= $dir->es_principal ? 'bg-cenco-green' : 'bg-secondary' ?>">
                                                            <?= htmlspecialchars($dir->nombre_etiqueta) ?>
                                                        </span>
                                                    </div>
                                                    <p class="mb-1 fw-bold text-dark pe-4"><?= htmlspecialchars($dir->direccion) ?></p>
                                                    <p class="mb-3 small text-muted">
                                                        <?= htmlspecialchars($dir->nombre_comuna) ?>, <?= htmlspecialchars($dir->nombre_region) ?>
                                                    </p>
                                                    <div class="d-flex gap-2">
                                                        <button onclick="cargarDatosEdicion(<?= $dir->id ?>)" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                            <i class="bi bi-pencil-square"></i> Editar
                                                        </button>
                                                        <?php if (!$dir->es_principal): ?>
                                                            <button onclick="confirmarBorrarAjax(<?= $dir->id ?>)" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="seguridad">
                            <h5 class="fw-bold text-cenco-indigo mb-4">Cambiar Contraseña</h5>
                            <form action="<?= BASE_URL ?>perfil/cambiarPassword" method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Contraseña Actual</label>
                                    <input type="password" name="pass_actual" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Nueva Contraseña</label>
                                    <input type="password" name="pass_nueva" class="form-control" required minlength="6">
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-warning text-dark fw-bold px-4 rounded-pill">Actualizar Clave</button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevaDireccion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-cenco-indigo">Agregar Nueva Dirección</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formNuevaDireccion" onsubmit="guardarDireccionAjax(event, this, 'agregarDireccionAjax')" method="POST">
                    <input type="hidden" name="latitud" id="inputLat">
                    <input type="hidden" name="longitud" id="inputLng">
                    <div class="row">
                        <div class="col-lg-5 mb-3">
                            <div class="mb-3">
                                <label class="fw-bold small mb-1">Nombre (Ej: Casa, Oficina)</label>
                                <input type="text" name="etiqueta" class="form-control" placeholder="Ej: Donde mi mamá" required>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold small mb-1">Región</label>
                                <select id="selectRegion" class="form-select" onchange="cargarComunas(this.value)" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($regiones as $reg): ?>
                                        <option value="<?= $reg->id ?>"><?= $reg->nombre ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold small mb-1">Comuna</label>
                                <select name="comuna_id" id="selectComuna" class="form-select" disabled required>
                                    <option value="">Elige Región primero</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold small mb-1">Calle y Número</label>
                                <div class="input-group">
                                    <input type="text" name="direccion" id="inputDireccion" class="form-control" placeholder="Ej: Av. Principal 123" required>
                                    <button type="button" id="btnBuscarMapa" class="btn btn-cenco-indigo" onclick="buscarEnMapa()">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <label class="fw-bold small mb-1 text-cenco-green"><i class="bi bi-pin-map-fill"></i> Ubicación Exacta</label>
                            <div id="mapaSelect" class="rounded-3 border" style="height: 350px; width: 100%;"></div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-cenco-green w-100 rounded-pill fw-bold py-2 mt-3">Guardar Dirección</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarDireccion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-cenco-indigo">Editar Dirección</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formEditarDireccion" onsubmit="guardarDireccionAjax(event, this, 'actualizarDireccion')" method="POST">
                    <input type="hidden" name="id_direccion" id="edit_id">
                    <input type="hidden" name="latitud" id="edit_lat">
                    <input type="hidden" name="longitud" id="edit_lng">
                    <div class="row">
                        <div class="col-lg-5 mb-3">
                            <div class="mb-3">
                                <label class="fw-bold small mb-1">Nombre</label>
                                <input type="text" name="etiqueta" id="edit_etiqueta" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold small mb-1">Región</label>
                                <select id="edit_region" class="form-select" onchange="cargarComunasEdicion(this.value)" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($regiones as $reg): ?>
                                        <option value="<?= $reg->id ?>"><?= $reg->nombre ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold small mb-1">Comuna</label>
                                <select name="comuna_id" id="edit_comuna" class="form-select" required></select>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold small mb-1">Calle y Número</label>
                                <div class="input-group">
                                    <input type="text" name="direccion" id="edit_direccion" class="form-control" required>
                                    <button type="button" id="btnBuscarMapaEdit" class="btn btn-cenco-indigo" onclick="buscarEnMapaEdit()">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <label class="fw-bold small mb-1 text-cenco-green">Ajustar Ubicación</label>
                            <div id="mapaEditar" class="rounded-3 border" style="height: 350px; width: 100%;"></div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-warning w-100 rounded-pill fw-bold py-2 mt-3">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetallePedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-cenco-indigo">
                    Detalle del Pedido <span id="detalleIdPedido" class="text-muted small ms-2"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="nav nav-tabs nav-fill" id="modalTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold small py-3" id="tab-productos" data-bs-toggle="tab" data-bs-target="#content-productos" type="button">
                            <i class="bi bi-cart3 me-2"></i>Productos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold small py-3" id="tab-seguimiento" data-bs-toggle="tab" data-bs-target="#content-seguimiento" type="button">
                            <i class="bi bi-clock-history me-2"></i>Seguimiento
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="modalTabsContent">
                    <div class="tab-pane fade show active" id="content-productos">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <tbody id="listaDetallePedido">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="content-seguimiento">
                        <div class="p-4" style="max-height: 400px; overflow-y: auto;">
                            <div id="timelineContainer" class="position-relative">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarTelefono" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-cenco-indigo">Editar Contacto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>perfil/editarTelefono" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_telefono" id="edit_tel_id">
                    <div class="mb-3">
                        <label class="fw-bold small">Alias (Ej: Santi)</label>
                        <input type="text" name="alias" id="edit_tel_alias" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">Número</label>
                        <input type="tel" name="numero" id="edit_tel_numero" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-cenco-indigo w-100 rounded-pill fw-bold">Actualizar Contacto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevoTelefono" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-cenco-indigo">Nuevo Contacto Alternativo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>perfil/agregarTelefonoPerfil" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="fw-bold small">Nombre del Contacto (Alias)</label>
                        <input type="text" name="alias" class="form-control" placeholder="Ej: Santi, Marido, Oficina..." required>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">Número de Teléfono</label>
                        <input type="tel" name="numero" class="form-control" placeholder="+569..." required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-cenco-green text-white w-100 rounded-pill fw-bold">Guardar Contacto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Variable global para usar en perfil.js
    if (typeof BASE_URL === 'undefined') {
        const BASE_URL = '<?= BASE_URL ?>';
    }

    // ==========================================
    // LÓGICA DE PAGINACIÓN PARA PEDIDOS
    // ==========================================
    document.addEventListener("DOMContentLoaded", function() {
        const filas = document.querySelectorAll('.fila-pedido');
        const porPagina = 10;
        const totalPaginas = Math.ceil(filas.length / porPagina);
        let paginaActual = 1;

        if (totalPaginas > 1) {
            document.getElementById('nav-paginacion-pedidos').style.display = 'block';
            mostrarPagina(1);
        } else if (totalPaginas === 1) {
            // Si solo hay una página, mostrar todo directamente sin botones
            mostrarPagina(1);
        }

        function mostrarPagina(pag) {
            paginaActual = pag;
            const inicio = (pag - 1) * porPagina;
            const fin = inicio + porPagina;

            filas.forEach((fila, index) => {
                if (index >= inicio && index < fin) {
                    fila.style.display = 'table-row';
                } else {
                    fila.style.display = 'none';
                }
            });
            renderPaginacion();
        }

        function renderPaginacion() {
            const ul = document.getElementById('paginacion-pedidos');
            if (!ul) return;
            ul.innerHTML = '';

            // Botón Anterior (<)
            ul.innerHTML += `
                <li class="page-item ${paginaActual === 1 ? 'disabled' : ''}">
                    <a class="page-link shadow-sm rounded-start-pill px-3" onclick="window.cambiarPaginaPedido(${paginaActual - 1})"><i class="bi bi-chevron-left"></i></a>
                </li>
            `;

            // Números de Página
            for (let i = 1; i <= totalPaginas; i++) {
                ul.innerHTML += `
                    <li class="page-item ${paginaActual === i ? 'active' : ''}">
                        <a class="page-link shadow-sm fw-bold px-3" onclick="window.cambiarPaginaPedido(${i})">${i}</a>
                    </li>
                `;
            }

            // Botón Siguiente (>)
            ul.innerHTML += `
                <li class="page-item ${paginaActual === totalPaginas ? 'disabled' : ''}">
                    <a class="page-link shadow-sm rounded-end-pill px-3" onclick="window.cambiarPaginaPedido(${paginaActual + 1})"><i class="bi bi-chevron-right"></i></a>
                </li>
            `;
        }

        // Hacer la función global para que la llamen los enlaces
        window.cambiarPaginaPedido = function(pag) {
            if (pag >= 1 && pag <= totalPaginas) {
                mostrarPagina(pag);
            }
        };
    });
</script>
<script src="<?= BASE_URL ?>js/perfil.js"></script>