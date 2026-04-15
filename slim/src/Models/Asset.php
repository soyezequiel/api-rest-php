<?php

namespace App\Models;

class Asset
{
 public static function sinFiltro(array $filtro = [])
    {
        $db = DB::getConnection();
        $query = "";
        $params = [];

        if(isset($filtro['max_price']) && $filtro['max_price'] !== '') {
            $query .= " AND current_price <= :max_price";
            
            $params['max_price'] = $filtro['max_price'];
        } 

        if(isset($filtro['min_price']) && $filtro['min_price'] !== '') {
            $query .= " AND current_price >= :min_price";
            
            $params['min_price'] = $filtro['min_price'];
        }

        if(isset($filtro['type']) && $filtro['type'] !== '') {
            $query .= " AND name LIKE :type";
            $params['type'] = '%' . $filtro['type'] . '%';
        }

        
        $stmt = $db->prepare("SELECT id,name, current_price FROM assets WHERE 1=1 $query");
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);  
    } 

    public static function actualizarPrecio(array $data =[]){

        $db = DB::getConnection();
        $adminId = $data['is_admin'] ?? null;

        // Obtener todos los assets
        $stmt = $db->prepare("SELECT id, current_price, last_update FROM assets");
        $stmt->execute();
        $assets = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Instancia de la clase para variar precios
        $variador = new \App\Controllers\VariarPrecioPorTiempo();

        foreach ($assets as $asset) {
            $timestampUltimaVez = strtotime($asset['last_update']);
            $precioActual = (float) $asset['current_price'];
            $nuevoPrecio = $variador->main($precioActual, $timestampUltimaVez);

            // Actualizar el precio y last_update
            $updateStmt = $db->prepare("UPDATE assets SET current_price = ?, last_update = NOW() WHERE id = ?");
            $updateStmt->execute([$nuevoPrecio, $asset['id']]);
        }

        return true; // O algún indicador de éxito
    }

}