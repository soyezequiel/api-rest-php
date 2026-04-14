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


}