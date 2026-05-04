<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Trade;
use Exception;

class TradeController
{
    public function buy(Request $request, Response $response)
    {
        $usuarioId = $request->getAttribute('user_id');
        $cuerpoPeticion = json_decode($request->getBody()->getContents(), true);
        if (!is_array($cuerpoPeticion)) {
            $cuerpoPeticion = [];
        }

        $asset_id = $cuerpoPeticion['asset_id'] ?? null;
        $quantity = $cuerpoPeticion['quantity'] ?? null;

        if (
            !is_numeric($asset_id) || $asset_id <= 0 || floor($asset_id) != $asset_id ||
            !is_numeric($quantity) || $quantity <= 0 || floor($quantity) != $quantity
        ) {
            $respuestaError = ["status" => "error", "message" => "Cantidad invalida o datos faltantes"];
            $response->getBody()->write(json_encode($respuestaError));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $resultadoCompra = Trade::procesarCompra($usuarioId, (int)$asset_id, (int)$quantity);

            if ($resultadoCompra['status'] === 'asset_not_found') {
                $respuestaError = ["status" => "error", "message" => "Activo inexistente"];
                $response->getBody()->write(json_encode($respuestaError));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            if ($resultadoCompra['status'] === 'insufficient_funds') {
                $respuestaError = ["status" => "error", "message" => "Saldo insuficiente"];
                $response->getBody()->write(json_encode($respuestaError));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            $respuestaExito = [
                "status" => "success",
                "message" => "Compra realizada con exito",
                "costo_total" => $resultadoCompra['costo_total'],
                "precio_ejecutado" => $resultadoCompra['precio_ejecutado']
            ];
            $response->getBody()->write(json_encode($respuestaExito));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $excepcion) {
            $respuestaError = [
                "status" => "error",
                "message" => "Error al procesar la compra: " . $excepcion->getMessage()
            ];
            $response->getBody()->write(json_encode($respuestaError));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
