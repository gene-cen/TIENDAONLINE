<?php if (isset($_GET['msg']) && $_GET['msg'] == 'compra_exitosa'): ?>
    <div class="alert alert-success text-center py-4 mb-5 shadow-sm border-0 rounded-3 bg-cenco-green text-white">
        <h2 class="fw-bold"><i class="bi bi-bag-check-fill me-2"></i> ¡Gracias por tu compra!</h2>
        <p class="fs-5 mb-1">Hemos recibido tu pedido</p>
    </div>
<?php endif; ?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="position-relative">
                <form action="<?= BASE_URL ?>home/buscar" method="GET" class="d-flex shadow-sm rounded-pill bg-white overflow-hidden border border-1" autocomplete="off">
                    <input type="text" name="q" id="inputBusqueda" class="form-control border-0 shadow-none ps-4 py-2 fs-5" placeholder="Ya viste lo nuevo de este mes? Encuéntralo aquí" required onkeyup="buscarPredictivo(this.value)">
                    <button type="submit" class="btn btn-cenco-green rounded-pill px-4 fw-bold d-flex align-items-center m-1">
                        <i class="bi bi-search fs-5 me-2 d-none d-sm-inline"></i> Buscar
                    </button>
                </form>
                <ul id="lista-predictiva" class="list-group position-absolute w-100 shadow-lg d-none" style="z-index: 1000; top: 100%; left: 0; border-radius: 0 0 15px 15px; overflow: hidden;"></ul>
            </div>
        </div>
    </div>
</div>
<div id="carruselPrincipal" class="carousel slide shadow-sm mb-5 rounded-4 overflow-hidden" data-bs-ride="carousel">

    <div class="carousel-indicators">
        <button type="button" data-bs-target="#carruselPrincipal" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#carruselPrincipal" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#carruselPrincipal" data-bs-slide-to="2"></button>
    </div>

    <div class="carousel-inner">

        <div class="carousel-item active" data-bs-interval="6000">
            <a href="<?= BASE_URL ?>home/catalogo?categoria=Cuidado Bebé">
                <img src="<?= BASE_URL ?>img/banner/banner baby.png" class="d-block w-100" alt="Banner Bebé" style="min-height: 300px; object-fit: cover;">
            </a>
        </div>

        <div class="carousel-item" data-bs-interval="6000">
            <a href="<?= BASE_URL ?>home/catalogo?marca=Cien">
                <img src="<?= BASE_URL ?>img/banner/banner Cien.png" class="d-block w-100" alt="Banner Cien" style="min-height: 300px; object-fit: cover;">
            </a>
        </div>

        <div class="carousel-item" data-bs-interval="6000">
            <a href="<?= BASE_URL ?>home/locales">
                <img src="<?= BASE_URL ?>img/banner/banner Despacho.png" class="d-block w-100" alt="Banner Despacho" style="min-height: 300px; object-fit: cover;">
            </a>
        </div>

    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#carruselPrincipal" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
    <button class="carousel-control-next" type="button" data-bs-target="#carruselPrincipal" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
</div>

<section class="bg-light py-5 border-bottom">
    <div class="container">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-black text-cenco-indigo mb-0">
                    <i class="bi bi-lightning-charge-fill text-warning me-2"></i>OFERTAS EXCLUSIVAS WEB
                </h3>
                <p class="text-muted small mb-0">Válido para compras realizadas únicamente en nuestra Tienda Online*</p>
            </div>
            <a href="<?= BASE_URL ?>home/catalogo" class="btn btn-outline-cenco-indigo rounded-pill px-4 fw-bold small">
                Ver Todo <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($ofertas as $oferta):
                $id = $oferta['id'];
                $nombre = $oferta['nombre_web'];
                $precioReal = $oferta['precio'];
                // Simulación de "Precio Antes" (le sumamos un 20% falso para que se vea el descuento)
                $precioAntes = $precioReal * 1.25;
                $img = !empty($oferta['imagen']) ? (strpos($oferta['imagen'], 'http') === 0 ? $oferta['imagen'] : BASE_URL . 'img/productos/' . $oferta['imagen']) : BASE_URL . 'img/no-image.png';
            ?>
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm rounded-4 position-relative overflow-hidden hover-scale-sm transition-hover">

                        <div class="position-absolute top-0 start-0 bg-danger text-white fw-bold px-3 py-1 rounded-end-pill mt-3 shadow-sm small z-1">
                            -20% OFF
                        </div>

                        <a href="<?= BASE_URL ?>home/producto?id=<?= $id ?>" class="text-decoration-none">
                            <div class="p-4 bg-white text-center position-relative">
                                <img src="<?= $img ?>" class="img-fluid" style="height: 180px; object-fit: contain;" alt="<?= htmlspecialchars($nombre) ?>">
                            </div>
                        </a>

                        <div class="card-body bg-white pt-2">
                            <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;"><?= htmlspecialchars($oferta['marca'] ?? '') ?></small>

                            <a href="<?= BASE_URL ?>home/producto?id=<?= $id ?>" class="text-decoration-none text-dark">
                                <h6 class="card-title fw-bold text-truncate mb-2"><?= htmlspecialchars($nombre) ?></h6>
                            </a>

                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="text-decoration-line-through text-muted small">$<?= number_format($precioAntes, 0, ',', '.') ?></span>
                                <span class="fw-black text-danger fs-5">$<?= number_format($precioReal, 0, ',', '.') ?></span>
                            </div>

                            <form onsubmit="agregarAlCarrito(event, this, <?= $id ?>)">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <input type="hidden" name="nombre" value="<?= htmlspecialchars($nombre) ?>">
                                <input type="hidden" name="precio" value="<?= $precioReal ?>">
                                <input type="hidden" name="imagen" value="<?= $oferta['imagen'] ?>">
                                <button class="btn btn-cenco-indigo w-100 rounded-pill fw-bold btn-sm py-2">
                                    <i class="bi bi-cart-plus me-1"></i> Aprovechar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>
<div class="container mb-5 mt-5">
    <div class="d-flex align-items-center mb-4">
        <h3 class="fw-black text-cenco-indigo border-start border-5 border-warning ps-3 mb-0 ls-1">CATEGORÍAS DESTACADAS</h3>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php
        // 1. Definimos la lista exacta de categorías que quieres mostrar
        $categoriasPermitidas = [
            'Bebidas y Refrescos',
            'Congelados',
            'Conservas',
            'Cuidado Bebé',
            'Cuidado Senior',
            'Despensa',
            'Higienicos',
            'Limpieza y Aseo',
            'Vinos y Licores',
            'Mascotas' // <--- ¡Agregado también! 🐶🐱
        ];

        // 2. Filtramos el array $categorias para quedarnos solo con las permitidas
        $categoriasGrid = array_filter($categorias ?? [], function ($cat) use ($categoriasPermitidas) {
            $nombreDB = trim($cat['nombre']);
            foreach ($categoriasPermitidas as $permitida) {
                if (strcasecmp($nombreDB, $permitida) === 0) return true;
            }
            return false;
        });

        // 3. Mostramos las categorías filtradas
        if (!empty($categoriasGrid)): foreach ($categoriasGrid as $cat):
                $nombreCat = $cat['nombre'];
                $urlImg = BASE_URL . "img/categorias/" . $nombreCat . ".jpg";
        ?>
                <div class="col">
                    <a href="<?= BASE_URL ?>home/catalogo?categoria=<?= urlencode($nombreCat) ?>" class="text-decoration-none text-dark">
                        <div class="card h-100 border-0 shadow-sm overflow-hidden text-center hover-scale transition-hover">
                            <div class="d-flex align-items-center justify-content-center p-3" style="height: 250px; background-color: #f8f9fa;">
                                <img src="<?= $urlImg ?>" alt="<?= htmlspecialchars($nombreCat) ?>" class="img-fluid rounded-3" style="max-height: 100%; max-width: 100%; object-fit: contain;" onerror="this.style.display='none'; this.parentNode.innerHTML='<i class=\'bi bi-image text-muted fs-1\'></i>';">
                            </div>
                            <div class="card-body p-4 bg-white">
                                <h5 class="fw-bold mb-0 text-uppercase text-cenco-indigo ls-1"><?= htmlspecialchars($nombreCat) ?></h5>
                            </div>
                        </div>
                    </a>
                </div>
        <?php endforeach;
        endif; ?>
    </div>
</div>

<div id="catalogo-rapido" class="d-flex justify-content-between align-items-center mb-4 container">
    <h3 class="fw-black text-cenco-indigo border-start border-5 border-warning ps-3 mb-0 ls-1">NOVEDADES</h3>
    <a href="<?= BASE_URL ?>home/catalogo" class="btn btn-outline-cenco-indigo rounded-pill btn-sm fw-bold px-3 transition-hover">Ver Todo <i class="bi bi-arrow-right"></i></a>
</div>
<div class="container mb-5">
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-5 g-4 mb-5">
        <?php if (empty($productos)): ?>
            <div class="col-12">
                <div class="alert bg-white shadow-sm text-center py-5">Pronto novedades.</div>
            </div>
            <?php else: foreach ($productos as $p):
                // LÓGICA VISUAL
                $id = $p['id'];
                $nombreMostrar = !empty($p['nombre_web']) ? $p['nombre_web'] : (isset($p['nombre']) ? $p['nombre'] : 'Producto');
                $precio = isset($p['precio']) ? $p['precio'] : 0;
                $stock = isset($p['stock']) ? $p['stock'] : 0;
                $marca = isset($p['marca']) ? $p['marca'] : '';
                $img = isset($p['imagen']) ? $p['imagen'] : '';
                $imgSrc = !empty($img) ? (strpos($img, 'http') === 0 ? $img : BASE_URL . 'img/productos/' . $img) : BASE_URL . 'img/no-image.png';

                // VERIFICAR CARRITO
                $enCarro = isset($_SESSION['carrito'][$id]);
                $cantidadEnCarro = $enCarro ? $_SESSION['carrito'][$id]['cantidad'] : 0;

                // CLASES DINÁMICAS
                $borderClass = $enCarro ? 'border-cenco-green shadow' : 'border-0';
                $bgClass = $enCarro ? 'bg-success-subtle' : 'bg-white';
                $displayBadge = $enCarro ? 'd-inline-block' : 'd-none';
            ?>
                <div class="col">
                    <div class="card h-100 shadow-sm transition-hover rounded-4 overflow-hidden <?= $borderClass ?>" id="card-prod-<?= $id ?>">
                        <div class="position-relative <?= $bgClass ?>" id="img-container-<?= $id ?>" style="transition: background-color 0.3s ease;">
                            <span class="position-absolute top-0 start-0 m-3 badge bg-cenco-green shadow-sm text-white <?= $displayBadge ?>" id="badge-llevas-<?= $id ?>" style="z-index: 2;">
                                <i class="bi bi-bag-check-fill me-1"></i> Llevas <span id="count-<?= $id ?>"><?= $cantidadEnCarro ?></span>
                            </span>
                            <img src="<?= $imgSrc ?>" class="card-img-top p-3" style="height:200px; object-fit:contain;" alt="<?= htmlspecialchars($nombreMostrar) ?>">
                            <?php if ($stock <= 0): ?>
                                <span class="badge bg-secondary position-absolute top-50 start-50 translate-middle">Agotado</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body d-flex flex-column p-4 bg-white">
                            <small class="text-muted mb-1 text-uppercase" style="font-size:0.75rem;"><?= htmlspecialchars($marca) ?></small>
                            <h6 class="card-title fw-bold text-cenco-indigo mb-2 text-truncate" title="<?= htmlspecialchars($nombreMostrar) ?>"><?= htmlspecialchars($nombreMostrar) ?></h6>
                            <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                                <h5 class="text-cenco-red fw-black mb-0 fs-6">$<?= number_format($precio, 0, ',', '.') ?></h5>
                                <div style="min-width: 100px; text-align: right;">
                                    <?php
                                    $displayAdd = $enCarro ? 'd-none' : 'd-block';
                                    $displayControls = $enCarro ? 'd-flex' : 'd-none';
                                    ?>
                                    <form id="form-add-<?= $id ?>" class="<?= $displayAdd ?>" onsubmit="agregarAlCarrito(event, this, <?= $id ?>)">
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <input type="hidden" name="nombre" value="<?= htmlspecialchars($nombreMostrar) ?>">
                                        <input type="hidden" name="precio" value="<?= $precio ?>">
                                        <input type="hidden" name="imagen" value="<?= $img ?>">
                                        <button type="submit" class="btn btn-sm btn-cenco-green rounded-circle shadow-sm transition-hover" style="width:38px;height:38px;">
                                            <i class="bi bi-plus-lg fs-6"></i>
                                        </button>
                                    </form>
                                    <div id="controls-<?= $id ?>" class="align-items-center justify-content-end gap-2 <?= $displayControls ?>">
                                        <button class="btn btn-sm btn-outline-danger rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:32px;height:32px;" onclick="gestionarClickTarjeta(<?= $id ?>, 'bajar')"><i class="bi bi-dash-lg"></i></button>
                                        <span class="fw-bold text-cenco-indigo" style="min-width: 20px; text-align: center;" id="card-count-<?= $id ?>"><?= $cantidadEnCarro ?></span>
                                        <button class="btn btn-sm btn-cenco-green rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:32px;height:32px;" onclick="gestionarClickTarjeta(<?= $id ?>, 'subir')"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        <?php endforeach;
        endif; ?>
    </div>
</div>
<div class="container mb-5 mt-5">
    <div class="rounded-4 overflow-visible shadow-lg position-relative px-4 px-md-5 py-4 d-flex align-items-center" style="background: linear-gradient(135deg, var(--cenco-indigo) 0%, #303f9f 60%, var(--cenco-green) 100%); min-height: 320px;">
        <div class="position-absolute top-0 start-0 translate-middle rounded-circle bg-white opacity-10" style="width: 300px; height: 300px;"></div>
        <div class="position-absolute bottom-0 end-0 translate-middle-x rounded-circle bg-white opacity-10" style="width: 200px; height: 200px;"></div>
        <div class="row w-100 align-items-center position-relative" style="z-index: 2;">
            <div class="col-lg-7 text-white text-center text-lg-start mb-4 mb-lg-0 py-4"> <span class="badge bg-cenco-red mb-3 px-3 py-2 rounded-pill fw-bold shadow-sm">
                    <i class="bi bi-star-fill me-1"></i> CALIDAD GARANTIZADA
                </span>
                <h2 class="fw-black display-5 mb-2">Descubre la selección de productos que solo encontrarás en Cencocal</h2>
                <p class="fs-5 opacity-90 mb-4 fw-light">
                    <br class="d-none d-lg-block">Calidad para tu hogar y negocio al mejor precio.
                </p>
                <a href="<?= BASE_URL ?>home/catalogo?filtro=exclusivos" class="btn btn-white text-cenco-indigo fw-bold rounded-pill px-5 py-3 shadow-sm transition-hover">
                    Ver Exclusivos <i class="bi bi-arrow-right-short fs-4 align-middle"></i>
                </a>
            </div>
            <div class="col-lg-5 text-center position-relative">
                <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_cien.png" alt="Marcas Exclusivas" class="img-fluid position-relative floating-anim"
                    style="width: auto; /* Deja que el ancho se ajuste automáticamente */
                            max-height: 450px; /* Aumenta significativamente la altura máxima */
                            /* En móviles, la imagen será más pequeña */
                            @media (max-width: 991.98px) { max-height: 300px; }
                            filter: drop-shadow(0 15px 30px rgba(0,0,0,0.4)); /* Sombra más pronunciada */
                            transform: rotate(5deg) scale(1.15); /* Rotación y escalado para hacerlo aún más grande */
                            margin-top: -40px; /* Desplaza la imagen hacia arriba para que se salga del contenedor */
                            margin-bottom: -40px; /* Desplaza la imagen hacia abajo para que se salga del contenedor */
                            pointer-events: none; /* Evita que la imagen grande interfiera con los clicks */
                            ">
            </div>
        </div>
    </div>
</div>
<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
        <h3 class="fw-black text-cenco-indigo border-start border-5 border-warning ps-3 mb-0 ls-1">LOS MÁS VENDIDOS</h3>
        <span class="badge bg-warning text-dark">Top 5</span>
    </div>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-5 g-4 mb-5">
        <?php if (empty($masVendidos)): ?>
            <div class="col-12 text-center text-muted py-4">Aún no hay suficientes datos.</div>
            <?php else: foreach ($masVendidos as $p):
                // Mismo bloque de lógica visual que novedades...
                $id = $p['id'];
                $nombreMostrar = !empty($p['nombre_web']) ? $p['nombre_web'] : $p['nombre'];
                $precio = isset($p['precio']) ? $p['precio'] : 0;
                $img = isset($p['imagen']) ? $p['imagen'] : '';
                $imgSrc = !empty($img) ? (strpos($img, 'http') === 0 ? $img : BASE_URL . 'img/productos/' . $img) : BASE_URL . 'img/no-image.png';
                $enCarro = isset($_SESSION['carrito'][$id]);
                $cantidadEnCarro = $enCarro ? $_SESSION['carrito'][$id]['cantidad'] : 0;
                $borderClass = $enCarro ? 'border-cenco-green shadow' : 'border-0';
                $bgClass = $enCarro ? 'bg-success-subtle' : 'bg-light';
                $displayBadge = $enCarro ? 'd-inline-block' : 'd-none';
            ?>
                <div class="col">
                    <div class="card h-100 shadow-sm transition-hover rounded-4 overflow-hidden <?= $borderClass ?>" id="card-prod-<?= $id ?>">
                        <div class="position-absolute top-0 start-0 m-2 badge bg-warning text-dark shadow-sm z-2"><i class="bi bi-star-fill"></i> Top</div>
                        <div class="card-img-top position-relative d-flex align-items-center justify-content-center p-3 <?= $bgClass ?>" id="img-container-<?= $id ?>" style="height: 200px; transition: background-color 0.3s ease;">
                            <span class="position-absolute top-0 end-0 m-2 badge bg-cenco-green shadow-sm text-white <?= $displayBadge ?>" id="badge-llevas-<?= $id ?>">
                                Llevas <span id="count-<?= $id ?>"><?= $cantidadEnCarro ?></span>
                            </span>
                            <img src="<?= $imgSrc ?>" class="img-fluid" style="max-height: 160px; object-fit: contain;">
                        </div>
                        <div class="card-body d-flex flex-column p-4 bg-white">
                            <small class="text-muted mb-1 text-uppercase" style="font-size:0.75rem;"><?= htmlspecialchars($p['marca'] ?? '') ?></small>
                            <h6 class="card-title fw-bold text-cenco-indigo mb-2 text-truncate" title="<?= htmlspecialchars($nombreMostrar) ?>"><?= htmlspecialchars($nombreMostrar) ?></h6>

                            <div class="text-end mb-1" style="height: 20px;">
                                <span id="subtotal-badge-<?= $id ?>" class="badge bg-light text-cenco-indigo border border-cenco-indigo fw-bold <?= $cantidadEnCarro > 0 ? 'd-inline-block' : 'd-none' ?>">
                                    Total: $<?= number_format($precio * $cantidadEnCarro, 0, ',', '.') ?>
                                </span>
                            </div>

                            <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                                <h5 class="text-cenco-red fw-black mb-0 fs-6">$<?= number_format($precio, 0, ',', '.') ?></h5>
                                <div style="min-width: 100px; text-align: right;">
                                    <?php
                                    $displayAdd = $enCarro ? 'd-none' : 'd-block';
                                    $displayControls = $enCarro ? 'd-flex' : 'd-none';
                                    ?>
                                    <form id="form-add-<?= $id ?>" class="<?= $displayAdd ?>" onsubmit="agregarAlCarrito(event, this, <?= $id ?>)">
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <input type="hidden" name="nombre" value="<?= htmlspecialchars($nombreMostrar) ?>">
                                        <input type="hidden" name="precio" value="<?= $precio ?>">
                                        <input type="hidden" name="imagen" value="<?= $img ?>">
                                        <button type="submit" class="btn btn-sm btn-cenco-green rounded-circle shadow-sm transition-hover" style="width:38px;height:38px;"><i class="bi bi-plus-lg fs-6"></i></button>
                                    </form>
                                    <div id="controls-<?= $id ?>" class="align-items-center justify-content-end gap-2 <?= $displayControls ?>">
                                        <button class="btn btn-sm btn-outline-danger rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:32px;height:32px;" onclick="gestionarClickTarjeta(<?= $id ?>, 'bajar')"><i class="bi bi-dash-lg"></i></button>
                                        <span class="fw-bold text-cenco-indigo" style="min-width: 20px; text-align: center;" id="card-count-<?= $id ?>"><?= $cantidadEnCarro ?></span>
                                        <button class="btn btn-sm btn-cenco-green rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:32px;height:32px;" onclick="gestionarClickTarjeta(<?= $id ?>, 'subir')"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        <?php endforeach;
        endif; ?>
    </div>
</div>

<div class="container mb-5 mt-4">
   <div class="d-flex justify-content-between align-items-center mb-4 px-2">
    <h3 class="fw-black text-cenco-indigo border-start border-5 border-warning ps-3 mb-0 ls-1">NUESTROS PROYECTOS</h3>
</div>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-4 justify-content-center">
        <?php
        $proyectosTop = ['Unilever', 'Carozzi', 'Watts', 'Softys', 'Iansa', 'Clorox'];
        foreach ($proyectosTop as $nombreMarca):
            $urlImg = BASE_URL . "img/marcas/" . $nombreMarca . ".png";
        ?>
            <div class="col">
                <a href="<?= BASE_URL ?>home/catalogo?marca=<?= urlencode($nombreMarca) ?>" class="brand-card d-flex align-items-center justify-content-center p-3 rounded-4 text-decoration-none w-100 shadow-sm">
                    <img src="<?= $urlImg ?>" alt="<?= htmlspecialchars($nombreMarca) ?>" class="img-fluid brand-logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <span style="display:none; font-weight:800; color: var(--cenco-indigo); font-size: 1.1rem; text-transform: uppercase;">
                        <?= htmlspecialchars($nombreMarca) ?>
                    </span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<style>
    /* Estilos para las líneas divisorias responsivas */
    .info-card-item {
        position: relative;
        padding: 2rem 1rem;
        /* Espaciado interno cómodo */
    }

    /* EN ESCRITORIO (LG > 992px): Líneas Verticales */
    @media (min-width: 992px) {
        .info-card-item:not(:last-child)::after {
            content: "";
            position: absolute;
            top: 15%;
            /* Dejamos un margen arriba */
            bottom: 15%;
            /* Dejamos un margen abajo para que la línea no toque los bordes */
            right: 0;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
            /* Color suave de la línea */
        }
    }

    /* EN TABLET (MD entre 768px y 991px): Grilla de 2x2 */
    @media (min-width: 768px) and (max-width: 991px) {

        /* Línea vertical para los impares (1 y 3) */
        .info-card-item:nth-child(odd)::after {
            content: "";
            position: absolute;
            top: 15%;
            bottom: 15%;
            right: 0;
            width: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }

        /* Línea horizontal para la primera fila (1 y 2) */
        .info-card-item:nth-child(-n+2) {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
    }

    /* EN MÓVIL (< 768px): Solo líneas horizontales */
    @media (max-width: 767px) {
        .info-card-item:not(:last-child) {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
    }
</style>

<div class="container my-5">
    <div class="row text-center g-0 bg-white rounded-4 shadow-sm border border-light overflow-hidden">

        <div class="col-md-6 col-lg-3 info-card-item d-flex flex-column align-items-center justify-content-center">
            <div class="mb-3 text-cenco-indigo"><i class="bi bi-file-earmark-text fs-1"></i></div>
            <h6 class="fw-bold text-cenco-indigo text-uppercase ls-1 mb-2">Atención Personalizada</h6>
            <p class="text-muted small mb-3 px-3">¿Necesitas una cotización especial o ayuda con tu pedido?</p>
            <a href="https://wa.me/56912345678" target="_blank" class="text-decoration-none fw-bold text-cenco-green small">Contáctanos aquí <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="col-md-6 col-lg-3 info-card-item d-flex flex-column align-items-center justify-content-center">
            <div class="mb-3 text-cenco-indigo"><i class="bi bi-box-seam fs-1"></i></div>
            <h6 class="fw-bold text-cenco-indigo text-uppercase ls-1 mb-2">Seguimiento de Compra</h6>
            <p class="text-muted small mb-3 px-3">Revisa el estado y detalle de tus despachos en tiempo real.</p>
            <a href="<?= BASE_URL ?>perfil" class="text-decoration-none fw-bold text-cenco-green small">Ver estado <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="col-md-6 col-lg-3 info-card-item d-flex flex-column align-items-center justify-content-center">
            <div class="mb-3 text-cenco-indigo position-relative">
                <i class="bi bi-headset fs-1"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-cenco-red border border-light rounded-circle">
                    <span class="visually-hidden">New alerts</span>
                </span>
            </div>
            <h6 class="fw-bold text-cenco-indigo text-uppercase ls-1 mb-2">¿Problemas con tu Pedido?</h6>
            <p class="text-muted small mb-3 px-3">Repórtanos cualquier inconveniente para solucionarlo rápido.</p>
            <a href="mailto:ventas@cencocal.cl?subject=Problema con Pedido" class="text-decoration-none fw-bold text-cenco-green small">Reportar ahora <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="col-md-6 col-lg-3 info-card-item d-flex flex-column align-items-center justify-content-center">
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

<script>
    const BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>js/home.js"></script>