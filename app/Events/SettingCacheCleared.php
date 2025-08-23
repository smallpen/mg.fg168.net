<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 設定快取清除事件
 */
class SettingCacheCleared
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * 清除的快取類型
     *
     * @var string
     */
    public string $cacheType;

    /**
     * 清除的設定鍵值（如果是特定設定）
     *
     * @var string|null
     */
    public ?string $settingKey;

    /**
     * 清除的分類（如果是特定分類）
     *
     * @var string|null
     */
    public ?string $category;

    /**
     * 建立新的事件實例
     *
     * @param string $cacheType 快取類型 (all, setting, category, search)
     * @param string|null $settingKey 設定鍵值
     * @param string|null $category 分類
     */
    public function __construct(string $cacheType, ?string $settingKey = null, ?string $category = null)
    {
        $this->cacheType = $cacheType;
        $this->settingKey = $settingKey;
        $this->category = $category;
    }
}