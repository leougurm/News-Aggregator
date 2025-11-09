<?php

namespace App\NewsAggregator\Fetchers;

use App\Models\ArticleSource;
use App\Models\Category;
use App\Models\NewsApiCategory;
use App\NewsAggregator\Rest\Rest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class NewsApiAdapter implements ArticleApiInterface
{
    public function __construct(private readonly Rest $rest)
    {
    }

    public function fetchData(ArticleSource $service, string $section, bool $retry=false): array
    {
        try {

            $response = $this->rest->fetchData(
                $service->api_url . "/everything",
                [
                    'q' => $section,
                    'apiKey' => !$retry ? $service->api_key : $service->fallback_api_key],
            );


            if (!isset($response['articles'])) {
                Log::warning("Invalid NewsApi API response structure", [
                    'section' => $section,
                    'response_keys' => array_keys($response)
                ]);
                return [];
            }

            return $response['articles'];
        } catch (\RuntimeException $e) {
            Log::error("Failed to fetch NYTimes data", [
                'section' => $section,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            // Don't rethrow - return empty array to allow processing to continue
            return [];

        } catch (\Exception $e) {
            Log::error("Unexpected error fetching NYTimes data", [
                'section' => $section,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function mapData(int $serviceId, array $result): array
    {
        $newsApiCategory = NewsApiCategory::where('source_id', $result['source']['id'])->first();

        if (!$newsApiCategory) {
            $category = Category::first();
        } else {
            $category = $newsApiCategory->category;
        }

        return [
            'source_id' => ArticleSource::where('name', 'newsapi')->first()->id,
            'category_id' => $category->id,
            'description' => $result['description'],
            'external_id' => $result['url'],
            'title' => $result['title'],
            'slug' => Str::slug($result['title']),
            'url' => $result['url'],
            'content' => $result['content'],
            'image_url' => $result['urlToImage'],
            'author' => $result['author'],
            'article_source' => $result['source']['name'],
            'published_at' => $result['publishedAt']
        ];
    }

    public function syncCategory(ArticleSource $service, bool $retry= false): void
    {
        try {
            $response = $this->rest->fetchData(
                $service->api_url . "/top-headlines/sources",
                [
                    'apiKey' => !$retry ? $service->api_key : $service->fallback_api_key],
            );
            $sources = $response['sources'];

            collect($sources)->each(function ($source) use ($service) {
                $category = Category::findOrCreateNormalized($source['category'], $service->id);
                $category->newsApıCategories()->updateOrCreate([
                    'category_id' => $category->id,
                    'source_id' => $source['id'],
                ],
                    [
                        'source_name' => $source['name'],
                        'url' => $source['url'],
                    ]
                );
            });
        } catch (TooManyRequestsHttpException $e) {
            if ($retry) {
                return;
            }
            if ($e->getCode() !== 429) {
                Log::info("Http request will run again for $service->name");
                $this->syncCategory($service, true);
            }
        }
    }
}
