<?php

class VariarPrecioPorTiempo {
    public function main($precioActual, $timestampUltimaVez, $volatilidadPorSegundo = 0.05) {
        $tiempoPasado = time() - $timestampUltimaVez;
        
        if($tiempoPasado < 0) {
            return $precioActual; // Si el timestamp es del futuro, no variamos el precio
        }

        $direccion = mt_rand(-100, 100) /100; // Genera un número entre -1 y 1 para determinar la dirección de la variación
        $variacion = $volatilidadPorSegundo * $tiempoPasado * $direccion; // Calcula la variación total basada en el tiempo pasado y la dirección

        $precioNuevo = $precioActual + $variacion;

        return $precioNuevo;
    }
}