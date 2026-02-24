<?php

class FeminiApiClient
{
    private $baseUrl;
    private $apiKey;

    public function __construct($baseUrl, $apiKey)
    {
        $this->baseUrl = rtrim((string)$baseUrl, '/');
        $this->apiKey = trim((string)$apiKey);
    }

    /**
     * API'ye yeni bir istek gönderir (Submit)
     */
    public function submitRequest($prompt, $chatId = null, $isImage = false)
    {
        $endpoint = $this->baseUrl . '/api/v1/submit';

        $data = [
            'prompt' => (string)$prompt,
            'is_image' => (bool)$isImage,
            'force_text' => !$isImage,
            'download' => (bool)$isImage,
            'return_image_data' => (bool)$isImage,
        ];

        if (!empty($chatId)) {
            $data['chat_id'] = $chatId;
        }

        return $this->sendCurlRequest($endpoint, 'POST', $data);
    }

    /**
     * Gönderilen isteğin anlık durumunu kontrol eder (Status)
     */
    public function getTaskStatus($taskId)
    {
        $endpoint = $this->baseUrl . '/api/v1/status/' . urlencode((string)$taskId);
        return $this->sendCurlRequest($endpoint, 'GET');
    }

    /**
     * Tamamlanmış isteğin tam sonucunu alır (Result)
     */
    public function getTaskResult($taskId)
    {
        $endpoint = $this->baseUrl . '/api/v1/result/' . urlencode((string)$taskId);
        return $this->sendCurlRequest($endpoint, 'GET');
    }

    /**
     * Merkezi cURL işleyici fonksiyonu
     */
    private function sendCurlRequest($url, $method = 'GET', $data = null)
    {
        $ch = curl_init($url);

        if ($this->apiKey === '') {
            return [
                'ok' => false,
                'http_code' => 0,
                'message' => 'API key boş olduğu için istek gönderilmedi.',
            ];
        }

        $headers = [
            'X-API-Key: ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $error) {
            return [
                'ok' => false,
                'http_code' => $httpCode,
                'error' => $error,
                'message' => 'cURL isteğinde hata oluştu.',
            ];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            $decoded = [
                'ok' => false,
                'message' => 'API geçersiz JSON döndürdü.',
                'raw' => $response,
            ];
        }

        $decoded['http_code'] = $httpCode;

        if ($httpCode !== 200) {
            error_log('MSD_API_ERROR: URL: ' . $url . ' | Code: ' . $httpCode . ' | Error: ' . $error . ' | Response: ' . $response);
        }

        return $decoded;
    }
}
