<?php
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/ChatController.php';
require_once __DIR__ . '/controllers/AdminController.php';

$route = $_GET['route'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$authController = new AuthController();
$chatController = new ChatController();
$adminController = new AdminController();

switch ($route) {
    case 'landing':
        include __DIR__ . '/views/landing.php';
        break;
    case 'login':
        $authController->showLogin();
        break;
    case 'login_post':
        if ($method === 'POST') {
            $authController->login();
            break;
        }
        http_response_code(405);
        echo 'Method not allowed';
        break;
    case 'register':
        $authController->showRegister();
        break;
    case 'register_post':
        if ($method === 'POST') {
            $authController->register();
            break;
        }
        http_response_code(405);
        echo 'Method not allowed';
        break;
    case 'logout':
        $authController->logout();
        break;
    case 'create_chat':
        if ($method === 'POST') {
            $chatController->createChat();
            break;
        }
        http_response_code(405);
        echo 'Method not allowed';
        break;
    case 'admin':
        $adminController->index();
        break;
    case 'admin_save':
        if ($method === 'POST') {
            $adminController->save();
            break;
        }
        http_response_code(405);
        echo 'Method not allowed';
        break;
    default:
        if (!is_logged_in()) {
            header('Location: /?route=landing');
            exit;
        }
        $chatController->index();
}
