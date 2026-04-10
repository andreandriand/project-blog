<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    private string $apiKey;

    private string $model;

    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model');
    }

    public function generatePost(string $topic, string $language = 'id'): array
    {
        $langLabel = $language === 'id' ? 'Bahasa Indonesia' : 'English';

        $prompt = <<<PROMPT
You are a professional blog writer. Generate a complete blog article about the following topic.

Topic: {$topic}
Language: {$langLabel}

Respond ONLY with valid JSON in this exact format (no markdown, no code blocks, just raw JSON):
{
    "title": "The article title",
    "excerpt": "A compelling 1-2 sentence summary of the article",
    "body": "The full article body in HTML format. Use <h2>, <h3>, <p>, <ul>, <ol>, <li>, <blockquote>, <strong>, <em> tags for formatting. Write at least 500 words. Make it informative, engaging, and well-structured."
}
PROMPT;

        $response = Http::withHeaders([
            'x-goog-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post("{$this->baseUrl}/models/{$this->model}:generateContent", [
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
            $error = $response->json('error.message', 'Unknown error from Gemini API');
            throw new \RuntimeException("Gemini API error: {$error}");
        }

        $text = $response->json('candidates.0.content.parts.0.text', '');

        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/i', '', $text);
        $text = trim($text);

        $data = json_decode($text, true);

        if (! $data || ! isset($data['title'], $data['excerpt'], $data['body'])) {
            throw new \RuntimeException('Gemini API returned invalid format. Please try again.');
        }

        return $data;
    }
}
