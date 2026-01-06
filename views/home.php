<?php if (isset($_GET['msg']) && $_GET['msg'] == 'compra_exitosa'): ?>
    <div class="alert alert-success text-center py-4 mb-5 shadow-sm border-0 rounded-3">
        <h2 class="fw-bold text-success"><i class="bi bi-bag-check-fill"></i> ¡Gracias por tu compra!</h2>
        <p class="fs-5 mb-1">Tu pedido <strong>#<?= htmlspecialchars($_GET['folio'] ?? '0') ?></strong> ha sido generado correctamente.</p>
        <small class="text-muted">Hemos enviado el detalle a tu correo electrónico.</small>
    </div>
<?php endif; ?>

<div class="row align-items-center mb-5">
    <div class="col-md-8">
        <h1 class="display-5 fw-bold text-primary">
            <?php if (isset($_SESSION['user_nombre'])): ?>
                ¡Hola, <?= htmlspecialchars($_SESSION['user_nombre']) ?>! 👋
            <?php else: ?>
                ¡Bienvenido a CENCOCAL! 🚀
            <?php endif; ?>
        </h1>
        <p class="lead text-muted">Explora nuestro catálogo de repuestos y suministros.</p>
    </div>
    <div class="col-md-4 text-center">
        <img src="<?= BASE_URL ?>img/mascota.png" alt="Cencocalín" class="img-fluid" style="max-height: 200px;">
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <h2 class="text-secondary">⚙️</h2>
                <h5 class="card-title mt-3">Repuestos</h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <h2 class="text-secondary">🛢️</h2>
                <h5 class="card-title mt-3">Lubricantes</h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <h2 class="text-secondary">🔧</h2>
                <h5 class="card-title mt-3">Herramientas</h5>
            </div>
        </div>
    </div>
</div>

<h3 class="mb-4 pb-2 border-bottom text-primary">Productos Disponibles</h3>

<div class="row">
    <?php if (empty($productos)): ?>
        <div class="col-12">
            <div class="alert alert-info">
                No hay productos cargados en la base de datos todavía.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($productos as $p): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    
                    <div class="card-img-top bg-white d-flex align-items-center justify-content-center border-bottom" style="height: 200px; overflow: hidden;">
                        <?php 
                            // 1. Verificamos si hay imagen definida
                            if (!empty($p->imagen)) {
                                // 2. Si empieza con http, es del ERP (URL externa)
                                if (strpos($p->imagen, 'http') === 0) {
                                    $imgSrc = $p->imagen;
                                } 
                                // 3. Si no, es una imagen local (subida desde el admin)
                                else {
                                    $imgSrc = BASE_URL . 'img/productos/' . $p->imagen;
                                }
                                
                                // Renderizamos la etiqueta img
                                echo '<img src="' . $imgSrc . '" class="img-fluid" alt="' . htmlspecialchars($p->nombre) . '" style="max-height: 100%; object-fit: contain; padding: 15px;">';
                            } 
                            // 4. Si no hay imagen, mostramos el placeholder (cámara)
                            else {
                                echo '<span class="text-muted fs-1">📷</span>';
                            }
                        ?>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= htmlspecialchars($p->nombre) ?></h5>
                        
                        <?php if(!empty($p->categoria)): ?>
                            <small class="text-muted mb-2 d-block"><?= htmlspecialchars($p->categoria) ?></small>
                        <?php endif; ?>

                        <h6 class="text-primary fw-bold mb-3 fs-4">
                            $<?= number_format($p->precio, 0, ',', '.') ?>
                        </h6>

                        <form action="<?= BASE_URL ?>carrito/agregar" method="POST" class="mt-auto">
                            <input type="hidden" name="id" value="<?= $p->id ?>">
                            <input type="hidden" name="nombre" value="<?= $p->nombre ?>">
                            <input type="hidden" name="precio" value="<?= $p->precio ?>">
                            <input type="hidden" name="imagen" value="<?= $p->imagen ?>">

                            <div class="d-grid gap-2">
                                <?php if(($p->stock ?? 1) > 0): ?>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-cart-plus"></i> Agregar
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-secondary" disabled>
                                        Sin Stock
                                    </button>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-outline-secondary btn-sm">Ver Detalle</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>