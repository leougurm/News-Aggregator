<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArticleSearchRequest;
use App\Http\Resources\ArticleCollection;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\Category;
use App\NewsAggregator\Cache\UserPreferenceCacheService;
use App\NewsAggregator\Fetchers\NewsAggregatorService;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function __construct(
        private readonly NewsAggregatorService $newsAggregator,
        private readonly UserPreferenceCacheService $preferenceCacheService){}

    public function getNews(string $service) {
        $this->newsAggregator->aggregateFromSource($service);
    }

    public function getSourceAndCategory(string $service) {
        $this->newsAggregator->syncCategoriesFromSource($service);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/articles/search",
     *     tags={"Articles"},
     *     summary="Search articles",
     *     description="Search articles with filters",
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search query",
     *         required=false,
     *         @OA\Schema(type="string", example="bitcoin")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Category name",
     *         required=false,
     *         @OA\Schema(type="string", example="technology")
     *     ),
     *     @OA\Parameter(
     *         name="source_id",
     *         in="query",
     *         description="Source ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="author",
     *         in="query",
     *         description="Author name",
     *         required=false,
     *         @OA\Schema(type="string", example="John Doe")
     *     ),
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         description="Start date (Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         description="End date (Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-12-31")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Results per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Article")
     *             )
     *         )
     *     )
     * )
     */
    public function search(ArticleSearchRequest $request) {
        $articles = Article::query()
            ->with(['source'])
            ->advancedSearch([
                'q' => $request->query('q'),
                'author' => $request->query('author'),
                'category' => $request->query('category'),
                'tags' => $request->query('tags') ? explode(',', $request->query('tags')) : [],
                'from' => $request->query('from'),
                'to' => $request->query('to'),
                'source_id' => $request->query('source_id'),
            ])
            ->paginate($request->query('per-page') ?? 10);

        return new ArticleCollection($articles);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user/feed",
     *     tags={"Articles"},
     *     summary="Get personalized feed",
     *     description="Get articles based on user preferences",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Results per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Personalized feed",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Article")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */

    /**
     * @OA\Get(
     *     path="/api/v1/articles/{id}",
     *     tags={"Articles"},
     *     summary="Get single article",
     *     description="Get article by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Article ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article details",
     *         @OA\JsonContent(ref="#/components/schemas/Article")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Article not found"
     *     )
     * )
     */
    public function show($id)
    {
        $article = Article::with(['source', 'category'])->findOrFail($id);
        return new ArticleResource($article);
    }


    /**
     * @OA\Get(
     *     path="/api/v1/user/feed",
     *     tags={"Articles"},
     *     summary="Search articles",
     *     description="Get articles for saved preferences",
     *     @OA\Response(
     *         response=200,
     *         description="Search results",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Article")
     *             )
     *         )
     *     )
     * )
     */
    public function personalizedFeed(Request $request)
    {
        $user = $request->user();
        $preferences = $this->preferenceCacheService->get($user->id);
        if (!$preferences) {
            return $this->search($request);
        }

        $query = Article::query()->with(['source', 'category']);

        // Filter by preferred sources
        if (!empty($preferences->preferred_sources)) {
            $query->whereIn('source_id', $preferences->preferred_sources);
        }

        // Filter by preferred categories
        if (!empty($preferences->preferred_categories)) {
            $categoryIds = Category::whereIn('normalized_name', $preferences->preferred_categories)
                ->pluck('id');
            $query->whereIn('category_id', $categoryIds);
        }

        // Filter by preferred authors
        if (!empty($preferences->preferred_authors)) {
            $query->where(function($q) use ($preferences) {
                foreach ($preferences->preferred_authors as $author) {
                    $q->orWhere('author', 'ILIKE', "%{$author}%");
                }
            });
        }

        $articles = $query->latest('published_at')
            ->paginate($request->input('per_page', 20));

        return new ArticleCollection($articles);
    }
}
