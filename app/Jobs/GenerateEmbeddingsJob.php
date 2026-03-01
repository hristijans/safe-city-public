<?php

namespace App\Jobs;

use App\Models\Bulletin;
use App\Services\BulletinEmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateEmbeddingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;

    public int $tries = 3;

    public function __construct(
        private readonly int $bulletinId
    ) {}

    public function handle(BulletinEmbeddingService $embeddingService): void
    {
        $bulletin = Bulletin::find($this->bulletinId);

        if (! $bulletin) {
            Log::warning("Bulletin record {$this->bulletinId} not found");

            return;
        }

        try {
            $embeddingService->generateEmbeddings($bulletin);
        } catch (\Exception $e) {
            Log::error("Failed to generate embeddings for Bulletin ID {$this->bulletinId}: ".$e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("GenerateEmbeddingsJob failed for ID {$this->bulletinId}: ".$exception->getMessage());
    }
}
