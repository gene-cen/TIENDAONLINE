<?php
// ==========================================
// FUNCIÓN REUTILIZABLE: SHOWCASE DE MARCAS PREMIUM (PROPORCIONES ORIGINALES + FONDO AZUL TRANSPARENTE)
// ==========================================
if (!function_exists('renderMarcaShowcase')) {
    function renderMarcaShowcase($marca) {
        if (empty($marca) || empty($marca['productos'])) return '';
        
        $urlImgMarca = str_starts_with($marca['ruta_imagen'], 'http') ? $marca['ruta_imagen'] : BASE_URL . ltrim($marca['ruta_imagen'], '/');
        ob_start();
        ?>
        <div class="container-fluid px-3 px-xl-5 mb-5 mt-5">
            <div class="row g-0 rounded-4 shadow-sm overflow-hidden border border-light">
                
                <div class="col-lg-4 col-md-4 d-flex flex-column align-items-center justify-content-center p-4 p-lg-5 position-relative" style="background: linear-gradient(135deg, var(--cenco-indigo) 0%, var(--cenco-green) 100%);">
                    
                    <div class="bg-white rounded-4 p-3 shadow-lg mb-4 hover-scale transition-hover" style="width: 200px; height: 130px; display:flex; align-items:center; justify-content:center;">
                        <img src="<?= $urlImgMarca ?>" alt="<?= htmlspecialchars($marca['nombre']) ?>" class="img-fluid" style="max-height:100%; object-fit:contain;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <span class="fw-black text-cenco-indigo" style="display:none; font-size:1.4rem; text-align:center;"><?= htmlspecialchars($marca['nombre']) ?></span>
                    </div>
                    
                    <h5 class="text-white fw-bold mb-3 text-center" style="font-size: 1.25rem;">Especial de <?= htmlspecialchars($marca['nombre']) ?></h5>
                    
                    <a href="<?= BASE_URL ?>home/catalogo?marca=<?= urlencode($marca['nombre']) ?>" class="btn btn-warning rounded-pill fw-bold px-4 shadow-sm hover-scale transition-hover text-dark" style="font-size: 0.95rem;">
                        Ver todo <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                
                <div class="col-lg-8 col-md-8 p-4 position-relative" style="background-color: rgba(13, 202, 240, 0.08);">
                    <div class="row row-cols-2 row-cols-md-2 row-cols-xl-4 g-3">
                        <?php foreach (array_slice($marca['productos'], 0, 4) as $p):
                            $id = $p['id'];
                            $nombreMostrar = !empty($p['nombre_web']) ? $p['nombre_web'] : (isset($p['nombre']) ? $p['nombre'] : 'Producto');
                            $precio = isset($p['precio']) ? $p['precio'] : 0;
                            $stock = is_object($p) ? (int)($p->stock_web ?? $p->stock ?? 0) : (int)($p['stock_web'] ?? $p['stock'] ?? 0);
                            $imgSrc = !empty($p['imagen']) ? (strpos($p['imagen'], 'http') === 0 ? $p['imagen'] : BASE_URL . 'img/productos/' . $p['imagen']) : BASE_URL . 'img/no-image.png';
                            $enCarro = isset($_SESSION['carrito'][$id]);
                            $cantidadEnCarro = $enCarro ? $_SESSION['carrito'][$id]['cantidad'] : 0;
                            $borderClass = $enCarro ? 'border-cenco-green shadow' : 'border-0 border-white';
                            $bgClass = $enCarro ? 'bg-success-subtle' : 'bg-white';
                        ?>
                            <div class="col">
                                <div class="card h-100 shadow-sm transition-hover rounded-4 overflow-hidden border <?= $borderClass ?>" id="card-prod-<?= $id ?>">
                                    <div class="position-relative <?= $bgClass ?>" id="img-container-<?= $id ?>" style="transition: background-color 0.3s ease;">
                                        <span class="position-absolute top-0 start-0 m-2 badge bg-cenco-green shadow-sm text-white <?= $enCarro ? 'd-inline-block' : 'd-none' ?>" id="badge-llevas-<?= $id ?>" style="z-index: 2;">
                                            <i class="bi bi-bag-check-fill me-1"></i> Llevas <span id="count-<?= $id ?>"><?= $cantidadEnCarro ?></span>
                                        </span>
                                        <img src="<?= $imgSrc ?>" class="card-img-top p-3" style="height:150px; object-fit:contain;" alt="<?= htmlspecialchars($nombreMostrar) ?>">
                                    </div>
                                    <div class="card-body d-flex flex-column p-3 bg-white border-top border-light">
                                        <a href="<?= BASE_URL ?>home/producto?id=<?= $id ?>" class="text-decoration-none">
                                            <h6 class="card-title fw-bold text-dark lh-sm mb-2" style="font-size: 0.85rem; height: 36px; overflow: hidden;" title="<?= htmlspecialchars($nombreMostrar) ?>">
                                                <?= htmlspecialchars($nombreMostrar) ?>
                                            </h6>
                                        </a>
                                        
                                        <div class="mb-2" style="font-size: 0.75rem;">
                                            <?php if ($stock <= 0): ?>
                                                <span class="text-danger fw-bold">Agotado</span>
                                            <?php else: ?>
                                                <span class="<?= $stock <= 8 ? 'text-danger fw-black animate__animated animate__flash animate__infinite' : 'text-success fw-bold' ?>">
                                                    <i class="bi bi-box-seam me-1"></i>Stock: <?= $stock ?> un.
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mt-auto pt-2 d-flex justify-content-between align-items-center border-top border-light">
                                            <div class="d-flex flex-column">
                                                <span class="fw-black text-cenco-red fs-5">$<?= number_format($precio, 0, ',', '.') ?></span>
                                                <?php if (!empty($p['precio_unidad_medida'])): ?>
                                                    <small class="text-muted fw-normal" style="font-size: 0.65rem; margin-top: -3px;"><?= htmlspecialchars($p['precio_unidad_medida']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <div style="min-width: 80px; text-align: right;">
                                                <form id="form-add-<?= $id ?>" class="<?= $enCarro ? 'd-none' : 'd-block' ?>" onsubmit="agregarAlCarrito(event, this, <?= $id ?>)">
                                                    <input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="nombre" value="<?= htmlspecialchars($nombreMostrar) ?>"><input type="hidden" name="precio" value="<?= $precio ?>"><input type="hidden" name="imagen" value="<?= $p['imagen'] ?? '' ?>">
                                                    <button type="submit" class="btn btn-sm btn-cenco-green rounded-circle shadow-sm transition-hover" style="width:34px;height:34px;"><i class="bi bi-plus-lg fs-6"></i></button>
                                                </form>
                                                <div id="controls-<?= $id ?>" class="align-items-center justify-content-end gap-1 <?= $enCarro ? 'd-flex' : 'd-none' ?>">
                                                    <button class="btn btn-sm btn-outline-danger rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:28px;height:28px;" onclick="gestionarClickTarjeta(<?= $id ?>, 'bajar')"><i class="bi bi-dash-lg"></i></button>
                                                    <span class="fw-bold text-cenco-indigo px-1" style="min-width: 15px; text-align: center; font-size:0.9rem;" id="card-count-<?= $id ?>"><?= $cantidadEnCarro ?></span>
                                                    <button class="btn btn-sm btn-cenco-green rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:28px;height:28px;" onclick="gestionarClickTarjeta(<?= $id ?>, 'subir')"><i class="bi bi-plus-lg"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// ==========================================
// 0. SISTEMA DE FRANJAS INICIAL (ALERTA LOGIN/COMPRA)
// ==========================================
$mostrarFranja = false;
$franjaTitle = ''; $franjaText = ''; $franjaImg = 'cencocalin_bienvenida.png';

if (isset($_GET['msg'])) {
    $mostrarFranja = true;
    switch ($_GET['msg']) {
        case 'compra_exitosa': $franjaTitle = '¡Gracias por tu compra!'; $franjaText = 'Hemos recibido tu pedido.'; $franjaImg = 'cencocalin_celebrando_compra.png'; break;
        case 'login_exito': $franjaTitle = '¡Hola de nuevo!'; $franjaText = '¡Estamos felices de tenerte de vuelta!'; $franjaImg = 'cencocalin_bienvenida.png'; break;
        case 'logout_exito': $franjaTitle = '¡Hasta la próxima!'; $franjaText = 'Esperamos verte pronto.'; $franjaImg = 'cencocalin_despidiendo.png'; break;
        default: $mostrarFranja = false; break;
    }
}
?>

<?php if ($mostrarFranja): ?>
    <style>
        .franja-wrapper { max-height: 0; opacity: 0; overflow: hidden; transition: max-height 0.6s ease-in-out, opacity 0.5s ease-in-out; }
        .franja-wrapper.desplegado { max-height: 180px; opacity: 1; }
        .franja-content { display: flex; align-items: center; justify-content: center; gap: 25px; padding: 15px 35px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); margin: 1.5rem auto 1rem auto; max-width: 700px; }
        .franja-img { width: 90px; height: 90px; object-fit: contain; background: transparent; filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.3)); transform: scale(1.15); }
    </style>
    <div class="container-fluid px-3 px-xl-5 franja-wrapper" id="franjaNotificacion">
        <div class="franja-content bg-cenco-indigo text-white">
            <img src="<?= BASE_URL ?>img/cencocalin/<?= $franjaImg ?>" alt="Cencocalin" class="franja-img">
            <div><h5 class="fw-black mb-0 lh-1" style="font-size: 1.3rem;"><?= $franjaTitle ?></h5><span class="small opacity-75 fw-medium" style="font-size: 0.95rem;"><?= $franjaText ?></span></div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const franja = document.getElementById('franjaNotificacion');
            if (franja) { setTimeout(() => { franja.classList.add('desplegado'); }, 150); setTimeout(() => { franja.classList.remove('desplegado'); setTimeout(() => { franja.remove(); }, 600); }, 3500); }
            setTimeout(() => { if (window.history.replaceState) { const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname; window.history.replaceState({ path: cleanUrl }, '', cleanUrl); } }, 100);
        });
    </script>
<?php endif; ?>

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

<style>
    .carousel-container { width: 100%; margin: 0 auto; }
    .banner-img { width: 100%; height: auto; display: block; border-radius: 1.5rem; }
    @media (max-width: 768px) { .banner-img { border-radius: 0; } .carousel-container { padding-left: 0 !important; padding-right: 0 !important; } }
</style>

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
        $categoriasPermitidas = ['Bebidas y Refrescos', 'Congelados', 'Despensa', 'Galletas y Golosinas', 'Cuidado Personal', 'Limpieza y Aseo', 'Vinos y Licores', 'Mascotas'];
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
        <?php else: foreach ($productos as $p):
            $id = $p['id'];
            $nombreMostrar = !empty($p['nombre_web']) ? $p['nombre_web'] : (isset($p['nombre']) ? $p['nombre'] : 'Producto');
            $precio = isset($p['precio']) ? $p['precio'] : 0;
            $stock = is_object($p) ? (int)($p->stock_web ?? $p->stock ?? 0) : (int)($p['stock_web'] ?? $p['stock'] ?? 0);
            $marca = isset($p['marca']) ? $p['marca'] : '';
            $imgSrc = !empty($p['imagen']) ? (strpos($p['imagen'], 'http') === 0 ? $p['imagen'] : BASE_URL . 'img/productos/' . $p['imagen']) : BASE_URL . 'img/no-image.png';
            $enCarro = isset($_SESSION['carrito'][$id]);
            $cantidadEnCarro = $enCarro ? $_SESSION['carrito'][$id]['cantidad'] : 0;
            $borderClass = $enCarro ? 'border-cenco-green shadow' : 'border-0 border-light';
            $bgClass = $enCarro ? 'bg-success-subtle' : 'bg-white';
        ?>
            <div class="col">
                <div class="card h-100 shadow-sm transition-hover rounded-4 overflow-hidden border <?= $borderClass ?>" id="card-prod-<?= $id ?>">
                    <div class="position-relative <?= $bgClass ?>" id="img-container-<?= $id ?>" style="transition: background-color 0.3s ease;">
                        <span class="position-absolute top-0 start-0 m-2 badge bg-cenco-green shadow-sm text-white <?= $enCarro ? 'd-inline-block' : 'd-none' ?>" id="badge-llevas-<?= $id ?>" style="z-index: 2;">
                            <i class="bi bi-bag-check-fill me-1"></i> Llevas <span id="count-<?= $id ?>"><?= $cantidadEnCarro ?></span>
                        </span>
                        <span class="position-absolute top-0 end-0 m-2 badge bg-danger shadow text-white fw-bold" style="z-index: 2; font-size: 0.7rem;">NUEVO</span>
                        <img src="<?= $imgSrc ?>" class="card-img-top p-4" style="height:180px; object-fit:contain;" alt="<?= htmlspecialchars($nombreMostrar) ?>">
                    </div>
                    <div class="card-body d-flex flex-column p-3 bg-white border-top border-light">
                        <small class="text-muted mb-1 text-uppercase fw-bold" style="font-size:0.65rem;"><?= htmlspecialchars($marca) ?></small>
                        <a href="<?= BASE_URL ?>home/producto?id=<?= $id ?>" class="text-decoration-none">
                            <h6 class="card-title fw-bold text-dark lh-sm mb-2" style="font-size: 0.9rem; height: 38px; overflow: hidden;" title="<?= htmlspecialchars($nombreMostrar) ?>">
                                <?= htmlspecialchars($nombreMostrar) ?>
                            </h6>
                        </a>
                        <div class="mb-2" style="font-size: 0.75rem;">
                            <?php if ($stock <= 0): ?><span class="text-danger fw-bold">Agotado</span>
                            <?php else: ?><span class="<?= $stock <= 8 ? 'text-danger fw-black animate__animated animate__flash animate__infinite' : 'text-success fw-bold' ?>"><i class="bi bi-box-seam me-1"></i>Stock: <?= $stock ?></span><?php endif; ?>
                        </div>
                        <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                            <div class="d-flex flex-column">
                                <span class="fw-black text-cenco-red fs-5">$<?= number_format($precio, 0, ',', '.') ?></span>
                                <?php if (!empty($p['precio_unidad_medida'])): ?><small class="text-muted fw-normal" style="font-size: 0.65rem; margin-top: -3px;"><?= htmlspecialchars($p['precio_unidad_medida']) ?></small><?php endif; ?>
                            </div>
                            <div style="min-width: 80px; text-align: right;">
                                <form id="form-add-<?= $id ?>" class="<?= $enCarro ? 'd-none' : 'd-block' ?>" onsubmit="agregarAlCarrito(event, this, <?= $id ?>)">
                                    <input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="nombre" value="<?= htmlspecialchars($nombreMostrar) ?>"><input type="hidden" name="precio" value="<?= $precio ?>"><input type="hidden" name="imagen" value="<?= $p['imagen'] ?? '' ?>">
                                    <button type="submit" class="btn btn-sm btn-cenco-green rounded-circle shadow-sm transition-hover" style="width:38px;height:38px;"><i class="bi bi-plus-lg fs-6"></i></button>
                                </form>
                                <div id="controls-<?= $id ?>" class="align-items-center justify-content-end gap-1 <?= $enCarro ? 'd-flex' : 'd-none' ?>">
                                    <button class="btn btn-sm btn-outline-danger rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:32px;height:32px;" onclick="gestionarClickTarjeta(<?= $id ?>, 'bajar')"><i class="bi bi-dash-lg"></i></button>
                                    <span class="fw-bold text-cenco-indigo px-1" style="min-width: 20px; text-align: center;" id="card-count-<?= $id ?>"><?= $cantidadEnCarro ?></span>
                                    <button class="btn btn-sm btn-cenco-green rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:32px;height:32px;" onclick="gestionarClickTarjeta(<?= $id ?>, 'subir')"><i class="bi bi-plus-lg"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
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
            <div class="carousel-indicators">
                <?php foreach ($bannersSecundarios as $index => $banner): ?>
                    <button type="button" data-bs-target="#carruselSecundario" data-bs-slide-to="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>"></button>
                <?php endforeach; ?>
            </div>
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

<div id="ofertas-rapidas" class="d-flex justify-content-between align-items-center mb-4 container-fluid px-3 px-xl-5 mt-5">
    <h3 class="fw-black text-danger border-start border-5 border-danger ps-3 mb-0 ls-1">OFERTAS DE LA SEMANA</h3>
    <a href="<?= BASE_URL ?>home/catalogo" class="btn btn-outline-danger rounded-pill fw-bold px-4 transition-hover">Ver Todo <i class="bi bi-arrow-right"></i></a>
</div>

<div class="container-fluid px-3 px-xl-5 mb-5">
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3 g-md-4 mb-5">
        <?php if (empty($ofertas)): ?>
            <div class="col-12"><div class="alert bg-white shadow-sm text-center py-5">Pronto nuevas ofertas.</div></div>
        <?php else: foreach ($ofertas as $p):
            $id = $p['id'];
            $nombreMostrar = !empty($p['nombre_web']) ? $p['nombre_web'] : (isset($p['nombre']) ? $p['nombre'] : 'Producto');
            $precio = isset($p['precio']) ? $p['precio'] : 0;
            $stock = is_object($p) ? (int)($p->stock_web ?? $p->stock ?? 0) : (int)($p['stock_web'] ?? $p['stock'] ?? 0);
            $marca = isset($p['marca']) ? $p['marca'] : '';
            $imgSrc = !empty($p['imagen']) ? (strpos($p['imagen'], 'http') === 0 ? $p['imagen'] : BASE_URL . 'img/productos/' . $p['imagen']) : BASE_URL . 'img/no-image.png';
            $enCarro = isset($_SESSION['carrito'][$id]);
            $cantidadEnCarro = $enCarro ? $_SESSION['carrito'][$id]['cantidad'] : 0;
            $borderClass = $enCarro ? 'border-danger shadow' : 'border-danger border-opacity-25';
            $bgClass = $enCarro ? 'bg-danger-subtle' : 'bg-white';
        ?>
            <div class="col">
                <div class="card h-100 shadow-sm transition-hover rounded-4 overflow-hidden border <?= $borderClass ?>" id="card-prod-<?= $id ?>">
                    <div class="position-relative <?= $bgClass ?>" id="img-container-<?= $id ?>" style="transition: background-color 0.3s ease;">
                        <span class="position-absolute top-0 start-0 m-2 badge bg-danger shadow-sm text-white <?= $enCarro ? 'd-inline-block' : 'd-none' ?>" id="badge-llevas-<?= $id ?>" style="z-index: 2;">
                            <i class="bi bi-bag-check-fill me-1"></i> Llevas <span id="count-<?= $id ?>"><?= $cantidadEnCarro ?></span>
                        </span>
                        <span class="position-absolute top-0 end-0 m-2 badge bg-warning text-dark shadow fw-bold px-3 py-2" style="z-index: 2; font-size: 0.8rem;"><i class="bi bi-tags-fill me-1"></i> OFERTA</span>
                        <img src="<?= $imgSrc ?>" class="card-img-top p-4" style="height:180px; object-fit:contain;" alt="<?= htmlspecialchars($nombreMostrar) ?>">
                    </div>
                    <div class="card-body d-flex flex-column p-3 bg-white border-top border-light">
                        <small class="text-muted mb-1 text-uppercase fw-bold" style="font-size:0.65rem;"><?= htmlspecialchars($marca) ?></small>
                        <a href="<?= BASE_URL ?>home/producto?id=<?= $id ?>" class="text-decoration-none">
                            <h6 class="card-title fw-bold text-dark lh-sm mb-2" style="font-size: 0.9rem; height: 38px; overflow: hidden;" title="<?= htmlspecialchars($nombreMostrar) ?>"><?= htmlspecialchars($nombreMostrar) ?></h6>
                        </a>
                        <div class="mb-2" style="font-size: 0.75rem;">
                            <?php if ($stock <= 0): ?><span class="text-danger fw-bold">Agotado</span>
                            <?php else: ?><span class="<?= $stock <= 8 ? 'text-danger fw-black animate__animated animate__flash animate__infinite' : 'text-success fw-bold' ?>"><i class="bi bi-box-seam me-1"></i>Stock: <?= $stock ?></span><?php endif; ?>
                        </div>
                        <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                            <div class="d-flex flex-column">
                                <span class="fw-black text-danger fs-4">$<?= number_format($precio, 0, ',', '.') ?></span>
                                <?php if (!empty($p['precio_unidad_medida'])): ?><small class="text-muted fw-normal" style="font-size: 0.65rem; margin-top: -3px;"><?= htmlspecialchars($p['precio_unidad_medida']) ?></small><?php endif; ?>
                            </div>
                            <div style="min-width: 80px; text-align: right;">
                                <form id="form-add-<?= $id ?>" class="<?= $enCarro ? 'd-none' : 'd-block' ?>" onsubmit="agregarAlCarrito(event, this, <?= $id ?>)">
                                    <input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="nombre" value="<?= htmlspecialchars($nombreMostrar) ?>"><input type="hidden" name="precio" value="<?= $precio ?>"><input type="hidden" name="imagen" value="<?= $p['imagen'] ?? '' ?>">
                                    <button type="submit" class="btn btn-sm btn-danger rounded-circle shadow-sm transition-hover" style="width:38px;height:38px;"><i class="bi bi-plus-lg fs-6"></i></button>
                                </form>
                                <div id="controls-<?= $id ?>" class="align-items-center justify-content-end gap-1 <?= $enCarro ? 'd-flex' : 'd-none' ?>">
                                    <button class="btn btn-sm btn-outline-danger rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:32px;height:32px;" onclick="gestionarClickTarjeta(<?= $id ?>, 'bajar')"><i class="bi bi-dash-lg"></i></button>
                                    <span class="fw-bold text-danger px-1" style="min-width: 20px; text-align: center;" id="card-count-<?= $id ?>"><?= $cantidadEnCarro ?></span>
                                    <button class="btn btn-sm btn-danger rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:32px;height:32px;" onclick="gestionarClickTarjeta(<?= $id ?>, 'subir')"><i class="bi bi-plus-lg"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
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
        <?php else: foreach ($masVendidos as $p):
            $id = $p['id'];
            $nombreMostrar = !empty($p['nombre_web']) ? $p['nombre_web'] : $p['nombre'];
            $precio = isset($p['precio']) ? $p['precio'] : 0;
            $stock = is_object($p) ? (int)($p->stock_web ?? $p->stock ?? 0) : (int)($p['stock_web'] ?? $p['stock'] ?? 0);
            $imgSrc = !empty($p['imagen']) ? (strpos($p['imagen'], 'http') === 0 ? $p['imagen'] : BASE_URL . 'img/productos/' . $p['imagen']) : BASE_URL . 'img/no-image.png';
            $enCarro = isset($_SESSION['carrito'][$id]);
            $cantidadEnCarro = $enCarro ? $_SESSION['carrito'][$id]['cantidad'] : 0;
            $borderClass = $enCarro ? 'border-cenco-green shadow' : 'border-0 border-light bg-light';
            $bgClass = $enCarro ? 'bg-success-subtle' : 'bg-white';
        ?>
            <div class="col">
                <div class="card h-100 shadow-sm transition-hover rounded-4 overflow-hidden border <?= $borderClass ?>" id="card-prod-<?= $id ?>">
                    <div class="position-absolute top-0 start-0 m-2 badge bg-warning text-dark shadow-sm z-2"><i class="bi bi-star-fill"></i> Top</div>
                    <div class="card-img-top position-relative d-flex align-items-center justify-content-center p-4 <?= $bgClass ?>" id="img-container-<?= $id ?>" style="height: 180px; transition: background-color 0.3s ease;">
                        <span class="position-absolute top-0 end-0 m-2 badge bg-cenco-green shadow-sm text-white <?= $enCarro ? 'd-inline-block' : 'd-none' ?>" id="badge-llevas-<?= $id ?>">
                            Llevas <span id="count-<?= $id ?>"><?= $cantidadEnCarro ?></span>
                        </span>
                        <img src="<?= $imgSrc ?>" class="img-fluid" style="max-height: 100%; object-fit: contain;">
                    </div>

                    <div class="card-body d-flex flex-column p-3 bg-white border-top border-light">
                        <small class="text-muted mb-1 text-uppercase fw-bold" style="font-size:0.65rem;"><?= htmlspecialchars($p['marca'] ?? '') ?></small>
                        <a href="<?= BASE_URL ?>home/producto?id=<?= $id ?>" class="text-decoration-none">
                            <h6 class="card-title fw-bold text-dark lh-sm mb-2" style="font-size: 0.9rem; height: 38px; overflow: hidden;" title="<?= htmlspecialchars($nombreMostrar) ?>">
                                <?= htmlspecialchars($nombreMostrar) ?>
                            </h6>
                        </a>
                        <div class="mb-2" style="font-size: 0.75rem;">
                            <?php if ($stock <= 0): ?><span class="text-danger fw-bold">Agotado</span>
                            <?php else: ?><span class="<?= $stock <= 8 ? 'text-danger fw-black animate__animated animate__flash animate__infinite' : 'text-success fw-bold' ?>"><i class="bi bi-box-seam me-1"></i>Stock: <?= $stock ?></span><?php endif; ?>
                        </div>
                        <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                            <div class="d-flex flex-column">
                                <span class="fw-black text-cenco-red fs-5">$<?= number_format($precio, 0, ',', '.') ?></span>
                                <?php if (!empty($p['precio_unidad_medida'])): ?><small class="text-muted fw-normal" style="font-size: 0.65rem; margin-top: -3px;"><?= htmlspecialchars($p['precio_unidad_medida']) ?></small><?php endif; ?>
                            </div>
                            <div style="min-width: 80px; text-align: right;">
                                <form id="form-add-<?= $id ?>" class="<?= $enCarro ? 'd-none' : 'd-block' ?>" onsubmit="agregarAlCarrito(event, this, <?= $id ?>)">
                                    <input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="nombre" value="<?= htmlspecialchars($nombreMostrar) ?>"><input type="hidden" name="precio" value="<?= $precio ?>"><input type="hidden" name="imagen" value="<?= $p['imagen'] ?? '' ?>">
                                    <button type="submit" class="btn btn-sm btn-cenco-green rounded-circle shadow-sm transition-hover" style="width:38px;height:38px;"><i class="bi bi-plus-lg fs-6"></i></button>
                                </form>
                                <div id="controls-<?= $id ?>" class="align-items-center justify-content-end gap-1 <?= $enCarro ? 'd-flex' : 'd-none' ?>">
                                    <button class="btn btn-sm btn-outline-danger rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:32px;height:32px;" onclick="gestionarClickTarjeta(<?= $id ?>, 'bajar')"><i class="bi bi-dash-lg"></i></button>
                                    <span class="fw-bold text-cenco-indigo px-1" style="min-width: 20px; text-align: center;" id="card-count-<?= $id ?>"><?= $cantidadEnCarro ?></span>
                                    <button class="btn btn-sm btn-cenco-green rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:32px;height:32px;" onclick="gestionarClickTarjeta(<?= $id ?>, 'subir')"><i class="bi bi-plus-lg"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
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

<style>
    .info-card-item { position: relative; padding: 2.5rem 1rem; }
    @media (min-width: 992px) { .info-card-item:not(:last-child)::after { content: ""; position: absolute; top: 20%; bottom: 20%; right: 0; width: 1px; background-color: rgba(0, 0, 0, 0.1); } }
    @media (min-width: 768px) and (max-width: 991px) {
        .info-card-item:nth-child(odd)::after { content: ""; position: absolute; top: 20%; bottom: 20%; right: 0; width: 1px; background-color: rgba(0, 0, 0, 0.1); }
        .info-card-item:nth-child(-n+2) { border-bottom: 1px solid rgba(0, 0, 0, 0.1); }
    }
    @media (max-width: 767px) { .info-card-item:not(:last-child) { border-bottom: 1px solid rgba(0, 0, 0, 0.1); } }
</style>

<div class="container-fluid px-3 px-xl-5 my-5">
    <div class="row text-center g-0 bg-white rounded-4 shadow-sm border border-light overflow-hidden">
        <div class="col-md-6 col-lg-3 info-card-item d-flex flex-column align-items-center justify-content-center hover-scale transition-hover">
            <div class="mb-3 text-cenco-indigo"><i class="bi bi-file-earmark-text fs-1"></i></div>
            <h6 class="fw-bold text-cenco-indigo text-uppercase ls-1 mb-2">Atención Personalizada</h6>
            <p class="text-muted small mb-3 px-3">¿Necesitas una cotización especial o ayuda con tu pedido?</p>
            <a href="https://wa.me/56946452516" target="_blank" class="text-decoration-none fw-bold text-cenco-green small">Contáctanos aquí <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="col-md-6 col-lg-3 info-card-item d-flex flex-column align-items-center justify-content-center hover-scale transition-hover">
            <div class="mb-3 text-cenco-indigo"><i class="bi bi-box-seam fs-1"></i></div>
            <h6 class="fw-bold text-cenco-indigo text-uppercase ls-1 mb-2">Seguimiento de Compra</h6>
            <p class="text-muted small mb-3 px-3">Revisa el estado y detalle de tus despachos en tiempo real.</p>
            <a href="<?= BASE_URL ?>perfil" class="text-decoration-none fw-bold text-cenco-green small">Ver estado <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="col-md-6 col-lg-3 info-card-item d-flex flex-column align-items-center justify-content-center hover-scale transition-hover">
            <div class="mb-3 text-cenco-indigo position-relative">
                <i class="bi bi-headset fs-1"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-cenco-red border border-light rounded-circle"></span>
            </div>
            <h6 class="fw-bold text-cenco-indigo text-uppercase ls-1 mb-2">¿Problemas con tu Pedido?</h6>
            <p class="text-muted small mb-3 px-3">Repórtanos cualquier inconveniente para solucionarlo rápido.</p>
            <a href="mailto:ventas@cencocal.cl?subject=Problema con Pedido" class="text-decoration-none fw-bold text-cenco-green small">Reportar ahora <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="col-md-6 col-lg-3 info-card-item d-flex flex-column align-items-center justify-content-center hover-scale transition-hover">
            <div class="mb-3 text-cenco-indigo"><i class="bi bi-shop fs-1"></i></div>
            <h6 class="fw-bold text-cenco-indigo text-uppercase ls-1 mb-2">Locales y Horarios</h6>
            <p class="text-muted small mb-3 px-3">Entérate cuál es la sucursal Cencocal más cerca de ti.</p>
            <a href="<?= BASE_URL ?>home/locales" class="text-decoration-none fw-bold text-cenco-green small">Ver ubicaciones <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</div>

<div class="modal fade" id="localesModal" tabindex="-1" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-cenco-indigo"><i class="bi bi-shop me-2"></i>Nuestras Sucursales</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-3 px-xl-5 mb-5 mt-5">
    <div class="d-flex justify-content-center align-items-center mb-4">
        <h4 class="fw-black text-secondary mb-0 ls-1">NUESTROS PROYECTOS</h4>
    </div>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-4 justify-content-center">
        <?php
        $proyectosTop = ['Unilever', 'Carozzi', 'Watts', 'Softys', 'Iansa', 'Clorox'];
        foreach ($proyectosTop as $nombreMarca):
            $urlImg = BASE_URL . "img/marcas/" . $nombreMarca . ".png";
        ?>
            <div class="col">
                <a href="<?= BASE_URL ?>home/catalogo?marca=<?= urlencode($nombreMarca) ?>" class="brand-card d-flex align-items-center justify-content-center p-3 rounded-4 text-decoration-none w-100 shadow-sm border border-light bg-white" style="filter: grayscale(100%); transition: filter 0.3s ease;" onmouseover="this.style.filter='grayscale(0%)'" onmouseout="this.style.filter='grayscale(100%)'">
                    <img src="<?= $urlImg ?>" alt="<?= htmlspecialchars($nombreMarca) ?>" class="img-fluid brand-logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <span style="display:none; font-weight:800; color: var(--cenco-indigo); font-size: 1.1rem; text-transform: uppercase;">
                        <?= htmlspecialchars($nombreMarca) ?>
                    </span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    const BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>js/home.js"></script>