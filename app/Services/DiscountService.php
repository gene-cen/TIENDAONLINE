<?php
namespace App\Services;

class DiscountService {
    public static function calcularTotal($subtotal, $usuario = null, $cupon = null) {
        $descuento = 0;

        // 1. Regla de Bienvenida: 10% si es su primera compra
        if ($usuario && !$usuario->primera_compra_realizada) {
            $descuento += 0.10; // 10% de descuento
        }

        // 2. Aplicar cupón si existe
        if ($cupon) {
            $descuento += ($cupon->descuento_porcentaje / 100);
        }

        return $subtotal * (1 - $descuento);
    }
}