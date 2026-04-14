<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .pagination .page-item.active .page-link {
        background-color: var(--cenco-indigo, #2A1B5E);
        border-color: var(--cenco-indigo, #2A1B5E);
        color: white;
    }
    .pagination .page-link { color: var(--cenco-indigo, #2A1B5E); cursor: pointer; }
    .pagination .page-link:focus { box-shadow: 0 0 0 0.25rem rgba(42, 27, 94, 0.25); }
    .ls-1 { letter-spacing: 1px; }
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
                    <p class="text-muted mb-2"><?= htmlspecialchars($usuario->email) ?></p>
                    
                    <span class="badge bg-cenco-indigo rounded-pill px-3 py-2">
                        <?= strtoupper($usuario->nombre_rol ?? 'Cliente') ?>
                    </span>

                    <?php if(!empty($usuario->razon_social)): ?>
                        <div class="mt-2 small text-muted fw-bold"><?= htmlspecialchars($usuario->razon_social) ?></div>
                    <?php endif; ?>

                    <hr class="my-4">
                    <div class="text-start">
                        <small class="text-uppercase text-muted fw-bold ls-1 d-block mb-2">Datos de Cuenta</small>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-dark"><i class="bi bi-card-heading me-2 text-cenco-green"></i>RUT</span>
                            <span class="fw-bold"><?= $usuario->rut ?: 'No registrado' ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-dark"><i class="bi bi-calendar3 me-2 text-cenco-green"></i>Registro</span>
                            <span><?= date('d/m/Y', strtotime($usuario->creado_en ?? 'now')) ?></span>
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
                            <h5 class="fw-bold text-cenco-indigo mb-4">Información de Identidad</h5>
                            <form action="<?= BASE_URL ?>perfil/guardar" method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small text-muted">Nombre Completo</label>
                                        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario->nombre) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small text-muted">Correo Electrónico (No editable)</label>
                                        <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($usuario->email) ?>" disabled>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small text-muted">RUT</label>
                                        <input type="text" name="rut" class="form-control" value="<?= htmlspecialchars($usuario->rut ?? '') ?>" placeholder="11.111.111-1">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small text-muted">Teléfono Principal</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-success"><i class="bi bi-whatsapp"></i></span>
                                            <input type="text" name="telefono" class="form-control fw-bold" value="<?= htmlspecialchars($usuario->telefono_principal ?? '') ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small text-muted">Razón Social</label>
                                        <input type="text" name="razon_social" class="form-control" value="<?= htmlspecialchars($usuario->razon_social ?? '') ?>" placeholder="Nombre de empresa">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small text-muted">Giro</label>
                                        <input type="text" name="giro" class="form-control" value="<?= htmlspecialchars($usuario->giro ?? '') ?>" placeholder="Giro comercial">
                                    </div>
                                </div>

                                <div class="text-end mb-4">
                                    <button type="submit" class="btn btn-cenco-green text-white fw-bold px-4 rounded-pill">
                                        <i class="bi bi-save me-2"></i>Guardar Cambios
                                    </button>
                                </div>
                            </form>

                            <hr class="my-4 border-light">

                            <div class="mt-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold text-cenco-indigo mb-0"><i class="bi bi-journal-bookmark me-2"></i>Contactos Alternativos</h6>
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
                                            <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center shadow-sm">
                                                <div>
                                                    <span class="d-block fw-bold text-dark small text-uppercase ls-1"><?= htmlspecialchars($tel->alias) ?></span>
                                                    <span class="text-cenco-indigo"><?= htmlspecialchars($tel->numero) ?></span>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                        <li><a class="dropdown-item small" href="javascript:void(0)" onclick="abrirEditarTelefono(<?= $tel->id ?>, '<?= htmlspecialchars($tel->alias) ?>', '<?= htmlspecialchars($tel->numero) ?>')"><i class="bi bi-pencil me-2"></i>Editar</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item small text-danger" href="javascript:void(0)" onclick="confirmarEliminarTelefono(<?= $tel->id ?>)"><i class="bi bi-trash me-2"></i>Eliminar</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; endforeach; ?>

                                    <?php if (!$hayAlternativos): ?>
                                        <div class="col-12"><div class="alert alert-light border text-center py-3 small text-muted">No tienes contactos alternativos guardados.</div></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="pedidos">
                            <h5 class="fw-bold text-cenco-indigo mb-4">Historial de Compras</h5>
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
                                            <tr><td colspan="5" class="text-center py-4">Aún no tienes pedidos.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($misPedidos as $pedido): 
                                                $entrega = $pedido['fecha_entrega'] ? 
                                                    '<span class="fw-bold text-dark"><i class="bi bi-truck me-2 text-success"></i>'.$pedido['fecha_entrega'].'</span>' : 
                                                    '<span class="text-muted">Pendiente</span>';
                                            ?>
                                                <tr class="fila-pedido">
                                                    <td class="ps-4">
                                                        <span class="fw-bold text-cenco-indigo">#<?= $pedido['id'] ?></span>
                                                        <div class="small text-muted" style="font-size: 0.75rem;">Tracking: <?= $pedido['numero_seguimiento'] ?? '---' ?></div>
                                                    </td>
                                                    <td><span class="badge rounded-pill bg-<?= $pedido['color_estado'] ?>"><?= $pedido['estado'] ?></span></td>
                                                    <td class="small"><?= $entrega ?></td>
                                                    <td class="text-end fw-bold">$<?= number_format($pedido['total'], 0, ',', '.') ?></td>
                                                    <td class="text-center">
                                                        <button class="btn btn-sm btn-outline-cenco-indigo rounded-circle" onclick="verDetallePedido(<?= $pedido['id'] ?>)" data-bs-toggle="modal" data-bs-target="#modalDetallePedido">
                                                            <i class="bi bi-eye-fill"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <nav id="nav-paginacion-pedidos" class="mt-4" style="display: none;">
                                <ul class="pagination justify-content-center mb-0" id="paginacion-pedidos"></ul>
                            </nav>
                        </div>

                        <div class="tab-pane fade" id="direcciones">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-bold text-cenco-indigo mb-0">Libreta de Direcciones</h5>
                                <button class="btn btn-sm btn-cenco-indigo text-white rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalNuevaDireccion">
                                    <i class="bi bi-plus-lg me-1"></i> Nueva Dirección
                                </button>
                            </div>

                            <div class="row g-3" id="contenedor-direcciones">
                                <?php if (empty($misDirecciones)): ?>
                                    <div class="col-12 text-center py-5 text-muted"><i class="bi bi-map fs-1 d-block mb-2"></i> No hay direcciones registradas.</div>
                                <?php else: foreach ($misDirecciones as $dir): ?>
                                    <div class="col-md-6" id="card-direccion-<?= $dir->id ?>">
                                        <div class="card h-100 border <?= $dir->es_principal ? 'border-cenco-green shadow-sm' : 'border-light' ?> rounded-4 position-relative">
                                            <div class="position-absolute top-0 end-0 p-3">
                                                <?php if ($dir->es_principal): ?>
                                                    <span class="text-cenco-green fs-4"><i class="bi bi-star-fill"></i></span>
                                                <?php else: ?>
                                                    <span onclick="confirmarFavoritoAjax(<?= $dir->id ?>)" class="text-muted fs-4 hover-star cursor-pointer"><i class="bi bi-star"></i></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-body">
                                                <span class="badge <?= $dir->es_principal ? 'bg-cenco-green' : 'bg-secondary' ?> mb-2"><?= htmlspecialchars($dir->nombre_etiqueta) ?></span>
                                                <p class="mb-1 fw-bold text-dark"><?= htmlspecialchars($dir->direccion) ?></p>
                                                <p class="mb-3 small text-muted"><?= htmlspecialchars($dir->nombre_comuna) ?></p>
                                                <div class="d-flex gap-2">
                                                    <button onclick="cargarDatosEdicion(<?= $dir->id ?>)" class="btn btn-sm btn-outline-primary rounded-pill px-3">Editar</button>
                                                    <?php if (!$dir->es_principal): ?>
                                                        <button onclick="confirmarBorrarAjax(<?= $dir->id ?>)" class="btn btn-sm btn-outline-danger rounded-pill px-3">Eliminar</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; endif; ?>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="seguridad">
                            <h5 class="fw-bold text-cenco-indigo mb-4">Acceso y Seguridad</h5>
                            <form action="<?= BASE_URL ?>perfil/cambiarPassword" method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">Contraseña Actual</label>
                                    <input type="password" name="pass_actual" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">Nueva Contraseña</label>
                                    <input type="password" name="pass_nueva" class="form-control" required minlength="6">
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-warning text-dark fw-bold px-4 rounded-pill shadow-sm">Cambiar Mi Clave</button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    if (typeof BASE_URL === 'undefined') { var BASE_URL = '<?= BASE_URL ?>'; }

    document.addEventListener("DOMContentLoaded", function() {
        const filas = document.querySelectorAll('.fila-pedido');
        const porPagina = 10;
        const totalPaginas = Math.ceil(filas.length / porPagina);
        if (totalPaginas > 1) {
            document.getElementById('nav-paginacion-pedidos').style.display = 'block';
            mostrarPagina(1);
        } else { filas.forEach(f => f.style.display = 'table-row'); }

        function mostrarPagina(pag) {
            filas.forEach((f, i) => f.style.display = (i >= (pag-1)*porPagina && i < pag*porPagina) ? 'table-row' : 'none');
            renderPaginacion(pag);
        }

        function renderPaginacion(actual) {
            const ul = document.getElementById('paginacion-pedidos');
            if (!ul) return;
            ul.innerHTML = `<li class="page-item ${actual === 1 ? 'disabled' : ''}"><a class="page-link" onclick="window.cambiarPaginaPedido(${actual-1})"><i class="bi bi-chevron-left"></i></a></li>`;
            for (let i = 1; i <= totalPaginas; i++) {
                ul.innerHTML += `<li class="page-item ${actual === i ? 'active' : ''}"><a class="page-link" onclick="window.cambiarPaginaPedido(${i})">${i}</a></li>`;
            }
            ul.innerHTML += `<li class="page-item ${actual === totalPaginas ? 'disabled' : ''}"><a class="page-link" onclick="window.cambiarPaginaPedido(${actual+1})"><i class="bi bi-chevron-right"></i></a></li>`;
        }

        window.cambiarPaginaPedido = (p) => { if (p >= 1 && p <= totalPaginas) mostrarPagina(p); };
    });
</script>
<script src="<?= BASE_URL ?>js/perfil.js"></script>