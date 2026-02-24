<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/Chat.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../app/Core/FeminiApiClient.php';

header('Content-Type: application/json; charset=utf-8');

function extract_text_content($payload)
{
    if (!is_array($payload)) {
        return '';
    }

    $candidates = [
        $payload['content'] ?? null,
        $payload['message'] ?? null,
        $payload['text'] ?? null,
        $payload['result']['content'] ?? null,
        $payload['result']['message'] ?? null,
        $payload['result']['text'] ?? null,
        $payload['data']['content'] ?? null,
        $payload['data']['message'] ?? null,
        $payload['data']['text'] ?? null,
        $payload['output_text'] ?? null,
    ];

    foreach ($candidates as $item) {
        if (is_string($item) && trim($item) !== '') {
            return trim($item);
        }
    }

    return '';
}

function extract_image_content($payload)
{
    if (!is_array($payload)) {
        return '';
    }

    $candidates = [
        $payload['image_url'] ?? null,
        $payload['url'] ?? null,
        $payload['image'] ?? null,
        $payload['result']['image_url'] ?? null,
        $payload['result']['url'] ?? null,
        $payload['result']['image'] ?? null,
        $payload['data']['image_url'] ?? null,
        $payload['data']['url'] ?? null,
        $payload['data']['image'] ?? null,
    ];

    foreach ($candidates as $item) {
        if (is_string($item) && trim($item) !== '') {
            return trim($item);
        }
    }

    return '';
}

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

$taskId = $submit['task_id'] ?? $submit['id'] ?? $submit['data']['task_id'] ?? null;
$resultData = $submit;

if (!empty($taskId)) {
    $maxPoll = 30;

    for ($i = 0; $i < $maxPoll; $i++) {
        $resultResp = $client->getTaskResult($taskId);
        $httpCode = (int)($resultResp['http_code'] ?? 0);
        $statusText = strtolower((string)($resultResp['status'] ?? $resultResp['result']['status'] ?? $resultResp['data']['status'] ?? ''));

        $text = extract_text_content($resultResp);
        $image = extract_image_content($resultResp);

        if (($isImage && $image !== '') || (!$isImage && $text !== '')) {
            $resultData = $resultResp;
            break;
        }

        if (in_array($statusText, ['completed', 'done', 'success', 'succeeded'], true)) {
            $resultData = $resultResp;
            break;
        }

        if ($httpCode >= 400 && !in_array($httpCode, [202, 404, 425], true)) {
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

$assistantContent = $isImage ? extract_image_content($resultData) : extract_text_content($resultData);

if ($assistantContent === '' && $isImage === false && !empty($resultData['result']) && is_array($resultData['result'])) {
    $assistantContent = trim((string)json_encode($resultData['result'], JSON_UNESCAPED_UNICODE));
}

if ($assistantContent === '') {
    http_response_code(504);
    echo json_encode([
        'ok' => false,
        'message' => 'API yanıtı alındı ancak içerik alanı bulunamadı.',
        'detail' => $resultData,
    ], JSON_UNESCAPED_UNICODE);
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
