<?php

namespace App\Services;

use App\Models\Bulletin;
use Illuminate\Support\Facades\Log;

class BulletinContentService
{
    private const CONTENT_SELECTOR = '.blog-content';

    public function __construct(
        private readonly WebScraperService $webScraper,
        private readonly TextProcessorService $textProcessor
    ) {}

    public function scrapeAndStoreContent(Bulletin $bulletin): void
    {
        // Skip if already processed
        if ($bulletin->status === 'processed') {
            Log::info("Bulletin record {$bulletin->id} already processed, skipping");

            return;
        }

        Log::info("Scraping content from: {$bulletin->url}");

        // Fetch and parse HTML
        $html = $this->webScraper->fetchHtml($bulletin->url);
        $textContent = $this->webScraper->extractText($html, self::CONTENT_SELECTOR);

        // Sanitize the text
        $sanitizedText = $this->textProcessor->sanitize($textContent);

        // Update the bullitens record
        $bulletin->update([
            'data' => $sanitizedText,
            'parsed_at' => now(),
            'status' => 'processed',
            'processed_at' => now(),
        ]);

        Log::info("Successfully scraped content for Bulliten ID: {$bulletin->id}");
    }
}
