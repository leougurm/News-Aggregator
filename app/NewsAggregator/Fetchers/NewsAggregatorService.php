<?php

namespace App\NewsAggregator\Fetchers;

use App\Models\ArticleSource;
use Illuminate\Support\Facades\Log;

class NewsAggregatorService
{
    public function __construct(
        private readonly ArticleApiFactory $apiFactory,
        private readonly ArticleRepository $repository
    )
    {
    }

    public function aggregateFromSource(string $source): void
    {
        $adapter = $this->apiFactory->make($source);
        $service = ArticleSource::where('name', $source)
            ->where('is_active', true)
            ->firstOrFail();
        $categories = $service->categories;
        foreach ($categories as $category) {
            try {
                $articles = $adapter->fetchData($service, $category->name);
            } catch (\Exception $e) {
                Log::error("Failed to fetch articles from {$source}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                continue;
            }
            Log::info("Will insert articles in to db for source {$source}", [
                'section' => $category->name,
                'count' => count($articles),
            ]);

            foreach ($articles as $article) {
                try {
                    $map = $adapter->mapData($service->id, $article);
                    $this->repository->updateOrCreate(
                        ['external_id' => $map['external_id']],
                        $map
                    );
                } catch (\Exception $e) {
                    Log::warning("Failed to save article from {$source}", [
                        'error' => $e->getMessage(),
                        'article_id' => $article['id'] ?? 'unknown',
                        'article_title' => $article['webTitle'] ?? 'unknown',
                    ]);
                }
            }
        }

        $service->update(['last_fetched_at' => now()]);

    }

    public function syncCategoriesFromSource(string $source): void
    {
        $adapter = $this->apiFactory->make($source);
        $service = ArticleSource::where('name', $source)->firstOrFail();
        $adapter->syncCategory($service);
    }
}
