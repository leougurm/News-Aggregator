<?php

namespace App\NewsAggregator\Cache;

use App\Models\UserPreference;
use Illuminate\Support\Facades\Cache;

class UserPreferenceCacheService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'user_preferences:';

    public function get(int $userId): ?UserPreference
    {
        return Cache::remember(
            self::CACHE_PREFIX . $userId,
            self::CACHE_TTL,
            fn() => UserPreference::where('user_id', $userId)->first()
        );
    }

    public function put(int $userId, UserPreference $preferences): void
    {
        Cache::put(
            self::CACHE_PREFIX . $userId,
            $preferences,
            self::CACHE_TTL
        );
    }

    public function forget(int $userId): void
    {
        Cache::forget(self::CACHE_PREFIX . $userId);
    }

    public function refresh(int $userId): ?UserPreference
    {
        $this->forget($userId);
        return $this->get($userId);
    }
}
