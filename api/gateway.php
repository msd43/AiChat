<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/Chat.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../app/Core/FeminiApiClient.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Oturum bulunamadı.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = [];
}

if (!verify_csrf($payload['csrf_token'] ?? null)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'CSRF doğrulaması başarısız.']);
    exit;
}

$chatId = (int)($payload['chat_id'] ?? 0);
$prompt = trim((string)($payload['prompt'] ?? ''));
$mode = (($payload['mode'] ?? 'text') === 'image') ? 'image' : 'text';
$isImage = ($mode === 'image');

if ($chatId <= 0 || $prompt === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'chat_id ve prompt zorunludur.']);
    exit;
}

$userId = (int)current_user()['id'];
$chat = Chat::findByIdAndUser($chatId, $userId);
if (!$chat) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Sohbete erişim yok.']);
    exit;
}

$apiBaseUrl = trim((string)Setting::get('api_base_url', ''));
$config = app_config();
$defaultApiKey = trim((string)($config['default_api_key'] ?? ''));
$apiKeyFromDb = trim((string)Setting::get('api_key', ''));
$apiKey = $apiKeyFromDb !== '' ? $apiKeyFromDb : $defaultApiKey;

if ($apiBaseUrl === '') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'API Base URL ayarlı değil.']);
    exit;
}

Message::create($chatId, 'user', $prompt, $mode);
Chat::touch($chatId);

$client = new FeminiApiClient($apiBaseUrl, $apiKey);
$submit = $client->submitRequest($prompt, $isImage);

if (!is_array($submit) || (int)($submit['http_code'] ?? 500) >= 400) {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'message' => 'Submit isteği başarısız.',
        'detail' => $submit,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$taskId = $submit['task_id'] ?? $submit['id'] ?? null;
$resultData = $submit;

if (!empty($taskId)) {
    $maxPoll = 25;

    for ($i = 0; $i < $maxPoll; $i++) {
        $resultResp = $client->getTaskResult($taskId);
        $httpCode = (int)($resultResp['http_code'] ?? 0);

        $hasText = !empty($resultResp['content']) || !empty($resultResp['message']) || !empty($resultResp['result']['content']) || !empty($resultResp['result']['message']);
        $hasImage = !empty($resultResp['image_url']) || !empty($resultResp['url']) || !empty($resultResp['result']['image_url']) || !empty($resultResp['result']['url']);

        if (($isImage && $hasImage) || (!$isImage && $hasText)) {
            $resultData = $resultResp;
            break;
        }

        if ($httpCode >= 400 && $httpCode !== 404 && $httpCode !== 425 && $httpCode !== 202) {
            http_response_code(502);
            echo json_encode([
                'ok' => false,
                'message' => 'Result isteği başarısız.',
                'detail' => $resultResp,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        usleep(500000);
    }
}

$assistantContent = '';
if ($isImage) {
    $assistantContent = (string)(
        $resultData['image_url']
        ?? $resultData['url']
        ?? $resultData['result']['image_url']
        ?? $resultData['result']['url']
        ?? ''
    );
} else {
    $assistantContent = (string)(
        $resultData['content']
        ?? $resultData['message']
        ?? $resultData['result']['content']
        ?? $resultData['result']['message']
        ?? ''
    );
}

if ($assistantContent === '') {
    http_response_code(504);
    echo json_encode(['ok' => false, 'message' => 'Görev henüz tamamlanmadı veya boş yanıt döndü.']);
    exit;
}

$assistantType = $isImage ? 'image' : 'text';
Message::create($chatId, 'assistant', $assistantContent, $assistantType);
Chat::touch($chatId);

echo json_encode([
    'ok' => true,
    'message' => 'Yanıt alındı.',
    'task_id' => $taskId,
    'assistant' => [
        'role' => 'assistant',
        'content' => $assistantContent,
        'type' => $assistantType,
    ],
], JSON_UNESCAPED_UNICODE);
