<?php

namespace App\Services;

class OcrService
{
    private const API_ENDPOINT = 'https://api.ocr.space/parse/image';

    private const ALLOWED_LANGUAGES = [
        'eng', 'spa', 'fra', 'deu', 'ita', 'por',
        'chi_sim', 'jpn', 'kor', 'ara', 'rus',
    ];

    public function extractText(string $imagePath, string $language = 'eng'): array
    {
        if (!in_array($language, self::ALLOWED_LANGUAGES, true)) {
            $language = 'eng';
        }

        $apiKey = config('tools.ocr.api_key', 'helloworld');

        $imageData = @file_get_contents($imagePath);
        if ($imageData === false) {
            throw new \RuntimeException('Could not read the uploaded image file.');
        }

        $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';
        $base64   = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);

        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL is not available on this server. Please contact your host.');
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => self::API_ENDPOINT,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => [
                'base64Image'          => $base64,
                'language'             => $language,
                'isOverlayRequired'    => 'false',
                'OCREngine'            => '2',
                'detectOrientation'    => 'true',
                'scale'                => 'true',
                'isTable'              => 'false',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => ['apikey: ' . $apiKey],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || !$response) {
            throw new \RuntimeException('Could not reach the OCR service. Please check your internet connection and try again.');
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException('OCR service returned an unexpected response (HTTP ' . $httpCode . ').');
        }

        $result = json_decode($response, true);

        if (!$result) {
            throw new \RuntimeException('OCR service returned an unreadable response.');
        }

        if (!empty($result['IsErroredOnProcessing'])) {
            $messages = (array) ($result['ErrorMessage'] ?? []);
            $errMsg = implode(' ', array_filter($messages)) ?: 'OCR processing failed.';
            throw new \RuntimeException($errMsg);
        }

        $text = '';
        foreach ($result['ParsedResults'] ?? [] as $page) {
            $text .= ($page['ParsedText'] ?? '');
        }

        $text = trim($text);

        if ($text === '') {
            throw new \RuntimeException(
                'No text was detected in this image. Make sure it contains clear, readable text with good contrast.'
            );
        }

        return [
            'text'  => $text,
            'pages' => count($result['ParsedResults'] ?? []),
            'words' => str_word_count($text),
            'chars' => mb_strlen($text),
        ];
    }
}
