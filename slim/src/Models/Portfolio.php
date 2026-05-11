<?php

namespace App\Models;

use App\Models\DB;
use PDO;

class Portfolio
{
    public static function obtenerPorUsuario($userId)
    {
        $db = DB::getConnection();

        $sql = "
            SELECT
                p.asset_id,
                a.name AS asset_name,
                p.quantity,
                a.current_price,
                (p.quantity * a.current_price) AS total_value            
            FROM portfolio p
            INNER JOIN assets a ON p.asset_id = a.id 
            WHERE p.user_id = :user_id
        ";
        $sentencia = $db->prepare($sql);
        $sentencia->bindValue(":user_id", $userId, PDO::PARAM_INT);
        $sentencia->execute();
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    // Elimina la entrada del portafolio para un activo específico si la cantidad es cero
    public static function borrarActivoEnCero($userId, $assetId)
    {
        $db = DB::getConnection();
        $sql = "
            SELECT
                quantity
            FROM portfolio
            WHERE user_id = :user_id
            AND asset_id = :asset_id
        ";

        $sentencia = $db->prepare($sql);
        $sentencia->bindValue(":user_id", $userId, PDO::PARAM_INT);
        $sentencia->bindValue(":asset_id", $assetId, PDO::PARAM_INT);
        $sentencia->execute();
        $resultado = $sentencia->fetch(PDO::FETCH_ASSOC);
        if (!$resultado) {
            return 'no existe';
        }
        if ($resultado['quantity'] > 0) {
            return 'tiene cantidad';
        }
        $sql = "
            DELETE
            FROM portfolio
            WHERE user_id = :user_id
            AND asset_id = :asset_id
            AND quantity = 0
        ";

        $sentencia = $db->prepare($sql);
        $sentencia->bindValue(":user_id", $userId, PDO::PARAM_INT);
        $sentencia->bindValue(":asset_id", $assetId, PDO::PARAM_INT);
        $sentencia->execute();
        return 'success';
    }
}
