<?php
$id = $p['id'];
$nombre = !empty($p['nombre_web']) ? $p['nombre_web'] : (isset($p['nombre']) ? $p['nombre'] : 'Producto');
$img = !empty($p['imagen']) ? $p['imagen'] : '';
$imgSrc = !empty($img) ? (strpos($img, 'http') === 0 ? $img : BASE_URL . 'img/productos/' . $img) : BASE_URL . 'img/no-image.png';
$enCarro = isset($_SESSION['carrito'][$id]);
$cant = $enCarro ? $_SESSION['carrito'][$id]['cantidad'] : 0;
$border = $enCarro ? 'border-cenco-green shadow border-2' : 'border-light';

$esObjeto = is_object($p);
$stockDisponible = $esObjeto ? (int)($p->stock_web ?? $p->stock ?? 0) : (int)($p['stock_web'] ?? $p['stock'] ?? 0);
$estaAgotado = ($stockDisponible <= 0);

$imgHeight = $alturaImagen ?? '180px';
?>
<div class="col">
    <div class="card h-100 rounded-4 overflow-hidden shadow-sm hover-scale transition-hover border <?= $border ?> <?= $estaAgotado ? 'opacity-75 bg-light' : 'bg-white' ?>" id="card-prod-<?= $id ?>">
        
        <a href="<?= BASE_URL ?>home/producto?id=<?= $id ?>" class="text-decoration-none">
            <div class="position-relative bg-white p-3 d-flex align-items-center justify-content-center" style="height: <?= $imgHeight ?>; <?= $estaAgotado ? 'filter: grayscale(100%);' : '' ?>">
                
                <?php if (isset($mostrarBadgeNuevo) && $mostrarBadgeNuevo && !$estaAgotado): ?>
                    <span class="position-absolute top-0 start-0 m-2 badge bg-danger shadow-sm fw-black z-2 px-2 py-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                        <i class="bi bi-stars"></i> ¡NUEVO!
                    </span>
                <?php endif; ?>

                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($nombre) ?>" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                
                <?php if ($estaAgotado): ?>
                    <span class="position-absolute top-50 start-50 translate-middle badge bg-danger fs-6 shadow z-2">AGOTADO</span>
                <?php endif; ?>
            </div>
        </a>

        <div class="card-body d-flex flex-column p-3 bg-white border-top border-light">
            <small class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.7rem;"><?= htmlspecialchars($p['marca'] ?? '') ?></small>
            
            <a href="<?= BASE_URL ?>home/producto?id=<?= $id ?>" class="text-decoration-none">
                <h6 class="card-title fw-black text-dark lh-sm mb-2 text-truncate-2" style="font-size: 0.95rem; height: 40px; overflow: hidden;" title="<?= htmlspecialchars($nombre) ?>">
                    <?= htmlspecialchars($nombre) ?>
                </h6>
            </a>
            
            <div class="mb-2" style="font-size: 0.75rem;">
                <?php if ($estaAgotado): ?>
                    <span class="text-danger fw-bold"><i class="bi bi-x-circle me-1"></i>Agotado</span>
                <?php elseif ($stockDisponible <= 10): ?>
                    <span class="text-danger fw-black animate__animated animate__pulse animate__infinite d-inline-block">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>¡Quedan pocas unidades!
                    </span>
                <?php else: ?>
                    <span class="text-success fw-bold">
                        <i class="bi bi-check-circle-fill me-1"></i>Stock disponible
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="mt-auto pt-2 d-flex justify-content-between align-items-center border-top border-light">
                <div class="d-flex flex-column">
                    <span class="fw-black text-cenco-red fs-5" style="letter-spacing: -0.5px;">$<?= number_format($p['precio'] ?? 0, 0, ',', '.') ?></span>
                </div>

                <div style="min-width: 90px; text-align: right;">
                    <?php if ($estaAgotado): ?>
                        <button class="btn btn-secondary rounded-circle shadow-sm" style="width:38px;height:38px;" disabled><i class="bi bi-cart-x fs-6"></i></button>
                    <?php else: ?>
                        <form id="form-add-<?= $id ?>" class="<?= $enCarro ? 'd-none' : 'd-block' ?>" 
                              onsubmit="agregarAlCarrito(event, this, <?= $id ?>, <?= $stockDisponible ?>)">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <button class="btn btn-cenco-green rounded-circle shadow-sm d-inline-flex align-items-center justify-content-center transition-hover" style="width:38px;height:38px;">
                                <i class="bi bi-plus-lg fs-5"></i>
                            </button>
                        </form>

                        <div id="controls-<?= $id ?>" class="align-items-center justify-content-end gap-1 <?= $enCarro ? 'd-flex' : 'd-none' ?>">
                            <button class="btn btn-outline-danger rounded-circle p-0 d-flex align-items-center justify-content-center" style="width:32px;height:32px;" 
                                    onclick="gestionarClickTarjeta(<?= $id ?>, 'bajar', <?= $stockDisponible ?>)">
                                <i class="bi bi-dash fs-5"></i>
                            </button>
                            
                            <span class="fw-bold text-cenco-indigo px-1 fs-6" id="card-count-<?= $id ?>"><?= $cant ?></span>
                            
                            <button class="btn btn-cenco-green rounded-circle p-0 d-flex align-items-center justify-content-center" style="width:32px;height:32px;" 
                                    onclick="gestionarClickTarjeta(<?= $id ?>, 'subir', <?= $stockDisponible ?>)">
                                <i class="bi bi-plus fs-5"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>