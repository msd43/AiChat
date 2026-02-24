<?php
require_once __DIR__ . '/../config/db.php';

class Chat
{
    public static function create(int $userId, string $title = 'Yeni Sohbet'): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO chats (user_id, title) VALUES (:user_id, :title)');
        $stmt->execute([
            ':user_id' => $userId,
            ':title' => $title,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function listByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, title, created_at, updated_at FROM chats WHERE user_id = :user_id ORDER BY updated_at DESC');
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function findByIdAndUser(int $chatId, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM chats WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute([':id' => $chatId, ':user_id' => $userId]);
        $chat = $stmt->fetch();
        return $chat ?: null;
    }

    public static function touch(int $chatId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE chats SET updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute([':id' => $chatId]);
    }
}
