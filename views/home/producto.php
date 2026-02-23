<?php
// Preparar variables
$nombre = !empty($producto['nombre_web']) ? $producto['nombre_web'] : $producto['nombre'];
$marca = $producto['marca'] ?? 'Genérico';
$precio = number_format($producto['precio'], 0, ',', '.');
$img = !empty($producto['imagen']) ? (strpos($producto['imagen'], 'http') === 0 ? $producto['imagen'] : BASE_URL . 'img/productos/' . $producto['imagen']) : BASE_URL . 'img/no-image.png';
$stock = $producto['stock'];
$sku = $producto['cod_producto'];
$desc = $producto['descripcion'];

// Verificar si está en carrito
$enCarro = isset($_SESSION['carrito'][$producto['id']]);
$cantEnCarro = $enCarro ? $_SESSION['carrito'][$producto['id']]['cantidad'] : 0;
?>

<div class="container py-5">
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>home" class="text-decoration-none text-muted">Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>home/catalogo" class="text-decoration-none text-muted">Catálogo</a></li>
            <?php if(!empty($producto['categoria_web'])): ?>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>home/catalogo?categoria=<?= urlencode($producto['categoria_web']) ?>" class="text-decoration-none text-muted"><?= $producto['categoria_web'] ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active text-cenco-indigo fw-bold" aria-current="page">Producto</li>
        </ol>
    </nav>

    <div class="row g-5">
        
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative bg-white h-100" style="min-height: 400px;">
                <div class="d-flex align-items-center justify-content-center h-100 p-5">
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($nombre) ?>" class="img-fluid" style="max-height: 400px; object-fit: contain;">
                </div>
                <?php if($stock <= 0): ?>
                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-white bg-opacity-75">
                        <span class="badge bg-secondary fs-3 shadow px-4 py-3">AGOTADO</span>
                    </div>
                <?php endif; ?>
                
                <span class="position-absolute top-0 start-0 m-4 badge bg-cenco-green shadow fs-6 py-2 px-3 <?= $enCarro ? 'd-inline-block' : 'd-none' ?>" id="badge-llevas-detalle">
                    <i class="bi bi-bag-check-fill me-1"></i> Tienes <span id="count-detalle"><?= $cantEnCarro ?></span> en el carro
                </span>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="ps-lg-4">
                <div class="mb-3">
                    <span class="badge bg-light text-dark border fw-bold text-uppercase ls-1 mb-2"><?= htmlspecialchars($marca) ?></span>
                    <h2 class="fw-bold text-cenco-indigo lh-sm mb-2"><?= htmlspecialchars($nombre) ?></h2>
                    <small class="text-muted">COD: <?= $sku ?></small>
                </div>

                <div class="d-flex align-items-center gap-3 mb-4 border-bottom pb-4">
                    <h1 class="fw-black text-cenco-red mb-0">$<?= $precio ?></h1>
                    <?php if($stock > 0): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill">
                            <i class="bi bi-check-circle-fill me-1"></i> Disponible
                        </span>
                    <?php else: ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-2 rounded-pill">
                            <i class="bi bi-x-circle-fill me-1"></i> Sin Stock
                        </span>
                    <?php endif; ?>
                </div>

                <?php if(!empty($desc)): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark">Descripción</h6>
                        <p class="text-muted text-justify"><?= nl2br(htmlspecialchars($desc)) ?></p>
                    </div>
                <?php endif; ?>

                <div class="bg-light p-4 rounded-4 border mb-4">
                    <?php if($stock > 0): ?>
                        <form id="form-add-detalle" onsubmit="agregarAlCarrito(event, this, <?= $producto['id'] ?>)">
                            <input type="hidden" name="id" value="<?= $producto['id'] ?>">
                            <input type="hidden" name="nombre" value="<?= htmlspecialchars($nombre) ?>">
                            <input type="hidden" name="precio" value="<?= $producto['precio'] ?>">
                            <input type="hidden" name="imagen" value="<?= $img ?>">
                            
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
                        <button class="btn btn-secondary btn-lg w-100 rounded-pill" disabled>No Disponible</button>
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
                                    <li><strong>Categoría:</strong> <?= $producto['categoria_web'] ?? 'General' ?></li>
                                    <li><strong>Subcategoría:</strong> <?= $producto['subcategoria'] ?? 'N/A' ?></li>
                                    <li><strong>Formato:</strong> Unidad</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold text-cenco-indigo" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo">
                                <i class="bi bi-truck me-2 text-cenco-green"></i> Despacho y Retiro
                            </button>
                        </h2>
                        <div id="flush-collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionProduct">
                            <div class="accordion-body small text-muted">
                                Envíos a toda la región en 24-48 horas hábiles. Retiro en tienda disponible sin costo en sucursales habilitadas.
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php if (!empty($relacionados)): ?>
    <div class="mt-5 pt-5 border-top">
        <h3 class="fw-bold text-cenco-indigo mb-4">También te podría interesar</h3>
        
        <div class="row row-cols-2 row-cols-md-4 g-4">
            <?php foreach ($relacionados as $rel): 
                $rImg = !empty($rel['imagen']) ? (strpos($rel['imagen'], 'http') === 0 ? $rel['imagen'] : BASE_URL . 'img/productos/' . $rel['imagen']) : BASE_URL . 'img/no-image.png';
                $rNombre = !empty($rel['nombre_web']) ? $rel['nombre_web'] : $rel['nombre'];
            ?>
            <div class="col">
                <a href="<?= BASE_URL ?>home/producto?id=<?= $rel['id'] ?>" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm transition-hover rounded-4 overflow-hidden">
                        <div class="p-3 bg-white text-center" style="height: 180px;">
                            <img src="<?= $rImg ?>" class="h-100 w-100 object-fit-contain" alt="<?= htmlspecialchars($rNombre) ?>">
                        </div>
                        <div class="card-body bg-light">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;"><?= htmlspecialchars($rel['marca'] ?? '') ?></small>
                            <h6 class="card-title text-dark fw-bold text-truncate mb-2"><?= htmlspecialchars($rNombre) ?></h6>
                            <span class="text-cenco-red fw-black">$<?= number_format($rel['precio'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
    // Pequeño script para actualizar la vista de detalle cuando se agrega al carro
    // (Sobrescribe la función global solo para esta vista si es necesario, o usa la lógica global)
    
    // NOTA: Asegúrate de que tu función global 'agregarAlCarrito' llame a 'actualizarInterfazDetalle' si existe.
    // Si no, la actualización del navbar funciona, pero los botones locales no cambiarán hasta refrescar.
    
    // Para simplificar, si usas el mismo JS que en el catálogo, solo necesitamos que los IDs coincidan.
    // He usado IDs como 'card-count-detalle' y 'badge-llevas-detalle' que deberíamos manejar en tu JS principal
    // o simplemente recargar la página tras agregar (menos elegante).
    
    // Lo ideal: Tu JS 'agregarAlCarrito' debería detectar si existe el elemento 'controles-detalle' y mostrarlo.
</script>