<?php
require_once __DIR__ . '/../config/db.php';

class Message
{
    public static function create(int $chatId, string $role, string $content, string $type): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO messages (chat_id, role, content, type) VALUES (:chat_id, :role, :content, :type)');
        return $stmt->execute([
            ':chat_id' => $chatId,
            ':role' => $role,
            ':content' => $content,
            ':type' => $type,
        ]);
    }

    public static function listByChat(int $chatId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, role, content, type, created_at FROM messages WHERE chat_id = :chat_id ORDER BY id ASC');
        $stmt->execute([':chat_id' => $chatId]);
        return $stmt->fetchAll();
    }
}
