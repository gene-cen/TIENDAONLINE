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
                    $totalProductosERP = 0; // Variable para sumar la mercadería real
                    if (!empty($detalles)):
                        foreach ($detalles as $d):
                            $esEliminado = $d['es_eliminado'] ?? false;
                            $esAgregado = $d['es_agregado'] ?? false;

                            // --- LÓGICA DE ALERTA DE STOCK ---
                            $stockActual = (int)($d['stock_sucursal'] ?? 99);
                            $esStockCritico = ($stockActual < 10 && !$esEliminado);

                            $precioBruto = $d['precio_bruto'] ?? ($d['precio_neto'] * 1.19);
                            $cantidad = $d['cantidad'] ?? 0;
                            $subtotalLinea = $precioBruto * $cantidad;

                            // Sumamos al ERP solo si no fue eliminado
                            if (!$esEliminado) {
                                $totalProductosERP += $subtotalLinea;
                            }

                            $nombreProd = $d['nombre_producto'] ?? $d['nombre'] ?? 'Producto';
                            $img = !empty($d['imagen']) ? (strpos($d['imagen'], 'http') === 0 ? $d['imagen'] : BASE_URL . 'img/productos/' . $d['imagen']) : BASE_URL . 'img/no-image.png';

                            // --- CLASES DINÁMICAS PARA LA FILA ---
                            $trClass = '';
                            if ($esEliminado) {
                                $trClass = 'bg-light opacity-50';
                            } elseif ($esStockCritico) {
                                $trClass = 'table-danger border-danger alert-stock-critico';
                            } elseif ($esAgregado) {
                                $trClass = 'bg-success bg-opacity-10 border-start border-4 border-success';
                            }

                            $textClass = $esEliminado ? 'text-muted text-decoration-line-through' : ($esAgregado ? 'text-success fw-bold' : 'text-dark');
                    ?>
                            <tr class="<?= $trClass ?>" title="<?= $esStockCritico ? '¡ATENCIÓN! Quedan solo ' . $stockActual . ' unidades en la sucursal.' : 'Stock disponible: ' . $stockActual . ' un.' ?>">
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="position-relative me-3">
                                            <img src="<?= $img ?>" class="rounded border bg-white" style="width: 40px; height: 40px; object-fit: contain;">
                                            <?php if ($esStockCritico): ?>
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow-sm" style="font-size: 0.55rem; z-index: 5;">
                                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold <?= $textClass ?>">
                                                <?php if ($esStockCritico): ?>
                                                    <span class="text-danger fw-black me-1" style="font-size: 0.8rem;"><i class="bi bi-fire"></i> CRÍTICO:</span>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($nombreProd) ?>
                                                <?php if ($esEliminado): ?> <span class="badge bg-danger ms-1" style="font-size: 0.6rem;">Removido</span> <?php endif; ?>
                                                <?php if ($esAgregado): ?> <span class="badge bg-success ms-1" style="font-size: 0.6rem;">Añadido</span> <?php endif; ?>
                                            </div>

                                            <div class="mt-1" style="font-size: 0.75rem;">
                                                <span class="text-muted me-3">COD: <?= $d['cod_producto'] ?? '---' ?></span>
                                                <span class="<?= $esStockCritico ? 'text-danger fw-bold' : 'text-muted' ?>">
                                                    <i class="bi bi-box-seam me-1"></i>Stock: <?= $stockActual ?> un.
                                                </span>
                                            </div>

                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">$<?= number_format($precioBruto, 0, ',', '.') ?></td>
                                <td class="text-center"><span class="badge border px-3 <?= $esStockCritico ? 'bg-danger text-white border-danger' : 'text-dark' ?>"><?= $cantidad ?></span></td>
                                <td class="pe-4 text-end fw-bold <?= $esStockCritico ? 'text-danger' : '' ?>">$<?= number_format($subtotalLinea, 0, ',', '.') ?></td>
                            </tr>
                        <?php
                        endforeach;
                    endif;
                    ?>
                </tbody>
            </table>
        </div>
    </div>

   <div class="card-footer bg-white p-4">
        <div class="row justify-content-end">
            <div class="col-md-7 col-lg-6">
                <?php
                // MATEMÁTICA CORRECTA Y ALINEADA AL CLIENTE
                $costoEnvio = (int)($pedido['costo_envio'] ?? 0);
                $costoServicio = 490;
                
                // El Total ERP real es lo que está en la BD (ya incluye envío y servicio)
                $nuevoTotalERP = (int)$pedido['monto_total'];

                // Subsidio de la base de datos
                $subsidioReal = (int)($pedido['subsidio_empresa'] ?? 0);

                // Cobro Final del Cliente
                $cobroClienteFinal = $nuevoTotalERP - $subsidioReal;
                ?>

                <div class="d-flex justify-content-between align-items-center mb-2 text-muted">
                    <span class="fw-bold fs-6">Subtotal Productos:</span>
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
                    <span class="fw-black fs-5 text-cenco-indigo">COBRO CLIENTE:</span>
                    <span class="fw-black fs-3 text-cenco-green">$<?= number_format($cobroClienteFinal, 0, ',', '.') ?></span>
                </div>
                
                <div class="text-end mt-1">
                    <small class="text-muted" style="font-size: 0.65rem;"><i class="bi bi-shield-check me-1"></i>Precio final cobrado al cliente</small>
                </div>
                
            </div>
        </div>
    </div>
</div>