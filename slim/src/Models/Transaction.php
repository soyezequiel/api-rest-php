<?php

namespace App\Models;

use App\Models\DB;
use PDO;

class Transaction
{
    public static function obtenerPorUsuario($userId, $type = null, $asset_id = null)
    {
        $db = DB::getConnection();

        $sql = "
            SELECT
                t.asset_id,
                a.name AS asset_name,
                t.transaction_type,
                t.quantity,
                t.price_per_unit,
                t.total_amount,
                t.transaction_date       
            FROM transactions t
            INNER JOIN assets a ON t.asset_id = a.id 
            WHERE t.user_id = :user_id
        ";


        if ($type !== null) {
            $sql .= " AND t.transaction_type = :type";
        }
        if ($asset_id !== null) {
            $sql .= " AND t.asset_id = :asset_id";
        }
        // 3. Agregamos el ordenamiento
        $sql .= " ORDER BY t.transaction_date DESC";


        $sentencia = $db->prepare($sql);
        $sentencia->bindValue(":user_id", $userId, PDO::PARAM_INT);
        // Los otros binds solo se agregan si la variable tiene un valor (y por ende, si están en el string SQL)
        if ($type !== null) {
            $sentencia->bindValue(":type", $type, PDO::PARAM_STR); // El tipo es string ('buy' o 'sell')
        }
        if ($asset_id !== null) {
            $sentencia->bindValue(":asset_id", $asset_id, PDO::PARAM_INT); // El ID del activo es número
        }
        $sentencia->execute();
        // 4. Retornas el arreglo armado
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function borrarActivoEnCero($userId, $assetId)
    {
        // 1. Buscas la cantidad que tiene el usuario de ese activo
        // 2. Si no existe, puedes devolver algo para disparar el 404
        // 3. Si cantidad > 0, devuelves algo para disparar el 409
        // 4. Si cantidad es exactamente 0 (o 0.00), haces el DELETE en la DB.
    }
}
