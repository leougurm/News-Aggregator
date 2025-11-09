<?php

namespace App\NewsAggregator\Fetchers;

use App\Models\ArticleSource;
use App\Models\Category;
use App\NewsAggregator\Rest\Rest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class GuardianApiAdapter implements ArticleApiInterface
{
    public function __construct(private readonly Rest $rest)
    {
    }

    public function fetchData(ArticleSource $service, string $section, bool $retry = false): array
    {
        try {
            $response = $this->rest->fetchData(
                $service->api_url . "/search",
                [
                    'show-fields' => 'all',
                    'show-tags' => 'all',
                    'page-size' => 200,
                    'section' => $section,
                    'api-key' => !$retry ? $service->api_key : $service->fallback_api_key],
            );

            if (!isset($response['response'])) {
                Log::warning("Invalid NYTimes API response structure", [
                    'section' => $section,
                    'response_keys' => array_keys($response)
                ]);
                return [];
            }

            $docs = $response['response']['results'];

            return $docs;
        } catch (TooManyRequestsHttpException $e) {
            if ($retry) {
                return [];
            }
            if ($e->getCode() !== 429) {
                Log::info("Http request will run again for $service->name: $section");
                return $this->fetchData($service, $section, true);
            }
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
        $category = Category::findOrCreateNormalized(
            $result['sectionName'],
            $serviceId
        );

        return [
            'source_id' => ArticleSource::where('name', 'guardian')->first()?->id,
            'category_id' => $category?->id,
            'description' => $result['fields']['headline'] ?? null,
            'keywords' => collect($result['tags'] ?? [])
                ->filter(fn($item) => isset($item['type']) && $item['type'] === 'keyword')
                ->map(fn($item) => $item['webTitle'] ?? null)
                ->filter() // Remove nulls
                ->values()
                ->toArray(),
            'external_id' => $result['id'] ?? null,
            'title' => $result['webTitle'] ?? 'Untitled',
            'slug' => Str::slug($result['webTitle'] ?? 'untitled-' . uniqid()),
            'url' => $result['webUrl'] ?? null,
            'content' => $result['fields']['body'] ?? null,
            'image_url' => $result['fields']['thumbnail'] ?? null,
            'author' => $result['fields']['byline'] ?? null,
            'article_source' => $result['fields']['byline'] ?? 'Unknown',
            'published_at' => $result['fields']['firstPublicationDate'] ?? now(),
        ];
    }

    public function syncCategory(ArticleSource $service, bool $retry = false): void
    {
        try {
            $response = $this->rest->fetchData(
                $service->api_url . "/sections",
                [
                    'api-key' => !$retry ? $service->api_key : $service->fallback_api_key
                ],
            );

            foreach ($response['response']['results'] as $section) {
                Category::findOrCreateNormalized($section['id'], $service->id);
            }
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
