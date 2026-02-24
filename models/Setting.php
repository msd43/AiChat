<?php
require_once __DIR__ . '/../config/db.php';

class Setting
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1');
        $stmt->execute([':key' => $key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (string)$value : $default;
    }

    public static function allIndexed(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
        return $result;
    }

    public static function upsert(string $key, string $value): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        return $stmt->execute([':k' => $key, ':v' => $value]);
    }
}
