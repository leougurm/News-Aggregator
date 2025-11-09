<?php

namespace App\NewsAggregator\Fetchers;

use App\Models\ArticleSource;

interface ArticleApiInterface
{
    public function fetchData(ArticleSource $service, string $section): array;
    function mapData(int $serviceId, array $result): array;

    /* it is for news Api since it does not give category in tpp-headlines or everything endpoints */
    public function syncCategory(ArticleSource $service): void;
}
