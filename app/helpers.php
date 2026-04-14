<?php
// app/helpers.php

function obtenerIconoCategoria($nombreCategoria) {
    $nombreLower = mb_strtolower($nombreCategoria, 'UTF-8');

    // Mapeo directo de palabras clave a iconos (FontAwesome)
    $mapaIconos = [
        'bebé'       => 'fa-solid fa-baby-carriage',
        'mascota'    => 'fa-solid fa-paw',
        'conserva'   => 'fa-solid fa-jar',
        'bebida'     => 'fa-solid fa-glass-water',
        'refresco'   => 'fa-solid fa-glass-water',
        'licor'      => 'fa-solid fa-glass-water',
        'botillería' => 'fa-solid fa-wine-bottle',
        'despensa'   => 'fa-solid fa-basket-shopping',
        'fruta'      => 'fa-solid fa-apple-whole',
        'verdura'    => 'fa-solid fa-carrot',
        'carne'      => 'fa-solid fa-drumstick-bite',
        'pescado'    => 'fa-solid fa-fish',
        'limpieza'   => 'fa-solid fa-sparkles',
        'aseo'       => 'fa-solid fa-sparkles',
        'lacteo'     => 'fa-solid fa-cow',
        'huevo'      => 'fa-solid fa-egg',
        'pan'        => 'fa-solid fa-bread-slice',
        'congelado'  => 'fa-solid fa-snowflake',
        'farmacia'   => 'fa-solid fa-pills'
    ];

    // Recorremos el mapa buscando coincidencias
    foreach ($mapaIconos as $palabraClave => $claseIcono) {
        if (strpos($nombreLower, $palabraClave) !== false) {
            return $claseIcono;
        }
    }

    // Icono por defecto si no encuentra nada
    return 'fa-solid fa-tag';
}
// Configuración de Venta Asistida (Piloto)
if (!defined('SUCURSALES_PILOTO_ASISTIDO')) {
    define('SUCURSALES_PILOTO_ASISTIDO', [10]); // Solo sucursal 10 habilitada
}

/**
 * Verifica si el usuario actual es un admin de una sucursal piloto
 */
function puedeRealizarVentaAsistida() {
    if (!isset($_SESSION['rol_id']) || !in_array((int)$_SESSION['rol_id'], [1, 2])) {
        return false;
    }

    // Asumimos que guardas la sucursal del admin en la sesión al loguear
    $sucursalAdmin = $_SESSION['admin_sucursal'] ?? null;
    return in_array((int)$sucursalAdmin, SUCURSALES_PILOTO_ASISTIDO);
}
?>

