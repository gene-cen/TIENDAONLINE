<?php 
// 1. CARGA DE DEPENDENCIAS
include __DIR__ . '/../componentes/ui/marca_showcase.php'; 
?>
<link rel="stylesheet" href="<?= BASE_URL ?>css/shop/home.css">

<?php include __DIR__ . '/partials/franja_notificacion.php'; ?>

<div class="container-fluid px-3 px-xl-5 my-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="position-relative">
                <form action="<?= BASE_URL ?>home/buscar" method="GET" class="d-flex shadow-sm rounded-pill bg-white overflow-hidden border border-1 border-secondary border-opacity-25" autocomplete="off">
                    <input type="text" name="q" id="inputBusqueda" class="form-control border-0 shadow-none ps-4 py-3 fs-5" placeholder="¿Qué estás buscando hoy?" required onkeyup="buscarPredictivo(this.value)">
                    <button type="submit" class="btn btn-cenco-green rounded-pill px-5 fw-bold d-flex align-items-center m-1 fs-5 text-white">
                        <i class="bi bi-search me-2 d-none d-sm-inline"></i> Buscar
                    </button>
                </form>
                <ul id="lista-predictiva" class="list-group position-absolute w-100 shadow-lg d-none" style="z-index: 1000; top: 100%; left: 0; border-radius: 0 0 15px 15px; overflow: hidden;"></ul>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid carousel-container px-3 px-xl-5 mb-5">
    <div id="carruselPrincipal" class="carousel slide shadow-sm overflow-hidden" style="border-radius: 1.5rem;" data-bs-ride="carousel" data-bs-pause="false">
        <?php if (!empty($bannersHome)): ?>
            <div class="carousel-indicators">
                <?php foreach ($bannersHome as $index => $banner): ?>
                    <button type="button" data-bs-target="#carruselPrincipal" data-bs-slide-to="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>"></button>
                <?php endforeach; ?>
            </div>
            <div class="carousel-inner bg-light">
                <?php foreach ($bannersHome as $index => $banner):
                    $ruta_img = str_starts_with($banner['ruta_imagen'], 'http') ? $banner['ruta_imagen'] : BASE_URL . ltrim($banner['ruta_imagen'], '/');
                ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>" data-bs-interval="6000">
                        <?php if (!empty($banner['palabra_clave'])): ?>
                            <a href="<?= BASE_URL ?>home/catalogo?coleccion=<?= urlencode($banner['palabra_clave']) ?>"><img src="<?= $ruta_img ?>" class="d-block w-100 banner-img" alt="<?= htmlspecialchars($banner['titulo'] ?? '') ?>"></a>
                        <?php elseif (!empty($banner['enlace'])): ?>
                            <?php $link_destino = str_starts_with($banner['enlace'], 'http') ? $banner['enlace'] : BASE_URL . ltrim($banner['enlace'], '/'); ?>
                            <a href="<?= $link_destino ?>"><img src="<?= $ruta_img ?>" class="d-block w-100 banner-img" alt="<?= htmlspecialchars($banner['titulo'] ?? '') ?>"></a>
                        <?php else: ?>
                            <img src="<?= $ruta_img ?>" class="d-block w-100 banner-img" alt="<?= htmlspecialchars($banner['titulo'] ?? '') ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($bannersHome) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#carruselPrincipal" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));"></span></button>
                <button class="carousel-control-next" type="button" data-bs-target="#carruselPrincipal" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));"></span></button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="container-fluid px-3 px-xl-5 mb-5 mt-5">
    <div class="d-flex align-items-center mb-4">
        <h3 class="fw-black text-cenco-indigo border-start border-5 border-warning ps-3 mb-0 ls-1">DESCUBRE NUESTRAS CATEGORÍAS</h3>
    </div>
    <div class="row row-cols-2 row-cols-sm-2 row-cols-md-4 g-3 g-md-4">
        <?php
        $categoriasPermitidas = ['Bebidas y Refrescos', 'Congelados', 'Despensa', 'Galletas y Golosinas', 'Conservas', 'Limpieza y Aseo', 'Vinos y Licores', 'Mascotas'];
        $categoriasGrid = array_filter($categorias ?? [], function ($cat) use ($categoriasPermitidas) {
            $nombreDB = trim($cat['nombre']); foreach ($categoriasPermitidas as $permitida) { if (strcasecmp($nombreDB, $permitida) === 0) return true; } return false;
        });

        if (!empty($categoriasGrid)): foreach ($categoriasGrid as $cat):
                $nombreCat = $cat['nombre'];
                $urlImg = BASE_URL . "img/categorias/" . $nombreCat . ".jpg";
        ?>
                <div class="col">
                    <a href="<?= BASE_URL ?>home/catalogo?categoria=<?= urlencode($nombreCat) ?>" class="text-decoration-none text-dark">
                        <div class="card h-100 border-0 shadow-sm overflow-hidden text-center hover-scale transition-hover rounded-4">
                            <div class="d-flex align-items-center justify-content-center p-3" style="height: 220px; background-color: #f8f9fa;">
                                <img src="<?= $urlImg ?>" alt="<?= htmlspecialchars($nombreCat) ?>" class="img-fluid rounded-3" style="max-height: 100%; max-width: 100%; object-fit: contain;" onerror="this.style.display='none'; this.parentNode.innerHTML='<i class=\'bi bi-image text-muted fs-1\'></i>';">
                            </div>
                            <div class="card-body p-3 bg-white">
                                <h6 class="fw-bold mb-0 text-uppercase text-cenco-indigo" style="font-size:1rem; letter-spacing: 0.5px;"><?= htmlspecialchars($nombreCat) ?></h6>
                            </div>
                        </div>
                    </a>
                </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<div class="container-fluid px-3 px-xl-5 mb-5 mt-5">
    <div class="row">
        <div class="col-12">
            <a href="<?= BASE_URL ?>home/locales" class="d-block text-decoration-none rounded-4 overflow-hidden shadow hover-scale transition-hover">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between p-4 p-md-4 w-100 position-relative" style="background-color: #e53935; overflow: hidden;">
                    <div class="position-absolute w-100 h-100" style="background-image: radial-gradient(circle, #ffffff20 2px, transparent 2px); background-size: 20px 20px; top:0; left:0;"></div>
                    <div class="text-center text-md-start mb-3 mb-md-0 position-relative z-1">
                        <h2 class="fw-black text-white mb-1" style="font-size: 2.2rem; letter-spacing: -1px;">
                            ¡Retira tu pedido <span class="text-warning">gratis</span> en nuestros locales!
                        </h2>
                        <h5 class="text-light opacity-75 mb-0 fw-bold">Servicio exclusivo en sucursales de La Calera y Villa Alemana</h5>
                    </div>
                    <div class="position-relative z-1 mt-3 mt-md-0">
                        <button class="btn btn-light text-danger btn-lg fw-bold rounded-pill shadow px-5">Conócelos aquí <i class="bi bi-chevron-right ms-1"></i></button>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<div id="catalogo-rapido" class="d-flex justify-content-between align-items-center mb-4 container-fluid px-3 px-xl-5 mt-5">
    <h3 class="fw-black text-cenco-indigo border-start border-5 border-warning ps-3 mb-0 ls-1">NUEVOS INGRESOS</h3>
    <a href="<?= BASE_URL ?>home/catalogo" class="btn btn-outline-cenco-indigo rounded-pill fw-bold px-4 transition-hover">Ver Todo <i class="bi bi-arrow-right"></i></a>
</div>

<div class="container-fluid px-3 px-xl-5 mb-5">
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3 g-md-4 mb-5">
        <?php if (empty($productos)): ?>
            <div class="col-12"><div class="alert bg-white shadow-sm text-center py-5">Pronto novedades.</div></div>
        <?php else: foreach ($productos as $p) { 
                include __DIR__ . '/partials/tarjeta_producto.php'; 
            } endif; ?>
    </div>
</div>

<div class="container-fluid px-3 px-xl-5 mb-5 mt-4">
    <div class="row">
        <div class="col-12">
            <a href="<?= BASE_URL ?>home/catalogo" class="d-block text-decoration-none rounded-4 overflow-hidden shadow hover-scale transition-hover">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between p-4 p-md-4 w-100 position-relative" style="background: linear-gradient(90deg, #0dcaf0 0%, #0aa2c0 100%); overflow: hidden;">
                    <i class="bi bi-truck position-absolute opacity-25" style="font-size: 15rem; right: -50px; top: -50px; color: #ffffff;"></i>
                    <div class="text-center text-md-start mb-3 mb-md-0 position-relative z-1">
                        <h2 class="fw-black text-white mb-0" style="font-size: 2.2rem; letter-spacing: -1px;">
                            ¡Despachos en <span class="text-dark">tiempo récord</span> a toda la zona!
                        </h2>
                    </div>
                    <div class="position-relative z-1 mt-3 mt-md-0">
                        <button class="btn btn-dark text-white btn-lg fw-bold rounded-pill shadow px-5">Pide ahora <i class="bi bi-chevron-right ms-1"></i></button>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<?= renderMarcaShowcase($marcasHome[0] ?? null) ?>

<?php if (!empty($bannersSecundarios)): ?>
    <div class="container-fluid carousel-container px-3 px-xl-5 mb-5 mt-5">
        <div id="carruselSecundario" class="carousel slide shadow-sm overflow-hidden" style="border-radius: 1.5rem;" data-bs-ride="carousel" data-bs-pause="false">
            <div class="carousel-inner bg-light">
                <?php foreach ($bannersSecundarios as $index => $banner):
                    $ruta_img = str_starts_with($banner['ruta_imagen'], 'http') ? $banner['ruta_imagen'] : BASE_URL . ltrim($banner['ruta_imagen'], '/');
                ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>" data-bs-interval="6000">
                        <?php if (!empty($banner['palabra_clave'])): ?>
                            <a href="<?= BASE_URL ?>home/catalogo?coleccion=<?= urlencode($banner['palabra_clave']) ?>"><img src="<?= $ruta_img ?>" class="d-block w-100 banner-img" alt="..."></a>
                        <?php elseif (!empty($banner['enlace'])): ?>
                            <?php $link_destino = str_starts_with($banner['enlace'], 'http') ? $banner['enlace'] : BASE_URL . ltrim($banner['enlace'], '/'); ?>
                            <a href="<?= $link_destino ?>"><img src="<?= $ruta_img ?>" class="d-block w-100 banner-img" alt="..."></a>
                        <?php else: ?>
                            <img src="<?= $ruta_img ?>" class="d-block w-100 banner-img" alt="...">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($bannersSecundarios) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#carruselSecundario" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));"></span></button>
                <button class="carousel-control-next" type="button" data-bs-target="#carruselSecundario" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));"></span></button>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?= renderMarcaShowcase($marcasHome[1] ?? null) ?>

<div class="container-fluid px-3 px-xl-5 mb-5 mt-4">
    <div class="row">
        <div class="col-12">
            <a href="https://wa.me/56946452516" target="_blank" class="d-block text-decoration-none rounded-4 overflow-hidden shadow hover-scale transition-hover">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between p-4 p-md-4 w-100 position-relative" style="background-color: var(--cenco-green); overflow: hidden;">
                    <i class="bi bi-shop position-absolute opacity-25" style="font-size: 15rem; left: -50px; top: -50px; color: #ffffff;"></i>
                    <div class="text-center text-md-end mb-3 mb-md-0 position-relative z-1 w-100 me-md-4">
                        <h2 class="fw-black text-white mb-0" style="font-size: 2.2rem; letter-spacing: -1px;">
                            ¿Tienes un negocio? <span class="text-dark">Cotiza al por mayor</span> con nosotros
                        </h2>
                    </div>
                    <div class="position-relative z-1 flex-shrink-0 mt-3 mt-md-0">
                        <button class="btn btn-dark text-white btn-lg fw-bold rounded-pill shadow px-5">Hablemos por WhatsApp <i class="bi bi-whatsapp ms-1 text-success"></i></button>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<?= renderMarcaShowcase($marcasHome[2] ?? null) ?>

<div class="container-fluid px-3 px-xl-5 mb-5 mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-black text-cenco-indigo border-start border-5 border-warning ps-3 mb-0 ls-1">LOS MÁS VENDIDOS</h3>
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fs-6"><i class="bi bi-star-fill me-1"></i> Top 5</span>
    </div>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3 g-md-4 mb-5">
        <?php if (empty($masVendidos)): ?>
            <div class="col-12 text-center text-muted py-4">Aún no hay suficientes datos.</div>
        <?php else: foreach ($masVendidos as $p) {
                // Aquí usamos la misma tarjeta. Si le quieres agregar el badge "TOP", puedes hacerlo condicional en tarjeta_producto.php
                include __DIR__ . '/partials/tarjeta_producto.php'; 
            } endif; ?>
    </div>
</div>

<?= renderMarcaShowcase($marcasHome[3] ?? null) ?>

<div class="container-fluid px-3 px-xl-5 mb-5 mt-4">
    <div class="row">
        <div class="col-12">
            <a href="<?= BASE_URL ?>home/catalogo?max_price=1000" class="d-block text-decoration-none rounded-4 overflow-hidden shadow hover-scale transition-hover">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between p-4 p-md-5 w-100 position-relative" style="background: linear-gradient(45deg, #ff9800 0%, #ffb74d 100%); overflow: hidden;">
                    <i class="bi bi-coin position-absolute opacity-25" style="font-size: 15rem; left: 10%; top: -50px; color: #ffffff;"></i>
                    <i class="bi bi-piggy-bank position-absolute opacity-25" style="font-size: 12rem; right: 5%; top: 10px; color: #ffffff;"></i>
                    
                    <div class="text-center text-md-start mb-3 mb-md-0 position-relative z-1">
                        <h2 class="fw-black text-white mb-0" style="font-size: 2.8rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);">
                            ¡Miles de productos por menos de luca! 💸
                        </h2>
                        <h5 class="text-dark fw-bold mt-2 bg-white d-inline-block px-3 py-1 rounded-pill shadow-sm">Aprovecha precios imbatibles a $1.000 o menos</h5>
                    </div>
                    <div class="position-relative z-1 mt-3 mt-md-0">
                        <button class="btn btn-dark text-white btn-lg fw-bold rounded-pill shadow-lg px-5 fs-5">Descubrir ofertas <i class="bi bi-bag-heart-fill ms-2 text-warning"></i></button>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<?= renderMarcaShowcase($marcasHome[4] ?? null) ?>

<?php include __DIR__ . '/partials/info_cards.php'; ?>

<script>var BASE_URL = '<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>js/home.js"></script>