<?php
require_once __DIR__ . '/../models/User.php';

class AuthController
{
    public function showLogin(): void
    {
        include __DIR__ . '/../views/login.php';
    }

    public function showRegister(): void
    {
        include __DIR__ . '/../views/register.php';
    }

    public function register(): void
    {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = 'Geçersiz CSRF doğrulaması.';
            header('Location: /?route=register');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            $_SESSION['error'] = 'Geçerli e-posta ve en az 6 karakterli şifre girin.';
            header('Location: /?route=register');
            exit;
        }

        if (User::findByEmail($email)) {
            $_SESSION['error'] = 'Bu e-posta zaten kayıtlı.';
            header('Location: /?route=register');
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        User::create($email, $passwordHash);

        $_SESSION['success'] = 'Kayıt başarılı. Şimdi giriş yapabilirsiniz.';
        header('Location: /?route=login');
        exit;
    }

    public function login(): void
    {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = 'Geçersiz CSRF doğrulaması.';
            header('Location: /?route=login');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['error'] = 'E-posta veya şifre hatalı.';
            header('Location: /?route=login');
            exit;
        }

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        header('Location: /');
        exit;
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        header('Location: /?route=login');
        exit;
    }
}
