<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-white py-3 px-4 border-bottom border-light">
        <h5 class="mb-0 fw-bold text-cenco-indigo"><i class="bi bi-cart3 me-2"></i>Productos del Pedido</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3 text-muted small fw-bold text-uppercase border-0">Producto / Servicio</th>
                        <th class="text-center py-3 text-muted small fw-bold text-uppercase border-0">Precio Bruto</th>
                        <th class="text-center py-3 text-muted small fw-bold text-uppercase border-0">Cant.</th>
                        <th class="pe-4 text-end py-3 text-muted small fw-bold text-uppercase border-0">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totalProductosERP = 0;
                    if (!empty($detalles)):
                        foreach ($detalles as $d):
                            // 1. Identificar estado del producto en la edición
                            $esEliminado = !empty($d['es_eliminado']);
                            $esAgregado = !empty($d['es_agregado']);

                            // 2. Lógica de Stock (Mostrar alerta si es <= 15)
                            $stockActual = isset($d['stock_sucursal']) ? (int)$d['stock_sucursal'] : 0;
                            $esStockCritico = ($stockActual <= 15 && !$esEliminado);

                            // 3. Cálculos matemáticos
                            // Priorizamos precio_bruto, si no existe usamos neto + IVA
                            $precioBruto = $d['precio_bruto'] ?? (($d['precio_neto'] ?? 0) * 1.19);
                            $cantidad = $d['cantidad'] ?? 0;
                            $subtotalLinea = $precioBruto * $cantidad;

                            // Sumamos al ERP solo si el producto NO fue eliminado en la edición
                            if (!$esEliminado) {
                                $totalProductosERP += $subtotalLinea;
                            }

                            // Manejo de nombres e imágenes
                            $nombreProd = $d['nombre_producto'] ?? $d['nombre'] ?? 'Producto';
                            $img = !empty($d['imagen'])
                                ? (strpos($d['imagen'], 'http') === 0 ? $d['imagen'] : BASE_URL . 'img/productos/' . $d['imagen'])
                                : BASE_URL . 'img/no-image.png';

                            // 4. CLASES DINÁMICAS (El corazón visual de la auditoría)
                            $trClass = '';
                            $textClass = 'text-dark';
                            $badgeEstado = '';
                            $estiloImagen = 'width: 45px; height: 45px; object-fit: contain;';

                            if ($esEliminado) {
                                // Fila gris y tachada (como en el modal)
                                $trClass = 'bg-light opacity-50';
                                $textClass = 'text-muted text-decoration-line-through';
                                $badgeEstado = '<span class="badge bg-secondary ms-2" style="font-size: 0.65rem;"><i class="bi bi-trash3"></i> Eliminado</span>';
                                $estiloImagen .= 'filter: grayscale(100%); opacity: 0.6;';
                            } elseif ($esAgregado) {
                                // Fila destacada en verde (Producto nuevo o ajuste)
                                $trClass = 'bg-success bg-opacity-10 border-start border-4 border-success';
                                $textClass = 'text-success fw-bold';
                                $badgeEstado = '<span class="badge bg-success ms-2" style="font-size: 0.65rem;"><i class="bi bi-plus-circle"></i> Nuevo / Ajuste</span>';
                            } elseif ($esStockCritico) {
                                // Fila destacada en rojo por bajo stock
                                $trClass = 'bg-danger bg-opacity-10 border-start border-4 border-danger';
                                $textClass = 'text-danger fw-bold';
                            }
                    ?>
                            <tr class="<?= $trClass ?>">
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="position-relative me-3">
                                            <img src="<?= $img ?>" class="rounded border bg-white shadow-sm" style="<?= $estiloImagen ?>">

                                            <?php if ($esStockCritico && !$esAgregado && !$esEliminado): ?>
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light shadow-sm" style="font-size: 0.6rem; z-index: 5;" title="¡Stock Crítico!">
                                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <div class="fw-bold <?= $textClass ?>" style="font-size: 0.9rem;">
                                                <?= htmlspecialchars($nombreProd) ?>
                                                <?= $badgeEstado ?>
                                            </div>

                                            <div class="mt-1" style="font-size: 0.75rem;">
                                                <span class="text-muted me-3">COD: <?= $d['cod_producto'] ?? '---' ?></span>

                                                <?php if (!$esEliminado): ?>
                                                    <span class="<?= $esStockCritico ? 'text-danger fw-black px-2 py-1 rounded bg-danger bg-opacity-10 border border-danger' : 'text-primary fw-bold' ?>">
                                                        <i class="bi bi-box-seam me-1"></i>Stock Real: <?= $stockActual ?> un.
                                                        <?php if ($esStockCritico): ?> <span class="d-none d-md-inline ms-1 text-uppercase" style="font-size: 0.65rem;">(¡Separar urgente!)</span> <?php endif; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center <?= $textClass ?>">$<?= number_format($precioBruto, 0, ',', '.') ?></td>
                                <td class="text-center">
                                    <span class="badge border px-3 py-2 <?= $esEliminado ? 'bg-light text-muted' : ($esStockCritico ? 'bg-danger text-white border-danger' : 'bg-white text-dark shadow-sm') ?>">
                                        <?= $cantidad ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end <?= $textClass ?> fw-bold">
                                    $<?= number_format($subtotalLinea, 0, ',', '.') ?>
                                </td>
                            </tr>
                        <?php
                        endforeach;
                    else:
                        ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-cart-x fs-2 d-block mb-2 opacity-50"></i>
                                No hay productos registrados en este pedido.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer bg-white p-4">
        <div class="row justify-content-end">
            <div class="col-md-7 col-lg-6">
                <?php
                // Cálculos finales
                $costoEnvio = (int)($pedido['costo_envio'] ?? 0);
                $costoServicio = 490;

                // Si el pedido fue editado, el $totalProductosERP podría haber cambiado, 
                // así que recalculamos el total teórico a cobrar.
                $nuevoTotalAcumulado = $totalProductosERP + $costoEnvio + $costoServicio;
                $subsidioReal = (int)($pedido['subsidio_empresa'] ?? 0);
                $cobroClienteFinal = $nuevoTotalAcumulado - $subsidioReal;
                ?>

                <div class="d-flex justify-content-between align-items-center mb-2 text-muted">
                    <span class="fw-bold fs-6">Subtotal Productos (Vigentes):</span>
                    <span class="fs-6 text-dark">$<?= number_format($totalProductosERP, 0, ',', '.') ?></span>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2 text-muted">
                    <span class="fw-bold fs-6">Costo por Servicio:</span>
                    <span class="fs-6 text-dark">$<?= number_format($costoServicio, 0, ',', '.') ?></span>
                </div>

                <?php if ($costoEnvio > 0): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 text-muted">
                        <span class="fw-bold fs-6">Costo de Despacho:</span>
                        <span class="fs-6 text-dark">$<?= number_format($costoEnvio, 0, ',', '.') ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($subsidioReal > 0): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 mt-3 pt-2 border-top">
                        <span class="text-danger small fw-bold">SUBSIDIO EMPRESA (-):</span>
                        <span class="fw-bold text-danger bg-danger bg-opacity-10 px-2 rounded">-$<?= number_format($subsidioReal, 0, ',', '.') ?></span>
                    </div>
                <?php endif; ?>

                <hr class="my-3 border-light">

                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-black fs-5 text-cenco-indigo">TOTAL A COBRAR:</span>
                    <span class="fw-black fs-3 text-cenco-green">$<?= number_format($cobroClienteFinal, 0, ',', '.') ?></span>
                </div>

                <div class="text-end mt-1">
                    <small class="text-muted" style="font-size: 0.65rem;"><i class="bi bi-info-circle me-1"></i>Total actualizado sin productos eliminados</small>
                </div>
            </div>
        </div>
    </div>
</div>