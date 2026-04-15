<?php

namespace App\Controllers;

class VariarPrecioPorTiempo {
    public function main($precioActual, $timestampUltimaVez, $volatilidadPorSegundo = 0.05) {
        $tiempoPasado = time() - $timestampUltimaVez;
        
        if($tiempoPasado <= 0) {
            return $precioActual;
        }

        // 1. Mantenemos tu lógica de dirección original
        $direccion = mt_rand(-100, 100) / 100; 

        // 2. Calculamos la variación total basada en el tiempo pasado
        $variacion = ($volatilidadPorSegundo / 10) * $tiempoPasado * $direccion; 

        // 3. Aplicamos la variación al precio actual
        $precioNuevo = $precioActual + $variacion;

        // 4. Piso mínimo de 0.01 para que nunca valga cero
        return round(max(0.01, $precioNuevo), 2);
    }
}