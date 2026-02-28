<?php

namespace App\Jobs;

use App\Services\BulletinLinksService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeBulletinLinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 3;

    public function __construct(
        private readonly ?int $startPage = null,
        private readonly ?int $endPage = null
    ) {}

    public function handle(BulletinLinksService $linksService): void
    {
        try {
            $linksService->scrapeAndStoreLinks($this->startPage, $this->endPage);
        } catch (\Exception $e) {
            Log::error('Failed to scrape bulletin links: '.$e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ScrapeBulletinLinksJob failed: '.$exception->getMessage());
    }
}
