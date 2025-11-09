<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ArticleCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    public function paginationInformation($request, $paginated, $default)
    {
        return [
            'pagination' => [
                'total' => $paginated['total'],
                'page' => $paginated['current_page'],
                'pages' => $paginated['last_page'],
                'next' => $paginated['next_page_url'],
            ],
        ];
    }
}
