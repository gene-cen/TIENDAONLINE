<?php
// =================================================================================
// 1. CONFIGURACIÓN DE REGLAS DE NEGOCIO (PHP)
// =================================================================================
$umbralEnvioGratis = 49950;
$umbralAlerta = 40000;
$costoDespachoFijo = 1990;

$totalCarro = 0;
if (!empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $totalCarro += $item['precio'] * $item['cantidad'];
    }
}

$costoEnvio = ($totalCarro >= $umbralEnvioGratis) ? 0 : $costoDespachoFijo;
$faltaParaGratis = $umbralEnvioGratis - $totalCarro;
$mostrarAlerta = ($totalCarro >= $umbralAlerta && $totalCarro < $umbralEnvioGratis);

// Preparar Data JS
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

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="fw-bold text-muted small mb-1">Cliente</label>
                                <div class="p-2 bg-light rounded border small fw-bold text-truncate"><?= htmlspecialchars($usuario->nombre) ?></div>
                            </div>
                            <div class="col-md-6 mt-2 mt-md-0">
                                <label class="fw-bold text-muted small mb-1">RUT</label>
                                <div class="p-2 bg-light rounded border small fw-bold">
                                    <?= htmlspecialchars($usuario->rut ?? '') ?>
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="fw-bold text-muted small mb-1">Teléfono Titular (Obligatorio)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bi bi-person-check text-success"></i></span>
                                            <input type="text" name="telefono_contacto" class="form-control bg-light"
                                                value="<?= htmlspecialchars($usuario->telefono_principal) ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-bold text-cenco-indigo small mb-1">Segundo Contacto (Opcional)</label>
                                        <select class="form-select shadow-sm" id="selector_segundo_contacto" name="telefono_seleccionado_2" onchange="gestionarSegundoTelefono()">
                                            <option value="">-- No indicar otro número --</option>
                                            <?php foreach ($telefonos as $tel): ?>
                                                <?php if (!$tel->es_principal): ?>
                                                    <option value="<?= htmlspecialchars($tel->numero) ?>">
                                                        <?= htmlspecialchars($tel->alias) ?>: <?= htmlspecialchars($tel->numero) ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                            <option value="nuevo" class="text-primary fw-bold">+ Añadir nuevo número...</option>
                                        </select>
                                    </div>
                                </div>

                                <div id="wrapper_nuevo_segundo_tel" class="d-none mt-3 p-3 bg-light rounded-3 border">
                                    <div class="row g-2">
                                        <div class="col-md-7">
                                            <label class="small fw-bold">Número</label>
                                            <input type="tel" name="telefono_nuevo_2" id="telefono_nuevo_2" class="form-control" placeholder="+569...">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="small fw-bold">Alias (Ej: Marido)</label>
                                            <input type="text" name="nuevo_alias_2" class="form-control" placeholder="Nombre">
                                        </div>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="guardar_nuevo_2" value="1" id="saveTel2" checked>
                                        <label class="form-check-label small text-muted" for="saveTel2">Guardar este contacto para siempre</label>
                                    </div>
                                </div>
                            </div>

                            <script>
                                function gestionarSegundoTelefono() {
                                    const sel = document.getElementById('selector_segundo_contacto');
                                    const wrapper = document.getElementById('wrapper_nuevo_segundo_tel');
                                    wrapper.classList.toggle('d-none', sel.value !== 'nuevo');
                                }
                            </script>
                            <hr class="border-light my-4">

                            <div class="row g-3 mb-4">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="tipo_entrega" id="opcion_despacho" value="1" checked onchange="cambiarMetodoEntrega()">
                                    <label class="btn btn-outline-cenco-indigo w-100 py-3 d-flex flex-column align-items-center justify-content-center gap-2 h-100 shadow-sm border-2 position-relative rounded-3" for="opcion_despacho" style="min-height: 120px;">
                                        <i class="bi bi-house-door-fill fs-2"></i>
                                        <span class="fw-bold">A Domicilio</span>
                                        <span class="small text-muted" id="texto-precio-despacho">
                                            <?= ($totalCarro >= $umbralEnvioGratis) ? '<span class="text-success fw-bold">¡GRATIS!</span>' : '$' . number_format($costoDespachoFijo, 0, ',', '.') ?>
                                        </span>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="tipo_entrega" id="opcion_retiro" value="2" onchange="cambiarMetodoEntrega()">
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
                                <div class="mb-3">
                                    <label class="fw-bold text-cenco-indigo small mb-1">Dirección de Envío</label>
                                    <select class="form-select mb-3" id="selector_direccion" name="origen_direccion" onchange="gestionarNuevaDireccion()">
                                        <option value="perfil"
                                            data-alias="Dirección Principal"
                                            data-direccion="<?= htmlspecialchars($usuario->direccion) ?>"
                                            data-lat="-32.7833" data-lng="-71.2000" selected>
                                            Principal: <?= htmlspecialchars($usuario->direccion) ?>
                                        </option>
                                        <?php foreach ($direcciones as $dir): ?>
                                            <option value="<?= $dir->id ?>"
                                                data-alias="<?= htmlspecialchars($dir->nombre_etiqueta) ?>"
                                                data-direccion="<?= htmlspecialchars($dir->direccion) ?>"
                                                data-lat="<?= !empty($dir->latitud) ? $dir->latitud : -32.7833 ?>"
                                                data-lng="<?= !empty($dir->longitud) ? $dir->longitud : -71.2000 ?>">
                                                <?= htmlspecialchars($dir->nombre_etiqueta) ?>: <?= htmlspecialchars($dir->direccion) ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="nueva" class="text-primary fw-bold">+ Agregar Nueva Dirección...</option>
                                    </select>
                                </div>

                                <div id="contenedor-detalle-direccion" class="mb-3">
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

                                <div id="form_nueva_direccion" class="card bg-light border-0 p-3 mb-3 d-none animate__animated animate__fadeIn">
                                    <h6 class="fw-bold text-cenco-indigo mb-3">Nueva Dirección</h6>
                                    <div class="mb-3">
                                        <label class="fw-bold small mb-1">Nombre</label>
                                        <input type="text" name="nueva_alias" id="nueva_alias" class="form-control" placeholder="Ej: Oficina">
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold small mb-1">Comuna</label>
                                        <select name="nueva_comuna_id" id="selectComuna" class="form-select" onchange="centrarMapaEnComuna()">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($listaComunas as $comuna): ?>
                                                <option value="<?= $comuna['id'] ?>"><?= htmlspecialchars($comuna['nombre']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold small mb-1">Calle y Número</label>
                                        <div class="input-group">
                                            <input type="text" name="nueva_direccion" id="inputDireccion" class="form-control">
                                            <button type="button" class="btn btn-lupa-azul" onclick="buscarEnMapa()"><i class="bi bi-search"></i></button>
                                        </div>
                                    </div>
                                    <div id="mapa_seleccion" class="bg-white"></div>

                                    <button type="button" id="btnGuardarDireccion" class="btn btn-success w-100 rounded-pill fw-bold mt-3" onclick="guardarDireccionAjax()">GUARDAR</button>
                                </div>
                                <input type="hidden" name="direccion" id="direccion_final_texto" value="<?= htmlspecialchars($usuario->direccion) ?>">
                            </div>

                            <div id="bloque_retiro" class="d-none">
                                <div class="mb-3">
                                    <label class="fw-bold text-cenco-indigo small mb-1">1. ¿En qué comuna deseas retirar?</label>
                                    <select id="filtro_comuna_retiro" class="form-select form-select-lg" onchange="gestionarRetiroAutomatico()">
                                        <option value="" disabled selected>Selecciona tu comuna...</option>
                                        <option value="La Calera">La Calera</option>
                                        <option value="Villa Alemana">Villa Alemana</option>
                                    </select>
                                </div>

                                <div id="contenedor-detalle-sucursal" class="d-none">
                                    <div class="sucursal-card-detail shadow-sm">
                                        <div class="row g-0">
                                            <div class="col-md-5 bg-light p-3 d-flex flex-column justify-content-center">
                                                <h6 class="fw-bold text-cenco-indigo mb-3" id="card-sucursal-nombre"><i class="bi bi-shop me-2"></i>...</h6>
                                                <ul class="list-unstyled sucursal-info-list mb-0">
                                                    <li class="d-flex align-items-start mb-2"><i class="bi bi-geo-alt text-danger me-2 mt-1"></i><span id="card-sucursal-dir" class="fw-bold text-dark">...</span></li>
                                                    <li class="d-flex align-items-center mb-2"><i class="bi bi-clock text-success me-2"></i><span id="card-sucursal-horario">...</span></li>
                                                    <li class="d-flex align-items-center"><i class="bi bi-telephone text-primary me-2"></i><span id="card-sucursal-fono">...</span></li>
                                                </ul>
                                            </div>
                                            <div class="col-md-7">
                                                <div id="mapa-retiro-inline"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="alert alert-success d-flex align-items-center mt-3 py-2 px-3 rounded-3 shadow-sm border-0">
                                        <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                                        <div><small class="fw-bold">¡Sucursal Seleccionada!</small>
                                            <div style="font-size: 0.75rem;">Retiro sin costo.</div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="sucursal_codigo" id="input_sucursal_codigo" disabled>
                            </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow rounded-4 border-0 bg-white sticky-top" style="top: 100px; z-index: 100;">
            <div class="card-body p-4">
                <h5 class="fw-black mb-4 text-cenco-indigo ls-1">Resumen del Pedido</h5>

                <div class="mb-3 border-bottom pb-3 pe-2" style="max-height: 250px; overflow-y: auto;">
                    <?php if (!empty($_SESSION['carrito'])): foreach ($_SESSION['carrito'] as $item):
                            $img = !empty($item['imagen']) ? (strpos($item['imagen'], 'http') === 0 ? $item['imagen'] : BASE_URL . 'img/productos/' . $item['imagen']) : BASE_URL . 'img/no-image.png';
                    ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="position-relative me-3">
                                    <img src="<?= $img ?>" class="rounded border" style="width: 50px; height: 50px; object-fit: contain;">
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-cenco-indigo"><?= $item['cantidad'] ?></span>
                                </div>
                                <div class="flex-grow-1 lh-sm">
                                    <small class="d-block text-dark fw-bold"><?= htmlspecialchars($item['nombre']) ?></small>
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
                    <div class="border-top border-secondary mt-2 pt-2 d-flex justify-content-between fs-4 fw-black text-cenco-red">
                        <span>Total a Pagar</span>
                        <span id="resumen-total-final">$<?= number_format($totalCarro + $costoEnvio, 0, ',', '.') ?></span>
                    </div>
                </div>

                <button type="submit" form="formCheckout" class="btn btn-cenco-green w-100 py-3 fw-bold shadow-sm rounded-pill fs-5 transition-hover">
                    CONFIRMAR COMPRA <i class="bi bi-arrow-right-circle ms-2"></i>
                </button>
            </div>
        </div>
    </div>
</div>
</div>

<script>
    // Objeto Configuración para JS
    const CheckoutConfig = {
        baseUrl: '<?= BASE_URL ?>',
        totalCarro: <?= $totalCarro ?>,
        umbralGratis: <?= $umbralEnvioGratis ?>,
        costoDespacho: <?= $costoDespachoFijo ?>,
        umbralAlerta: <?= $umbralAlerta ?>,
        sucursales: <?= json_encode($jsSucursales) ?>
    };


    function toggleNuevoTelefono(show) {
        const wrapper = document.getElementById('wrapper_nuevo_telefono');
        const inputNuevo = document.getElementById('telefono_nuevo');

        if (show) {
            wrapper.classList.remove('d-none');
            inputNuevo.setAttribute('required', 'required');
            inputNuevo.focus();
        } else {
            wrapper.classList.add('d-none');
            inputNuevo.removeAttribute('required');
        }
    }
</script>
<script src="<?= BASE_URL ?>js/checkout.js"></script>