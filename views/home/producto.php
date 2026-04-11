<?php
// Preparar variables del producto principal
$nombre = !empty($producto['nombre_web']) ? $producto['nombre_web'] : ($producto['nombre'] ?? 'Producto');
$marca = $producto['marca'] ?? 'Genérico';
$precio = number_format($producto['precio'], 0, ',', '.');
$img = !empty($producto['imagen']) ? (strpos($producto['imagen'], 'http') === 0 ? $producto['imagen'] : BASE_URL . 'img/productos/' . $producto['imagen']) : BASE_URL . 'img/no-image.png';

$stock = (int)($producto['stock_web'] ?? $producto['stock'] ?? 0);
$sku = $producto['cod_producto'] ?? '';
$desc = $producto['descripcion'] ?? '';

// Verificar si está en carrito
$enCarro = isset($_SESSION['carrito'][$producto['id']]);
$cantEnCarro = $enCarro ? $_SESSION['carrito'][$producto['id']]['cantidad'] : 0;
$puedeComprar = ($stock > 0 || $enCarro);
?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>home" class="text-decoration-none text-muted">Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>home/catalogo" class="text-decoration-none text-muted">Catálogo</a></li>
            <?php if (!empty($producto['categoria_web'])): ?>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>home/catalogo?categoria=<?= urlencode($producto['categoria_web']) ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($producto['categoria_web']) ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active text-cenco-indigo fw-bold" aria-current="page">Producto</li>
        </ol>
    </nav>

    <div class="row g-5">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative bg-white h-100" style="min-height: 400px;">
                <div class="d-flex align-items-center justify-content-center h-100 p-5">
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($nombre) ?>" class="img-fluid" style="max-height: 400px; object-fit: contain; <?= !$puedeComprar ? 'filter: grayscale(100%); opacity: 0.7;' : '' ?>">
                </div>

                <?php if (!$puedeComprar): ?>
                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-white bg-opacity-50">
                        <span class="badge bg-secondary fs-4 shadow px-4 py-3 text-wrap text-center" style="line-height: 1.5;">
                            AGOTADO EN WEB<br><small class="fs-6 fw-normal">Solo Tienda Física</small>
                        </span>
                    </div>
                <?php endif; ?>

                <span class="position-absolute top-0 start-0 m-4 badge bg-cenco-green shadow fs-6 py-2 px-3 <?= $enCarro ? 'd-inline-block' : 'd-none' ?>" id="badge-llevas-detalle" style="z-index: 5;">
                    <i class="bi bi-bag-check-fill me-1"></i> Tienes <span id="count-detalle"><?= $cantEnCarro ?></span> en el carro
                </span>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="ps-lg-4">
                <div class="mb-3">
                    <span class="badge bg-light text-dark border fw-bold text-uppercase ls-1 mb-2"><?= htmlspecialchars($marca) ?></span>
                    <h2 class="fw-bold text-cenco-indigo lh-sm mb-2"><?= htmlspecialchars($nombre) ?></h2>
                    <small class="text-muted">COD: <?= htmlspecialchars($sku) ?></small>
                </div>
                
                <div class="d-flex align-items-center flex-wrap gap-3 mb-4 border-bottom pb-4">
                    <div>
                        <h1 class="fw-black text-cenco-red mb-0">$<?= $precio ?></h1>
                        <?php if (!empty($producto['precio_unidad_medida'])): ?>
                            <div class="text-muted small mt-1 fw-bold">
                                <i class="bi bi-info-circle me-1"></i> Precio Ref: <?= htmlspecialchars($producto['precio_unidad_medida']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="ms-md-auto d-flex gap-2">
                        <?php if ($puedeComprar): ?>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill d-flex align-items-center">
                                <i class="bi bi-check-circle-fill me-1"></i> Disponible
                            </span>
                            <span class="<?= $stock <= 10 ? 'text-danger fw-black animate__animated animate__flash animate__infinite' : 'text-success fw-bold' ?> border px-3 py-2 rounded-pill d-flex align-items-center" style="background: rgba(0,0,0,0.02)">
                                <i class="bi bi-box-seam me-1"></i> Quedan <?= $stock ?> un.
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-2 rounded-pill">
                                <i class="bi bi-shop me-1"></i> Exclusivo Tienda Física
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($desc)): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark">Descripción</h6>
                        <p class="text-muted text-justify"><?= nl2br(htmlspecialchars($desc)) ?></p>
                    </div>
                <?php endif; ?>

                <div class="bg-light p-4 rounded-4 border mb-4">
                    <?php if ($puedeComprar): ?>
                        <form id="form-add-detalle" class="<?= $enCarro ? 'd-none' : 'd-block' ?>" onsubmit="agregarAlCarrito(event, this, <?= $producto['id'] ?>)">
                            <input type="hidden" name="id" value="<?= $producto['id'] ?>">
                            <input type="hidden" name="nombre" value="<?= htmlspecialchars($nombre) ?>">
                            <input type="hidden" name="precio" value="<?= $producto['precio'] ?>">
                            <input type="hidden" name="imagen" value="<?= htmlspecialchars($img) ?>">

                            <div class="d-grid gap-2">
                                <button class="btn btn-cenco-green btn-lg fw-bold rounded-pill shadow-sm hover-scale" type="submit">
                                    <i class="bi bi-cart-plus-fill me-2"></i> Añadir al Carro
                                </button>
                            </div>
                        </form>

                        <div id="controles-detalle" class="mt-3 text-center <?= $enCarro ? 'd-block' : 'd-none' ?>">
                            <div class="d-inline-flex align-items-center bg-white rounded-pill border shadow-sm p-1">
                                <button class="btn btn-sm btn-outline-danger rounded-circle border-0" style="width: 32px; height: 32px;" onclick="gestionarClickTarjeta(<?= $producto['id'] ?>, 'bajar', true)">
                                    <i class="bi bi-dash-lg"></i>
                                </button>
                                <span class="fw-bold px-3 text-cenco-indigo fs-5" id="card-count-detalle"><?= $cantEnCarro ?></span>
                                <button class="btn btn-sm btn-cenco-green rounded-circle text-white border-0" style="width: 32px; height: 32px;" onclick="gestionarClickTarjeta(<?= $producto['id'] ?>, 'subir', true)">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <div class="text-success small fw-bold mt-2"><i class="bi bi-check-lg"></i> Producto en tu carro</div>
                        </div>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-lg w-100 rounded-pill fw-bold shadow-sm" disabled>
                            <i class="bi bi-shop me-2"></i> Solo Venta Presencial
                        </button>
                    <?php endif; ?>
                </div>

                <div class="accordion accordion-flush border rounded-3 overflow-hidden" id="accordionProduct">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold text-cenco-indigo" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne">
                                <i class="bi bi-info-circle me-2 text-cenco-green"></i> Ficha Técnica
                            </button>
                        </h2>
                        <div id="flush-collapseOne" class="accordion-collapse collapse" data-bs-parent="#accordionProduct">
                            <div class="accordion-body small text-muted">
                                <ul class="list-unstyled mb-0">
                                    <li><strong>Categoría:</strong> <?= htmlspecialchars($producto['categoria_web'] ?? 'General') ?></li>
                                    <li><strong>Subcategoría:</strong> <?= htmlspecialchars($producto['subcategoria'] ?? 'N/A') ?></li>
                                    <li><strong>Formato:</strong> Unidad</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-primary bg-primary bg-opacity-10 border-0 shadow-sm d-flex align-items-center mb-4 p-3 rounded-4 mt-3">
                        <i class="bi bi-info-circle-fill text-primary fs-2 me-3"></i>
                        <div>
                            <h6 class="fw-bold text-primary mb-1" style="font-size: 0.85rem;">Condiciones de Entrega</h6>
                            <p class="mb-0 text-dark" style="font-size: 0.75rem;">
                                Las entregas se realizan de <strong>Lunes a Viernes entre 09:00 y 16:00 hrs</strong>.<br>
                                <em>Despachos en 48 hrs hábiles (compras realizadas hasta las 15:00 hrs).</em>
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php if (!empty($relacionados)): ?>
        <div class="mt-5 pt-5 border-top">
            <h3 class="fw-bold text-cenco-indigo mb-4">También te podría interesar</h3>
            <div class="row row-cols-2 row-cols-md-4 row-cols-xl-5 g-4">
                <?php foreach ($relacionados as $rel):
                    // 🔥 MAGIA: Asignamos el producto relacionado a la variable $p que espera la tarjeta
                    $p = $rel; 
                    include __DIR__ . '/partials/tarjeta_producto.php';
                endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<script src="<?= BASE_URL ?>js/shop/producto.js"></script>