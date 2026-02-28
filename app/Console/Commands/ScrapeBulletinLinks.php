<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeBulletinLinksJob;
use App\Models\ScraperSetting;
use Illuminate\Console\Command;

class ScrapeBulletinLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraper:links
                            {--start= : Starting page number (default: continues from last scraped)}
                            {--end= : Ending page number (default: auto-detect)}
                            {--reset : Reset scraping state and start from page 1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape bulletin links from MVR website pagination';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('reset')) {
            if ($this->confirm('This will reset the scraping state. Continue?')) {
                ScraperSetting::set('mvr_last_scraped_page', 0);
                ScraperSetting::set('mvr_total_pages', 0);
                $this->info('Scraping state has been reset.');
            } else {
                return self::SUCCESS;
            }
        }

        $startPage = $this->option('start');
        $endPage = $this->option('end');

        if ($startPage === null) {
            $lastScraped = (int) ScraperSetting::get('mvr_last_scraped_page', 0);
            $startPage = $lastScraped + 1;
            $this->info("Continuing from page {$startPage} (last scraped: {$lastScraped})");
        } else {
            $startPage = (int) $startPage;
            $this->info("Starting from page {$startPage} (manually specified)");
        }

        if ($endPage !== null) {
            $endPage = (int) $endPage;
            $this->info("Will scrape until page {$endPage}");
        } else {
            $this->info('Will auto-detect total pages');
        }

        $this->info('Dispatching to queue...');
        ScrapeBulletinLinksJob::dispatch($startPage, $endPage);
        $this->info('Job has been dispatched to the queue.');
        $this->info('Monitor with: php artisan queue:work');

        return self::SUCCESS;
    }
}
