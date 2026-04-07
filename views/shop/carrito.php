<?php
// Calculamos el total inicial
$total = 0;
if (!empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $total += ($item['precio'] * $item['cantidad']);
    }
}
?>

<div class="container py-5">

    <div class="d-flex align-items-center mb-4">
        <h2 class="fw-black text-cenco-indigo mb-0">
            <i class="bi bi-cart3 me-2 text-cenco-green"></i>Tu Carrito
        </h2>
        <span class="badge bg-light text-cenco-indigo border ms-3 rounded-pill px-3 py-2">
            <?= !empty($_SESSION['carrito']) ? count($_SESSION['carrito']) : 0 ?> productos
        </span>
    </div>

    <?php if (empty($_SESSION['carrito'])): ?>

        <div class="card border-0 shadow-sm rounded-4 text-center py-5 bg-white">
            <div class="card-body">
                <div class="mb-4">
                    <div class="bg-light d-inline-flex p-4 rounded-circle shadow-sm">
                        <i class="bi bi-basket3 text-muted opacity-25" style="font-size: 4rem;"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-cenco-indigo mb-2">Tu carrito está vacío</h3>
                <p class="text-muted mb-4">Parece que aún no has encontrado lo que buscas.</p>
                <a href="<?= BASE_URL ?>home/catalogo" class="btn btn-cenco-indigo rounded-pill px-5 py-3 fw-bold shadow-sm transition-hover">
                    Ir a Vitrinear
                </a>
            </div>
        </div>

    <?php else: ?>

        <div class="row g-4">

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light border-bottom">
                                    <tr>
                                        <th class="ps-4 py-3 text-muted small fw-bold text-uppercase border-0">Producto</th>
                                        <th class="text-center py-3 text-muted small fw-bold text-uppercase border-0">Precio</th>
                                        <th class="text-center py-3 text-muted small fw-bold text-uppercase border-0">Cantidad</th>
                                        <th class="text-center py-3 text-muted small fw-bold text-uppercase border-0">Total</th>
                                        <th class="border-0"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($_SESSION['carrito'] as $id => $item):
                                        $subtotal = ($item['precio'] ?? 0) * ($item['cantidad'] ?? 1);
                                        $img = !empty($item['imagen'])
                                            ? (strpos($item['imagen'], 'http') === 0 ? $item['imagen'] : BASE_URL . 'img/productos/' . $item['imagen'])
                                            : BASE_URL . 'img/no-image.png';

                                        // --- LÓGICA DE VALIDACIÓN DE STOCK EN TIEMPO REAL ---
                                        $sucursal_activa = $_SESSION['sucursal_activa'] ?? 29;
                                        // Usamos $this->db que está disponible en el contexto de la vista si viene del controlador
                                        $stmtStock = $this->db->prepare("SELECT (ps.stock - ps.stock_reservado) as disponible 
                                                                         FROM productos_sucursales ps 
                                                                         INNER JOIN productos p ON ps.cod_producto = p.cod_producto 
                                                                         WHERE p.id = ? AND ps.sucursal_id = ?");
                                        $stmtStock->execute([$id, $sucursal_activa]);
                                        $stockReal = (int)$stmtStock->fetchColumn();

                                        $hayQuiebre = ($stockReal <= 0);
                                        $esCritico = ($stockReal > 0 && $stockReal < 5);
                                    ?>
                                        <tr class="<?= $hayQuiebre ? 'opacity-50 bg-light' : '' ?>" id="fila-carrito-<?= $id ?>">
                                            <td class="ps-4 py-3 border-bottom-0">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-white border rounded-3 p-1 me-3 flex-shrink-0 shadow-sm position-relative" style="width: 70px; height: 70px;">
                                                        <img src="<?= $img ?>" class="w-100 h-100 object-fit-contain <?= $hayQuiebre ? 'grayscale' : '' ?>" onerror="this.src='<?= BASE_URL ?>img/no-image.png';">
                                                        <?php if ($hayQuiebre): ?>
                                                            <span class="position-absolute top-50 start-50 translate-middle badge bg-danger" style="font-size: 0.5rem;">AGOTADO</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <h6 class="fw-bold text-cenco-indigo mb-1 text-truncate" style="max-width: 220px;">
                                                            <?= htmlspecialchars($item['nombre'] ?? 'Producto') ?>
                                                        </h6>
                                                        <small class="text-muted d-block mb-1">COD: <?= $id ?></small>

                                                        <div id="status-stock-<?= $id ?>">
                                                            <?php if ($hayQuiebre): ?>
                                                                <span class="text-danger small fw-bold"><i class="bi bi-x-circle"></i> Sin stock disponible</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-light text-dark border fw-normal" style="font-size: 0.75rem;">
                                                                    Stock disponible: <strong id="stock-num-<?= $id ?>"><?= $stockReal - $item['cantidad'] ?></strong>
                                                                </span>

                                                                <?php if ($esCritico): ?>
                                                                    <br>
                                                                    <span class="text-danger extra-small fw-bold animate__animated animate__flash animate__infinite">
                                                                        <i class="bi bi-exclamation-triangle"></i> ¡Últimas unidades!
                                                                    </span>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="text-center text-muted fw-bold border-bottom-0">$<?= number_format($item['precio'] ?? 0, 0, ',', '.') ?></td>

                                            <td class="text-center border-bottom-0">
                                                <div class="d-inline-flex align-items-center border rounded-pill px-1 py-1 shadow-sm bg-white">
                                                    <button type="button" class="btn btn-sm btn-white rounded-circle text-danger border-0" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;" onclick="cambiarCantidad(<?= $id ?>, 'bajar')">
                                                        <i class="bi bi-dash"></i>
                                                    </button>

                                                    <input type="text" id="input-cant-<?= $id ?>" data-precio="<?= $item['precio'] ?>" class="form-control border-0 text-center bg-transparent p-0 fw-bold text-cenco-indigo" value="<?= $item['cantidad'] ?? 1 ?>" readonly style="width: 35px; font-size: 0.9rem;">

                                                    <?php if ($item['cantidad'] < $stockReal): ?>
                                                        <button type="button" id="btn-subir-<?= $id ?>" class="btn btn-sm btn-white rounded-circle text-success border-0" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;" onclick="cambiarCantidad(<?= $id ?>, 'subir')">
                                                            <i class="bi bi-plus"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" id="btn-subir-<?= $id ?>" class="btn btn-sm btn-white rounded-circle text-muted border-0" style="width: 28px; height: 28px;" disabled title="Máximo disponible alcanzado">
                                                            <i class="bi bi-plus"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>

                                            <td id="subtotal-item-<?= $id ?>" class="text-center fw-black text-cenco-indigo border-bottom-0 fs-6">$<?= number_format($subtotal, 0, ',', '.') ?></td>

                                            <td class="text-end pe-4 border-bottom-0">
                                                <button type="button" class="btn btn-sm btn-light text-danger rounded-circle shadow-sm" onclick="confirmarEliminarCarrito(<?= $id ?>)">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-top p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <a href="<?= BASE_URL ?>home/catalogo" class="text-decoration-none fw-bold text-muted small hover-link">
                            <i class="bi bi-arrow-left me-1"></i> Seguir Comprando
                        </a>

                        <button type="button" class="btn btn-link text-decoration-none fw-bold text-danger small hover-link p-0 border-0" data-bs-toggle="modal" data-bs-target="#modalVaciarCarrito">
                            <i class="bi bi-trash me-1"></i> Vaciar Carrito
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 bg-light overflow-hidden">
                    <div class="card-body p-4">
                        <h5 class="fw-black text-cenco-indigo mb-4 border-bottom pb-2">Resumen</h5>
                        <div class="d-flex justify-content-between mb-2 small text-muted">
                            <span>Subtotal</span>
                            <span id="resumen-subtotal">$<?= number_format($total, 0, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-4 small text-muted">
                            <span>IVA (Incluido)</span>
                            <span id="resumen-iva">$<?= number_format($total * 0.19, 0, ',', '.') ?></span>
                        </div>
                        <div class="bg-white p-3 rounded-3 shadow-sm mb-4 border border-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark">Total a Pagar</span>
                                <span id="resumen-total" class="fw-black text-cenco-green fs-3">$<?= number_format($total, 0, ',', '.') ?></span>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <?php
                            // Lógica inteligente: Si es admin, lo mandamos al modo asistido
                            $checkoutUrl = BASE_URL . "checkout";
                            if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
                                $checkoutUrl .= "?modo=asistido";
                            }
                            ?>

                            <?php if (!isset($_SESSION['user_id']) && !isset($_SESSION['invitado'])): ?>
                                <button type="button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#checkoutAuthModal"
                                    class="btn btn-cenco-green rounded-pill py-3 fw-bold shadow hover-scale text-white">
                                    Ir a Pagar <i class="bi bi-person-badge-fill ms-2"></i>
                                </button>
                            <?php else: ?>
                                <a href="<?= $checkoutUrl ?>" class="btn btn-cenco-green rounded-pill py-3 fw-bold shadow hover-scale text-white">
                                    Ir a Pagar <i class="bi bi-credit-card-2-front-fill ms-2"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    <?php endif; ?>
</div>

<div class="modal fade" id="modalVaciarCarrito" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg text-center p-4">
            <div class="modal-body p-2">
                <div class="mb-3 position-relative d-inline-block">
                    <div class="position-absolute top-50 start-50 translate-middle bg-danger bg-opacity-10 rounded-circle" style="width: 120px; height: 120px; filter: blur(15px);"></div>
                    <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_preocupado.png" alt="¿Seguro?" style="width: 130px; position: relative; z-index: 2; transform: rotate(-5deg);">
                </div>

                <h3 class="fw-black text-cenco-indigo mb-2">¿Estás seguro?</h3>
                <p class="text-muted mb-4">Estás a punto de eliminar todos los productos de tu carrito.</p>

                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <a href="<?= BASE_URL ?>carrito/vaciar" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm">Sí, vaciar todo</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .grayscale {
        filter: grayscale(1);
        opacity: 0.6;
    }
</style>