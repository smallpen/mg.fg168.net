<?php

namespace App\Listeners;

use App\Events\SettingUpdated;
use App\Events\SettingsBatchUpdated;
use App\Events\SettingCacheCleared;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 清除設定快取監聽器
 */
class ClearSettingCache implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * 快取前綴
     */
    private const CACHE_PREFIX = 'settings_';

    /**
     * 處理設定更新事件
     *
     * @param SettingUpdated $event
     * @return void
     */
    public function handleSettingUpdated(SettingUpdated $event): void
    {
        $this->clearSettingRelatedCache($event->key, $event->setting?->category);
        
        Log::info('設定快取已清除', [
            'setting_key' => $event->key,
            'category' => $event->setting?->category,
        ]);
    }

    /**
     * 處理批量設定更新事件
     *
     * @param SettingsBatchUpdated $event
     * @return void
     */
    public function handleBatchUpdated(SettingsBatchUpdated $event): void
    {
        // 批量更新時清除所有相關快取
        $this->clearAllSettingsCache();
        
        // 觸發快取清除事件
        event(new SettingCacheCleared('all'));
        
        Log::info('批量設定快取已清除', [
            'update_count' => $event->updateCount,
            'affected_categories' => $event->affectedCategories,
        ]);
    }

    /**
     * 清除特定設定相關的快取
     *
     * @param string $key 設定鍵值
     * @param string|null $category 分類
     * @return void
     */
    protected function clearSettingRelatedCache(string $key, ?string $category = null): void
    {
        // 清除特定設定快取
        Cache::forget(self::CACHE_PREFIX . "key_{$key}");
        Cache::forget(self::CACHE_PREFIX . "value_{$key}");
        
        // 清除分類相關快取
        if ($category) {
            Cache::forget(self::CACHE_PREFIX . "category_{$category}");
        }
        
        // 清除聚合快取
        $this->clearAggregateCache();
        
        // 觸發快取清除事件
        event(new SettingCacheCleared('setting', $key, $category));
    }

    /**
     * 清除所有設定快取
     *
     * @return void
     */
    protected function clearAllSettingsCache(): void
    {
        // 使用標籤清除（如果支援）
        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            Cache::tags(['settings'])->flush();
        } else {
            // 手動清除已知的快取鍵
            $this->clearKnownCacheKeys();
        }
    }

    /**
     * 清除聚合快取
     *
     * @return void
     */
    protected function clearAggregateCache(): void
    {
        $aggregateKeys = [
            self::CACHE_PREFIX . 'all',
            self::CACHE_PREFIX . 'by_category',
            self::CACHE_PREFIX . 'changed',
            self::CACHE_PREFIX . 'categories',
            self::CACHE_PREFIX . 'types',
        ];
        
        foreach ($aggregateKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * 清除已知的快取鍵
     *
     * @return void
     */
    protected function clearKnownCacheKeys(): void
    {
        // 清除聚合快取
        $this->clearAggregateCache();
        
        // 清除搜尋快取（使用模式匹配，如果快取驅動支援）
        if (method_exists(Cache::getStore(), 'flush')) {
            // 對於支援 flush 的驅動，直接清除所有快取
            // 注意：這會清除所有應用程式快取，在生產環境中需要謹慎使用
            // Cache::flush();
        }
        
        // 清除配置快取
        Cache::forget('system_settings_config');
    }

    /**
     * 註冊事件監聽器
     *
     * @return array
     */
    public function subscribe(): array
    {
        return [
            SettingUpdated::class => 'handleSettingUpdated',
            SettingsBatchUpdated::class => 'handleBatchUpdated',
        ];
    }
}