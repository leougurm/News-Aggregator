<?php

namespace App\NewsAggregator\Rest;

use App\Exceptions\RateLimitException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class Rest
{
    private string $url;
    private string $apiKey;

    public function fetchData(string $url, array $query, string $fallbackApiKey = null): array
    {
        try {
            $response = Http::retry(2,1000)->get($this->createUrl($url, $query));

            if ($response->status() >= 400 && $response->status() < 500) {
                Log::error("Client error from API", [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                throw new \RuntimeException(
                    "API client error: {$response->status()}",
                    $response->status()
                );
            }
            $response->throw();

            $data = $response->json();
            if (!is_array($data)) {
                throw new \RuntimeException("Invalid API response format: expected array");
            }
            return $data;
        } catch (\Exception $e) {
            if($e->getCode() == 429) {
                Log::error("Rate limit exceeded for $url");
                throw new TooManyRequestsHttpException();
            }
            throw new \Exception("Error: {$e->getMessage()}", $e->getCode());
        }
    }

    private function createUrl(string $url, array $query): string
    {
        return $url . "?" . http_build_query($query);

    }
}
