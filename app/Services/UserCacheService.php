<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * 使用者快取服務
 * 
 * 統一管理使用者相關的快取操作
 */
class UserCacheService
{
    /**
     * 快取標籤
     */
    const CACHE_TAGS = [
        'users' => 'users',
        'users_paginated' => 'users_paginated',
        'user_stats' => 'user_stats',
        'user_roles' => 'user_roles'
    ];

    /**
     * 快取鍵前綴
     */
    const CACHE_KEYS = [
        'user_stats' => 'user_stats',
        'user_list_stats' => 'user_list_stats',
        'user_roles_list' => 'user_roles_list',
        'users_paginated' => 'users_paginated'
    ];

    /**
     * 快取過期時間（秒）
     */
    const CACHE_TTL = [
        'user_stats' => 1800,      // 30 分鐘
        'user_roles' => 3600,      // 1 小時
        'users_paginated' => 300,  // 5 分鐘
        'user_search' => 60        // 1 分鐘
    ];

    /**
     * 清除所有使用者相關快取
     */
    public function clearAll(): void
    {
        // 清除統計快取
        Cache::forget(self::CACHE_KEYS['user_stats']);
        Cache::forget(self::CACHE_KEYS['user_list_stats']);
        
        // 清除角色列表快取
        Cache::forget(self::CACHE_KEYS['user_roles_list']);
        
        // 清除分頁查詢快取
        Cache::tags([self::CACHE_TAGS['users_paginated']])->flush();
    }

    /**
     * 清除統計快取
     */
    public function clearStats(): void
    {
        Cache::forget(self::CACHE_KEYS['user_stats']);
        Cache::forget(self::CACHE_KEYS['user_list_stats']);
    }

    /**
     * 清除角色快取
     */
    public function clearRoles(): void
    {
        Cache::forget(self::CACHE_KEYS['user_roles_list']);
    }

    /**
     * 清除查詢結果快取
     */
    public function clearQueries(): void
    {
        Cache::tags([self::CACHE_TAGS['users_paginated']])->flush();
    }

    /**
     * 記住快取值
     *
     * @param string $key 快取鍵
     * @param callable $callback 回調函數
     * @param int|null $ttl 過期時間
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? self::CACHE_TTL['user_stats'];
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * 使用標籤記住快取值
     *
     * @param array $tags 快取標籤
     * @param string $key 快取鍵
     * @param callable $callback 回調函數
     * @param int|null $ttl 過期時間
     * @return mixed
     */
    public function rememberWithTags(array $tags, string $key, callable $callback, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? self::CACHE_TTL['users_paginated'];
        return Cache::tags($tags)->remember($key, $ttl, $callback);
    }

    /**
     * 建立查詢快取鍵
     *
     * @param string $prefix 前綴
     * @param array $params 參數
     * @return string
     */
    public function buildQueryCacheKey(string $prefix, array $params): string
    {
        ksort($params); // 確保參數順序一致
        return $prefix . '_' . md5(serialize($params));
    }

    /**
     * 預熱快取
     * 在系統空閒時預先載入常用資料到快取
     */
    public function warmUp(): void
    {
        // 預熱角色列表
        $this->remember(self::CACHE_KEYS['user_roles_list'], function () {
            return \App\Models\Role::select('id', 'name', 'display_name')
                                  ->orderBy('display_name')
                                  ->get();
        }, self::CACHE_TTL['user_roles']);

        // 預熱統計資料
        $this->remember(self::CACHE_KEYS['user_stats'], function () {
            return app(\App\Repositories\UserRepository::class)->getStats();
        }, self::CACHE_TTL['user_stats']);
    }

    /**
     * 取得快取統計資訊
     */
    public function getCacheStats(): array
    {
        $stats = [];
        
        foreach (self::CACHE_KEYS as $name => $key) {
            $stats[$name] = [
                'exists' => Cache::has($key),
                'key' => $key
            ];
        }
        
        return $stats;
    }
}