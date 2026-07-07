<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiService
{
    public function generateContent(string $prompt): string
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model');

        if (empty($apiKey)) {
            throw new RuntimeException('GEMINI_API_KEY belum diatur di file .env');
        }

        $response = Http::timeout(60)
            ->withHeader('x-goog-api-key', $apiKey)
            ->withHeader('Content-Type', 'application/json')
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 4096,
                ],
            ]);

        if ($response->failed()) {
            $error = $response->json();
            $message = $error['error']['message'] ?? $response->body();
            throw new RuntimeException('Gemini API error: '.$message);
        }

        $data = $response->json();

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($text === null) {
            $finishReason = $data['candidates'][0]['finishReason'] ?? 'unknown';
            throw new RuntimeException("Gemini API: tidak ada teks dalam respons (finishReason: {$finishReason})");
        }

        return $text;
    }
}
