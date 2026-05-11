<?php

namespace App\Models;

use Exception;

class Trade
{
    // Procesa la compra de un activo por parte de un usuario de forma atomica y segura
    public static function procesarCompra($usuarioId, $asset_id, $quantity)
    {
        $conexion = DB::getConnection();

        try {
            $conexion->beginTransaction();

            $consultaActivo = $conexion->prepare("
                SELECT id, current_price
                FROM assets
                WHERE id = :asset_id
                FOR UPDATE
            ");
            $consultaActivo->execute([':asset_id' => $asset_id]);
            $activo = $consultaActivo->fetch(\PDO::FETCH_ASSOC);

            if (!$activo) {
                $conexion->rollBack();
                return ["status" => "asset_not_found"];
            }

            $current_price = (float) $activo['current_price'];
            $costoTotal = $current_price * $quantity;

            $actualizarBalance = $conexion->prepare("
                UPDATE users
                SET balance = balance - :costo
                WHERE id = :user_id AND balance >= :costo_minimo
            ");
            $actualizarBalance->execute([
                ':costo'        => $costoTotal,
                ':user_id'      => $usuarioId,
                ':costo_minimo' => $costoTotal
            ]);

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
                VALUES (:user_id, :asset_id, :quantity)
                ON DUPLICATE KEY UPDATE quantity = quantity + :quantity_update
            ");
            $actualizarPortfolio->execute([
                ':user_id'         => $usuarioId,
                ':asset_id'        => $asset_id,
                ':quantity'        => $quantity,
                ':quantity_update' => $quantity
            ]);

            $registrarTransaccion = $conexion->prepare("
                INSERT INTO transactions (user_id, asset_id, transaction_type, quantity, price_per_unit, total_amount)
                VALUES (:user_id, :asset_id, 'buy', :quantity, :price_per_unit, :total_amount)
            ");
            $registrarTransaccion->execute([
                ':user_id'        => $usuarioId,
                ':asset_id'       => $asset_id,
                ':quantity'       => $quantity,
                ':price_per_unit' => $current_price,
                ':total_amount'   => $costoTotal
            ]);

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
    // Procesa la venta de un activo por parte de un usuario de forma atomica y segura
    public static function procesarVenta($usuarioId, $asset_id, $quantity)
    {
        $conexion = DB::getConnection();

        try {
            $conexion->beginTransaction();

            $consultaActivo = $conexion->prepare("
                SELECT id, current_price
                FROM assets
                WHERE id = :asset_id
                FOR UPDATE
            ");
            $consultaActivo->execute([':asset_id' => $asset_id]);
            $activo = $consultaActivo->fetch(\PDO::FETCH_ASSOC);

            if (!$activo) {
                $conexion->rollBack();
                return ["status" => "asset_not_found"];
            }

            $current_price = (float) $activo['current_price'];
            $gananciaTotal = $current_price * $quantity;

            $actualizarPortfolio = $conexion->prepare("
                UPDATE portfolio
                SET quantity = quantity - :cantidad
                WHERE user_id = :user_id 
                  AND asset_id = :asset_id 
                  AND quantity >= :cantidad_minima
            ");
            $actualizarPortfolio->execute([
                ':cantidad'        => $quantity,
                ':user_id'         => $usuarioId,
                ':asset_id'        => $asset_id,
                ':cantidad_minima' => $quantity
            ]);

            if ($actualizarPortfolio->rowCount() !== 1) {
                $conexion->rollBack();
                return [
                    "status" => "insuficientes activos",
                    "ganancia_total" => $gananciaTotal,
                    "precio_ejecutado" => $current_price
                ];
            }

            $actualizarBalance = $conexion->prepare("
                UPDATE users
                SET balance = balance + :ganancia
                WHERE id = :user_id
            ");
            $actualizarBalance->execute([
                ':ganancia' => $gananciaTotal,
                ':user_id'  => $usuarioId
            ]);

            $registrarTransaccion = $conexion->prepare("
                INSERT INTO transactions (user_id, asset_id, transaction_type, quantity, price_per_unit, total_amount)
                VALUES (:user_id, :asset_id, 'sell', :quantity, :price_per_unit, :total_amount)
            ");
            $registrarTransaccion->execute([
                ':user_id'        => $usuarioId,
                ':asset_id'       => $asset_id,
                ':quantity'       => $quantity,
                ':price_per_unit' => $current_price,
                ':total_amount'   => $gananciaTotal
            ]);

            $conexion->commit();

            return [
                "status" => "success",
                "ganancia_total" => $gananciaTotal,
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
