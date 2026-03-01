<?php

namespace App\Services;

use App\Models\Bulletin;
use App\Models\BulletinEmbedding;
use Illuminate\Support\Facades\Log;

class BulletinEmbeddingService
{
    private const MAX_CHUNK_LENGTH = 2000;

    public function __construct(
        private readonly TextProcessorService $textProcessor,
        private readonly EmbeddingGeneratorService $embeddingGenerator
    ) {}

    public function generateEmbeddings(Bulletin $bulletin): void
    {
        // Validate raw data has content
        if (empty($bulletin->data)) {
            Log::info("Bulletin record {$bulletin->id} has no content, skipping embedding generation");

            return;
        }

        // Skip if already has embeddings
        if ($bulletin->hasEmbeddings()) {
            Log::info("Bulletin record {$bulletin->id} already has embeddings, skipping");

            return;
        }

        Log::info("Generating embeddings for Bulletin ID: {$bulletin->id}");

        // Split text into chunks
        $chunks = $this->textProcessor->chunk($bulletin->data, self::MAX_CHUNK_LENGTH);

        $embeddings = $this->embeddingGenerator->generateBatch($chunks);

        foreach ($chunks as $index => $chunk) {
            BulletinEmbedding::create([
                'bulletin_id' => $bulletin->id,
                'chunk_index' => $index,
                'chunk_text' => $chunk,
                'embedding' => $embeddings[$index],
                'metadata' => [
                    'url' => $bulletin->url,
                    'parsed_at' => $bulletin->parsed_at?->toIso8601String(),
                    'total_chunks' => count($chunks),
                ],
            ]);

            Log::info("Created embedding for Bulletin ID {$bulletin->id}, chunk {$index}");
        }

        Log::info('Successfully generated '.count($chunks)." embedding(s) for Bulletin ID: {$bulletin->id}");
    }
}
