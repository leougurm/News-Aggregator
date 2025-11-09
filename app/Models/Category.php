<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'normalized_name',
        'source_id',
    ];


    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function source()
    {
        return $this->belongsTo(ArticleSource::class, 'source_id');
    }

    /**
     * Auto-create category with normalized name
     */
    public static function findOrCreateNormalized(string $name, int $sourceId): self
    {
        return self::firstOrCreate(
            [
                'name' => $name,
                'source_id' => $sourceId,
            ],
            [
                'normalized_name' => self::normalize($name),
            ]
        );
    }

    /**
     * Normalize category name for fuzzy matching
     */
    public static function normalize(string $name): string
    {
        $normalized = strtolower(trim($name));

        $normalized = str_replace(['&', ' and ', '-', '_'], ' ', $normalized);

        $normalized = Str::singular($normalized);

        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    /**
     * Search by normalized name across all sources
     */
    public static function findByNormalized(string $search): \Illuminate\Support\Collection
    {
        $normalized = self::normalize($search);
        return self::where('normalized_name', $normalized)->get();
    }

    public function newsApıCategories(): HasMany
    {
        return $this->hasMany(NewsApiCategory::class, 'category_id');
    }
}




