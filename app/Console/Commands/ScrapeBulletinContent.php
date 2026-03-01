<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeBulletinContentJob;
use App\Models\Bulletin;
use Illuminate\Console\Command;

class ScrapeBulletinContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraper:content
                            {--limit= : Limit number of items to process (default: all)}
                            {--batch=50 : Number of jobs to dispatch per batch}
                            {--delay=2 : Delay in seconds between batches}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape content from bulletin detail pages stored in bulletins table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get all unprocessed bulliten records
        $query = Bulletin::where('status', 'new');

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $items = $query->get();
        $totalItems = $items->count();

        if ($totalItems === 0) {
            $this->info('No unprocessed items found in bulletins table.');

            return self::SUCCESS;
        }

        $this->info("Found {$totalItems} unprocessed items to scrape.");

        $batchSize = (int) $this->option('batch');
        $delay = (int) $this->option('delay');

        $this->info("Dispatching jobs to queue in batches of {$batchSize}...");

        $bar = $this->output->createProgressBar($totalItems);
        $bar->start();

        $batches = $items->chunk($batchSize);

        foreach ($batches as $batchIndex => $batch) {
            foreach ($batch as $item) {
                ScrapeBulletinContentJob::dispatch($item->id);
                $bar->advance();
            }

            // Add delay between batches to avoid overwhelming the server
            if ($batchIndex < $batches->count() - 1) {
                sleep($delay);
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("{$totalItems} jobs have been dispatched to the queue.");
        $this->info('Monitor with: php artisan queue:work');

        return self::SUCCESS;
    }
}
