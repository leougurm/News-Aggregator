<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @OA\Schema(
 * schema="Article",
 * title="Article Model",
 * description="Detailed Article object",
 * @OA\Property(
 * property="id",
 * type="integer",
 * format="int64",
 * description="Unique identifier for the article"
 * ),
 * @OA\Property(
 * property="title",
 * type="string",
 * description="The title of the article"
 * ),
 * @OA\Property(
 * property="content",
 * type="string",
 * description="The main body content of the article"
 * ),
 * @OA\Property(
 * property="published_at",
 * type="string",
 * format="date-time",
 * description="The date and time the article was published"
 * ),
 * example={
 * "id": 1,
 * "title": "A Great Blog Post",
 * "content": "This is the content of the article.",
 * "published_at": "2024-11-08T10:00:00.000000Z"
 * }
 * )
 */
class Article extends Model
{
    protected $fillable = [
        'source_id',
        'category_id',
        'keywords',
        'external_id',
        'title',
        'description',
        'slug',
        'url',
        'content',
        'image_url',
        'article_source',
        'author',
        'published_at'
    ];

    protected $casts = [
        'keywords' => 'array', // ✅ Important!
        'raw_data' => 'array',
        'metadata' => 'array',
        'published_at' => 'datetime',
        'fetched_at' => 'datetime',
    ];

    public function source() // Keep as 'source' (singular)
    {
        return $this->belongsTo(ArticleSource::class, 'source_id');
    }

    public function category() // Keep as 'source' (singular)
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Semantic search using vector embeddings
     */
    public function scopeSearch($query, $searchTerm)
    {
        if (empty($searchTerm)) {
            return $query;
        }

        return $query->whereRaw(
            "search_vector @@ plainto_tsquery('english', ?)",
            [$searchTerm]
        )->selectRaw(
            "*, ts_rank(search_vector, plainto_tsquery('english', ?)) as rank",
            [$searchTerm]
        )->orderByDesc('rank');
    }

    /**
     * Filter by author
     */
    public function scopeByAuthor($query, $author)
    {
        return $query->where('author', 'ILIKE', "%{$author}%");
    }

    public function scopeByCategory($query, string $categorySearch)
    {
        // Find all matching normalized categories
        $categoryIds = Category::findByNormalized($categorySearch)->pluck('id');

        if ($categoryIds->isEmpty()) {
            // Fallback: direct search on original name
            $categoryIds = Category::where('name', 'ILIKE', "%{$categorySearch}%")
                ->pluck('id');
        }

        return $query->whereIn('category_id', $categoryIds);
    }
    public function scopeDateRange($query, $from = null, $to = null)
    {
        if ($from) {
            $query->where('published_at', '>=', $from);
        }
        if ($to) {
            $query->where('published_at', '<=', $to);
        }
        return $query;
    }

    public function scopeByTags($query, array $tagNames)
    {
        return $query->whereHas('tags', function ($q) use ($tagNames) {
            $q->whereIn('name', $tagNames);
        });
    }
    public function scopeAdvancedSearch($query, array $filters)
    {
        // Text search
        if (!empty($filters['q'])) {
            $query->search($filters['q']);
        }

        // Author filter
        if (!empty($filters['author'])) {
            $query->byAuthor($filters['author']);
        }

        // Category filter
        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        // Tags filter
        if (!empty($filters['tags'])) {
            $query->byTags($filters['tags']);
        }

        // Date range
        if (!empty($filters['from']) || !empty($filters['to'])) {
            $query->dateRange($filters['from'] ?? null, $filters['to'] ?? null);
        }

        // Source filter
        if (!empty($filters['source_id'])) {
            $query->where('source_id', $filters['source_id']);
        }

        return $query;
    }
}
