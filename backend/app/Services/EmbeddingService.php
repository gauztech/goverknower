<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    private string $apiKey;
    private string $endpoint;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->endpoint = "https://generativelanguage.googleapis.com/v1beta/models/embedding-001:embedContent?key={$this->apiKey}";
    }

    /**
     * Generate embeddings for the given text using Gemini's embedding model
     *
     * @param string $text
     * @return array|null
     */
    public function generateEmbedding(string $text): ?array
    {
        try {
            $payload = [
                'model' => 'models/embedding-001',
                'content' => [
                    'parts' => [
                        ['text' => $text]
                    ]
                ]
            ];

            $response = Http::post($this->endpoint, $payload);

            if ($response->failed()) {
                Log::error('Gemini embedding API call failed:', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }

            $result = $response->json();

            if (isset($result['embedding']['values'])) {
                return $result['embedding']['values'];
            }

            Log::warning('Unexpected embedding response structure:', ['response' => $result]);
            return null;

        } catch (\Exception $e) {
            Log::error('Error generating embedding:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
} 