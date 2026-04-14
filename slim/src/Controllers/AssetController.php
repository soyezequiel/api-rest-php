<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use App\Models\Asset;

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
            return $this->errorResponse($response, 'Error interno del servidor', 500);
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