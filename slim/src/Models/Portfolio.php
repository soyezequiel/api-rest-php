<?php

namespace App\Models;

use App\Models\DB; // Tu conexión a base de datos
use PDO;

class Portfolio
{
    public static function obtenerPorUsuario($userId)
    {
        $db = DB::getConnection();

        // 1. Aquí harías el SQL para obtener las tenencias (query depende de tu DB)
        // 2. Aquí harías el cruce con el precio actual (current_price) del activo
        // 3. Calculas valuación (cantidad * precio)
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
