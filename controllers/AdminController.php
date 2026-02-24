<?php
require_once __DIR__ . '/../models/Setting.php';

class AdminController
{
    public function index(): void
    {
        require_admin();
        $settings = Setting::allIndexed();
        include __DIR__ . '/../views/admin.php';
    }

    public function save(): void
    {
        require_admin();

        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = 'Geçersiz CSRF doğrulaması.';
            header('Location: /?route=admin');
            exit;
        }

        $apiBaseUrl = trim($_POST['api_base_url'] ?? '');
        $apiKey = trim($_POST['api_key'] ?? '');
        $systemPrompt = trim($_POST['system_prompt'] ?? '');

        Setting::upsert('api_base_url', $apiBaseUrl);
        Setting::upsert('api_key', $apiKey);
        Setting::upsert('system_prompt', $systemPrompt);

        $_SESSION['success'] = 'Ayarlar güncellendi.';
        header('Location: /?route=admin');
        exit;
    }
}
