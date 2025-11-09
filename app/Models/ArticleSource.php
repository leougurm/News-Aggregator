<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 * schema="ArticleSource",
 * title="Article Source List",
 * @OA\Property(
 * property="id",
 * type="integer",
 * format="int64",
 * description="Unique identifier for the source"
 * ),
 * @OA\Property(
 * property="name",
 * type="string",
 * description="The name of the source"
 * ),
 * @OA\Property(
 * property="isActive",
 * type="boolean",
 * description="Gives if the source fetching is active"
 * ),
 * )
 */

class ArticleSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'last_fetched_at'
    ];

    protected $casts = [
        'last_fetched_at' => 'datetime',
    ];


    public function categories(): HasMany
    {
        return $this->hasMany(Category::class,'source_id');
    }
}
