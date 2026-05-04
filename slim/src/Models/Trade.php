<?php

namespace App\Models;

use Exception;

class Trade
{
    public static function procesarCompra($usuarioId, $asset_id, $quantity)
    {
        $conexion = DB::getConnection();

        try {
            $conexion->beginTransaction();

            $consultaActivo = $conexion->prepare("
                SELECT id, current_price
                FROM assets
                WHERE id = ?
                FOR UPDATE
            ");
            $consultaActivo->execute([$asset_id]);
            $activo = $consultaActivo->fetch(\PDO::FETCH_ASSOC);

            if (!$activo) {
                $conexion->rollBack();
                return ["status" => "asset_not_found"];
            }

            $current_price = (float)$activo['current_price'];
            $costoTotal = $current_price * $quantity;

            $actualizarBalance = $conexion->prepare("
                UPDATE users
                SET balance = balance - ?
                WHERE id = ? AND balance >= ?
            ");
            $actualizarBalance->execute([$costoTotal, $usuarioId, $costoTotal]);

            if ($actualizarBalance->rowCount() !== 1) {
                $conexion->rollBack();
                return [
                    "status" => "insufficient_funds",
                    "costo_total" => $costoTotal,
                    "precio_ejecutado" => $current_price
                ];
            }

            $actualizarPortfolio = $conexion->prepare("
                INSERT INTO portfolio (user_id, asset_id, quantity)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
            ");
            $actualizarPortfolio->execute([$usuarioId, $asset_id, $quantity]);

            $registrarTransaccion = $conexion->prepare("
                INSERT INTO transactions (user_id, asset_id, transaction_type, quantity, price_per_unit, total_amount)
                VALUES (?, ?, 'buy', ?, ?, ?)
            ");
            $registrarTransaccion->execute([$usuarioId, $asset_id, $quantity, $current_price, $costoTotal]);

            $conexion->commit();

            return [
                "status" => "success",
                "costo_total" => $costoTotal,
                "precio_ejecutado" => $current_price
            ];
        } catch (Exception $excepcion) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            throw $excepcion;
        }
    }
}
