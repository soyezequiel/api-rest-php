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
}