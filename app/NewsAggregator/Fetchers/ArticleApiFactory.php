<?php

namespace App\NewsAggregator\Fetchers;

class ArticleApiFactory
{
    public function make(string $source): ArticleApiInterface
    {
        return match($source) {
            'newsapi' => app(NewsApiAdapter::class),
            'nytimes' => app(NewYorkTimesApiAdapter::class),
            'guardian' => app(GuardianApiAdapter::class),
            default => throw new \InvalidArgumentException("Unknown source: $source")
        };
    }
}
