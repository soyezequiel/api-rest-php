<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PortfolioController
{
    public function obtenerPortafolio(Request $request, Response $response, array $args = [])
    {

        $userId = $request->getAttribute('user_id');
        $tenencias = \App\Models\Portfolio::obtenerPorUsuario($userId);

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $tenencias,
        ]));
        return $response;
    }
    public function borrarPortafolio(Request $request, Response $response, array $args = [])
    {
        $userId = $request->getAttribute('user_id');
        $assetId = $request->getAttribute('asset_id');
        $respuesta = \App\Models\Portfolio::borrarActivoEnCero($userId, $assetId);

        if ($respuesta === 'no existe') {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'No existe el activo',
            ]));
            return $response->withStatus(404)->withHeader('Content-type', 'application/json');
        }

        if ($respuesta === 'tiene cantidad') {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'No se puede borrar el activo porque tiene cantidad',
            ]));
            return $response->withStatus(409)->withHeader('Content-type', 'application/json');
        }

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Activo borrado correctamente',
        ]));

        return $response->withHeader('Content-type', 'application/json');
    }
}