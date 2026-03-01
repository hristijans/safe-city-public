<?php

namespace App\Services;

use App\Contracts\Services\EmbeddingGeneratorInterface;
use Laravel\Ai\Ai;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Enums\Lab;
use Prism\Prism\Enums\Provider;

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
