<?php

namespace App\NewsAggregator\Fetchers;

use App\Models\Article;

class ArticleRepository
{
    public function __construct(private Article $model) {}

    public function updateOrCreate(array $attributes, array $values): Article
    {
        return $this->model->updateOrCreate($attributes, $values);
    }
}
