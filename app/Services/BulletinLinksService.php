<?php

namespace App\Services;

use App\Models\Bulletin;
use App\Models\ScraperSetting;
use Illuminate\Support\Facades\Log;

class BulletinLinksService
{
    private const BASE_URL = 'https://mvr.gov.mk/mk-MK/odnosi-so-javnost/dnevni-bilteni';

    private const BASE_DOMAIN = 'https://mvr.gov.mk';

    private const READ_MORE_TEXT = 'Прочитај повеќе';

    private const MAX_PAGES_SAFETY_LIMIT = 1000;

    public function __construct(
        private readonly WebScraperService $webScraper
    ) {}

    public function scrapeAndStoreLinks(?int $startPage = null, ?int $endPage = null): void
    {
        Log::info('Starting bulletin links scraping job');

        $startPage = $startPage ?? ((int) ScraperSetting::get('mvr_last_scraped_page', 0)) + 1;

        if ($endPage !== null) {
            $this->scrapePageRange($startPage, $endPage);
        } else {
            $this->scrapeUntilEmpty($startPage);
        }

        Log::info('Bulletin links scraping completed');
    }

    /**
     * Scrape a specific range of pages
     */
    private function scrapePageRange(int $startPage, int $endPage): void
    {
        Log::info("Scraping pages from {$startPage} to {$endPage}");

        for ($page = $startPage; $page <= $endPage; $page++) {
            $this->scrapePage($page);
            ScraperSetting::set('mvr_last_scraped_page', $page);
            Log::info("Successfully scraped page {$page}");
            sleep(1);
        }
    }

    /**
     * Scrape pages until no links are found
     */
    private function scrapeUntilEmpty(int $startPage): void
    {
        Log::info("Starting from page {$startPage}, will scrape until no links found");

        $page = $startPage;

        while ($page <= self::MAX_PAGES_SAFETY_LIMIT) {
            $linksCount = $this->scrapePage($page);

            // If no links found, we've reached the end
            if ($linksCount === 0) {
                Log::info("No links found on page {$page}, scraping complete");
                $totalPages = $page - 1;
                ScraperSetting::set('mvr_total_pages', $totalPages, 'Total pages detected during scraping');
                break;
            }

            ScraperSetting::set('mvr_last_scraped_page', $page);
            Log::info("Successfully scraped page {$page} ({$linksCount} links found)");

            $page++;
            sleep(1);
        }

        if ($page > self::MAX_PAGES_SAFETY_LIMIT) {
            Log::warning('Reached safety limit of '.self::MAX_PAGES_SAFETY_LIMIT.' pages');
        }
    }

    /**
     * Scrape a single pagination page
     *
     * @return int Number of "Прочитај повеќе" links found on the page
     */
    private function scrapePage(int $page): int
    {
        $url = $this->buildPageUrl($page);
        $html = $this->webScraper->fetchHtml($url);

        // Extract "Read more" links
        $links = $this->webScraper->extractLinks($html, function ($node) {
            return trim($node->text()) === self::READ_MORE_TEXT;
        });

        $totalLinksFound = $links->count();
        $newLinksCount = 0;

        foreach ($links as $href) {
            $absoluteUrl = $this->webScraper->makeAbsoluteUrl($href, self::BASE_DOMAIN);

            // Create new RawData record if URL doesn't exist
            if (! Bulletin::urlExists($absoluteUrl)) {
                Bulletin::create([
                    'url' => $absoluteUrl,
                    'status' => 'new',
                    'parsed_at' => null,
                ]);
                $newLinksCount++;
            }
        }

        Log::info("Found {$totalLinksFound} total links on page {$page} ({$newLinksCount} new)");

        return $totalLinksFound;
    }

    /**
     * Build URL for a specific page number
     */
    private function buildPageUrl(int $page): string
    {
        return $page === 1
            ? self::BASE_URL
            : self::BASE_URL."?page={$page}";
    }
}
