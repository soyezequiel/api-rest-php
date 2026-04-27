<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;
use App\Models\Asset;
use App\Models\Trade;
use Exception;

class TradeController
{
    public function buy(Request $request, Response $response)
    {
        // 1. Obtener ID del usuario autenticado (inyectado por AuthMiddleware)
        $usuarioId = $request->getAttribute('user_id');

        // 2. Obtener los parámetros body de la petición HTTP
        $cuerpoPeticion = json_decode($request->getBody()->getContents(), true);

        $asset_id = $cuerpoPeticion['asset_id'] ?? null;
        $quantity = $cuerpoPeticion['quantity'] ?? null;

        // Validar cantidad insuficiente, decimal o datos faltantes (400)
        if (!$asset_id || !is_numeric($quantity) || $quantity <= 0 || floor($quantity) != $quantity) {
            $respuestaError = ["status" => "error", "message" => "Cantidad inválida o datos faltantes"];
            $response->getBody()->write(json_encode($respuestaError));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Validar existencia de activo (404) a través del Modelo Asset
            $activo = Asset::getById($asset_id);
            if (!$activo) {
                $respuestaError = ["status" => "error", "message" => "Activo inexistente"];
                $response->getBody()->write(json_encode($respuestaError));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Sacar cuentas del usuario y costo
            $current_price = $activo['current_price'];
            $costoTotal = $current_price * $quantity;

            // Validar saldo (409) a través del Modelo User
            $usuario = User::getById($usuarioId);
            $saldoUsuario = $usuario['balance'];

            if ($saldoUsuario < $costoTotal) {
                $respuestaError = ["status" => "error", "message" => "Saldo insuficiente"];
                $response->getBody()->write(json_encode($respuestaError));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            // Procesar la compra de forma transaccional usando el Modelo Trade
            Trade::procesarCompra($usuarioId, $asset_id, $quantity, $current_price, $costoTotal);

            // Devolver éxito 200 OK
            $respuestaExito = [
                "status" => "success",
                "message" => "Compra realizada con éxito",
                "costo_total" => $costoTotal,
                "precio_ejecutado" => $current_price
            ];
            $response->getBody()->write(json_encode($respuestaExito));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (Exception $excepcion) {
            // Error general
            $respuestaError = [
                "status" => "error",
                "message" => "Error al procesar la compra: " . $excepcion->getMessage()
            ];
            $response->getBody()->write(json_encode($respuestaError));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
