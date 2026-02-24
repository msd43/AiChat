<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/Chat.php';
require_once __DIR__ . '/../models/Message.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Oturum bulunamadı.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];

if (!verify_csrf($payload['csrf_token'] ?? null)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'CSRF doğrulaması başarısız.']);
    exit;
}

$chatId = (int)($payload['chat_id'] ?? 0);
$prompt = trim((string)($payload['prompt'] ?? ''));
$mode = ($payload['mode'] ?? 'text') === 'image' ? 'image' : 'text';

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

$apiBaseUrl = Setting::get('api_base_url', '');
$apiKey = Setting::get('api_key', '');
$systemPrompt = Setting::get('system_prompt', 'Sen MSD adında akıllı bir asistansın...');

$history = Message::listByChat($chatId);
$historyPayload = array_map(static function ($msg) {
    return [
        'role' => $msg['role'],
        'content' => $msg['content'],
        'type' => $msg['type'],
    ];
}, $history);

$endpoint = $mode === 'image' ? '/image' : '/chat';
$url = rtrim($apiBaseUrl, '/') . $endpoint;

$requestBody = [
    'prompt' => $prompt,
    'system_prompt' => $systemPrompt,
    'history' => $historyPayload,
    'mode' => $mode,
];

Message::create($chatId, 'user', $prompt, $mode);
Chat::touch($chatId);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS => json_encode($requestBody, JSON_UNESCAPED_UNICODE),
]);

$rawResponse = curl_exec($ch);
$curlError = curl_error($ch);
$statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($rawResponse === false || $curlError) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'message' => 'Dış API bağlantı hatası: ' . $curlError]);
    exit;
}

$responseData = json_decode($rawResponse, true);
if (!is_array($responseData) || $statusCode >= 400) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'message' => 'Dış API geçersiz bir yanıt döndürdü.', 'raw' => $rawResponse]);
    exit;
}

$assistantContent = (string)($responseData['content'] ?? $responseData['message'] ?? 'Boş yanıt alındı.');
$assistantType = $mode === 'image' ? 'image' : 'text';
Message::create($chatId, 'assistant', $assistantContent, $assistantType);
Chat::touch($chatId);

echo json_encode([
    'ok' => true,
    'message' => 'Yanıt alındı.',
    'assistant' => [
        'role' => 'assistant',
        'content' => $assistantContent,
        'type' => $assistantType,
    ],
], JSON_UNESCAPED_UNICODE);
