<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&family=Roboto:wght@400;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>css/transporte.css">

<div class="app-header">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <div class="small fw-bold opacity-75 text-uppercase mb-1"><?= date('d \d\e F, Y') ?></div>
            <h2 class="fw-black mb-0">Mi Ruta</h2>
        </div>
        <div class="text-center">
            <h2 class="fw-black text-white mb-0"><?= count($pedidos) ?></h2>
            <span class="small opacity-75 fw-bold">Pendientes</span>
        </div>
    </div>
</div>

<div class="container pb-5">

    <?php if (empty($pedidos)): ?>
        <div class="text-center py-5">
            <img src="<?= BASE_URL ?>img/logo.png" style="width: 150px; opacity: 0.2; filter: grayscale(1);" class="mb-4">
            <h3 class="fw-black text-cenco-indigo">Turno Finalizado</h3>
            <p class="text-muted">No tienes más entregas asignadas hoy.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($pedidos as $p): ?>
        <div class="driver-card" id="orden-<?= $p['id'] ?>">
            <div class="card-top-bar">
                <span class="badge-orden">#<?= $p['id'] ?></span>
                <span class="small"><i class="bi bi-clock-history me-1"></i><?= $p['hora_creacion'] ?></span>
            </div>

            <div class="card-content">
                <div class="cliente-nombre"><?= htmlspecialchars($p['nombre_destinatario']) ?></div>
                <div class="cliente-direccion">
                    <i class="bi bi-geo-alt-fill text-danger mt-1"></i>
                    <span><?= htmlspecialchars($p['direccion_entrega_texto']) ?></span>
                </div>

                <div class="action-grid">
                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($p['direccion_entrega_texto']) ?>" target="_blank" class="btn-app-action btn-mapa">
                        <i class="bi bi-map-fill"></i>
                        <span>Ir al Mapa</span>
                    </a>
                    <a href="tel:<?= $p['telefono_contacto'] ?>" class="btn-app-action btn-llamar">
                        <i class="bi bi-telephone-outbound-fill"></i>
                        <span>Llamar</span>
                    </a>
                </div>

                <form onsubmit="procesarEntrega(event, <?= $p['id'] ?>)" enctype="multipart/form-data">
                    <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="latitud" class="lat">
                    <input type="hidden" name="longitud" class="lng">

                    <?php if (in_array($p['forma_pago_id'], [5, 7])): // 5 y 7 suelen ser Transferencia/Venta Asistida ?>
                        <label class="upload-zone" id="zone-<?= $p['id'] ?>">
                            <i class="bi bi-camera-fill fs-1 d-block mb-2" id="icon-<?= $p['id'] ?>"></i>
                            <span class="fw-bold" id="text-<?= $p['id'] ?>">Toca para tomar foto del voucher</span>
                            <input type="file" name="comprobante" accept="image/*" capture="camera" required onchange="fotoLista(this, <?= $p['id'] ?>)">
                        </label>
                    <?php endif; ?>

                    <button type="submit" class="btn-submit-app">
                        <i class="bi bi-check2-all fs-4"></i> ENTREGAR
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>window.BASE_URL = '<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>js/transporte.js"></script>