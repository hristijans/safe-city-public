<?php

namespace App\Services;

use Laravel\Ai\Embeddings;
use Laravel\Ai\Enums\Lab;

class EmbeddingGeneratorService
{
    public function generateBatch(array $texts): array
    {
        $response = Embeddings::for($texts)->generate(
            provider: Lab::OpenAI,
            model: 'text-embedding-3-small',
        );

        return $response->embeddings;
    }
}
