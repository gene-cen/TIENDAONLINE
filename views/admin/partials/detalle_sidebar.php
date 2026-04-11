<?php
// 1. Verificación de Pago por Confianza (Crédito)
if ((int)($pedido['forma_pago_id'] ?? 0) === 7 && (int)($pedido['estado_pedido_id'] ?? 1) === 1): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-warning bg-warning bg-opacity-10">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-shield-check me-2"></i>Pago por Confianza</h6>
            <p class="small text-dark mb-3">Este pedido no requiere validación de Transbank. ¿Enviar directo a bodega?</p>
            <button type="button" onclick="pasarAPreparacionDirecto(<?= $idPedido ?>)" class="btn btn-warning w-100 fw-bold shadow-sm border-dark border-opacity-10">
                <i class="bi bi-box-seam me-2"></i>LISTO PARA PREPARACIÓN
            </button>
        </div>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 mb-4 bg-cenco-indigo text-white">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Gestión de Estado</h6>
        <form action="<?= BASE_URL ?>admin/pedido/cambiarEstado" method="POST" id="formCambiarEstado">
            <input type="hidden" name="pedido_id" value="<?= $idPedido ?>">
            <div class="input-group mb-3 shadow-sm">
                <select name="estado_id" class="form-select border-0 bg-light fw-bold text-dark" id="selectEstadoPedido" onchange="document.getElementById('btnActualizarEstado').disabled = false;">
                    <?php
                    $listaEstados = [1 => 'Pendiente de Pago', 2 => 'Pagado / Confirmado', 3 => 'En Preparación', 4 => 'En Ruta', 5 => 'Entregado', 6 => 'Anulado'];
                    $estadoIdActualInt = (int)($pedido['estado_pedido_id'] ?? 1);
                    $tipoEntregaId = (int)($pedido['tipo_entrega_id'] ?? 1);

                    foreach ($listaEstados as $opcionId => $nombreEstado):
                        if ($tipoEntregaId === 2 && $opcionId === 4) continue;

                        $isDisabled = ($opcionId < $estadoIdActualInt) || ($opcionId === 6 && $estadoIdActualInt === 5);
                        $styleStr = $isDisabled ? 'color: #adb5bd; background-color: #f8f9fa; font-style: italic;' : 'color: #212529;';
                    ?>
                        <option value="<?= $opcionId ?>" <?= $opcionId === $estadoIdActualInt ? 'selected' : '' ?> <?= $isDisabled ? 'disabled' : '' ?> style="<?= $styleStr ?>">
                            <?= $nombreEstado ?> <?= $isDisabled ? ' 🔒' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-warning fw-bold" id="btnActualizarEstado" type="submit" disabled>Actualizar</button>
            </div>
        </form>
    </div>
</div>
<?php
// --- CONFIGURACIÓN DE SEGURIDAD ---
$idPagoMetodo = (int)($pedido['forma_pago_id'] ?? 0);
$nombreMetodo = strtolower($pedido['forma_pago_nombre'] ?? '');
$statusWP     = $estadoWebpay ?? 'sin_pago';

// El monto que el sistema dice que hay que cobrar AHORA (Post-edición)
$montoASolicitar = (int)$cobroCliente;

// Verificamos si es Webpay (ID 5 o por nombre)
if ($idPagoMetodo === 5 || str_contains($nombreMetodo, 'webpay')): ?>

    <?php if ($statusWP === 'autorizado' || $statusWP === 'authorized'): ?>

        <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-success bg-white">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-success bg-opacity-10 p-2 rounded-circle me-2">
                        <i class="bi bi-robot text-success"></i>
                    </div>
                    <h6 class="fw-bold mb-0 text-success">Captura Automatizada</h6>
                </div>

                <form action="<?= BASE_URL ?>admin/pedido/capturar_pago" method="POST" id="formCapturaWebpay">
                    <input type="hidden" name="pedido_id" value="<?= $idPedido ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Monto calculado por Sistema:</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0 text-cenco-indigo fw-bold">$</span>
                            <input type="number"
                                class="form-control fw-black text-cenco-indigo fs-4 bg-light border-0"
                                name="monto_final"
                                value="<?= $montoASolicitar ?>"
                                readonly
                                style="pointer-events: none;">
                        </div>

                        <div class="form-text mt-3 p-2 bg-light rounded-3" style="font-size: 0.75rem;">
                            <i class="bi bi-info-circle-fill text-primary me-1"></i>
                            Este monto corresponde al total de productos vigentes + despacho + servicio.
                            <strong>Máximo permitido por banco: $<?= number_format($montoFijoBanco, 0, ',', '.') ?></strong>
                        </div>
                    </div>

                    <button type="button" class="btn btn-success w-100 fw-bold shadow-sm py-3 rounded-pill"
                        onclick="confirmarCapturaWebpay(this.form, <?= $montoASolicitar ?>)">
                        <i class="bi bi-check-all me-2"></i>CONFIRMAR CAPTURA
                    </button>
                </form>
            </div>
        </div>

    <?php elseif ($statusWP === 'capturado' || $statusWP === 'captured'): ?>

        <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-secondary bg-light">
            <div class="card-body p-4 text-center">
                <div class="bg-white d-inline-flex p-3 rounded-circle shadow-sm mb-3">
                    <i class="bi bi-shield-check text-success fs-2"></i>
                </div>
                <h6 class="fw-bold text-success mb-1">Pago Capturado</h6>
                <p class="small text-muted mb-0">La transacción bancaria fue cerrada exitosamente.</p>
            </div>
        </div>

    <?php endif; ?>

<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white py-3 px-4 border-bottom border-light">
        <h6 class="mb-0 fw-bold text-cenco-indigo"><i class="bi bi-person me-2"></i>Cliente</h6>
    </div>
    <div class="card-body p-4">
        <?php
        $tipoCli = $pedido['tipo_cliente'] ?? 'web';
        if ($tipoCli === 'asistido'): ?>
            <span class="badge bg-primary mb-3 shadow-sm px-3 py-2"><i class="bi bi-person-vcard me-1"></i> Venta Asistida</span>
        <?php elseif ($tipoCli === 'invitado'): ?>
            <span class="badge bg-secondary mb-3 shadow-sm px-3 py-2"><i class="bi bi-person-slash me-1"></i> Cliente Invitado</span>
        <?php endif; ?>

        <?php
        $nombreCliente = $pedido['nombre_destinatario'] ?? $pedido['nombre_cliente'] ?? 'Sin Nombre';
        $rutCliente = $pedido['rut_cliente'] ?? '---';
        $fonoCliente = $pedido['telefono_contacto'] ?? $pedido['telefono_cliente'] ?? '';
        ?>
        <h6 class="fw-bold mb-1 text-dark fs-5"><?= htmlspecialchars($nombreCliente) ?></h6>
        <div class="small text-muted mb-3 fw-bold">RUT: <?= htmlspecialchars($rutCliente) ?></div>
        <ul class="list-unstyled small mb-0">
            <li class="mb-2 d-flex align-items-center">
                <i class="bi bi-telephone-fill text-success fs-5 me-3"></i>
                <span class="fw-bold"><?= !empty($fonoCliente) ? htmlspecialchars($fonoCliente) : 'No registrado' ?></span>
            </li>
        </ul>
    </div>
</div>

<?php
// Mostrar panel de comprobante SOLO si es Pago Presencial (ID 8)
if (isset($pedido['forma_pago_id']) && $pedido['forma_pago_id'] == 8):
?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 border-bottom border-light">
            <h6 class="mb-0 fw-bold text-success"><i class="bi bi-receipt me-2"></i>Comprobante en Tienda</h6>
        </div>
        <div class="card-body">

            <?php if (!empty($pedido['comprobante_pago'])): ?>
                <div class="alert alert-success small mb-3 border-0 d-flex align-items-center">
                    <i class="bi bi-check-circle-fill fs-4 me-2 text-success"></i>
                    <div>
                        <strong>Pago Verificado</strong><br>
                        Comprobante adjunto.
                    </div>
                </div>

                <button type="button" class="btn btn-outline-success w-100 fw-bold rounded-pill" data-bs-toggle="modal" data-bs-target="#modalComprobante">
                    <i class="bi bi-eye me-1"></i> Ver Comprobante
                </button>

                <div class="modal fade" id="modalComprobante" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content rounded-4 border-0 shadow">
                            <div class="modal-header bg-success text-white rounded-top-4 py-3">
                                <h5 class="modal-title fw-bold"><i class="bi bi-receipt me-2"></i>Comprobante de Pago</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0 text-center bg-light rounded-bottom-4 overflow-hidden">
                                <?php
                                $ext = strtolower(pathinfo($pedido['comprobante_pago'], PATHINFO_EXTENSION));
                                $rutaDoc = BASE_URL . 'img/comprobantes/' . htmlspecialchars($pedido['comprobante_pago']);

                                // Si es imagen, la mostramos con img. Si es PDF, usamos iframe.
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                    <img src="<?= $rutaDoc ?>" class="img-fluid w-100 object-fit-contain" style="max-height: 70vh;">
                                <?php else: ?>
                                    <iframe src="<?= $rutaDoc ?>" class="w-100" style="height: 70vh;" frameborder="0"></iframe>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($pedido['estado_pedido_id'] < 3): ?>
                <div class="alert alert-warning small mb-3 border-0 d-flex align-items-center bg-warning bg-opacity-10">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-2 text-warning"></i>
                    <div>Sube la foto de la boleta o voucher TBK para enviar a preparación.</div>
                </div>

                <form id="form-comprobante" action="<?= BASE_URL ?>admin/pedidos/subir_comprobante" method="POST" enctype="multipart/form-data" onsubmit="return validarFolioPreparacion(event)">
                    <input type="hidden" name="id_pedido" value="<?= $pedido['id'] ?>">

                    <input type="hidden" id="flag_confianza" value="<?= $pedido['es_cliente_confianza'] ?? 0 ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Medio de Pago Real *</label>
                        <select class="form-select form-select-sm" name="medio_pago_real" required>
                            <option value="">Selecciona...</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta / Transbank</option>
                            <option value="transferencia">Transferencia</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Foto del Comprobante *</label>
                        <input class="form-control form-control-sm" type="file" name="foto_comprobante" required>
                    </div>

                    <div class="mb-3" id="bloque_folio">
                        <label class="form-label small fw-bold">N° Folio Boleta/Factura <span class="text-danger">*</span></label>
                        <input type="text" name="folio_documento" id="folio_documento" class="form-control form-control-sm border-cenco-indigo" placeholder="8 dígitos" maxlength="8" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>

                    <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm">
                        <i class="bi bi-cloud-upload-fill me-1"></i> Subir y Preparar
                    </button>
                </form>

                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        const esConfianza = document.getElementById('flag_confianza').value == "1";
                        const bloqueFolio = document.getElementById('bloque_folio');
                        const inputFolio = document.getElementById('folio_documento');

                        // Si ES de confianza, ocultamos y deshabilitamos el folio en esta etapa (se pedirá al entregar)
                        if (esConfianza) {
                            bloqueFolio.style.display = 'none';
                            inputFolio.disabled = true;
                        }
                    });

                    function validarFolioPreparacion(event) {
                        const esConfianza = document.getElementById('flag_confianza').value == "1";

                        // Si NO es de confianza, el folio es obligatorio aquí.
                        if (!esConfianza) {
                            const folio = document.getElementById('folio_documento').value.trim();
                            if (folio.length !== 8) {
                                event.preventDefault(); // Detiene el envío del formulario

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Folio Incompleto',
                                    text: 'Debes ingresar los 8 dígitos del número de boleta o factura para mandar este pedido a preparación.',
                                    confirmButtonColor: '#E53935'
                                });

                                document.getElementById('folio_documento').classList.add('is-invalid', 'border-danger');
                                setTimeout(() => document.getElementById('folio_documento').classList.remove('is-invalid', 'border-danger'), 3000);

                                return false;
                            }
                        }

                        // Si todo está ok, el formulario sigue su curso al backend
                        return true;
                    }
                </script>
            <?php endif; ?>

        </div>
    </div>
<?php endif; ?>