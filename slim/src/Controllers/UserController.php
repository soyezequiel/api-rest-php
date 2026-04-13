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

        // 1. Validaciones de formato (incluyen el chequeo de empty)
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

    private function errorResponse(Response $response, string $message, int $status)
    {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => $message
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
