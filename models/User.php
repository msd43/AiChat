<?php
require_once __DIR__ . '/../config/db.php';

class User
{
    public static function create(string $email, string $passwordHash): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (:email, :password_hash)');
        return $stmt->execute([
            ':email' => $email,
            ':password_hash' => $passwordHash,
        ]);
    }

    public static function findByEmail(string $email): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, email, role, created_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}
