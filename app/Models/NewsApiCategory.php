<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsApiCategory extends Model
{
    protected $table = 'news_api_categories';

    protected $fillable = [
        'source_name',
        'source_id',
        'category_id',
        'url'
    ];

    public function category(): BelongsTo {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
}
