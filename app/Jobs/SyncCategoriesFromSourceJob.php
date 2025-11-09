<?php

namespace App\Jobs;

use App\NewsAggregator\Fetchers\NewsAggregatorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncCategoriesFromSourceJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private string $source)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(NewsAggregatorService $service): void
    {
        $service->syncCategoriesFromSource($this->source);
    }
}
