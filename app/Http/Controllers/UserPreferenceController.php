<?php

namespace App\Http\Controllers;

use App\NewsAggregator\Cache\UserPreferenceCacheService;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    public function __construct(private readonly UserPreferenceCacheService $cacheService){}
    /**
     * @OA\Get(
     *     path="/api/v1/user/preferences",
     *     tags={"User Preferences"},
     *     summary="Get user preferences",
     *     description="Retrieve the authenticated user's news preferences",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User preferences retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="user_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="preferred_sources",
     *                     type="array",
     *                     @OA\Items(type="integer", example=1),
     *                     example={1, 2, 3}
     *                 ),
     *                 @OA\Property(
     *                     property="preferred_categories",
     *                     type="array",
     *                     @OA\Items(type="string", example="technology"),
     *                     example={"technology", "business", "sports"}
     *                 ),
     *                 @OA\Property(
     *                     property="preferred_authors",
     *                     type="array",
     *                     @OA\Items(type="string", example="John Doe"),
     *                     example={"John Doe", "Jane Smith"}
     *                 ),
     *                 @OA\Property(
     *                     property="created_at",
     *                     type="string",
     *                     format="date-time",
     *                     example="2024-11-08T10:00:00.000000Z"
     *                 ),
     *                 @OA\Property(
     *                     property="updated_at",
     *                     type="string",
     *                     format="date-time",
     *                     example="2024-11-08T10:00:00.000000Z"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function show(Request $request)
    {
        $preferences = $this->cacheService->get($request->user()->id);

        return response()->json([
            'data' => $preferences ?? [
                    'preferred_sources' => [],
                    'preferred_categories' => [],
                    'preferred_authors' => [],
                ]
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/user/preferences",
     *     tags={"User Preferences"},
     *     summary="Update user preferences",
     *     description="Update the authenticated user's news preferences",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="preferred_sources",
     *                 type="array",
     *                 description="Array of news source IDs",
     *                 @OA\Items(type="integer", example=1),
     *                 example={1, 2, 3}
     *             ),
     *             @OA\Property(
     *                 property="preferred_categories",
     *                 type="array",
     *                 description="Array of category names",
     *                 @OA\Items(type="string", example="technology"),
     *                 example={"technology", "business", "sports"}
     *             ),
     *             @OA\Property(
     *                 property="preferred_authors",
     *                 type="array",
     *                 description="Array of author names",
     *                 @OA\Items(type="string", example="John Doe"),
     *                 example={"John Doe", "Jane Smith"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preferences updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Preferences updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(
     *                     property="preferred_sources",
     *                     type="array",
     *                     @OA\Items(type="integer"),
     *                     example={1, 2, 3}
     *                 ),
     *                 @OA\Property(
     *                     property="preferred_categories",
     *                     type="array",
     *                     @OA\Items(type="string"),
     *                     example={"technology", "business"}
     *                 ),
     *                 @OA\Property(
     *                     property="preferred_authors",
     *                     type="array",
     *                     @OA\Items(type="string"),
     *                     example={"John Doe"}
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="preferred_sources.0",
     *                     type="array",
     *                     @OA\Items(type="string", example="The selected preferred_sources.0 is invalid.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'preferred_sources' => 'nullable|array',
            'preferred_sources.*' => 'integer|exists:article_sources,id',
            'preferred_categories' => 'nullable|array',
            'preferred_categories.*' => 'string',
            'preferred_authors' => 'nullable|array',
            'preferred_authors.*' => 'string',
        ]);

        $user = $request->user();

        $preferences = $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        $this->cacheService->put($user->id, $preferences);

        return response()->json([
            'message' => 'Preferences updated successfully',
            'data' => $preferences
        ]);
    }
}
