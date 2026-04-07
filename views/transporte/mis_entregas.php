<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&family=Roboto:wght@400;700;900&display=swap" rel="stylesheet">

<style>
    :root {
        --cenco-indigo: #2A1B5E;
        --cenco-green: #76C043;
        --cenco-light: #F4F6F8;
        --text-dark: #2D3748;
        --text-muted: #718096;
    }

    body {
        font-family: 'Roboto', sans-serif;
        background-color: var(--cenco-light);
        -webkit-tap-highlight-color: transparent; /* Quita el destello azul en celulares */
    }

    /* --- ENCABEZADO FIJO (Estilo App) --- */
    .app-header {
        background: var(--cenco-indigo);
        color: white;
        padding: 20px;
        border-bottom-left-radius: 25px;
        border-bottom-right-radius: 25px;
        box-shadow: 0 4px 15px rgba(42, 27, 94, 0.2);
        margin-bottom: 25px;
    }

    /* --- TARJETAS DE PEDIDO --- */
    .driver-card {
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        border: 1px solid rgba(0,0,0,0.02);
        margin-bottom: 25px;
        overflow: hidden;
    }

    .card-top-bar {
        background: #fafafa;
        padding: 12px 20px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 900;
        color: var(--text-dark);
    }

    .badge-orden {
        background: rgba(42, 27, 94, 0.1);
        color: var(--cenco-indigo);
        padding: 6px 14px;
        border-radius: 12px;
        font-size: 0.85rem;
    }

    .card-content {
        padding: 20px;
    }

    .cliente-nombre {
        font-family: 'Nunito', sans-serif;
        font-weight: 900;
        font-size: 1.3rem;
        color: var(--cenco-indigo);
        line-height: 1.2;
        margin-bottom: 8px;
    }

    .cliente-direccion {
        color: var(--text-muted);
        font-size: 0.95rem;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 25px;
    }

    /* --- BOTONES DE ACCIÓN (Mapa y Llamar) --- */
    /* Usamos flexbox con gap para que NUNCA se superpongan */
    .action-grid {
        display: flex;
        gap: 12px;
        margin-bottom: 25px;
    }

    .btn-app-action {
        flex: 1; /* Ambos botones miden exactamente la mitad */
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 15px 5px;
        border-radius: 18px;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.85rem;
        transition: 0.2s;
        border: 2px solid transparent;
    }

    .btn-mapa {
        background: #EBF4FF;
        color: #3182CE;
    }
    .btn-mapa:active { background: #bee3f8; }

    .btn-llamar {
        background: #F0FFF4;
        color: #38A169;
    }
    .btn-llamar:active { background: #c6f6d5; }

    .btn-app-action i {
        font-size: 1.5rem;
        margin-bottom: 5px;
    }

    /* --- ÁREA DE CÁMARA MEJORADA --- */
    .upload-zone {
        border: 2px dashed #CBD5E0;
        border-radius: 18px;
        padding: 20px;
        text-align: center;
        background: #F7FAFC;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.3s;
        display: block;
        margin-bottom: 20px;
    }

    /* Clase que se añade con JS cuando toman la foto */
    .upload-zone.foto-ok {
        border-color: var(--cenco-green);
        background: #F0FFF4;
        color: var(--cenco-green);
    }

    .upload-zone input[type="file"] {
        display: none; /* Ocultamos el feo botón por defecto del navegador */
    }

    /* --- BOTÓN FINALIZAR GIGANTE --- */
    .btn-submit-app {
        background: var(--cenco-green);
        color: white;
        border: none;
        width: 100%;
        padding: 18px;
        border-radius: 18px;
        font-weight: 900;
        font-size: 1.1rem;
        letter-spacing: 1px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 10px 20px rgba(118, 192, 67, 0.25);
    }
    .btn-submit-app:active {
        transform: scale(0.98);
    }
    .btn-submit-app:disabled {
        background: #A0AEC0;
        box-shadow: none;
    }
</style>

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

                    <?php if (in_array($p['forma_pago_id'], [5, 7])): ?>
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
<script>
// Feedback visual cuando el chofer toma la foto
function fotoLista(input, id) {
    if (input.files && input.files[0]) {
        const zone = document.getElementById('zone-' + id);
        const icon = document.getElementById('icon-' + id);
        const text = document.getElementById('text-' + id);
        
        // Cambiamos el estilo a verde (éxito)
        zone.classList.add('foto-ok');
        icon.className = 'bi bi-check-circle-fill fs-1 d-block mb-2';
        text.innerText = '¡Foto Capturada Correctamente!';
    }
}

// Proceso de guardado
function procesarEntrega(event, id) {
    event.preventDefault();
    const btn = event.target.querySelector('button');
    const form = event.target;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> GUARDANDO...';

    if (!navigator.geolocation) {
        Swal.fire('Error', 'Dispositivo sin GPS compatible.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-all fs-4"></i> ENTREGAR';
        return;
    }

    navigator.geolocation.getCurrentPosition((pos) => {
        form.querySelector('.lat').value = pos.coords.latitude;
        form.querySelector('.lng').value = pos.coords.longitude;

        fetch('<?= BASE_URL ?>transporte/finalizarEntrega', {
            method: 'POST',
            body: new FormData(form)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    title: '¡ÉXITO!',
                    text: 'Entrega registrada en el sistema.',
                    icon: 'success',
                    confirmButtonColor: '#76C043',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Animación suave al desaparecer la tarjeta
                    const card = document.getElementById('orden-' + id);
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(() => card.remove(), 300);
                });
            } else {
                Swal.fire('Error', data.message, 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check2-all fs-4"></i> ENTREGAR';
            }
        });
    }, (err) => {
        Swal.fire('Se requiere GPS', 'Por favor activa la ubicación de tu celular.', 'warning');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-all fs-4"></i> ENTREGAR';
    }, { enableHighAccuracy: true });
}
</script>