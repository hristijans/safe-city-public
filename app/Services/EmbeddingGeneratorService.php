<?php

namespace App\Services;

use App\Contracts\Services\EmbeddingGeneratorInterface;
use Laravel\Ai\Ai;
use Prism\Prism\Enums\Provider;

class EmbeddingGeneratorService
{
    public function generate(string $text): array
    {
        $response = Ai::embeddings()->create(
            $text   ,
            provider: 'openai',
        );

        return $response->embeddings;
    }
}
