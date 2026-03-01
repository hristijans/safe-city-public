<?php

namespace App\Jobs;

use App\Models\Bulletin;
use App\Services\BulletinContentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeBulletinContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(
        private readonly int $bulletinId
    ) {}

    public function handle(BulletinContentService $contentService): void
    {
        $bulletin = Bulletin::find($this->bulletinId);

        if (! $bulletin) {
            Log::warning("Bulletin record {$this->$bulletin} not found");

            return;
        }

        try {
            $contentService->scrapeAndStoreContent($bulletin);
        } catch (\Exception $e) {
            Log::error("Failed to scrape content for Bulletin ID {$this->bulletinId}: ".$e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ScrapeBulletinContentJob failed for ID {$this->bulletinId}: ".$exception->getMessage());

        $bulletin = Bulletin::find($this->bulletinId);
        if ($bulletin) {
            Log::error("URL that failed: {$bulletin->url}");
        }
    }
}
