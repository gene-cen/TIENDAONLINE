<?php
// views/shop/checkout.php

// 1. CONFIGURACIÓN
$umbralEnvioGratis = 39950;
$umbralAlerta = 30000;
$costoDespachoFijo = 2990;
$costoServicioFijo = 490; 

$esAdmin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin');
$esInvitado = !isset($_SESSION['user_id']);

$sucursalActivaID = $_SESSION['sucursal_activa'] ?? 29;
$comunaRetiroPredefinida = ($sucursalActivaID == 10) ? 'Villa Alemana' : 'La Calera';

$listaComunasFiltrada = array_filter($listaComunas ?? [], function ($c) use ($sucursalActivaID) {
    $sId = is_object($c) ? ($c->sucursal_id ?? null) : ($c['sucursal_id'] ?? null);
    return $sId == $sucursalActivaID;
});

$totalCarro = 0;
if (!empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) { $totalCarro += $item['precio'] * $item['cantidad']; }
}

$costoEnvio = ($totalCarro >= $umbralEnvioGratis) ? 0 : $costoDespachoFijo;
$faltaParaGratis = $umbralEnvioGratis - $totalCarro;
$mostrarAlerta = ($totalCarro >= $umbralAlerta && $totalCarro < $umbralEnvioGratis);

$jsSucursales = [];
foreach ($sucursales as $s) {
    $jsSucursales[] = [
        'codigo' => $s['codigo_erp'], 'nombre' => $s['nombre'], 'direccion' => $s['direccion'],
        'comuna' => $s['nombre_comuna'] ?? 'Sin Comuna', 'lat' => !empty($s['latitud']) ? floatval($s['latitud']) : 0,
        'lng' => !empty($s['longitud']) ? floatval($s['longitud']) : 0, 'horario' => $s['horario'] ?? '09:00 - 19:00',
        'fono' => $s['fono'] ?? ''
    ];
}
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

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
                                <div><strong class="text-primary d-block fs-6">MODO VENTA ASISTIDA</strong><small class="text-dark">Estás registrando una compra presencial.</small></div>
                            </div>
                            <div id="seccion_asistida" class="animate__animated animate__fadeIn mb-4">
                                <div class="card border-primary border-2 bg-white rounded-4 p-4 shadow-sm">
                                    <h5 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-person-vcard me-2"></i>Datos del Cliente</h5>
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="small fw-bold text-dark">RUT Cliente *</label><input type="text" name="asistido_rut" id="asistido_rut" class="form-control border-primary" placeholder="12.345.678-9" maxlength="12" oninput="formatearRut(this)" onblur="verificarRutCliente(this.value)"></div>
                                        <div class="col-md-6"><label class="small fw-bold text-dark">Teléfono Contacto *</label><div class="input-group"><span class="input-group-text bg-light border-primary">+569</span><input type="tel" name="asistido_telefono" id="asistido_telefono" class="form-control border-primary" maxlength="8" oninput="this.value = this.value.replace(/[^0-9]/g, '')"></div></div>
                                        <div class="col-md-6"><label class="small fw-bold text-dark">Primer Nombre *</label><input type="text" name="asistido_p_nombre" id="asistido_p_nombre" class="form-control border-primary" oninput="capitalizarLetras(this)"></div>
                                        <div class="col-md-6"><label class="small fw-bold text-dark">Segundo Nombre</label><input type="text" name="asistido_s_nombre" class="form-control border-primary" oninput="capitalizarLetras(this)"></div>
                                        <div class="col-md-6"><label class="small fw-bold text-dark">Primer Apellido *</label><input type="text" name="asistido_p_apellido" id="asistido_p_apellido" class="form-control border-primary" oninput="capitalizarLetras(this)"></div>
                                        <div class="col-md-6"><label class="small fw-bold text-dark">Segundo Apellido</label><input type="text" name="asistido_s_apellido" class="form-control border-primary" oninput="capitalizarLetras(this)"></div>
                                        <div class="col-md-12"><label class="small fw-bold text-dark">Correo Electrónico (Opcional)</label><input type="email" name="asistido_email" id="asistido_email" class="form-control border-primary" placeholder="cliente@correo.com"></div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div id="datos_cliente_normal">
                                <div class="row mb-3">
                                    <div class="col-md-6"><label class="fw-bold text-muted small mb-1">Cliente</label><div class="p-2 bg-light rounded border small fw-bold text-truncate"><?= htmlspecialchars($usuario->nombre ?? 'Invitado') ?></div></div>
                                    <div class="col-md-6"><label class="fw-bold text-muted small mb-1">RUT</label><div class="p-2 bg-light rounded border small fw-bold"><?= htmlspecialchars($usuario->rut ?? 'No Registrado') ?></div></div>
                                </div>
                                <div class="mb-4">
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="fw-bold text-muted small mb-1">Teléfono Titular</label><div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-person-check text-success"></i></span><input type="text" name="telefono_contacto" class="form-control bg-light" value="<?= htmlspecialchars($usuario->telefono_principal ?? '') ?>" <?= $esInvitado ? 'placeholder="Ej: 98765432" required' : 'readonly' ?>></div></div>
                                        <?php if (!$esInvitado): ?>
                                            <div class="col-md-6">
                                                <label class="fw-bold text-cenco-indigo small mb-1">Segundo Contacto</label>
                                                <select class="form-select shadow-sm" id="selector_segundo_contacto" name="telefono_seleccionado_2" onchange="gestionarSegundoTelefono()">
                                                    <option value="">-- No indicar otro número --</option>
                                                    <?php foreach ($telefonos ?? [] as $tel): if (!$tel->es_principal): ?>
                                                        <option value="<?= htmlspecialchars($tel->numero) ?>"><?= htmlspecialchars($tel->alias) ?>: <?= htmlspecialchars($tel->numero) ?></option>
                                                    <?php endif; endforeach; ?>
                                                    <option value="nuevo" class="text-primary fw-bold">+ Añadir nuevo número...</option>
                                                </select>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!$esInvitado): ?>
                                        <div id="wrapper_nuevo_segundo_tel" class="d-none mt-3 p-3 bg-light rounded-3 border border-primary border-opacity-25 shadow-sm">
                                            <h6 class="small fw-bold text-cenco-indigo mb-2"><i class="bi bi-telephone-plus me-1"></i> Añadir Nuevo Contacto</h6>
                                            <div class="row g-2">
                                                <div class="col-md-7"><label class="form-label small fw-bold">Número</label><div class="input-group shadow-sm rounded-3 overflow-hidden"><span class="input-group-text bg-white border-0 text-muted fw-bold">+569</span><input type="tel" id="telefono_nuevo_2" name="telefono_nuevo_2" class="form-control bg-white border-0" maxlength="8"></div></div>
                                                <div class="col-md-5"><label class="small text-muted">Alias</label><input type="text" name="nuevo_alias_2" class="form-control form-control-sm" placeholder="Nombre"></div>
                                                <div class="mt-3 d-flex align-items-center"><input type="hidden" name="guardar_nuevo_2" id="hidden_save_tel2" value="0"><button type="button" class="btn btn-sm btn-outline-cenco-indigo rounded-pill fw-bold px-3 transition-hover" id="btnConfirmarTel" onclick="confirmarNuevoTelefono()">Vincular Contacto</button></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <hr class="border-light my-4">

                        <?php $esSoloRetiro = ($sucursalActivaID == 29); ?>
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="tipo_entrega" id="opcion_despacho" value="1" <?= $esSoloRetiro ? 'disabled' : 'checked' ?> onchange="cambiarMetodoEntrega()">
                                <label class="btn btn-outline-cenco-indigo w-100 py-3 d-flex flex-column align-items-center justify-content-center gap-1 h-100 shadow-sm border-2 rounded-3 <?= $esSoloRetiro ? 'opacity-50 bg-light' : '' ?>" for="opcion_despacho" style="min-height: 120px;">
                                    <i class="bi bi-house-door-fill fs-2"></i> <span class="fw-bold">A Domicilio</span>
                                    <?php if ($esSoloRetiro): ?>
                                        <span class="badge bg-danger mt-2 text-wrap shadow-sm"><i class="bi bi-cone-striped me-1"></i>No disponible</span>
                                    <?php else: ?>
                                        <span class="small text-muted"><?= ($totalCarro >= $umbralEnvioGratis) ? '<span class="text-success fw-bold">¡GRATIS!</span>' : '$' . number_format($costoDespachoFijo, 0, ',', '.') ?></span>
                                        <span class="badge bg-light text-dark border mt-1"><i class="bi bi-clock text-primary"></i> 09:00 a 19:00 hrs</span>
                                    <?php endif; ?>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="tipo_entrega" id="opcion_retiro" value="2" <?= $esSoloRetiro ? 'checked' : '' ?> onchange="cambiarMetodoEntrega()">
                                <label class="btn btn-outline-cenco-indigo w-100 py-3 d-flex flex-column align-items-center justify-content-center gap-2 h-100 shadow-sm border-2 position-relative rounded-3" for="opcion_retiro" style="min-height: 120px;">
                                    <span class="badge-gratis">¡GRATIS!</span> <i class="bi bi-shop fs-2"></i> <span class="fw-bold">Retiro en Tienda</span> <span class="small text-muted">$0</span>
                                </label>
                            </div>
                        </div>

                        <div id="alerta-envio-gratis" class="mb-4 <?= $mostrarAlerta ? '' : 'd-none' ?>">
                            <div class="shipping-alert animate__animated animate__pulse">
                                <i class="bi bi-piggy-bank-fill fs-3 text-warning"></i>
                                <div><strong>¡Estás muy cerca!</strong><div class="small">Agrega <span class="fw-bold text-dark">$<?= number_format($faltaParaGratis, 0, ',', '.') ?></span> más para envío gratis.</div></div>
                            </div>
                        </div>

                        <div id="bloque_despacho">
                            <div class="alert alert-primary bg-primary bg-opacity-10 border-0 shadow-sm d-flex align-items-center mb-4 p-3 rounded-4">
                                <i class="bi bi-info-circle-fill text-primary fs-2 me-3"></i>
                                <div><h6 class="fw-bold text-primary mb-1">Condiciones de Entrega</h6><p class="mb-0 text-dark small">Entregas de Lunes a Viernes entre 09:00 y 19:00 hrs.</p></div>
                            </div>

                            <div class="mb-3" style="<?= ($esAdmin || $esInvitado) ? 'display:none;' : '' ?>">
                                <label class="fw-bold text-cenco-indigo small mb-1">Dirección de Envío</label>
                                <select class="form-select mb-3" id="selector_direccion" name="origen_direccion" onchange="gestionarNuevaDireccion()">
                                    <?php if ($esAdmin || $esInvitado): ?>
                                        <option value="nueva" data-lat="-32.9238" data-lng="-71.5176" selected>Nueva Dirección</option>
                                    <?php else: ?>
                                        <option value="perfil" data-direccion="<?= htmlspecialchars($usuario->direccion ?? '') ?>" data-lat="-32.7833" data-lng="-71.2000" selected>Principal: <?= htmlspecialchars($usuario->direccion ?? '') ?></option>
                                        <?php foreach ($direcciones ?? [] as $dir): ?>
                                            <option value="<?= $dir->id ?>" data-direccion="<?= htmlspecialchars($dir->direccion) ?>" data-lat="<?= $dir->latitud ?? -32.7833 ?>" data-lng="<?= $dir->longitud ?? -71.2000 ?>">
                                                <?= htmlspecialchars($dir->nombre_etiqueta) ?>: <?= htmlspecialchars($dir->direccion) ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="nueva" class="text-primary fw-bold">+ Nueva Dirección...</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div id="contenedor-detalle-direccion" class="mb-3" style="<?= ($esAdmin || $esInvitado) ? 'display:none;' : '' ?>">
                                <div class="sucursal-card-detail shadow-sm">
                                    <div class="row g-0">
                                        <div class="col-md-5 bg-light p-3"><h6 class="fw-bold text-cenco-indigo mb-3" id="card-dir-alias">...</h6><span id="card-dir-texto" class="fw-bold text-dark small">...</span></div>
                                        <div class="col-md-7"><div id="mapa-direccion-guardada"></div></div>
                                    </div>
                                </div>
                            </div>

                            <div id="form_nueva_direccion" class="card bg-light border-0 p-3 mb-3 <?= ($esAdmin || $esInvitado) ? '' : 'd-none animate__animated animate__fadeIn' ?>">
                                <h6 class="fw-bold text-cenco-indigo mb-3">Dirección de Entrega</h6>
                                <?php if (!$esAdmin && !$esInvitado): ?>
                                    <div class="mb-3"><label class="fw-bold small">Nombre (Ej: Casa)</label><input type="text" name="nueva_alias" id="nueva_alias" class="form-control"></div>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label class="fw-bold small">Comuna *</label>
                                    <select name="nueva_comuna_id" id="selectComuna" class="form-select border-cenco-indigo" onchange="centrarMapaEnComuna()">
                                        <option value="">Selecciona comuna...</option>
                                        <?php foreach ($listaComunasFiltrada as $c) echo '<option value="' . (is_object($c)?$c->id:$c['id']) . '">' . htmlspecialchars(is_object($c)?$c->nombre:$c['nombre']) . '</option>'; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold small">Calle y Número *</label>
                                    <div class="input-group shadow-sm">
                                        <input type="text" name="nueva_direccion" id="inputDireccion" class="form-control border-end-0">
                                        <button type="button" class="btn border border-start-0 text-primary" onclick="buscarEnMapa()"><i class="bi bi-search"></i></button>
                                    </div>
                                </div>
                                <div id="mapa_seleccion" class="bg-white border rounded shadow-sm w-100" style="height: 300px; z-index: 1;"></div>
                                <button type="button" class="btn btn-success w-100 rounded-pill fw-bold mt-3 shadow-sm" onclick="confirmarDireccionCheckout()"><i class="bi bi-check2-circle"></i> CONFIRMAR</button>
                            </div>
                            <input type="hidden" name="direccion" id="direccion_final_texto" value="<?= htmlspecialchars($usuario->direccion ?? '') ?>">
                        </div>

                       <div id="bloque_retiro" class="d-none">
                            <div class="mb-3">
                                <label class="fw-bold text-cenco-indigo small mb-1">Sucursal de Retiro</label>
                                <select id="filtro_comuna_retiro" class="form-select border-cenco-green" disabled><option selected><?= $comunaRetiroPredefinida ?></option></select>
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
                                        <div class="col-md-7"><div id="mapa-retiro-inline" style="height: 180px;"></div></div>
                                    </div>
                                </div>
                                <div class="alert alert-success d-flex align-items-center mt-3 py-2 px-3 rounded-3 shadow-sm border-0"><i class="bi bi-check-circle-fill fs-4 me-2"></i><div><small class="fw-bold">¡Confirmada!</small><div style="font-size:0.75rem;">Retiro sin costo.</div></div></div>
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

                    <div class="mb-3 border-bottom pb-3 pe-3 pt-2" style="max-height: 250px; overflow-y: auto;">
                        <?php if (!empty($_SESSION['carrito'])): foreach ($_SESSION['carrito'] as $item): $img = !empty($item['imagen']) ? (strpos($item['imagen'], 'http')===0?$item['imagen']:BASE_URL.'img/productos/'.$item['imagen']) : BASE_URL.'img/no-image.png'; ?>
                            <div class="d-flex align-items-center mb-3 item-carrito-resumen">
                                <div class="position-relative me-3 mt-1">
                                    <img src="<?= $img ?>" class="rounded border bg-white" style="width: 50px; height: 50px; object-fit: contain;">
                                    <span class="position-absolute badge rounded-pill bg-cenco-indigo shadow-sm qty-badge" style="top:-8px; right:-8px; font-size:0.75rem; z-index:10; border:2px solid white;"><?= $item['cantidad'] ?></span>
                                </div>
                                <div class="flex-grow-1 lh-sm ps-1"><small class="d-block text-dark fw-bold nombre-item-resumen"><?= htmlspecialchars($item['nombre']) ?></small></div>
                                <div class="text-end ps-2"><small class="fw-bold text-cenco-indigo">$<?= number_format($item['precio'] * $item['cantidad'], 0, ',', '.') ?></small></div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>

                    <div class="bg-light rounded-3 p-3 mb-4">
                        <div class="d-flex justify-content-between mb-2 text-muted small"><span>Subtotal Productos</span><span>$<?= number_format($totalCarro, 0, ',', '.') ?></span></div>
                        <div class="d-flex justify-content-between mb-2 small fw-bold text-cenco-indigo"><span>Costo de Envío</span><span id="resumen-costo-envio"><?= ($costoEnvio == 0) ? '<span class="text-success">GRATIS</span>' : '$' . number_format($costoEnvio, 0, ',', '.') ?></span></div>
                        <div class="d-flex justify-content-between mb-2 small fw-bold text-dark border-bottom pb-2"><span>Costo Servicio</span><span id="resumen-costo-servicio">$<?= number_format($costoServicioFijo, 0, ',', '.') ?></span></div>
                        <div class="mt-2 pt-1 d-flex justify-content-between fs-4 fw-black text-cenco-red"><span>Total a Pagar</span><span id="resumen-total-final">$<?= number_format($totalCarro + $costoEnvio + $costoServicioFijo, 0, ',', '.') ?></span></div>
                    </div>

                    <div id="bloque-metodos-pago" class="bg-light rounded-3 p-3 mb-4 border border-cenco-green" style="<?= (!$esAdmin && (!isset($usuario->es_cliente_confianza) || $usuario->es_cliente_confianza != 1)) ? 'display:none;' : '' ?>">
                        <h6 class="fw-bold text-cenco-indigo mb-3"><i class="bi bi-wallet2 me-2"></i>Método de Pago</h6>
                        <?php if ($esAdmin): ?>
                            <div id="opciones_pago_admin">
                                <div class="form-check mb-2"><input class="form-check-input" type="radio" name="metodo_pago" id="pago_tienda" value="pago_tienda" checked form="formCheckout" onchange="gestionarFolio()"><label class="form-check-label small fw-bold" for="pago_tienda">Pago Presencial en Tienda</label></div>
                                <div id="opcion_credito_confianza" class="form-check d-none mt-2 p-2 bg-success-subtle border border-success rounded-3"><input class="form-check-input ms-1 border-success" type="radio" name="metodo_pago" id="pago_credito" value="credito_confianza" form="formCheckout" onchange="gestionarFolio()"><label class="form-check-label small fw-bold text-success ms-2" for="pago_credito">Pago con Crédito</label></div>
                            </div>
                        <?php else: ?>
                            <div id="opciones_pago_normales">
                                <div class="form-check mb-2"><input class="form-check-input" type="radio" name="metodo_pago" id="pago_webpay" value="webpay" checked form="formCheckout"><label class="form-check-label small fw-bold" for="pago_webpay">Webpay Plus</label></div>
                                <?php if (isset($usuario->es_cliente_confianza) && $usuario->es_cliente_confianza == 1): ?>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="metodo_pago" id="pago_contra_entrega" value="contra_entrega" form="formCheckout"><label class="form-check-label small fw-bold text-cenco-green" for="pago_contra_entrega">Pago Contra Entrega</label></div>
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

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Variables Globales INYECTADAS para el archivo externo checkout.js
    window.CheckoutConfig = {
        baseUrl: '<?= BASE_URL ?>',
        totalCarro: <?= $totalCarro ?>,
        umbralGratis: <?= $umbralEnvioGratis ?>,
        costoDespacho: <?= $costoDespachoFijo ?>,
        costoServicio: <?= $costoServicioFijo ?>,
        esAdmin: <?= $esAdmin ? 'true' : 'false' ?>,
        esInvitado: <?= $esInvitado ? 'true' : 'false' ?>,
        sucursales: <?= json_encode($jsSucursales) ?>,
        nombreCliente: <?= json_encode($usuario->nombre ?? 'Invitado') ?>,
        telefonoPrincipal: <?= json_encode($usuario->telefono_principal ?? '') ?>
    };
</script>
<script src="<?= BASE_URL ?>js/shop/checkout.js?v=<?= time() ?>"></script>