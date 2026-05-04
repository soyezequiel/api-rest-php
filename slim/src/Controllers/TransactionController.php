<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TransactionController
{
    public function obtenerTransacciones(Request $request, Response $response, array $args = [])
    {

        $userId = $request->getAttribute('user_id');
        $transacciones = \App\Models\Transaction::obtenerPorUsuario($userId);

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $transacciones,
        ]));
        return $response;
    }
}