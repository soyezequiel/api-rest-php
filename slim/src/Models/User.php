<?php

namespace App\Models;

class User
{
    public static function create($name, $email, $password)
    {
        $db = DB::getConnection();

        $passHash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->prepare("INSERT INTO users (name, email, password, balance) VALUES (?, ?, ?, ?)");

        // el usuario inicia con 1000 USD
        return $stmt->execute([$name, $email, $passHash, 1000.00]);
    }

    public static function emailExists($email)
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }

    public static function getByEmail($email)
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC); // Retorna un array con los datos o false
    }

    public static function updateToken($userId, $token, $expiration, $creation)
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("UPDATE users SET token = :token, token_expired_at = :exp, created_at = :gen WHERE id = :id");

        $stmt->execute([
            'token' => $token,   
            'exp'   => $expiration, 
            'gen'   => $creation,   
            'id'    => $userId  
        ]);
    }

    public static function logout($userId)
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("UPDATE users SET token = NULL, token_expired_at = NULL WHERE id = :id");
        return $stmt->execute(['id' => $userId]);
    }
}
