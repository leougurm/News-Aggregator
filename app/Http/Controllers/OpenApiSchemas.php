<?php

/**
 * All OpenAPI Schema Definitions
 *
 * @OA\Schema(
 *     schema="Article",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Bitcoin reaches new high"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="content", type="string"),
 *     @OA\Property(property="url", type="string"),
 *     @OA\Property(property="image_url", type="string"),
 *     @OA\Property(property="author", type="string", example="John Doe"),
 *     @OA\Property(property="published_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Source",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="TechNews")
 * )
 *
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="technology")
 * )
 */

