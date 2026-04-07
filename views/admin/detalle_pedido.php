<?php
// --- PREPARACIÓN DE DATOS FINANCIEROS ---
$idPedido       = $pedido['id'] ?? 0;

// El total del ERP (lo que valen los productos hoy)
$totalERP       = (float)($pedido['monto_total'] ?? $pedido['total_bruto'] ?? 0);

// El subsidio que la empresa aceptó pagar
$subsidio       = (float)($pedido['subsidio_empresa'] ?? 0);

// Lo que el cliente pagó realmente (Matemática pura: Total ERP - Subsidio)
$cobroCliente   = $totalERP - $subsidio;

// --- MATEMÁTICA PARA EL DESGLOSE DE ADMINISTRADOR ---
$costoEnvio = (int)($pedido['costo_envio'] ?? 0);
$costoServicio = 490;
$subtotalProductos = $totalERP - $costoEnvio - $costoServicio;

$estadoIdActual = $pedido['estado_pedido_id'] ?? 1;

// --- BÚSQUEDA DEL PAGO ORIGINAL (INMUTABLE) ---
// 1. Buscamos si hay un pago de Webpay
$stmtWp = $this->db->prepare("SELECT status, amount FROM transacciones_webpay WHERE pedido_id = ? LIMIT 1");
$stmtWp->execute([$idPedido]); // <-- ASEGÚRATE QUE SEA UNA FLECHA ->
$wpData = $stmtWp->fetch(\PDO::FETCH_ASSOC);

// !!! ESTA ES LA LÍNEA QUE DEFINE LA VARIABLE PARA EL SIDEBAR !!!
$estadoWebpay = strtolower($wpData['status'] ?? 'sin_pago'); 

// 2. Buscamos si existe un registro de la primera edición
$stmtPrimeraEd = $this->db->prepare("SELECT monto_original FROM pedidos_ediciones WHERE pedido_id = ? ORDER BY id ASC LIMIT 1");
$stmtPrimeraEd->execute([$idPedido]); // <-- OTRA FLECHA ->
$montoPrimeraEdicion = $stmtPrimeraEd->fetchColumn();

// 3. Asignamos el verdadero valor pagado
$montoFijoBanco = $wpData['amount'] ?? ($montoPrimeraEdicion ?: $cobroCliente);

// Badge Class
$estadoStr = strtolower($pedido['estado'] ?? 'pendiente');
$badgeClass = match ($estadoStr) {
    'pendiente de pago'   => 'bg-warning text-dark',
    'pagado / confirmado' => 'bg-info text-white',
    'en preparación'      => 'bg-primary text-white',
    'en ruta'             => 'bg-indigo text-white',
    'entregado'           => 'bg-success text-white',
    'anulado'             => 'bg-danger text-white',
    default               => 'bg-secondary text-white'
};
?>

<div class="container-fluid px-4 py-4 bg-light min-vh-100">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="<?= BASE_URL ?>admin/pedidos" class="btn btn-link text-decoration-none text-muted fw-bold btn-sm p-0">
            <i class="bi bi-arrow-left-circle-fill me-2"></i>Volver al Historial
        </a>
      
    </div>

    <?php include 'partials/detalle_header.php'; ?>

    <?php if ($subsidio > 0): ?>
        <div class="mb-4">
            <?php include 'partials/alerta_subsidio.php'; ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 px-4 border-bottom border-light">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-cart-check-fill me-2 text-cenco-green"></i>Resumen de Productos
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php include 'partials/detalle_tabla_productos.php'; ?>
                </div>
            </div>

            <div class="mt-4">
                <?php include 'partials/detalle_historial_ediciones.php'; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <aside class="sticky-top" style="top: 1.5rem; z-index: 1020;">
                <?php include 'partials/detalle_sidebar.php'; ?>
            </aside>
        </div>
    </div>
</div>

<?php include 'partials/modal_edicion.php'; ?>
<?php include 'partials/modal_evidencia.php'; ?>
<script>
    // IMPORTANTE: Definimos las constantes ANTES de cargar el archivo JS externo
    const PEDIDO_ID = <?= (int)($idPedido ?? 0) ?>;

    // ANCLAMOS EL JS AL VALOR REAL DEL BANCO
    const MONTO_WEBPAY_ORIGINAL = <?= (int)$montoFijoBanco ?>;

    const COSTO_ENVIO_FIJO = <?= (int)($pedido['costo_envio'] ?? 0) ?>;
    const CARRITO_ORIGINAL = <?= json_encode($detalles ?? []) ?>;
    const BASE_URL = '<?= BASE_URL ?>';
</script>

<script src="<?= BASE_URL ?>js/admin/detalle-pedido.js"></script>