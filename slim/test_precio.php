<?php

require_once __DIR__ . '/src/Controllers/variarPrecioPorTiempo.php';

$logic = new VariarPrecioPorTiempo();

$precioInicial = 1000.0;
$ahora = time();

echo "--- Pruebas de Variación de Precio ---\n";

// Caso 1: Pasaron 10 segundos
$diezSegundosAtras = $ahora - 10;
$resultado1 = $logic->main($precioInicial, $diezSegundosAtras);
echo "1. Pasaron 10s: Precio final $resultado1\n";

// Caso 2: Pasó 1 hora (3600 segundos)
$unaHoraAtras = $ahora - 3600;
$resultado2 = $logic->main($precioInicial, $unaHoraAtras);
echo "2. Pasó 1 hora: Precio final $resultado2\n";

// Caso 3: No pasó tiempo (0 segundos)
$resultado3 = $logic->main($precioInicial, $ahora);
echo "3. Tiempo cero: Precio final $resultado3 (Debe ser $precioInicial)\n";
