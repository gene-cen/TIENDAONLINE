<?php
// views/home/partials/franja_notificacion.php
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
    <div class="container-fluid px-3 px-xl-5 franja-wrapper" id="franjaNotificacion">
        <div class="franja-content bg-cenco-indigo text-white">
            <img src="<?= BASE_URL ?>img/cencocalin/<?= $franjaImg ?>" alt="Cencocalin" class="franja-img">
            <div><h5 class="fw-black mb-0 lh-1" style="font-size: 1.3rem;"><?= $franjaTitle ?></h5><span class="small opacity-75 fw-medium" style="font-size: 0.95rem;"><?= $franjaText ?></span></div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const franja = document.getElementById('franjaNotificacion');
            if (franja) { 
                setTimeout(() => { franja.classList.add('desplegado'); }, 150); 
                setTimeout(() => { franja.classList.remove('desplegado'); setTimeout(() => { franja.remove(); }, 600); }, 3500); 
            }
            setTimeout(() => { if (window.history.replaceState) { const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname; window.history.replaceState({ path: cleanUrl }, '', cleanUrl); } }, 100);
        });
    </script>
<?php endif; ?>