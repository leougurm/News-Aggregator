<?php

namespace App\NewsAggregator\Fetchers;

use App\Exceptions\RateLimitException;
use App\Models\ArticleSource;
use App\Models\Category;
use App\NewsAggregator\Rest\Rest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class NewYorkTimesApiAdapter implements ArticleApiInterface
{

    public function __construct(private readonly Rest $rest)
    {
    }

    public function fetchData(ArticleSource $service, string $section, bool $retry = false): array
    {
        try {
            $response = $this->rest->fetchData(
                $service->api_url . "/svc/topstories/v2/$section.json",
                [
                    'api-key' => !$retry ? $service->api_key : $service->fallback_api_key,
                ],
            );

            if (!isset($response)) {
                Log::warning("Invalid NYTimes API response structure", [
                    'section' => $section,
                    'response_keys' => array_keys($response)
                ]);
                return [];
            }

            $docs = $response['results'] ?? [];

            return $docs;

        } catch (TooManyRequestsHttpException $e) {
            if ($retry) {
                return [];
            }
            if ($e->getCode() !== 429) {
                sleep(5);
                Log::info("Http request will run again for $service->name: $section");
                return $this->fetchData($service, $section, true);
            }
        } catch (\RuntimeException $e) {
            Log::error("Failed to fetch NYTimes data", [
                'section' => $section,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

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
        $category = Category::findOrCreateNormalized(
            $result['section'],
            $serviceId
        );
        return [
            'source_id' => ArticleSource::where('name', 'nytimes')->first()->id,
            'category_id' => $category?->id,
            'description' => Arr::get($result, 'abstract'),
            'external_id' => $result['uri'],
            'title' => Arr::get($result, 'title'),
            'slug' => Str::slug(Arr::get($result, 'title')),
            'url' => Arr::get($result, 'published_date'),
            'content' => Arr::get($result, 'lead_paragraph')
                ?? Arr::get($result, 'abstract'),
            'image_url' => Arr::get($result, 'multimedia.0.url'),
            'author' => Arr::get($result, 'byline'),
            'published_at' => Arr::get($result, 'published_date')
        ];
    }

    public function syncCategory(ArticleSource $service): void
    {
        $sections = [
            'home', 'arts', 'automobiles', 'books', 'business', 'fashion',
            'food', 'health', 'insider', 'movies', 'nyregion',
            'obituaries', 'opinion', 'politics', 'realestate', 'science',
            'sports', 'sundayreview', 'technology', 'theater', 't-magazine',
            'travel', 'upshot', 'us', 'world'
        ];
        foreach ($sections as $section) {
            Category::findOrCreateNormalized($section, $service->id);
        }
    }
}

