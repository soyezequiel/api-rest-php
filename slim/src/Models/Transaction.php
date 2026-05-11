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
        $sql .= " ORDER BY t.transaction_date DESC";


        $sentencia = $db->prepare($sql);
        $sentencia->bindValue(":user_id", $userId, PDO::PARAM_INT);
        if ($type !== null) {
            $sentencia->bindValue(":type", $type, PDO::PARAM_STR); 
        }
        if ($asset_id !== null) {
            $sentencia->bindValue(":asset_id", $asset_id, PDO::PARAM_INT); 
        }
        $sentencia->execute();
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

}
