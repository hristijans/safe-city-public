<?php

namespace App\Console\Commands;

use App\Jobs\GenerateEmbeddingsJob;
use App\Models\Bulletin;
use Illuminate\Console\Command;

class GenerateEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'embeddings:generate
                            {--limit= : Limit number of items to process (default: all)}
                            {--batch=10 : Number of jobs to dispatch per batch}
                            {--delay=3 : Delay in seconds between batches to avoid rate limiting}
                            {--force : Regenerate embeddings even if they already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate embeddings for bulletin data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get processed raw_data records that don't have embeddings yet
        $query = Bulletin::where('status', 'processed')
            ->whereNotNull('data');

        // If not forcing, exclude records that already have embeddings
        if (! $this->option('force')) {
            $query->doesntHave('embeddings');
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $items = $query->get();
        $totalItems = $items->count();

        if ($totalItems === 0) {
            $this->info('No items found to generate embeddings for.');
            $this->info('Make sure you have processed bulletins with scraped content.');

            return self::SUCCESS;
        }

        $this->info("Found {$totalItems} items to generate embeddings for.");

        // Estimate cost
        $avgCharsPerItem = $items->avg(fn ($item) => mb_strlen($item->data));
        $estimatedTokens = ($avgCharsPerItem * $totalItems) / 4; // Rough estimate: 4 chars per token
        $estimatedCost = ($estimatedTokens / 1000000) * 0.02; // $0.02 per 1M tokens for text-embedding-3-small

        $this->info('Estimated cost: ~$'.number_format($estimatedCost, 4).' USD');

        if (! $this->confirm('Do you want to proceed?', true)) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $batchSize = (int) $this->option('batch');
        $delay = (int) $this->option('delay');

        $this->info("Dispatching jobs to queue in batches of {$batchSize}...");

        $bar = $this->output->createProgressBar($totalItems);
        $bar->start();

        $batches = $items->chunk($batchSize);

        foreach ($batches as $batchIndex => $batch) {
            foreach ($batch as $item) {
                GenerateEmbeddingsJob::dispatch($item->id);
                $bar->advance();
            }

            // Add delay between batches to avoid rate limiting
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
