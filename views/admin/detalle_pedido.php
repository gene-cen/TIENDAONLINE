<div class="container-fluid px-4 py-4 bg-light min-vh-100">

    <div class="mb-4">
        <a href="<?= BASE_URL ?>admin/pedidos" class="text-decoration-none text-muted fw-bold small hover-link">
            <i class="bi bi-arrow-left me-1"></i> Volver al Historial
        </a>
    </div>

    <?php
    // 1. PREPARACIÓN DE DATOS (Misma lógica logística)
    $idPedido = $pedido['id'] ?? 0;
    $fecha = isset($pedido['fecha_creacion']) ? date('d/m/Y', strtotime($pedido['fecha_creacion'])) : '--/--/----';
    $hora = isset($pedido['hora_creacion']) ? date('H:i', strtotime($pedido['hora_creacion'])) : '--:--';

    $estado = strtolower($pedido['estado'] ?? 'pendiente de pago');
    $estadoIdActual = $pedido['estado_pedido_id'] ?? 1;

    $badgeClass = match ($estado) {
        'pendiente de pago' => 'bg-warning text-dark',
        'pagado / confirmado' => 'bg-info text-white',
        'en preparación' => 'bg-primary text-white',
        'en ruta' => 'bg-indigo text-white',
        'entregado' => 'bg-success text-white',
        'anulado' => 'bg-danger text-white',
        default => 'bg-secondary text-white'
    };

    // Cliente
    $clienteNombre = $pedido['nombre_cliente'] ?? $pedido['usuario_nombre'] ?? 'Cliente Desconocido';
    $clienteRut = $pedido['rut_cliente'] ?? $pedido['usuario_rut'] ?? '---';
    $clienteEmail = $pedido['email_cliente'] ?? $pedido['usuario_email'] ?? '---';
    $clienteFono = $pedido['telefono_cliente'] ?? $pedido['usuario_telefono'] ?? '---';

    // Logística
    $direccion = $pedido['direccion_envio'] ?? $pedido['direccion_entrega_texto'] ?? 'Retiro en tienda';
    $sucursal = $pedido['sucursal_codigo'] ?? null;

    $fechaEntrega = isset($pedido['fecha_entrega_estimada']) ? date('d/m/Y', strtotime($pedido['fecha_entrega_estimada'])) : null;
    $rangoHorario = $pedido['rango_horario'] ?? $pedido['nombre_rango'] ?? 'Por definir';

    // Totales
    $totalNeto = $pedido['total_neto'] ?? 0;
    $totalIva = round($totalNeto * 0.19);
    $totalBruto = $pedido['monto_total'] ?? 0;
    ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-3 mb-1">
                <h2 class="fw-black text-cenco-indigo mb-0">Pedido #<?= str_pad($idPedido, 6, '0', STR_PAD_LEFT) ?></h2>
                <span class="badge rounded-pill <?= $badgeClass ?> px-3 py-2 text-uppercase ls-1">
                    <?= $estado ?>
                </span>
            </div>
            <p class="text-muted mb-0">
                Realizado el <strong><?= $fecha ?></strong> a las <?= $hora ?>
            </p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <button class="btn btn-white border shadow-sm text-cenco-indigo fw-bold" onclick="window.print()">
                <i class="bi bi-printer me-2"></i> Imprimir
            </button>
        </div>
    </div>

    <div class="row g-4">

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom border-light">
                    <h5 class="mb-0 fw-bold text-cenco-indigo"><i class="bi bi-cart3 me-2"></i>Productos</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-muted small fw-bold text-uppercase border-0">Producto</th>
                                    <th class="text-center py-3 text-muted small fw-bold text-uppercase border-0">Precio Neto</th>
                                    <th class="text-center py-3 text-muted small fw-bold text-uppercase border-0">Cant.</th>
                                    <th class="pe-4 text-end py-3 text-muted small fw-bold text-uppercase border-0">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($detalles)): ?>
                                    <?php foreach ($detalles as $d):
                                        $precio = $d['precio_neto'] ?? 0;
                                        $cantidad = $d['cantidad'] ?? 0;
                                        $subtotalLinea = $precio * $cantidad;
                                        $nombreProd = $d['nombre_producto'] ?? $d['nombre'] ?? 'Producto';
                                        $codProd = $d['cod_producto'] ?? '---';
                                        $img = !empty($d['imagen']) ? (strpos($d['imagen'], 'http') === 0 ? $d['imagen'] : BASE_URL . 'img/productos/' . $d['imagen']) : BASE_URL . 'img/no-image.png';
                                    ?>
                                        <tr>
                                            <td class="ps-4 py-3">
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= $img ?>" class="rounded border me-3" style="width: 40px; height: 40px; object-fit: contain;">
                                                    <div>
                                                        <div class="fw-bold text-dark"><?= $nombreProd ?></div>
                                                        <small class="text-muted">COD: <?= $codProd ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">$<?= number_format($precio, 0, ',', '.') ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border px-3"><?= $cantidad ?></span>
                                            </td>
                                            <td class="pe-4 text-end fw-bold text-cenco-indigo">
                                                $<?= number_format($subtotalLinea, 0, ',', '.') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">No hay detalles disponibles.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-white p-4">
                    <div class="row justify-content-end">
                        <div class="col-md-5 col-lg-4">
                            <div class="d-flex justify-content-between mb-2 text-muted">
                                <span>Subtotal Neto:</span>
                                <span>$<?= number_format($totalNeto, 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 text-muted">
                                <span>IVA (19%):</span>
                                <span>$<?= number_format($totalIva, 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between pt-3 border-top">
                                <span class="fw-bold fs-5 text-cenco-indigo">Total Bruto:</span>
                                <span class="fw-black fs-4 text-cenco-green">$<?= number_format($totalBruto, 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">

            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-cenco-indigo text-white">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Gestión de Estado</h6>
                    <p class="small opacity-75 mb-3">Cambiar el estado enviará un correo automático al cliente.</p>

                    <form action="<?= BASE_URL ?>admin/pedido/cambiarEstado" method="POST" id="formCambiarEstado">
                        <input type="hidden" name="pedido_id" value="<?= $idPedido ?>">

                        <div class="input-group mb-3">
                            <select name="estado_id" class="form-select border-0 fw-bold text-cenco-indigo">
                                <option value="1" <?= $estadoIdActual == 1 ? 'selected' : '' ?>>Pendiente de Pago</option>
                                <option value="2" <?= $estadoIdActual == 2 ? 'selected' : '' ?>>Pagado / Confirmado</option>
                                <option value="3" <?= $estadoIdActual == 3 ? 'selected' : '' ?>>En Preparación</option>
                                <option value="4" <?= $estadoIdActual == 4 ? 'selected' : '' ?>>En Ruta</option>
                                <option value="5" <?= $estadoIdActual == 5 ? 'selected' : '' ?>>Entregado</option>
                                <option value="6" <?= $estadoIdActual == 6 ? 'selected' : '' ?>>Anulado</option>
                            </select>
                            <button class="btn btn-warning fw-bold" type="submit">Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom border-light">
                    <h5 class="mb-0 fw-bold text-cenco-indigo"><i class="bi bi-truck me-2"></i>Logística</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-start mb-3">
                        <i class="bi bi-geo-alt-fill text-cenco-red fs-4 me-3 mt-1"></i>
                        <div>
                            <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Dirección de Entrega</small>
                            <p class="fw-bold text-dark mb-0 mt-1 lh-sm"><?= $direccion ?></p>
                        </div>
                    </div>

                    <hr class="border-light my-3">

                    <div class="d-flex align-items-start mb-3">
                        <i class="bi bi-calendar-check text-success fs-4 me-3 mt-1"></i>
                        <div>
                            <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Fecha Programada</small>
                            <?php if ($fechaEntrega): ?>
                                <p class="fw-bold text-dark mb-0 mt-1 fs-5"><?= $fechaEntrega ?></p>
                            <?php else: ?>
                                <p class="text-muted fst-italic mb-0 mt-1">Por confirmar</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex align-items-start">
                        <i class="bi bi-clock text-primary fs-4 me-3 mt-1"></i>
                        <div>
                            <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Rango Horario</small>
                            <p class="fw-bold text-dark mb-0 mt-1"><?= $rangoHorario ?></p>
                        </div>
                    </div>

                    <?php if (!empty($sucursal)): ?>
                        <div class="mt-4 pt-3 border-top border-light text-center">
                            <span class="badge bg-light text-dark border px-3 py-2">
                                <i class="bi bi-shop me-1"></i> Origen: Sucursal <?= $sucursal ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 px-4 border-bottom border-light">
                    <h5 class="mb-0 fw-bold text-cenco-indigo"><i class="bi bi-person me-2"></i>Cliente</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-light rounded-circle p-3 me-3 text-cenco-indigo">
                            <i class="bi bi-person-circle fs-3"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0"><?= $clienteNombre ?></h6>
                            <span class="text-muted small">RUT: <?= $clienteRut ?></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Contacto</small>
                        <div class="d-flex align-items-center mt-2">
                            <i class="bi bi-envelope me-2 text-muted"></i>
                            <a href="mailto:<?= $clienteEmail ?>" class="text-decoration-none text-dark small text-truncate" style="max-width: 200px;"><?= $clienteEmail ?></a>
                        </div>
                        <div class="d-flex align-items-center mt-2">
                            <i class="bi bi-telephone me-2 text-muted"></i>
                            <span class="text-dark small"><?= $clienteFono ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // 1. Mostrar Spinner de Carga al enviar el formulario
    const formEstado = document.getElementById('formCambiarEstado');
    if (formEstado) {
        formEstado.addEventListener('submit', function() {
            Swal.fire({
                title: 'Actualizando estado...',
                html: 'Guardando cambios y notificando.<br><b>Por favor, espera...</b>',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading(); // Lanza el spinner nativo de SweetAlert
                }
            });
        });
    }

    // 2. Mostrar Modal de Cencocalín al recargar la página
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);

        // Si la URL detecta que el estado se actualizó...
        if (urlParams.get('msg') === 'estado_actualizado') {
            Swal.fire({
                title: '<h2 class="fw-black text-cenco-green mb-0">¡Éxito!</h2>',
                html: '<p class="text-muted fs-5 mt-2">El estado del pedido se ha actualizado correctamente.</p>',
                // Llamamos a tu imagen de Cencocalín exitoso
                imageUrl: '<?= BASE_URL ?>img/cencocalin/cencocalin_logrado.png',
                imageWidth: 150,
                imageAlt: 'Cencocalin Logrado',
                confirmButtonColor: '#2A1B5E', // Tu color cenco-indigo
                confirmButtonText: '<i class="bi bi-check-lg me-2"></i>Genial',
                customClass: {
                    popup: 'rounded-4 shadow-lg border-0'
                }
            }).then(() => {
                // Truco Pro: Limpiamos la URL quitando el "?msg=estado_actualizado" 
                // para que si el admin recarga la página (F5), no vuelva a salir la alerta.
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }
    });
</script>