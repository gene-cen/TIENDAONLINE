<div class="container mb-5">
    <h2 class="mb-4 fw-bold text-primary">
        <i class="bi bi-cart3 me-2"></i>Tu Carrito de Compras
    </h2>

    <?php if (empty($_SESSION['carrito'])): ?>

        <div class="alert alert-info text-center py-5">
            <i class="bi bi-basket fs-1 d-block mb-3"></i>
            <h4>Tu carrito está vacío</h4>
            <p class="mb-4">Parece que aún no has agregado productos.</p>
            <a href="<?= BASE_URL ?>home" class="btn btn-primary">Ir a Vitrinear</a>
        </div>

    <?php else: ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Producto</th>
                                        <th class="text-center">Precio</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-center">Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($_SESSION['carrito'] as $id => $item): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <?php if ($item['imagen']): ?>
                                                        <img src="<?= BASE_URL ?>uploads/<?= $item['imagen'] ?>" class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="rounded me-3 bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">📷</div>
                                                    <?php endif; ?>

                                                    <span class="fw-bold text-dark"><?= $item['nombre'] ?></span>
                                                </div>
                                            </td>

                                            <td class="text-center">
                                                $<?= number_format($item['precio'], 0, ',', '.') ?>
                                            </td>

                                            <td class="text-center" style="width: 150px;">
                                                <div class="input-group input-group-sm justify-content-center">
                                                    <a href="<?= BASE_URL ?>carrito/bajar?id=<?= $id ?>" class="btn btn-outline-secondary">
                                                        <i class="bi bi-dash"></i>
                                                    </a>

                                                    <input type="text" class="form-control text-center bg-white" value="<?= $item['cantidad'] ?>" readonly style="max-width: 50px;">

                                                    <a href="<?= BASE_URL ?>carrito/subir?id=<?= $id ?>" class="btn btn-outline-secondary">
                                                        <i class="bi bi-plus"></i>
                                                    </a>
                                                </div>
                                            </td>

                                            <td class="text-center fw-bold text-primary">
                                                $<?= number_format($item['precio'] * $item['cantidad'], 0, ',', '.') ?>
                                            </td>

                                            <td class="text-end pe-4">
                                                <a href="<?= BASE_URL ?>carrito/eliminar?id=<?= $id ?>" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>home" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Seguir Comprando
                    </a>
                    <a href="<?= BASE_URL ?>carrito/vaciar" class="btn btn-outline-danger">
                        <i class="bi bi-trash"></i> Vaciar Carrito
                    </a>
                </div>
            </div>

            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="card shadow-sm border-0 bg-light">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold mb-4">Resumen del Pedido</h5>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span>$<?= number_format($total, 0, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">IVA (19%)</span>
                            <span>Calculado en Checkout</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-4">
                            <h4 class="fw-bold">Total</h4>
                            <h4 class="fw-bold text-primary">$<?= number_format($total, 0, ',', '.') ?></h4>
                        </div>

                        <div class="d-grid">
                            <a href="<?= BASE_URL ?>checkout" class="btn btn-primary py-2 fw-bold shadow-sm">
                                Ir a Pagar <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>