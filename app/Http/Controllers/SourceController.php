<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleSourceResource;
use App\Models\Article;
use App\Models\ArticleSource;
use Illuminate\Http\Request;

class SourceController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/v1/sources",
     *     tags={"Sources"},
     *     summary="Get Sources",
     *     description="Get sources",
     *     @OA\Response(
     *         response=200,
     *         description="Source Details",
     *         @OA\JsonContent(ref="#/components/schemas/Article")
     *     ),
     * )
     */
    public function index() {
        $sources = ArticleSource::select( ['id','name','is_active'])->get();

        return ArticleSourceResource::collection($sources);
    }
}
