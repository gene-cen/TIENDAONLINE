<div class="container-fluid px-4 py-4 bg-light min-vh-100">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-1">
                <i class="bi bi-receipt-cutoff me-2"></i>Historial de Ventas
            </h2>
            <p class="text-muted mb-0">Gestiona, filtra y revisa la logística de tus pedidos.</p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <?php
            // Construimos la URL de exportación manteniendo los filtros actuales
            $exportParams = $_GET;
            unset($exportParams['url']); // Limpiar variable interna si existe
            $exportQuery = http_build_query($exportParams);
            $urlExport = BASE_URL . "admin/exportar_pedidos" . (!empty($exportQuery) ? '?' . $exportQuery : '');
            ?>
            <a href="<?= $urlExport ?>" class="btn btn-cenco-green text-white shadow-sm fw-bold hover-scale">
                <i class="bi bi-file-earmark-excel me-1"></i> Exportar a Excel
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="<?= BASE_URL ?>admin/pedidos" method="GET" class="row g-2 align-items-center">

                <div class="col-md-4 d-flex gap-2">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted small">Desde</span>
                        <input type="date" name="desde" class="form-control border-start-0 ps-0 text-muted"
                            value="<?= $_GET['desde'] ?? date('Y-m-01') ?>"
                            onchange="this.form.submit()">
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted small">Hasta</span>
                        <input type="date" name="hasta" class="form-control border-start-0 ps-0 text-muted"
                            value="<?= $_GET['hasta'] ?? date('Y-m-d') ?>"
                            onchange="this.form.submit()">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control border-start-0 ps-0"
                            placeholder="RUT, Cliente, Folio o Tracking..."
                            value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <select name="estado" class="form-select text-muted" onchange="this.form.submit()">
                        <option value="">Todos los Estados</option>
                        <option value="1" <?= ($_GET['estado'] ?? '') == '1' ? 'selected' : '' ?>>Pendiente de Pago</option>
                        <option value="2" <?= ($_GET['estado'] ?? '') == '2' ? 'selected' : '' ?>>Pagado / Confirmado</option>
                        <option value="3" <?= ($_GET['estado'] ?? '') == '3' ? 'selected' : '' ?>>En Preparación</option>
                        <option value="4" <?= ($_GET['estado'] ?? '') == '4' ? 'selected' : '' ?>>En Ruta</option>
                        <option value="5" <?= ($_GET['estado'] ?? '') == '5' ? 'selected' : '' ?>>Entregado</option>
                        <option value="6" <?= ($_GET['estado'] ?? '') == '6' ? 'selected' : '' ?>>Anulado</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-cenco-indigo w-100 fw-bold shadow-sm">Buscar</button>
                    <?php if (!empty($_GET['q']) || !empty($_GET['estado'])): ?>
                        <a href="<?= BASE_URL ?>admin/pedidos" class="btn btn-outline-danger" title="Limpiar">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php include 'partials/dashboard_subsidios.php'; ?>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">


                    <thead class="bg-light border-bottom">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold text-uppercase border-0">Folio</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0">Cliente</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0" style="width: 20%;">Logística</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0 text-end">Cobro (Cliente)</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0 text-end">Total A Facturar</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0 text-end">Subsidio</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase border-0 text-center">Estado</th>
                            <th class="pe-4 py-3 text-muted small fw-bold text-uppercase border-0 text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pedidos)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                    No se encontraron pedidos con estos filtros.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pedidos as $p):
                                $id = $p['id'] ?? 0;
                                $fechaRaw = $p['fecha_creacion'] ?? null;
                                $nombreCliente = $p['nombre_cliente'] ?? 'Cliente Web';
                                $rutCliente = $p['rut_cliente'] ?? '---';

                                // LOGÍSTICA
                                $sucursal = $p['sucursal_codigo'] ?? 'WEB';
                                $fechaEntrega = $p['fecha_entrega_fmt'] ?? null;
                                $rangoHorario = $p['rango_horario'] ?? '';
                                $tipoEntrega = (int)($p['tipo_entrega_id'] ?? 1);

                                if ($tipoEntrega === 2) {
                                    $textoLogistica = "Suc. $sucursal (Retiro)";
                                    $iconoLogistica = "bi-shop text-secondary";
                                } else {
                                    $textoLogistica = "Suc. $sucursal (Dom.)";
                                    $iconoLogistica = "bi-house-door-fill text-cenco-red";
                                }

                                // MATEMÁTICA DE TOTALES Y SUBSIDIOS
                                $totalERP = (int)($p['monto_total'] ?? $p['total_bruto'] ?? 0);
                                $subsidio = (int)($p['subsidio_empresa'] ?? 0);
                                $costoEnvio = (int)($p['costo_envio'] ?? 0);

                                // ¡LA CLAVE ESTÁ AQUÍ! 
                                // El cobro final es simplemente el ERP menos el subsidio. NO le restamos el $costoEnvio.
                                $cobroCliente = $totalERP - $subsidio;

                               // ESTADOS COMBINADOS (CON COLORES DINÁMICOS)
                                $estadoLogistico = strtolower($p['estado'] ?? 'pendiente de pago');
                                $textoEstado = strtoupper($p['estado'] ?? 'PENDIENTE DE PAGO');
                                
                                // ¡AQUÍ ESTÁ LA LÍNEA QUE HABÍA BORRADO POR ERROR! 👇
                                $estadoPagoId = (int)($p['estado_pago_id'] ?? 1); 

                                $badgeClass = match ($estadoLogistico) {
                                    'pendiente de pago', 'pendiente' => 'bg-warning text-dark shadow-sm',
                                    'pagado / confirmado', 'pagado'  => 'bg-info text-white shadow-sm',
                                    'en preparación', 'en preparacion' => 'bg-primary text-white shadow-sm',
                                    'en ruta', 'enviado'             => 'bg-cenco-indigo text-white shadow-sm',
                                    'entregado'                      => 'bg-success text-white shadow-sm',
                                    'anulado', 'cancelado'           => 'bg-danger text-white shadow-sm',
                                    default                          => 'bg-secondary text-white shadow-sm'
                                };

                              
                               
                                $formaPagoId = (int)($p['forma_pago_id'] ?? 5);

                                if ($formaPagoId === 8) {
                                    // Si es Venta Asistida (8), revisamos si está pendiente o pagado
                                    if ($estadoPagoId === 1) {
                                        $formaPagoHtml = '<span class="text-warning fw-bold"><i class="bi bi-clock-history"></i> Pago Pendiente</span>';
                                    } else {
                                        // Si ya está pagado, leemos con qué pagó
                                        $metodoReal = $p['metodo_pago_real'] ?? '';
                                        if ($metodoReal === 'Efectivo') {
                                            $formaPagoHtml = '<span class="text-success fw-bold"><i class="bi bi-shop"></i> Pago Sucursal <span style="font-size:0.55rem;" class="text-muted">(Efectivo)</span></span>';
                                        } elseif ($metodoReal === 'Tarjeta (Transbank POS)') {
                                            $formaPagoHtml = '<span class="text-primary fw-bold"><i class="bi bi-shop"></i> Pago Sucursal <span style="font-size:0.55rem;" class="text-muted">(TBK)</span></span>';
                                        } else {
                                            // Por si es un pedido antiguo antes de esta actualización
                                            $formaPagoHtml = '<span class="text-success fw-bold"><i class="bi bi-shop"></i> Pago Sucursal</span>';
                                        }
                                    }
                                } elseif ($formaPagoId === 7) {
                                    $formaPagoHtml = '<span class="text-warning fw-bold"><i class="bi bi-shield-check"></i> Créd. Confianza</span>';
                                } else {
                                    $formaPagoHtml = '<span class="text-muted"><i class="bi bi-credit-card"></i> Webpay Plus</span>';
                                }
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold text-primary">#<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?></span><br>
                                        <small class="text-muted" style="font-size: 0.75rem;"><?= date('d/m/y', strtotime($fechaRaw)) ?></small>
                                    </td>

                                    <td>
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 150px;"><?= htmlspecialchars($nombreCliente) ?></div>
                                        <div class="small text-muted d-flex align-items-center gap-2">
                                            <span><i class="bi bi-person-vcard"></i> <?= $rutCliente ?></span>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <div>
                                                <span class="badge bg-white text-dark border shadow-sm rounded-pill px-2">
                                                    <i class="bi <?= $iconoLogistica ?> me-1"></i> <?= $textoLogistica ?>
                                                </span>
                                            </div>
                                            <?php if ($fechaEntrega): ?>
                                                <div class="d-flex align-items-center mt-1">
                                                    <i class="bi bi-calendar-check text-success me-1" style="font-size: 0.8rem;"></i>
                                                    <span class="fw-bold text-dark" style="font-size: 0.75rem;"><?= $fechaEntrega ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="small text-muted fst-italic ms-1" style="font-size: 0.75rem;">Sin agendar</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="text-end">
                                        <div class="fw-black text-cenco-indigo fs-6">$<?= number_format($cobroCliente, 0, ',', '.') ?></div>

                                        <?php if ($costoEnvio > 0): ?>
                                            <div class="text-success fw-bold mb-1" style="font-size: 0.65rem;" title="Incluye valor de despacho">
                                                (Incluye $<?= number_format($costoEnvio, 0, ',', '.') ?> envío)
                                            </div>
                                        <?php endif; ?>

                                        <div class="d-flex align-items-center justify-content-end gap-1 mt-1" style="font-size: 0.65rem;">
                                            <?= $formaPagoHtml ?>

                                            <?php if ($estadoPagoId === 3): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill ms-1" style="font-size: 0.55rem;" title="El dinero ya está en la cuenta Cencocal">
                                                    <i class="bi bi-lock-fill"></i> Capturado
                                                </span>
                                            <?php elseif ($estadoPagoId === 2 && $formaPagoId === 5): ?>
                                                <span class="badge bg-warning bg-opacity-10 text-dark border border-warning rounded-pill ms-1" style="font-size: 0.55rem;" title="Dinero retenido por el banco (Falta capturar)">
                                                    <i class="bi bi-hourglass-split"></i> Retenido
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-bold text-dark fs-6">$<?= number_format($cobroCliente, 0, ',', '.') ?></div>

                                        <?php if ($subsidio > 0): ?>
                                            <div class="text-muted text-decoration-line-through" style="font-size: 0.65rem;" title="Valor Bruto Original">
                                                Bruto: $<?= number_format($totalERP, 0, ',', '.') ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end">
                                        <?php if ($subsidio > 0): ?>
                                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning px-2 rounded-pill" title="Asumido por empresa">
                                                +$<?= number_format($subsidio, 0, ',', '.') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge rounded-pill <?= $badgeClass ?> px-2 py-1 text-uppercase" style="font-size: 0.65rem;">
                                            <?= $textoEstado ?>
                                        </span>
                                    </td>

                                    <td class="text-end pe-4">
                                        <a href="<?= BASE_URL ?>admin/pedido/ver/<?= $id ?>" class="btn btn-sm btn-white border shadow-sm text-primary fw-bold hover-scale" title="Ver Detalles">
                                            Ver <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php
        if (!function_exists('buildPageUrl')) {
            function buildPageUrl($page)
            {
                $params = $_GET;
                $params['page'] = $page;
                unset($params['url']);
                return BASE_URL . 'admin/pedidos?' . http_build_query($params);
            }
        }
        $totalRegs = $totalRegistros ?? count($pedidos);
        $pagActual = $paginaActual ?? 1;
        $pagTotales = $totalPaginas ?? 1;
        ?>

        <div class="card-footer bg-white border-top py-3 d-flex flex-column flex-md-row justify-content-between align-items-center">
            <span class="text-muted small mb-3 mb-md-0 fw-bold">
                Mostrando <?= count($pedidos) ?> de <?= $totalRegs ?> registros (Página <?= $pagActual ?> de <?= $pagTotales ?>)
            </span>

            <?php if ($pagTotales > 1): ?>
                <nav aria-label="Paginación de pedidos">
                    <ul class="pagination pagination-sm mb-0 shadow-sm">
                        <li class="page-item <?= ($pagActual <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link text-cenco-indigo fw-bold" href="<?= buildPageUrl($pagActual - 1) ?>"><i class="bi bi-chevron-left"></i> Atrás</a>
                        </li>
                        <?php
                        $inicio = max(1, $pagActual - 2);
                        $fin = min($pagTotales, $pagActual + 2);
                        if ($inicio > 1): ?>
                            <li class="page-item"><a class="page-link text-cenco-indigo" href="<?= buildPageUrl(1) ?>">1</a></li>
                            <?php if ($inicio > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                            <li class="page-item <?= ($i == $pagActual) ? 'active' : '' ?>">
                                <a class="page-link <?= ($i == $pagActual) ? 'bg-cenco-indigo border-cenco-indigo text-white' : 'text-cenco-indigo' ?>" href="<?= buildPageUrl($i) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($fin < $pagTotales): ?>
                            <?php if ($fin < $pagTotales - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                            <li class="page-item"><a class="page-link text-cenco-indigo" href="<?= buildPageUrl($pagTotales) ?>"><?= $pagTotales ?></a></li>
                        <?php endif; ?>

                        <li class="page-item <?= ($pagActual >= $pagTotales) ? 'disabled' : '' ?>">
                            <a class="page-link text-cenco-indigo fw-bold" href="<?= buildPageUrl($pagActual + 1) ?>">Siguiente <i class="bi bi-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>