<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * 設定快取模型
 * 
 * @property int $id
 * @property string $cache_key 快取鍵值
 * @property string $cache_data 快取資料
 * @property string $cache_type 快取類型
 * @property \Illuminate\Support\Carbon|null $expires_at 過期時間
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SettingCache extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     *
     * @var string
     */
    protected $table = 'setting_cache';

    /**
     * 可批量賦值的屬性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cache_key',
        'cache_data',
        'cache_type',
        'expires_at',
    ];

    /**
     * 屬性轉換
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * 儲存快取資料
     *
     * @param string $key 快取鍵值
     * @param mixed $data 快取資料
     * @param string $type 快取類型
     * @param int $ttl 存活時間（秒）
     * @return static
     */
    public static function store(string $key, mixed $data, string $type = 'setting', int $ttl = 3600): static
    {
        $serializedData = serialize($data);
        $expiresAt = now()->addSeconds($ttl);

        return static::updateOrCreate(
            ['cache_key' => $key],
            [
                'cache_data' => $serializedData,
                'cache_type' => $type,
                'expires_at' => $expiresAt,
            ]
        );
    }

    /**
     * 取得快取資料
     *
     * @param string $key 快取鍵值
     * @return mixed|null
     */
    public static function retrieve(string $key): mixed
    {
        $cache = static::where('cache_key', $key)
                      ->where(function ($query) {
                          $query->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                      })
                      ->first();

        if (!$cache) {
            return null;
        }

        try {
            return unserialize($cache->cache_data);
        } catch (\Exception $e) {
            Log::warning('快取資料反序列化失敗', [
                'cache_key' => $key,
                'error' => $e->getMessage(),
            ]);
            
            // 刪除損壞的快取
            $cache->delete();
            return null;
        }
    }

    /**
     * 刪除快取
     *
     * @param string $key 快取鍵值
     * @return bool
     */
    public static function forget(string $key): bool
    {
        return static::where('cache_key', $key)->delete() > 0;
    }

    /**
     * 檢查快取是否存在且未過期
     *
     * @param string $key 快取鍵值
     * @return bool
     */
    public static function has(string $key): bool
    {
        return static::where('cache_key', $key)
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                              ->orWhere('expires_at', '>', now());
                    })
                    ->exists();
    }

    /**
     * 清理過期的快取
     *
     * @return int 清理的記錄數
     */
    public static function cleanupExpired(): int
    {
        return static::where('expires_at', '<=', now())->delete();
    }

    /**
     * 清理指定類型的快取
     *
     * @param string $type 快取類型
     * @return int 清理的記錄數
     */
    public static function cleanupByType(string $type): int
    {
        return static::where('cache_type', $type)->delete();
    }

    /**
     * 範圍查詢：按快取類型
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('cache_type', $type);
    }

    /**
     * 範圍查詢：未過期的快取
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * 範圍查詢：已過期的快取
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * 取得快取統計資訊
     *
     * @return array
     */
    public static function getStats(): array
    {
        $total = static::count();
        $expired = static::expired()->count();
        $byType = static::selectRaw('cache_type, COUNT(*) as count')
                        ->groupBy('cache_type')
                        ->pluck('count', 'cache_type')
                        ->toArray();

        return [
            'total' => $total,
            'active' => $total - $expired,
            'expired' => $expired,
            'by_type' => $byType,
            'hit_rate' => $total > 0 ? round((($total - $expired) / $total) * 100, 2) : 0,
        ];
    }

    /**
     * 批量儲存快取
     *
     * @param array $items 快取項目陣列 [key => data]
     * @param string $type 快取類型
     * @param int $ttl 存活時間（秒）
     * @return int 儲存的項目數量
     */
    public static function storeBatch(array $items, string $type = 'setting', int $ttl = 3600): int
    {
        $stored = 0;
        $expiresAt = now()->addSeconds($ttl);

        foreach ($items as $key => $data) {
            try {
                static::updateOrCreate(
                    ['cache_key' => $key],
                    [
                        'cache_data' => serialize($data),
                        'cache_type' => $type,
                        'expires_at' => $expiresAt,
                    ]
                );
                $stored++;
            } catch (\Exception $e) {
                Log::error('批量快取儲存失敗', [
                    'cache_key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stored;
    }

    /**
     * 批量取得快取
     *
     * @param array $keys 快取鍵值陣列
     * @return array 快取資料陣列
     */
    public static function retrieveBatch(array $keys): array
    {
        $results = [];
        
        $caches = static::whereIn('cache_key', $keys)
                       ->notExpired()
                       ->get()
                       ->keyBy('cache_key');

        foreach ($keys as $key) {
            if (isset($caches[$key])) {
                try {
                    $results[$key] = unserialize($caches[$key]->cache_data);
                } catch (\Exception $e) {
                    Log::warning('批量快取反序列化失敗', [
                        'cache_key' => $key,
                        'error' => $e->getMessage(),
                    ]);
                    
                    // 刪除損壞的快取
                    $caches[$key]->delete();
                }
            }
        }

        return $results;
    }

    /**
     * 取得快取大小估算
     *
     * @return array
     */
    public static function getSizeEstimate(): array
    {
        $stats = static::selectRaw('
            cache_type,
            COUNT(*) as count,
            AVG(LENGTH(cache_data)) as avg_size,
            SUM(LENGTH(cache_data)) as total_size
        ')
        ->groupBy('cache_type')
        ->get();

        $result = [];
        $totalSize = 0;

        foreach ($stats as $stat) {
            $size = [
                'count' => $stat->count,
                'avg_size' => round($stat->avg_size, 2),
                'total_size' => $stat->total_size,
                'formatted_size' => static::formatBytes($stat->total_size),
            ];
            
            $result[$stat->cache_type] = $size;
            $totalSize += $stat->total_size;
        }

        $result['total'] = [
            'size' => $totalSize,
            'formatted_size' => static::formatBytes($totalSize),
        ];

        return $result;
    }

    /**
     * 格式化位元組大小
     *
     * @param int $bytes
     * @return string
     */
    protected static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}