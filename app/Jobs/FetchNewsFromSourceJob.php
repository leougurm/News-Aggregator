<?php

namespace App\Jobs;

use App\NewsAggregator\Fetchers\NewsAggregatorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchNewsFromSourceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 1;

    // How long the unique lock should be held (in seconds)
    public $uniqueFor = 600;

    protected string $source;

    public function __construct(string $source)
    {
        $this->source = $source;
    }

    // Define what makes this job unique
    public function uniqueId(): string
    {
        return $this->source;
    }
    /**
     * Execute the job.
     */
    public function handle(NewsAggregatorService $service): void
    {
        Log::info("Job started for $this->source");
        $service->aggregateFromSource($this->source);
    }
}
