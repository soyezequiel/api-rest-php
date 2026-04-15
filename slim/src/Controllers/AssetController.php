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

        try {
            $assets = Asset::sinFiltro($queryParams);

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

    private function errorResponse(Response $response, string $message, int $status)
        {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $message
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
        }


}