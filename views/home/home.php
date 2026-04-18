<?php
// 1. CARGA DE DEPENDENCIAS
include __DIR__ . '/../componentes/ui/marca_showcase.php';

// Función Helper para crear slugs
function crearSlug($texto)
{
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', str_replace(' ', '-', $texto)), '-'));
}
?>
<link rel="stylesheet" href="<?= BASE_URL ?>css/shop/home.css">

<style>
    .fw-black,
    .titulo-seccion {
        font-weight: 900 !important;
    }
</style>

<?php include __DIR__ . '/partials/franja_notificacion.php'; ?>

<div class="container-fluid px-3 px-xl-5 my-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="position-relative">
                <form onsubmit="event.preventDefault(); window.location.href='<?= BASE_URL ?>buscar/' + document.getElementById('inputBusqueda').value.trim().toLowerCase().replace(/\s+/g, '-');" class="d-flex shadow-sm rounded-pill bg-white overflow-hidden border border-1 border-secondary border-opacity-25" autocomplete="off">
                    <input type="text" id="inputBusqueda" class="form-control border-0 shadow-none ps-4 py-3 fs-5" placeholder="¿Qué estás buscando hoy?" required onkeyup="buscarPredictivo(this.value)">
                    <button type="submit" class="btn btn-cenco-green rounded-pill px-5 fw-black d-flex align-items-center m-1 fs-5 text-white">
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
                            <a href="<?= BASE_URL ?>coleccion/<?= crearSlug($banner['palabra_clave']) ?>"><img src="<?= $ruta_img ?>" class="d-block w-100 banner-img" alt="..."></a>
                        <?php elseif (!empty($banner['enlace'])): ?>
                            <?php $link_destino = str_starts_with($banner['enlace'], 'http') ? $banner['enlace'] : BASE_URL . ltrim($banner['enlace'], '/'); ?>
                            <a href="<?= $link_destino ?>"><img src="<?= $ruta_img ?>" class="d-block w-100 banner-img" alt="..."></a>
                        <?php else: ?>
                            <img src="<?= $ruta_img ?>" class="d-block w-100 banner-img" alt="...">
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
        <h3 class="titulo-seccion text-cenco-indigo border-start border-5 border-warning ps-3 mb-0 ls-1">DESCUBRE NUESTRAS CATEGORÍAS</h3>
    </div>
    <div class="row row-cols-2 row-cols-sm-2 row-cols-md-4 g-3 g-md-4">
        <?php
        $categoriasPermitidas = ['Bebidas y Refrescos', 'Congelados', 'Despensa', 'Golosinas', 'Conservas', 'Aseo', 'Vinos y Licores', 'Mascotas'];
        $categoriasGrid = array_filter($categorias ?? [], function ($cat) use ($categoriasPermitidas) {
            $nombreDB = trim($cat['nombre']);
            foreach ($categoriasPermitidas as $permitida) {
                if (strcasecmp($nombreDB, $permitida) === 0) return true;
            }
            return false;
        });

        if (!empty($categoriasGrid)): foreach ($categoriasGrid as $cat):
                $nombreCat = $cat['nombre'];
                $urlImg = BASE_URL . "img/categorias/" . $nombreCat . ".jpg";
        ?>
                <div class="col">
                    <a href="<?= BASE_URL ?>categoria/<?= crearSlug($nombreCat) ?>" class="text-decoration-none text-dark">
                        <div class="card h-100 border-0 shadow-sm overflow-hidden text-center hover-scale transition-hover rounded-4">
                            <div class="d-flex align-items-center justify-content-center p-3" style="height: 220px; background-color: #f8f9fa;">
                                <img src="<?= $urlImg ?>" alt="<?= htmlspecialchars($nombreCat) ?>" class="img-fluid rounded-3" style="max-height: 100%; max-width: 100%; object-fit: contain;" onerror="this.style.display='none'; this.parentNode.innerHTML='<i class=\'bi bi-image text-muted fs-1\'></i>';">
                            </div>
                            <div class="card-body p-3 bg-white">
                                <h6 class="fw-black mb-0 text-uppercase text-cenco-indigo" style="font-size:1rem; letter-spacing: 0.5px;"><?= htmlspecialchars($nombreCat) ?></h6>
                            </div>
                        </div>
                    </a>
                </div>
        <?php endforeach;
        endif; ?>
    </div>
</div>

<div class="container-fluid px-3 px-xl-5 mb-5 mt-4">
    <div class="row">
        <div class="col-12">
            <a href="<?= BASE_URL ?>home/locales" class="d-block text-decoration-none rounded-4 overflow-hidden shadow hover-scale transition-hover">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between p-4 p-md-4 w-100 position-relative" style="background: linear-gradient(135deg, #1A36B6 0%, #11257a 100%); overflow: hidden;">
                    <i class="bi bi-headset position-absolute opacity-10" style="font-size: 15rem; right: -20px; top: -40px; color: #ffffff;"></i>

                    <div class="text-center text-md-start mb-3 mb-md-0 position-relative z-1 w-100 ms-md-4">
                        <h2 class="fw-black text-white mb-1" style="font-size: 2.2rem; letter-spacing: -1px;">
                            ¡Visita nuestras sucursales y <span class="text-warning">asistimos tu compra</span> online!
                        </h2>
                        <h5 class="text-light opacity-75 mb-0 fw-bold">*Servicio exclusivo en sucursales de La Calera y Villa Alemana</h5>
                    </div>

                    <div class="position-relative z-1 flex-shrink-0 mt-3 mt-md-0 me-md-4">
                        <button class="btn btn-warning text-dark btn-lg fw-black rounded-pill shadow px-5">Ver Sucursales <i class="bi bi-geo-alt-fill ms-1"></i></button>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<div class="container-fluid px-3 px-xl-5 mb-5 mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="titulo-seccion text-cenco-indigo border-start border-5 border-warning ps-3 mb-0 ls-1">DESCUBRE NUESTROS PRECIOS</h3>
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fs-6 fw-black"><i class="bi bi-tags-fill me-1"></i> Increíbles</span>
    </div>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3 g-md-4 mb-5">
        <?php if (empty($masVendidos)): ?>
            <div class="col-12 text-center text-muted py-4">Aún no hay suficientes datos.</div>
        <?php else: foreach ($masVendidos as $p) {
                $mostrarBadgeNuevo = false;
                include __DIR__ . '/partials/tarjeta_producto.php';
            }
        endif; ?>
    </div>
</div>

<div class="container-fluid px-3 px-xl-5 mb-5 mt-5">
    <div class="row">
        <div class="col-12">
            <a href="<?= BASE_URL ?>home/locales" class="d-block text-decoration-none rounded-4 overflow-hidden shadow hover-scale transition-hover">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between p-4 p-md-4 w-100 position-relative" style="background: linear-gradient(135deg, #85C226 0%, #649618 100%); overflow: hidden;">
                    <div class="position-absolute w-100 h-100" style="background-image: radial-gradient(circle, #ffffff20 2px, transparent 2px); background-size: 20px 20px; top:0; left:0;"></div>
                    <div class="text-center text-md-start mb-3 mb-md-0 position-relative z-1 ms-md-4">
                        <h2 class="fw-black text-white mb-1" style="font-size: 2.2rem; letter-spacing: -1px;">
                            ¡Retira tu pedido <span style="color:#1A36B6;">gratis</span> en nuestros locales!
                        </h2>
                        <h5 class="text-light opacity-75 mb-0 fw-bold">Servicio exclusivo en sucursales de La Calera y Villa Alemana</h5>
                    </div>
                    <div class="position-relative z-1 mt-3 mt-md-0 me-md-4">
                        <button class="btn btn-dark text-white btn-lg fw-black rounded-pill shadow px-5">Conócelos aquí <i class="bi bi-chevron-right ms-1"></i></button>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<?php if (isset($marcasHome[0]) && $marcasHome[0] !== null) echo renderMarcaShowcase($marcasHome[0]); ?>

<div class="container-fluid px-3 px-xl-5 mb-5 mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="titulo-seccion text-cenco-indigo border-start border-5 border-warning ps-3 mb-0 ls-1">LOS MÁS VENDIDOS</h3>
    </div>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3 g-md-4 mb-5">
        <?php if (empty($masVendidos)): ?>
            <div class="col-12 text-center text-muted py-4">Aún no hay suficientes datos.</div>
        <?php else: foreach ($masVendidos as $p) {
                $mostrarBadgeNuevo = false;
                include __DIR__ . '/partials/tarjeta_producto.php';
            }
        endif; ?>
    </div>
</div>

<?php if (isset($marcasHome[1]) && $marcasHome[1] !== null) echo renderMarcaShowcase($marcasHome[1]); ?>

<div id="catalogo-rapido" class="d-flex justify-content-between align-items-center mb-4 container-fluid px-3 px-xl-5 mt-5">
    <h3 class="titulo-seccion text-cenco-indigo border-start border-5 border-warning ps-3 mb-0 ls-1">NUEVOS INGRESOS</h3>
    <a href="<?= BASE_URL ?>home/catalogo" class="btn btn-outline-cenco-indigo rounded-pill fw-black px-4 transition-hover">Ver Todo <i class="bi bi-arrow-right"></i></a>
</div>

<div class="container-fluid px-3 px-xl-5 mb-5">
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3 g-md-4 mb-5">
        <?php if (empty($productos)): ?>
            <div class="col-12">
                <div class="alert bg-white shadow-sm text-center py-5">Pronto novedades.</div>
            </div>
        <?php else: foreach ($productos as $p) {
                $mostrarBadgeNuevo = true;
                include __DIR__ . '/partials/tarjeta_producto.php';
            }
        endif; ?>
    </div>
</div>

<?php if (isset($marcasHome[2]) && $marcasHome[2] !== null) echo renderMarcaShowcase($marcasHome[2]); ?>

<?php if (!empty($bannersSecundarios)): ?>
    <div class="container-fluid carousel-container px-3 px-xl-5 mb-5 mt-5">
        <div id="carruselSecundario" class="carousel slide shadow-sm overflow-hidden" style="border-radius: 1.5rem;" data-bs-ride="carousel" data-bs-pause="false">
            <div class="carousel-inner bg-light">
                <?php foreach ($bannersSecundarios as $index => $banner):
                    $ruta_img = str_starts_with($banner['ruta_imagen'], 'http') ? $banner['ruta_imagen'] : BASE_URL . ltrim($banner['ruta_imagen'], '/');
                ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>" data-bs-interval="6000">
                        <?php if (!empty($banner['palabra_clave'])): ?>
                            <a href="<?= BASE_URL ?>coleccion/<?= crearSlug($banner['palabra_clave']) ?>"><img src="<?= $ruta_img ?>" class="d-block w-100 banner-img" alt="..."></a>
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

<?php if (isset($marcasHome[3]) && $marcasHome[3] !== null) echo renderMarcaShowcase($marcasHome[3]); ?>

<div class="container-fluid px-3 px-xl-5 mb-5 mt-4">
    <div class="row">
        <div class="col-12">
            <a href="<?= BASE_URL ?>home/catalogo?max_price=1000" class="d-block text-decoration-none rounded-4 overflow-hidden shadow hover-scale transition-hover position-relative" style="background: linear-gradient(90deg, #FFAB00 0%, #FF8F00 100%);">
                <i class="bi bi-coin position-absolute" style="font-size: 14rem; color: #ffffff; opacity: 0.15; left: 5%; top: -50px; transform: rotate(-15deg);"></i>
                <i class="bi bi-piggy-bank-fill position-absolute d-none d-md-block" style="font-size: 16rem; color: #ffffff; opacity: 0.1; right: -20px; top: -60px; transform: rotate(10deg);"></i>
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between p-4 p-md-4 w-100 position-relative z-1">
                    <div class="text-center text-md-start mb-3 mb-md-0 ms-md-2">
                        <h2 class="fw-bold text-white mb-2" style="font-size: 2rem; text-shadow: 1px 1px 3px rgba(0,0,0,0.1);">
                            ¡Miles de productos por menos de luca! 💸
                        </h2>
                        <span class="bg-white text-dark fw-bold px-3 py-1 rounded-pill small shadow-sm d-inline-block mt-1" style="font-size: 0.85rem;">
                            Aprovecha precios imbatibles a $1.000 o menos
                        </span>
                    </div>
                    <div class="flex-shrink-0 mt-2 mt-md-0 me-md-2">
                        <button class="btn btn-dark btn-lg fw-bold rounded-pill shadow px-4" style="font-size: 0.95rem;">
                            Descubrir ofertas <i class="bi bi-bag-check-fill text-warning ms-1"></i>
                        </button>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<?php if (isset($marcasHome[4]) && $marcasHome[4] !== null) echo renderMarcaShowcase($marcasHome[4]); ?>

<div class="container-fluid px-3 px-xl-5 mb-5 mt-4">
    <div class="row">
        <div class="col-12">
            <a href="https://wa.me/56946452516" target="_blank" class="d-block text-decoration-none rounded-4 overflow-hidden shadow hover-scale transition-hover">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between p-4 p-md-4 w-100 position-relative" style="background: linear-gradient(135deg, #1A36B6 0%, #11257a 100%); overflow: hidden;">
                    <i class="bi bi-shop position-absolute opacity-25" style="font-size: 15rem; left: -50px; top: -50px; color: #ffffff;"></i>
                    <div class="text-center text-md-end mb-3 mb-md-0 position-relative z-1 w-100 me-md-4">
                        <h2 class="fw-black text-white mb-0" style="font-size: 2.2rem; letter-spacing: -1px;">
                            ¿Tienes un negocio? <span class="text-warning">Cotiza al por mayor</span> con nosotros
                        </h2>
                    </div>
                    <div class="position-relative z-1 flex-shrink-0 mt-3 mt-md-0 me-md-4">
                        <button class="btn btn-warning text-dark btn-lg fw-black rounded-pill shadow px-5">Hablemos por WhatsApp <i class="bi bi-whatsapp ms-1 text-success"></i></button>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/info_cards.php'; ?>

<script>
    var BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>js/home.js"></script>