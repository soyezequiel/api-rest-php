<?php

namespace App\Controllers;

class VariarPrecioPorTiempo {
    public function main($precioActual, $timestampUltimaVez, $volatilidadPorSegundo = 0.05) { //funcion que recibe el precio actual, el timestamp de la última vez que se actualizó el precio y la volatilidad por segundo (por defecto 0.05)   
        $tiempoPasado = time() - $timestampUltimaVez;
        
        if($tiempoPasado <= 0) {
            return $precioActual;
        }

        $direccion = mt_rand(-100, 100) / 100; 
        
        $variacion = ($volatilidadPorSegundo / 10) * $tiempoPasado * $direccion; 
        
        $precioNuevo = $precioActual + $variacion;
        
        return round(max(0.01, $precioNuevo), 2);
    }
}