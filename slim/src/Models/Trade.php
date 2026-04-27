<?php

namespace App\Models;

use Exception;

class Trade
{
    public static function procesarCompra($usuarioId, $asset_id, $quantity, $current_price, $costoTotal)
    {
        $conexion = DB::getConnection();

        try {
            // Empezamos la transacción atómica
            $conexion->beginTransaction();

            // 1. Descontar saldo del usuario
            $actualizarBalance = $conexion->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $actualizarBalance->execute([$costoTotal, $usuarioId]);

            // 2. Alta o actualización de portfolio
            // Usamos ON DUPLICATE KEY UPDATE por si el usuario ya tenía el activo
            $actualizarPortfolio = $conexion->prepare("
                INSERT INTO portfolio (user_id, asset_id, quantity) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
            ");
            $actualizarPortfolio->execute([$usuarioId, $asset_id, $quantity]);

            // 3. Registrar historial en transactions
            $registrarTransaccion = $conexion->prepare("
                INSERT INTO transactions (user_id, asset_id, transaction_type, quantity, price_per_unit, total_amount) 
                VALUES (?, ?, 'buy', ?, ?, ?)
            ");
            $registrarTransaccion->execute([$usuarioId, $asset_id, $quantity, $current_price, $costoTotal]);

            // Confirmar transacción
            $conexion->commit();

            return true;
        } catch (Exception $excepcion) {
            // Si algo falla, deshacemos los cambios en las 3 tablas y pasamos el error arriba
            $conexion->rollBack();
            throw $excepcion;
        }
    }
}
