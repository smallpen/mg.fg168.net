<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 批量設定更新事件
 */
class SettingsBatchUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * 更新的設定陣列
     *
     * @var array
     */
    public array $settings;

    /**
     * 影響的分類
     *
     * @var array
     */
    public array $affectedCategories;

    /**
     * 更新數量
     *
     * @var int
     */
    public int $updateCount;

    /**
     * 建立新的事件實例
     *
     * @param array $settings 更新的設定陣列
     * @param array $affectedCategories 影響的分類
     */
    public function __construct(array $settings, array $affectedCategories = [])
    {
        $this->settings = $settings;
        $this->affectedCategories = $affectedCategories;
        $this->updateCount = count($settings);
    }
}