<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Probots\Pinecone\Pinecone;

class PineconeService
{
    private $pineconeClient;
    private string $indexHost;

    public function __construct()
    {
        $this->pineconeClient = Pinecone::client(
            env('PINECONE_API_KEY'),
            env('PINECONE_INDEX_HOST') // This should be the connection URL from your teammate
        );
    }

    /**
     * Query Pinecone for similar vectors
     *
     * @param array $vector
     * @param int $topK
     * @return array|null
     */
    public function querySimilar(array $vector, int $topK = 3): ?array
    {
        try {
            $response = $this->pineconeClient->data()->vectors()->query(
                vector: $vector,
                topK: $topK,
                includeMetadata: true,
                includeValues: false
            );

            $result = $response->json();

            if (!isset($result['matches'])) {
                Log::warning('Pinecone query response missing matches:', ['response' => $result]);
                return null;
            }

            return $result['matches'];

        } catch (\Exception $e) {
            Log::error('Error querying Pinecone:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Extract text content from Pinecone matches
     *
     * @param array $matches
     * @return array
     */
    public function extractTextFromMatches(array $matches): array
    {
        $texts = [];
        
        foreach ($matches as $match) {
            if (isset($match['metadata']['text'])) {
                $texts[] = $match['metadata']['text'];
            }
        }

        return $texts;
    }

    /**
     * Get a formatted string of all matched texts
     *
     * @param array $matches
     * @return string
     */
    public function getFormattedResults(array $matches): string
    {
        $texts = $this->extractTextFromMatches($matches);
        Log::info('Extracted texts from Pinecone matches:', $texts);
        return implode("\n", $texts);
    }
} 