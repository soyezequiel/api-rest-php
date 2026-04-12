<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;

require_once __DIR__ . '/../models/User.php';

class UserController
{
    public function registrar(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        // 1. Validar nombre (solo letras)
        if (empty($name) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/", $name)) {
            return $this->errorResponse($response, 'El nombre es obligatorio y solo debe contener letras', 400);
        }

        // 2. Validar email (debe contener @)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->errorResponse($response, 'El formato de email no es válido (ej: usuario@unlp.edu.ar)', 400);
        }

        // 3. Validar password (8 caracteres, Mayús, Minús, Número y Especial)
        $regexPass = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&+])[A-Za-z\d@$!%*?&]{8,}$/";
        if (!preg_match($regexPass, $password)) {
            return $this->errorResponse($response, 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial', 400);
        }

        try {
            if (empty($name) || empty($email) || empty($password)) {
                $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Datos incompletos']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            if (User::emailExists($email)) {
                $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'El email ya está registrado']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            User::create($name, $email, $password);

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Usuario registrado exitosamente'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            throw $e;
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

            $dateFormatted = date('Y-m-d H:i:s', $expirationTime);
            User::updateToken($user['id'], $token, $dateFormatted);
            
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
