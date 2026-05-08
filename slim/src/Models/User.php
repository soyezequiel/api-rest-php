<?php

namespace App\Models;

class User
{
    public static function create($name, $email, $password)
    {
        $db = DB::getConnection();

        $passHash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->prepare("INSERT INTO users (name, email, password, balance) VALUES (?, ?, ?, ?)");

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


    public static function getById($userId)
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT id, name, email, balance, is_admin FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public static function getProfileWithTotal(int $userId)
    {
        $user = self::getById($userId);
        if (!$user) return null;

        $tenencias = \App\Models\Portfolio::obtenerPorUsuario($userId);

        // Sumamos el valor de todos los activos en el portfolio
        $totalPortfolio = array_reduce($tenencias, function ($sum, $item) {
            return $sum + $item['total_value'];
        }, 0);

        $user['portfolio_value'] = $totalPortfolio;
        $user['total_wealth'] = $user['balance'] + $totalPortfolio;

        return $user;
    }

    public static function update($userId, $data)
    {
        $db = DB::getConnection();
        $fields = [];
        $params = [];

        if (!empty($data['name'])) {
            $fields[] = "name = ?";
            $params[] = $data['name'];
        }

        if (!empty($data['password'])) {
            $fields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) return true;

        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);

        return $stmt->execute($params);
    }

    public static function getAllWithTotal()
    {
        $db = DB::getConnection();
        $stmt = $db->query("SELECT id, name, balance FROM users");
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($users as &$user) {
            $tenencias = \App\Models\Portfolio::obtenerPorUsuario($user['id']);
            $totalPortfolio = array_reduce($tenencias, function ($sum, $item) {
                return $sum + $item['total_value'];
            }, 0);

            $user['total_wealth'] = $user['balance'] + $totalPortfolio;
            unset($user['balance']);
        }

        return $users;
    }
}
