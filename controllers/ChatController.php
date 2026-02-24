<?php
require_once __DIR__ . '/../models/Chat.php';
require_once __DIR__ . '/../models/Message.php';

class ChatController
{
    public function index(): void
    {
        require_login();
        $user = current_user();
        $chats = Chat::listByUser((int)$user['id']);

        $activeChatId = isset($_GET['chat_id']) ? (int)$_GET['chat_id'] : 0;
        $activeChat = null;
        $messages = [];

        if ($activeChatId > 0) {
            $activeChat = Chat::findByIdAndUser($activeChatId, (int)$user['id']);
            if ($activeChat) {
                $messages = Message::listByChat($activeChatId);
            }
        }

        include __DIR__ . '/../views/chat.php';
    }

    public function createChat(): void
    {
        require_login();
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo 'Geçersiz CSRF doğrulaması.';
            return;
        }

        $chatId = Chat::create((int)current_user()['id']);
        header('Location: /?chat_id=' . $chatId);
    }
}
