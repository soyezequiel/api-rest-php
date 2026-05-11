<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use App\Models\User;

class UserController
{
    public function registrar(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($name) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/", $name)) {
            return $this->errorResponse($response, 'El nombre es obligatorio y solo debe contener letras', 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->errorResponse($response, 'El formato de email no es válido', 400);
        }

        $regexPass = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&+])[A-Za-z\d@$!%*?&]{8,}$/";
        if (!preg_match($regexPass, $password)) {
            return $this->errorResponse($response, 'La contraseña no cumple los requisitos de seguridad', 400);
        }

        try {

            if (User::emailExists($email)) {
                return $this->errorResponse($response, 'El email ya está registrado', 409);
            }

            User::create($name, $email, $password);

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Usuario registrado exitosamente'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error interno del servidor', 500);
        }
    }

    public function login(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $user = User::getByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            $secretKey = $_ENV['JWT_SECRET'] ?? 'esta_es_una_clave_secreta_muy_larga_y_segura_unlp_2026_abc123';

            $duration = 300;
            $expirationTime = time() + $duration;

            $payload = [
                'sub' => $user['id'],
                'iat' => time(),
                'exp' => $expirationTime
            ];

            $token = JWT::encode($payload, $secretKey, 'HS256');

            $currentDate = date('Y-m-d H:i:s');
            $expirationDate = date('Y-m-d H:i:s', $expirationTime);

            User::updateToken($user['id'], $token, $expirationDate, $currentDate);

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'token' => $token,
                'message' => 'Login exitoso'
            ]));


            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Credenciales inválidas']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }

    public function logout(Request $request, Response $response)
    {
        $userId = $request->getAttribute('user_id');
        User::logout($userId);

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Sesión cerrada correctamente'
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    private function errorResponse(Response $response, string $message, int $status)
    {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => $message
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function verPerfil(Request $request, Response $response, array $args)
    {
        $targetUserId = (int)$args['user_id'];
        $requesterId = (int)$request->getAttribute('user_id');

        $requester = User::getById($requesterId);

        if ($requesterId !== $targetUserId && !($requester['is_admin'] ?? false)) {
            return $this->errorResponse($response, 'No tiene permisos para ver este perfil', 401);
        }

        $userData = User::getProfileWithTotal($targetUserId);

        if (!$userData) {
            return $this->errorResponse($response, 'Usuario no encontrado', 404);
        }

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $userData
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function editarPerfil(Request $request, Response $response, array $args)
    {
        $targetUserId = (int)$args['user_id'];
        $requesterId = (int)$request->getAttribute('user_id');
        $data = $request->getParsedBody();

        $requester = User::getById($requesterId);
        if ($requesterId !== $targetUserId && !($requester['is_admin'] ?? false)) {
            return $this->errorResponse($response, 'No tiene permisos para editar este perfil', 401);
        }

        if (!User::getById($targetUserId)) {
            return $this->errorResponse($response, 'Usuario no encontrado', 404);
        }

        if (isset($data['name']) && !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/", $data['name'])) {
            return $this->errorResponse($response, 'El nombre solo debe contener letras', 400);
        }

        if (isset($data['password'])) {
            $regexPass = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&+])[A-Za-z\d@$!%*?&]{8,}$/";
            if (!preg_match($regexPass, $data['password'])) {
                return $this->errorResponse($response, 'La nueva contraseña no cumple los requisitos', 400);
            }
        }

        User::update($targetUserId, $data);

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Perfil actualizado correctamente'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function listarUsuarios(Request $request, Response $response)
    {
        $requesterId = (int)$request->getAttribute('user_id');
        $requester = User::getById($requesterId);

        if (!($requester['is_admin'] ?? false)) {
            return $this->errorResponse($response, 'Acceso denegado: se requieren permisos de administrador', 401);
        }

        $lista = User::getAllWithTotal();

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $lista
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
