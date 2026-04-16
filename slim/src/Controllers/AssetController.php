<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use App\Models\Asset;
use App\Models\User;

class AssetController
{
    public function listar(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();
        $allowedFilters = ['min_price', 'max_price', 'type'];

        $invalidParams = array_diff(array_keys($queryParams), $allowedFilters);

        if(!empty($invalidParams)) {  
            return $this->errorResponse(
                $response,'Parámetros inválidos',400);   
        }
        
        try {
            $assets = Asset::obtenerAssetsFiltrados($queryParams);

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $assets
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error interno del servidor', 404);
        }
    }

    public function actualizarPrecio(Request $request, Response $response)
    {
        $userId = $request->getAttribute('user_id');
        $user = User::getById($userId);

        if (!$user) {
            return $this->errorResponse($response, 'Usuario no encontrado', 404);
        }

        if ($user['is_admin'] != 1) {
            return $this->errorResponse($response, 'Acceso denegado: se requiere ser administrador', 401);
        }

        try {
            Asset::actualizarPrecio();

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Precios de activos actualizados correctamente'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error al actualizar precios', 409);
        }
    }

    public function consultarHistorial(Request $request, Response $response, array $args = [])
    {
        $asset_id = (int)$args['asset_id'];   
        $quantity = (int)$args['quantity'];
        // Validar que quantity esté entre 1 y 5
        if ($quantity < 1 || $quantity > 5) {
            return $this->errorResponse($response, 'Quantity no esta entre 1 y 5', 400);
        }
        
        try {
            $historial = Asset::getHistorial($asset_id, $quantity); 
            // Si el historial es null, significa que no se encontró el asset
            if($historial === null) {
                return $this->errorResponse($response, 'No existe ese activo', 404);
            }

            // Si el historial es un array vacío, significa que no hay registros para ese asset
            $response->getBody()->write(json_encode([
                "status" => "success",
                "data" => $historial
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error al consultar historial de precios', 409);
        }


    }


    private function errorResponse(Response $response, string $message, int $status)
        {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $message
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
        }


}