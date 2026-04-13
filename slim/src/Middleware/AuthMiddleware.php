<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;
use App\Models\User;

class AuthMiddleware
{
    public function __invoke(Request $request, Handler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);

        if (!$token) {
            return $this->errorResponse("Token no proporcionado", 401);
        }

        $db = \App\Models\DB::getConnection();
        $stmt = $db->prepare("SELECT id, token_expired_at FROM users WHERE token = :token LIMIT 1");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        $now = new \DateTime();

        if (!$user || $now > new \DateTime($user['token_expired_at'])) {
            return $this->errorResponse("Sesión expirada o token inválido", 401);
        }

        $currentDate = $now->format('Y-m-d H:i:s');
        $newExpiry = $now->modify('+5 minutes')->format('Y-m-d H:i:s');

        // Extensión de la sesión por 5 minutos adicionales
        User::updateToken($user['id'], $token, $newExpiry, $currentDate);
        // -------------------------------------------------------

        $request = $request->withAttribute('user_id', $user['id']);
        return $handler->handle($request);
    }

    private function errorResponse(string $message, int $status): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode(["status" => "error", "message" => $message]));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}