<?php
// =================================================================================
// 1. CONFIGURACIÓN DE REGLAS DE NEGOCIO (PHP)
// =================================================================================
$umbralEnvioGratis = 39950;
$umbralAlerta = 30000;
$costoDespachoFijo = 2990;
$costoServicioFijo = 490; // Costo por preparación/servicio

// Detectar Roles
$esAdmin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin');
// Detectar si NO hay usuario logueado (Invitado)
$esInvitado = !isset($_SESSION['user_id']);

// =================================================================================
// 2. FILTRO DINÁMICO DE COMUNAS POR SUCURSAL
// =================================================================================

$sucursalActivaID = $_SESSION['sucursal_activa'] ?? 29;
$comunaRetiroPredefinida = ($sucursalActivaID == 10) ? 'Villa Alemana' : 'La Calera';

$listaComunasFiltrada = array_filter($listaComunas ?? [], function ($c) use ($sucursalActivaID) {
    $sId = is_object($c) ? ($c->sucursal_id ?? null) : ($c['sucursal_id'] ?? null);
    return $sId == $sucursalActivaID;
});

// Cálculo del carrito
$totalCarro = 0;
if (!empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $totalCarro += $item['precio'] * $item['cantidad'];
    }
}

$costoEnvio = ($totalCarro >= $umbralEnvioGratis) ? 0 : $costoDespachoFijo;
$faltaParaGratis = $umbralEnvioGratis - $totalCarro;
$mostrarAlerta = ($totalCarro >= $umbralAlerta && $totalCarro < $umbralEnvioGratis);

// Preparar Data JS para los mapas
$jsSucursales = [];
foreach ($sucursales as $s) {
    $jsSucursales[] = [
        'codigo' => $s['codigo_erp'],
        'nombre' => $s['nombre'],
        'direccion' => $s['direccion'],
        'comuna' => $s['nombre_comuna'] ?? 'Sin Comuna',
        'lat' => !empty($s['latitud']) ? floatval($s['latitud']) : 0,
        'lng' => !empty($s['longitud']) ? floatval($s['longitud']) : 0,
        'horario' => $s['horario'] ?? '09:00 - 19:00',
        'fono' => $s['fono'] ?? '',
    ];
}
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container mb-5 mt-4">
    <h2 class="mb-4 fw-black text-cenco-indigo ls-1">Finalizar Compra</h2>

    <div class="row g-5">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h5 class="mb-0 fw-bold text-cenco-indigo"><i class="bi bi-truck me-2 text-cenco-green"></i>Datos de Entrega</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= BASE_URL ?>checkout/procesar" method="POST" id="formCheckout">

                        <input type="hidden" name="nueva_lat" id="inputLat">
                        <input type="hidden" name="nueva_lng" id="inputLng">

                        <?php if ($esAdmin): ?>
                            <input type="hidden" name="venta_asistida_flag" value="1">

                            <div class="alert alert-primary border-0 shadow-sm rounded-4 mb-4 p-3 d-flex align-items-center bg-primary bg-opacity-10">
                                <i class="bi bi-person-badge-fill fs-2 text-primary me-3"></i>
                                <div>
                                    <strong class="text-primary d-block fs-6">MODO VENTA ASISTIDA</strong>
                                    <small class="text-dark">Estás registrando una compra presencial desde tu cuenta de administrador.</small>
                                </div>
                            </div>
                            <div id="seccion_asistida" class="animate__animated animate__fadeIn mb-4">
                                <div class="card border-primary border-2 bg-white rounded-4 p-4 shadow-sm">
                                    <h5 class="fw-bold text-primary mb-3 border-bottom pb-2">
                                        <i class="bi bi-person-vcard me-2"></i>Datos del Cliente en Tienda
                                    </h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="small fw-bold text-dark">RUT Cliente *</label>
                                            <input type="text" name="asistido_rut" id="asistido_rut" class="form-control border-primary" placeholder="12.345.678-9" maxlength="12" oninput="formatearRut(this)">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="small fw-bold text-dark">Teléfono Contacto *</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-primary fw-bold text-muted">+569</span>
                                                <input type="tel" name="asistido_telefono" id="asistido_telefono" class="form-control border-primary" maxlength="8" placeholder="98765432" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="small fw-bold text-dark">Primer Nombre *</label>
                                            <input type="text" name="asistido_p_nombre" id="asistido_p_nombre" class="form-control border-primary" oninput="capitalizarLetras(this)">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="small fw-bold text-dark">Segundo Nombre</label>
                                            <input type="text" name="asistido_s_nombre" class="form-control border-primary" oninput="capitalizarLetras(this)">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="small fw-bold text-dark">Primer Apellido *</label>
                                            <input type="text" name="asistido_p_apellido" id="asistido_p_apellido" class="form-control border-primary" oninput="capitalizarLetras(this)">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="small fw-bold text-dark">Segundo Apellido</label>
                                            <input type="text" name="asistido_s_apellido" class="form-control border-primary" oninput="capitalizarLetras(this)">
                                        </div>
                                        <div class="col-md-12">
                                            <label class="small fw-bold text-dark">Correo Electrónico (Opcional)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-primary"><i class="bi bi-envelope text-muted"></i></span>
                                                <input type="email" name="asistido_email" id="asistido_email" class="form-control border-primary" placeholder="cliente@correo.com">
                                            </div>
                                            <div class="form-text small text-muted">Si ingresas un correo, se le enviará una copia del comprobante al cliente.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div id="datos_cliente_normal">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="fw-bold text-muted small mb-1">Cliente</label>
                                        <div class="p-2 bg-light rounded border small fw-bold text-truncate"><?= htmlspecialchars($usuario->nombre ?? 'Invitado') ?></div>
                                    </div>
                                    <div class="col-md-6 mt-2 mt-md-0">
                                        <label class="fw-bold text-muted small mb-1">RUT</label>
                                        <div class="p-2 bg-light rounded border small fw-bold">
                                            <?= htmlspecialchars($usuario->rut ?? 'No Registrado') ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="fw-bold text-muted small mb-1">Teléfono Titular (Obligatorio)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="bi bi-person-check text-success"></i></span>
                                                <input type="text" name="telefono_contacto" class="form-control bg-light"
                                                    value="<?= htmlspecialchars($usuario->telefono_principal ?? '') ?>" <?= $esInvitado ? 'placeholder="Ej: 98765432" required' : 'readonly' ?>>
                                            </div>
                                        </div>

                                        <?php if (!$esInvitado): ?>
                                            <div class="col-md-6">
                                                <label class="fw-bold text-cenco-indigo small mb-1">Segundo Contacto (Opcional)</label>
                                                <select class="form-select shadow-sm" id="selector_segundo_contacto" name="telefono_seleccionado_2" onchange="gestionarSegundoTelefono()">
                                                    <option value="">-- No indicar otro número --</option>
                                                    <?php foreach ($telefonos ?? [] as $tel): ?>
                                                        <?php if (!$tel->es_principal): ?>
                                                            <option value="<?= htmlspecialchars($tel->numero) ?>">
                                                                <?= htmlspecialchars($tel->alias) ?>: <?= htmlspecialchars($tel->numero) ?>
                                                            </option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                    <option value="nuevo" class="text-primary fw-bold">+ Añadir nuevo número...</option>
                                                </select>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!$esInvitado): ?>
                                        <div id="wrapper_nuevo_segundo_tel" class="d-none mt-3 p-3 bg-light rounded-3 border border-primary border-opacity-25 shadow-sm">
                                            <h6 class="small fw-bold text-cenco-indigo mb-2"><i class="bi bi-telephone-plus me-1"></i> Añadir Nuevo Contacto</h6>
                                            <div class="row g-2">
                                                <div class="col-md-7">
                                                    <label class="form-label small fw-bold">Número</label>
                                                    <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                                        <span class="input-group-text bg-white border-0 text-muted fw-bold">+569</span>
                                                        <input type="tel" id="telefono_nuevo_2" name="telefono_nuevo_2" class="form-control bg-white border-0" placeholder="Ej: 98765432" maxlength="8" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                                    </div>
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="small text-muted">Alias (Ej: Marido)</label>
                                                    <input type="text" name="nuevo_alias_2" class="form-control form-control-sm" placeholder="Nombre">
                                                </div>
                                                <div class="mt-3 d-flex align-items-center">
                                                    <input type="hidden" name="guardar_nuevo_2" id="hidden_save_tel2" value="0">
                                                    <button type="button" class="btn btn-sm btn-outline-cenco-indigo rounded-pill fw-bold px-3 transition-hover" id="btnConfirmarTel" onclick="confirmarNuevoTelefono()">
                                                        Vincular Contacto
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <hr class="border-light my-4">

                        <?php
                        // LÓGICA VISUAL TEMPORAL: La Calera (ID 29) solo tiene Retiro en Tienda habilitado
                        $esSoloRetiro = ($sucursalActivaID == 29);
                        ?>

                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="tipo_entrega" id="opcion_despacho" value="1" <?= $esSoloRetiro ? 'disabled' : 'checked' ?> onchange="cambiarMetodoEntrega()">

                                <label class="btn btn-outline-cenco-indigo w-100 py-3 d-flex flex-column align-items-center justify-content-center gap-1 h-100 shadow-sm border-2 position-relative rounded-3 <?= $esSoloRetiro ? 'opacity-50 bg-light' : '' ?>" for="opcion_despacho" style="min-height: 120px; <?= $esSoloRetiro ? 'cursor: not-allowed;' : '' ?>">
                                    <i class="bi bi-house-door-fill fs-2 <?= $esSoloRetiro ? 'text-secondary' : '' ?>"></i>
                                    <span class="fw-bold <?= $esSoloRetiro ? 'text-secondary' : '' ?>">A Domicilio</span>

                                    <?php if ($esSoloRetiro): ?>
                                        <span class="badge bg-danger mt-2 text-wrap shadow-sm" style="font-size: 0.65rem; max-width: 90%;">
                                            <i class="bi bi-cone-striped me-1"></i>No disponible en tu zona
                                        </span>
                                    <?php else: ?>
                                        <span class="small text-muted" id="texto-precio-despacho">
                                            <?= ($totalCarro >= $umbralEnvioGratis) ? '<span class="text-success fw-bold">¡GRATIS!</span>' : '$' . number_format($costoDespachoFijo, 0, ',', '.') ?>
                                        </span>
                                        <span class="badge bg-light text-dark border mt-1" style="font-size: 0.65rem;">
                                            <i class="bi bi-clock text-primary"></i> 09:00 a 19:00 hrs
                                        </span>
                                    <?php endif; ?>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="tipo_entrega" id="opcion_retiro" value="2" <?= $esSoloRetiro ? 'checked' : '' ?> onchange="cambiarMetodoEntrega()">
                                <label class="btn btn-outline-cenco-indigo w-100 py-3 d-flex flex-column align-items-center justify-content-center gap-2 h-100 shadow-sm border-2 position-relative rounded-3" for="opcion_retiro" style="min-height: 120px;">
                                    <span class="badge-gratis">¡GRATIS!</span>
                                    <i class="bi bi-shop fs-2"></i>
                                    <span class="fw-bold">Retiro en Tienda</span>
                                    <span class="small text-muted">$0</span>
                                </label>
                            </div>
                        </div>

                        <div id="alerta-envio-gratis" class="mb-4 <?= $mostrarAlerta ? '' : 'd-none' ?>">
                            <div class="shipping-alert animate__animated animate__pulse">
                                <i class="bi bi-piggy-bank-fill fs-3 text-warning"></i>
                                <div>
                                    <strong>¡Estás muy cerca!</strong>
                                    <div class="small">Agrega <span class="fw-bold text-dark">$<?= number_format($faltaParaGratis, 0, ',', '.') ?></span> más para envío gratis.</div>
                                </div>
                            </div>
                        </div>

                        <div id="bloque_despacho">
                            <div class="alert alert-primary bg-primary bg-opacity-10 border-0 shadow-sm d-flex align-items-center mb-4 p-3 rounded-4">
                                <i class="bi bi-info-circle-fill text-primary fs-2 me-3"></i>
                                <div>
                                    <h6 class="fw-bold text-primary mb-1" style="font-size: 0.85rem;">Condiciones de Entrega</h6>
                                    <p class="mb-0 text-dark" style="font-size: 0.75rem;">
                                        Las entregas se realizan de <strong>Lunes a Viernes entre 09:00 y 19:00 hrs</strong>.<br>
                                        <em>Despachos en 48 hrs hábiles (compras realizadas hasta las 15:00 hrs).</em>
                                    </p>
                                </div>
                            </div>

                            <div class="mb-3" style="<?= ($esAdmin || $esInvitado) ? 'display:none;' : '' ?>">
                                <label class="fw-bold text-cenco-indigo small mb-1">Dirección de Envío</label>
                                <select class="form-select mb-3" id="selector_direccion" name="origen_direccion" onchange="gestionarNuevaDireccion()">
                                    <?php if ($esAdmin || $esInvitado): ?>
                                        <option value="nueva" data-lat="-32.9238" data-lng="-71.5176" selected>Nueva Dirección Manual</option>
                                    <?php else: ?>
                                        <option value="perfil"
                                            data-alias="Dirección Principal"
                                            data-direccion="<?= htmlspecialchars($usuario->direccion ?? '') ?>"
                                            data-lat="-32.7833" data-lng="-71.2000" selected>
                                            Principal: <?= htmlspecialchars($usuario->direccion ?? '') ?>
                                        </option>
                                        <?php foreach ($direcciones ?? [] as $dir): ?>
                                            <option value="<?= $dir->id ?>"
                                                data-alias="<?= htmlspecialchars($dir->nombre_etiqueta) ?>"
                                                data-direccion="<?= htmlspecialchars($dir->direccion) ?>"
                                                data-lat="<?= !empty($dir->latitud) ? $dir->latitud : -32.7833 ?>"
                                                data-lng="<?= !empty($dir->longitud) ? $dir->longitud : -71.2000 ?>">
                                                <?= htmlspecialchars($dir->nombre_etiqueta) ?>: <?= htmlspecialchars($dir->direccion) ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="nueva" class="text-primary fw-bold">+ Agregar Nueva Dirección...</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div id="contenedor-detalle-direccion" class="mb-3" style="<?= ($esAdmin || $esInvitado) ? 'display:none;' : '' ?>">
                                <div class="sucursal-card-detail shadow-sm">
                                    <div class="row g-0">
                                        <div class="col-md-5 bg-light p-3 d-flex flex-column justify-content-center">
                                            <h6 class="fw-bold text-cenco-indigo mb-3" id="card-dir-alias"><i class="bi bi-house-door me-2"></i>...</h6>
                                            <ul class="list-unstyled sucursal-info-list mb-0">
                                                <li class="d-flex align-items-start mb-2"><i class="bi bi-geo-alt-fill text-danger me-2 mt-1"></i><span id="card-dir-texto" class="fw-bold text-dark">...</span></li>
                                                <li class="d-flex align-items-center"><i class="bi bi-truck text-success me-2"></i><span class="text-success fw-bold">Cobertura Disponible</span></li>
                                            </ul>
                                        </div>
                                        <div class="col-md-7">
                                            <div id="mapa-direccion-guardada"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="form_nueva_direccion" class="card bg-light border-0 p-3 mb-3 <?= ($esAdmin || $esInvitado) ? '' : 'd-none animate__animated animate__fadeIn' ?>">
                                <h6 class="fw-bold text-cenco-indigo mb-3">Dirección de Entrega</h6>

                                <?php if (!$esAdmin && !$esInvitado): ?>
                                    <div class="mb-3">
                                        <label class="fw-bold small mb-1">Nombre de la dirección</label>
                                        <input type="text" name="nueva_alias" id="nueva_alias" class="form-control" placeholder="Ej: Oficina, Casa...">
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label class="fw-bold small mb-1">Comuna de Despacho *</label>
                                    <select name="nueva_comuna_id" id="selectComuna" class="form-select border-cenco-indigo" onchange="centrarMapaEnComuna()">
                                        <option value="">Selecciona comuna...</option>
                                        <?php foreach ($listaComunasFiltrada as $comuna):
                                            $cId = is_object($comuna) ? $comuna->id : $comuna['id'];
                                            $cNombre = is_object($comuna) ? $comuna->nombre : $comuna['nombre'];
                                        ?>
                                            <option value="<?= $cId ?>"><?= htmlspecialchars($cNombre) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="fw-bold small mb-1">Calle y Número *</label>
                                    <div class="input-group shadow-sm">
                                        <input type="text" name="nueva_direccion" id="inputDireccion" class="form-control border-end-0">
                                        <button type="button" class="btn btn-lupa-azul btn-white border border-start-0 text-primary" onclick="buscarEnMapa()">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>

                                <div id="mapa_seleccion" class="bg-white border rounded shadow-sm w-100" style="height: 300px; z-index: 1;"></div>

                                <button type="button" id="btnGuardarDireccion" class="btn btn-success w-100 rounded-pill fw-bold mt-3 shadow-sm" onclick="confirmarDireccionCheckout()">
                                    <i class="bi bi-check2-circle me-1"></i> CONFIRMAR DIRECCIÓN
                                </button>
                            </div>

                            <input type="hidden" name="direccion" id="direccion_final_texto" value="<?= htmlspecialchars($usuario->direccion ?? '') ?>">
                        </div>

                        <div id="bloque_retiro" class="d-none">
                            <div class="mb-3">
                                <label class="fw-bold text-cenco-indigo small mb-1">1. Sucursal de Retiro Asignada</label>
                                <select id="filtro_comuna_retiro" class="form-select form-select-lg bg-light text-dark fw-bold border-cenco-green" disabled>
                                    <option value="<?= $comunaRetiroPredefinida ?>" selected>
                                        <?= $comunaRetiroPredefinida ?> (Catálogo actual)
                                    </option>
                                </select>
                                <div class="form-text text-cenco-indigo mt-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-info-circle-fill"></i> Para respetar las ofertas y stock de tu carrito, el retiro se ha asignado automáticamente a tu sucursal actual.
                                </div>
                            </div>

                            <div id="contenedor-detalle-sucursal" class="d-none">
                                <div class="sucursal-card-detail shadow-sm border border-success border-opacity-25 rounded-4 overflow-hidden">
                                    <div class="row g-0">
                                        <div class="col-md-5 bg-light p-3 d-flex flex-column justify-content-center">
                                            <h6 class="fw-bold text-cenco-indigo mb-3" id="card-sucursal-nombre"><i class="bi bi-shop me-2"></i>...</h6>
                                            <ul class="list-unstyled sucursal-info-list mb-0 small">
                                                <li class="d-flex align-items-start mb-2"><i class="bi bi-geo-alt text-danger me-2 mt-1"></i><span id="card-sucursal-dir" class="fw-bold text-dark">...</span></li>
                                                <li class="d-flex align-items-center mb-2"><i class="bi bi-clock text-success me-2"></i><span id="card-sucursal-horario">...</span></li>
                                                <li class="d-flex align-items-center"><i class="bi bi-telephone text-primary me-2"></i><span id="card-sucursal-fono">...</span></li>
                                            </ul>
                                        </div>
                                        <div class="col-md-7">
                                            <div id="mapa-retiro-inline" style="min-height: 180px; height: 100%;"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-success d-flex align-items-center mt-3 py-2 px-3 rounded-3 shadow-sm border-0">
                                    <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                                    <div><small class="fw-bold">¡Sucursal Confirmada!</small>
                                        <div style="font-size: 0.75rem;">Retiro sin costo.</div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="sucursal_codigo" id="input_sucursal_codigo">
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5 col-12">
            <div class="card shadow rounded-4 border-0 bg-white sticky-top" style="top: 20px; z-index: 10;">
                <div class="card-body p-4">
                    <h5 class="fw-black mb-4 text-cenco-indigo ls-1">Resumen del Pedido</h5>

                    <div class="mb-3 border-bottom pb-3 pe-3 pt-2" style="max-height: 250px; overflow-y: auto; overflow-x: hidden;">
                        <?php if (!empty($_SESSION['carrito'])): foreach ($_SESSION['carrito'] as $item):
                                $img = !empty($item['imagen']) ? (strpos($item['imagen'], 'http') === 0 ? $item['imagen'] : BASE_URL . 'img/productos/' . $item['imagen']) : BASE_URL . 'img/no-image.png';
                        ?>
                                <div class="d-flex align-items-center mb-3 item-carrito-resumen">
                                    <div class="position-relative me-3 mt-1">
                                        <img src="<?= $img ?>" class="rounded border bg-white" style="width: 50px; height: 50px; object-fit: contain;">
                                        <span class="position-absolute badge rounded-pill bg-cenco-indigo border border-2 border-white shadow-sm qty-badge"
                                            style="top: -8px; right: -8px; font-size: 0.75rem; z-index: 10;">
                                            <?= $item['cantidad'] ?>
                                        </span>
                                    </div>

                                    <div class="flex-grow-1 lh-sm ps-1">
                                        <small class="d-block text-dark fw-bold nombre-item-resumen"><?= htmlspecialchars($item['nombre']) ?></small>
                                    </div>
                                    <div class="text-end ps-2">
                                        <small class="fw-bold text-cenco-indigo">$<?= number_format($item['precio'] * $item['cantidad'], 0, ',', '.') ?></small>
                                    </div>
                                </div>
                        <?php endforeach;
                        endif; ?>
                    </div>

                    <div class="bg-light rounded-3 p-3 mb-4">
                        <div class="d-flex justify-content-between mb-2 text-muted small">
                            <span>Subtotal Productos</span>
                            <span>$<?= number_format($totalCarro, 0, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small fw-bold text-cenco-indigo">
                            <span>Costo de Envío <i class="bi bi-truck ms-1"></i></span>
                            <span id="resumen-costo-envio">
                                <?= ($costoEnvio == 0) ? '<span class="text-success">GRATIS</span>' : '$' . number_format($costoEnvio, 0, ',', '.') ?>
                            </span>
                        </div>

                        <div class="d-flex justify-content-between mb-2 small fw-bold text-dark border-bottom pb-2">
                            <span>Costo por Servicio <i class="bi bi-bag-check ms-1"></i></span>
                            <span id="resumen-costo-servicio">$<?= number_format($costoServicioFijo, 0, ',', '.') ?></span>
                        </div>

                        <div class="mt-2 pt-1 d-flex justify-content-between fs-4 fw-black text-cenco-red">
                            <span>Total a Pagar</span>
                            <span id="resumen-total-final">$<?= number_format($totalCarro + $costoEnvio + $costoServicioFijo, 0, ',', '.') ?></span>
                        </div>
                    </div>

                    <div id="bloque-metodos-pago" class="bg-light rounded-3 p-3 mb-4 border border-cenco-green" style="<?= (!$esAdmin && (!isset($usuario->es_cliente_confianza) || $usuario->es_cliente_confianza != 1)) ? 'display:none;' : '' ?>">
                        <h6 class="fw-bold text-cenco-indigo mb-3"><i class="bi bi-wallet2 me-2"></i>Método de Pago</h6>

                        <?php if ($esAdmin): ?>
                            <div id="opcion_pago_tienda">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="metodo_pago" id="pago_tienda" value="pago_tienda" checked form="formCheckout">
                                    <label class="form-check-label small fw-bold text-success" for="pago_tienda">
                                        <i class="bi bi-shop me-1"></i> Pago Presencial en Tienda (Efectivo/Tarjeta)
                                    </label>
                                </div>
                            </div>
                        <?php else: ?>
                            <div id="opciones_pago_normales">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="metodo_pago" id="pago_webpay" value="webpay" checked form="formCheckout">
                                    <label class="form-check-label small fw-bold" for="pago_webpay">
                                        Webpay Plus (Débito/Crédito)
                                    </label>
                                </div>
                                <?php if (isset($usuario->es_cliente_confianza) && $usuario->es_cliente_confianza == 1): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="metodo_pago" id="pago_contra_entrega" value="contra_entrega" form="formCheckout">
                                        <label class="form-check-label small fw-bold text-cenco-green" for="pago_contra_entrega">
                                            Pago Contra Entrega (Transferencia/Transbank al recibir)
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="button" onclick="abrirModalResumen()" class="btn btn-cenco-green w-100 py-3 fw-bold shadow-sm rounded-pill fs-5 transition-hover">
                        REVISAR Y CONFIRMAR <i class="bi bi-check2-circle ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalResumenCompra" tabindex="-1" aria-labelledby="modalResumenLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">

            <div class="modal-header bg-cenco-indigo text-white rounded-top-4 py-3">
                <h5 class="modal-title fw-bold" id="modalResumenLabel"><i class="bi bi-card-checklist me-2"></i>Confirma tu Pedido</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-6 border-end">
                        <h6 class="fw-black text-cenco-indigo border-bottom pb-2 mb-3">Tus Datos</h6>
                        <p class="small mb-1 fw-bold text-dark">
                            <i class="bi bi-person text-secondary me-1"></i> <span id="resumen-nombre"></span>
                        </p>
                        <div class="small mb-4 text-dark" id="resumen-telefono"></div>

                        <h6 class="fw-black text-cenco-indigo border-bottom pb-2 mb-3" id="resumen-titulo-entrega">Datos de Entrega</h6>
                        <div class="small bg-light p-3 rounded-3 border border-light shadow-sm" id="resumen-detalle-entrega"></div>
                    </div>

                    <div class="col-md-6">
                        <h6 class="fw-black text-cenco-indigo border-bottom pb-2 mb-3">Resumen de Compra</h6>
                        <ul class="list-group list-group-flush small mb-3 overflow-auto" id="resumen-lista-productos" style="max-height: 200px;"></ul>

                        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 border border-success border-opacity-25 shadow-sm">
                            <span class="fw-bold text-dark fs-6">Total a Pagar:</span>
                            <span class="text-cenco-red fw-black fs-4" id="resumen-total-monto">$0</span>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning d-flex align-items-center mt-4 mb-0 py-2 border-0 bg-warning bg-opacity-10">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-4"></i>
                    <p class="mb-0 small fw-bold" id="texto-advertencia-pago">
                        Si no hay stock de algún producto, te contactaremos para coordinar.
                    </p>
                </div>
            </div>

            <div class="modal-footer bg-light border-top-0 rounded-bottom-4 py-3 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary fw-bold rounded-pill px-4" data-bs-dismiss="modal">Volver y Editar</button>
                <button type="button"
                    class="btn btn-cenco-green fw-bold rounded-pill px-4 shadow-sm"
                    onclick="document.getElementById('formCheckout').submit();">
                    Estoy de acuerdo y Pagar <i class="bi bi-check-circle-fill ms-2"></i>
                </button>
            </div>

        </div>
    </div>
</div>

<script>
    const CheckoutConfig = {
        baseUrl: '<?= BASE_URL ?>',
        totalCarro: <?= $totalCarro ?>,
        umbralGratis: <?= $umbralEnvioGratis ?>,
        costoDespacho: <?= $costoDespachoFijo ?>,
        costoServicio: <?= $costoServicioFijo ?>,
        umbralAlerta: <?= $umbralAlerta ?>,
        sucursales: <?= json_encode($jsSucursales) ?>
    };

    function gestionarSegundoTelefono() {
        const sel = document.getElementById('selector_segundo_contacto');
        const wrapper = document.getElementById('wrapper_nuevo_segundo_tel');
        if (wrapper) wrapper.classList.toggle('d-none', sel.value !== 'nuevo');

        const saveHidden = document.getElementById('hidden_save_tel2');
        if (saveHidden) saveHidden.value = "0";
        const btn = document.getElementById('btnConfirmarTel');
        if (btn) {
            btn.className = "btn btn-sm btn-outline-cenco-indigo rounded-pill fw-bold px-3 transition-hover";
            btn.innerHTML = "Vincular Contacto";
        }
    }

    function confirmarNuevoTelefono() {
        const num = document.getElementById('telefono_nuevo_2').value;
        if (!num || num.length < 8) {
            Swal.fire('Falta el número', 'Por favor, ingresa un número de teléfono válido.', 'warning');
            return;
        }
        document.getElementById('hidden_save_tel2').value = "1";
        const btn = document.getElementById('btnConfirmarTel');
        btn.className = "btn btn-sm btn-cenco-green rounded-pill fw-bold px-3 text-white shadow-sm";
        btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> ¡Vinculado!';
    }

    function formatearRut(input) {
        let valor = input.value.replace(/[^0-9kK]/g, "");
        if (valor.length > 1) {
            let cuerpo = valor.slice(0, -1);
            let dv = valor.slice(-1).toUpperCase();
            input.value = cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, ".") + "-" + dv;
        } else {
            input.value = valor;
        }
    }

    function abrirModalResumen() {
        const tipoEntrega = document.querySelector('input[name="tipo_entrega"]:checked').value;
        const esAsistida = <?= $esAdmin ? 'true' : 'false' ?>;
        const esInvitado = <?= $esInvitado ? 'true' : 'false' ?>;

        if (tipoEntrega == 2) {
            const sucVal = document.getElementById('input_sucursal_codigo').value;
            if (!sucVal) {
                Swal.fire('Falta información', 'Por favor, selecciona en qué sucursal deseas retirar tu pedido.', 'warning');
                return;
            }
        } else {
            if (esAsistida || esInvitado) {
                const calle = document.getElementById('inputDireccion').value;
                const selectComuna = document.getElementById('selectComuna');
                if (!calle || selectComuna.value === '') {
                    Swal.fire('Faltan Datos de Despacho', 'Debes buscar en el mapa la calle e indicar la comuna para el despacho.', 'warning');
                    return;
                }
                const comunaText = selectComuna.options[selectComuna.selectedIndex].text;
                document.getElementById('direccion_final_texto').value = calle + ', ' + comunaText;
            } else {
                const dirSel = document.getElementById('selector_direccion').value;
                if (dirSel === 'nueva' && document.getElementById('inputDireccion').value.trim() === '') {
                    Swal.fire('Falta información', 'Por favor, ingresa tu calle y número o busca en el mapa.', 'warning');
                    return;
                }
                if (dirSel === 'nueva' && document.getElementById('selectComuna').value === '') {
                    Swal.fire('Falta información', 'Por favor, selecciona una comuna de despacho válida.', 'warning');
                    return;
                }
            }
        }

        let nombreClienteSeguro = <?= json_encode($usuario->nombre ?? 'Invitado') ?>;

        if (esAsistida) {
            const pNombre = document.getElementById('asistido_p_nombre').value.trim();
            const pApellido = document.getElementById('asistido_p_apellido').value.trim();
            const rutAsistido = document.getElementById('asistido_rut').value.trim();
            const telAsistido = document.getElementById('asistido_telefono').value.trim();

            if (!rutAsistido || !pNombre || !pApellido || !telAsistido) {
                Swal.fire('Faltan Datos Personales', 'Por favor completa el RUT, Teléfono, Primer Nombre y Primer Apellido del cliente presencial.', 'warning');
                return;
            }
            nombreClienteSeguro = (pNombre + ' ' + pApellido).trim();
        }

        const resumenNombreEl = document.getElementById('resumen-nombre');
        if (resumenNombreEl) {
            resumenNombreEl.innerText = nombreClienteSeguro;
        }

        // Lógica visual del teléfono
        let telPrincipalVisual = '';
        if (esAsistida) {
             telPrincipalVisual = document.getElementById('asistido_telefono').value;
        } else if (esInvitado) {
             telPrincipalVisual = document.querySelector('input[name="telefono_contacto"]').value;
        } else {
             telPrincipalVisual = '<?= htmlspecialchars($usuario->telefono_principal ?? '') ?>';
        }
        
        let htmlTelefonos = `<div class="mb-1"><i class="bi bi-telephone text-success me-1"></i> <strong>Contacto:</strong> +569${telPrincipalVisual.replace('+569','')}</div>`;

        if (!esAsistida && !esInvitado) {
            const segSel = document.getElementById('selector_segundo_contacto');
            if (segSel && segSel.value) {
                if (segSel.value === 'nuevo') {
                    let guardado = document.getElementById('hidden_save_tel2').value;
                    let telNuevo = document.getElementById('telefono_nuevo_2').value;

                    if (telNuevo && guardado === "1") {
                        htmlTelefonos += `<div><i class="bi bi-telephone-plus text-primary me-1"></i> <strong>Alternativo:</strong> ${telNuevo}</div>`;
                    } else if (telNuevo && guardado === "0") {
                        Swal.fire('¡Falta un paso!', 'Escribiste un nuevo número pero olvidaste presionar el botón "Vincular Contacto".', 'info');
                        return;
                    }
                } else {
                    htmlTelefonos += `<div><i class="bi bi-telephone-plus text-primary me-1"></i> <strong>Alternativo:</strong> ${segSel.value}</div>`;
                }
            }
        }
        
        if (esInvitado && !document.querySelector('input[name="telefono_contacto"]').value) {
            Swal.fire('Teléfono Obligatorio', 'Por favor, ingresa tu número telefónico para poder contactarte por el despacho.', 'warning');
            return;
        }

        document.getElementById('resumen-telefono').innerHTML = htmlTelefonos;

        const tituloEntrega = document.getElementById('resumen-titulo-entrega');
        const detalleEntrega = document.getElementById('resumen-detalle-entrega');

        if (tipoEntrega == '1') {
            tituloEntrega.innerHTML = '<i class="bi bi-house-door me-2"></i>Despacho a Domicilio';
            let direccionEscogida = '';

            if (esAsistida || esInvitado) {
                direccionEscogida = document.getElementById('direccion_final_texto').value;
            } else {
                let dirSelect = document.getElementById('selector_direccion');
                direccionEscogida = (dirSelect.value === 'nueva') ?
                    document.getElementById('inputDireccion').value :
                    dirSelect.options[dirSelect.selectedIndex].getAttribute('data-direccion');
            }

            let now = new Date();
            let procDate = new Date(now);

            if (esAsistida) {
                // LA REGLA DE LA VENTA ASISTIDA HASTA LAS 14:00 HRS
                let entregaStr = '';
                if (now.getHours() < 14) {
                    procDate.setHours(procDate.getHours() + 3);
                    entregaStr = `Hoy a las ${procDate.getHours().toString().padStart(2, '0')}:${procDate.getMinutes().toString().padStart(2, '0')} hrs.`;
                } else {
                    procDate.setDate(procDate.getDate() + 1);
                    while (procDate.getDay() === 6 || procDate.getDay() === 0) {
                        procDate.setDate(procDate.getDate() + 1);
                    }
                    const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                    entregaStr = `El ${dias[procDate.getDay()]} ${procDate.getDate()} de ${meses[procDate.getMonth()]}, durante la mañana.`;
                }

                detalleEntrega.innerHTML = `
                    <div class="mb-2"><strong><i class="bi bi-geo-alt-fill text-danger me-1"></i> Dirección:</strong><br><span class="ms-4 text-muted">${direccionEscogida}</span></div>
                    <div><strong><i class="bi bi-truck text-success me-1"></i> Envío Express:</strong><br><span class="ms-4 badge bg-success text-white px-2 py-1 mt-1">${entregaStr}</span></div>
                `;
            } else {
                if (procDate.getDay() === 6 || procDate.getDay() === 0 || procDate.getHours() >= 15) {
                    procDate.setDate(procDate.getDate() + 1);
                    while (procDate.getDay() === 6 || procDate.getDay() === 0) {
                        procDate.setDate(procDate.getDate() + 1);
                    }
                }

                let deliveryDate = new Date(procDate);
                let daysToAdd = 2;
                while (daysToAdd > 0) {
                    deliveryDate.setDate(deliveryDate.getDate() + 1);
                    if (deliveryDate.getDay() !== 6 && deliveryDate.getDay() !== 0) {
                        daysToAdd--;
                    }
                }

                const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                let entregaStr = `El ${dias[deliveryDate.getDay()]} ${deliveryDate.getDate()} de ${meses[deliveryDate.getMonth()]}, entre 09:00 y 19:00 hrs.`;

                detalleEntrega.innerHTML = `
                    <div class="mb-2"><strong><i class="bi bi-geo-alt-fill text-danger me-1"></i> Dirección:</strong><br><span class="ms-4 text-muted">${direccionEscogida}</span></div>
                    <div><strong><i class="bi bi-truck text-success me-1"></i> Llega:</strong><br><span class="ms-4 badge bg-success text-white px-2 py-1 mt-1">${entregaStr}</span></div>
                `;
            }

        } else {
            tituloEntrega.innerHTML = '<i class="bi bi-shop me-2"></i>Retiro en Tienda';
            let sucNombre = document.getElementById('card-sucursal-nombre').innerText || 'Sucursal Asignada';
            let sucDir = document.getElementById('card-sucursal-dir').innerText || '';

            let htmlInfo = `<div class="mb-1"><strong>${sucNombre}</strong></div>
                            <div class="small text-muted mb-1"><i class="bi bi-geo-alt me-1"></i> ${sucDir}</div>`;

            if (esAsistida) {
                htmlInfo += `<div class="alert alert-info py-2 mt-3 mb-0 border-0" style="font-size: 0.75rem;">
                                <i class="bi bi-check-circle-fill me-1"></i> Listo para entrega inmediata en mostrador.
                            </div>`;
            } else {
                const ahora = new Date();
                let horaBase = ahora.getHours();
                if (ahora.getMinutes() >= 30) horaBase = horaBase + 1;
                let horaCalculada = horaBase + 3;
                if (horaCalculada >= 24) horaCalculada = horaCalculada - 24;
                const horaRetiroFormateada = horaCalculada.toString().padStart(2, '0') + ':00';

                htmlInfo += `<div class="alert alert-info py-2 mt-3 mb-0 border-0" style="font-size: 0.75rem;">
                                <i class="bi bi-clock-history me-1"></i> Tu pedido estará listo para retiro a partir de las <strong>${horaRetiroFormateada} hrs</strong>.
                            </div>`;
            }
            detalleEntrega.innerHTML = htmlInfo;
        }

        const listaUl = document.getElementById('resumen-lista-productos');
        listaUl.innerHTML = '';
        document.querySelectorAll('.item-carrito-resumen').forEach(el => {
            let nombre = el.querySelector('.nombre-item-resumen').innerText;
            let qty = el.querySelector('.qty-badge').innerText;
            listaUl.innerHTML += `
                <li class="list-group-item px-2 py-2 d-flex justify-content-between align-items-center bg-transparent border-light">
                    <span class="text-truncate me-3" style="max-width: 80%;">${nombre}</span>
                    <span class="badge bg-secondary rounded-pill">x${qty}</span>
                </li>
            `;
        });

        // ==========================================
        // TEXTO DINÁMICO DE ADVERTENCIA DE PAGO
        // ==========================================
        const alertaPagoTexto = document.getElementById('texto-advertencia-pago');
        if (alertaPagoTexto) {
            if (esAsistida) {
                // Si es Admin, mostramos la instrucción para la cajera
                let verboLogistica = (tipoEntrega == '1') ? 'despachar al domicilio' : 'entregar en mostrador';
                alertaPagoTexto.innerHTML = `Recuerda que debes verificar el pago presencial del cliente adjuntando el comprobante en el gestor de pedido para poder preparar y <strong>${verboLogistica}</strong>.`;
            } else {
                // Si es cliente normal, revisamos si paga contra entrega o webpay
                const metodoPagoSel = document.querySelector('input[name="metodo_pago"]:checked');
                if (metodoPagoSel && metodoPagoSel.value === 'contra_entrega') {
                    alertaPagoTexto.innerHTML = `Recuerda que deberás realizar el pago (Efectivo, Transferencia o Transbank) al momento de recibir tus productos. <br><span class="fw-normal">Si no hay stock de algún producto, te contactaremos.</span>`;
                } else {
                    alertaPagoTexto.innerHTML = `Si no hay stock de algún producto, te contactaremos para coordinar.`;
                }
            }
        }

        // INTEGRAR COSTO DE SERVICIO Y DESPACHO AL FINAL DE LA LISTA
        let costoEnvioMonto = (tipoEntrega == '1' && CheckoutConfig.totalCarro < CheckoutConfig.umbralGratis) ? CheckoutConfig.costoDespacho : 0;
        let txtEnvio = costoEnvioMonto === 0 ? '<span class="text-success fw-bold">GRATIS</span>' : '+$' + new Intl.NumberFormat('es-CL').format(costoEnvioMonto);
        let txtServicio = '+$' + new Intl.NumberFormat('es-CL').format(CheckoutConfig.costoServicio);

        listaUl.innerHTML += `
            <li class="list-group-item px-2 py-2 d-flex justify-content-between align-items-center bg-light border-top border-light mt-1">
                <span class="text-muted small"><i class="bi bi-bag-check me-1"></i> Costo por Servicio</span>
                <span class="fw-bold text-dark small">${txtServicio}</span>
            </li>
            <li class="list-group-item px-2 py-2 d-flex justify-content-between align-items-center bg-light border-light mb-2">
                <span class="text-muted small"><i class="bi bi-truck me-1"></i> Despacho</span>
                <span class="fw-bold text-dark small">${txtEnvio}</span>
            </li>
        `;

        document.getElementById('resumen-total-monto').innerText = document.getElementById('resumen-total-final').innerText;

        // ==========================================
        // MENSAJE DE ADVERTENCIA DINÁMICO
        // ==========================================
        const alertaWarningText = document.querySelector('#modalResumenCompra .alert-warning p');
        if (alertaWarningText) {
            if (esAsistida) {
                // Mensaje adaptado para el Administrador
                let accionLogistica = (tipoEntrega == '1') ? 'despachar al domicilio' : 'entregar en la sucursal';
                alertaWarningText.innerHTML = `<strong>Modo Venta Asistida:</strong> Recuerda verificar el pago del cliente (Efectivo o Transbank) y subir el comprobante antes de <strong>${accionLogistica}</strong> los productos.`;
            } else {
                // Lógica normal para el cliente Web
                const metodoPagoSel = document.querySelector('input[name="metodo_pago"]:checked');
                if (metodoPagoSel && metodoPagoSel.value === 'contra_entrega') {
                    alertaWarningText.innerHTML = `Recuerda que deberás realizar el pago (Efectivo, Transferencia o Transbank) al momento de recibir tus productos. <br><span class="fw-normal text-muted">Si no hay stock de algún producto, te contactaremos.</span>`;
                } else {
                    alertaWarningText.innerHTML = `Si no hay stock de algún producto, te contactaremos para coordinar.`;
                }
            }
        }
        new bootstrap.Modal(document.getElementById('modalResumenCompra')).show();
    }

    document.addEventListener("DOMContentLoaded", function() {
        const selectRetiro = document.getElementById('filtro_comuna_retiro');
        if (selectRetiro && typeof gestionarRetiroAutomatico === 'function') {
            gestionarRetiroAutomatico();
        }
    });
</script>

<script src="<?= BASE_URL ?>js/checkout.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const inputAlias = document.querySelector('input[name="nuevo_alias_2"]');
        if (inputAlias) {
            inputAlias.addEventListener('input', function(e) {
                let palabras = e.target.value.toLowerCase().split(' ');
                for (let i = 0; i < palabras.length; i++) {
                    if (palabras[i].length > 0) {
                        palabras[i] = palabras[i][0].toUpperCase() + palabras[i].substring(1);
                    }
                }
                e.target.value = palabras.join(' ');
            });
        }

        const inputTelN = document.querySelector('input[name="telefono_nuevo_2"]');
        if (inputTelN) {
            inputTelN.setAttribute("placeholder", "Ej: 98765432");
            inputTelN.addEventListener('input', function(e) {
                let val = e.target.value.replace(/\D/g, '');
                if (val.length > 8) val = val.substring(0, 8);
                e.target.value = val;
            });
        }
    });

    function confirmarDireccionCheckout() {
        const esInvitado = <?= empty($_SESSION['user_id']) ? 'true' : 'false' ?>;
        const esAdmin = <?= $esAdmin ? 'true' : 'false' ?>;

        if (esInvitado || esAdmin) {
            const calle = document.getElementById('inputDireccion').value;
            const selectComuna = document.getElementById('selectComuna');
            const comunaText = selectComuna.options[selectComuna.selectedIndex].text;

            if (!calle || selectComuna.value === '') {
                Swal.fire('Faltan Datos', 'Ingresa la calle y selecciona una comuna para el despacho.', 'warning');
                return;
            }

            document.getElementById('direccion_final_texto').value = calle + ', ' + comunaText;

            Swal.fire({
                icon: 'success',
                title: '¡Dirección Confirmada!',
                text: 'La dirección de despacho ha sido agregada a este pedido.',
                confirmButtonColor: '#76C043'
            });
        } else {
            if (typeof guardarDireccionAjax === 'function') {
                guardarDireccionAjax();
            }
        }
    }

    function capitalizarLetras(input) {
        let palabras = input.value.split(' ');
        for (let i = 0; i < palabras.length; i++) {
            if (palabras[i].length > 0) {
                palabras[i] = palabras[i][0].toUpperCase() + palabras[i].substring(1).toLowerCase();
            }
        }
        input.value = palabras.join(' ');
    }
    
    // --- PARCHE DE INICIALIZACIÓN PARA ADMIN E INVITADOS ---
    document.addEventListener("DOMContentLoaded", function() {
        const esAdmin = <?= $esAdmin ? 'true' : 'false' ?>;
        const esInvitado = <?= $esInvitado ? 'true' : 'false' ?>;

        if (esAdmin || esInvitado) {
            // Aseguramos que el valor sea "nueva" para que el JS no se pierda
            const selectorDir = document.getElementById('selector_direccion');
            if (selectorDir) selectorDir.value = "nueva";

            setTimeout(function() {
                try {
                    // 1. Forzar redibujado de Leaflet para evitar el cuadro gris
                    let elMapa = null;
                    if (typeof mapaSeleccion !== 'undefined' && mapaSeleccion !== null) elMapa = mapaSeleccion;
                    else if (typeof map !== 'undefined' && map !== null) elMapa = map;

                    if (elMapa) {
                        elMapa.invalidateSize();
                    }

                    // 2. Ejecutar funciones de carga inicial
                    if (typeof gestionarNuevaDireccion === 'function') {
                        gestionarNuevaDireccion();
                    }
                    if (typeof centrarMapaEnComuna === 'function') {
                        centrarMapaEnComuna();
                    }

                } catch (e) {
                    console.warn("Aviso: Error menor en inicialización del mapa:", e.message);
                }
            }, 800); // 800ms es el tiempo seguro para que cargue el CSS
        }
    });
</script>