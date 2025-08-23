<?php

namespace App\Events;

use App\Models\Setting;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 設定更新事件
 */
class SettingUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * 設定鍵值
     *
     * @var string
     */
    public string $key;

    /**
     * 新值
     *
     * @var mixed
     */
    public mixed $newValue;

    /**
     * 舊值
     *
     * @var mixed
     */
    public mixed $oldValue;

    /**
     * 設定模型
     *
     * @var Setting|null
     */
    public ?Setting $setting;

    /**
     * 建立新的事件實例
     *
     * @param string $key 設定鍵值
     * @param mixed $newValue 新值
     * @param mixed $oldValue 舊值
     * @param Setting|null $setting 設定模型
     */
    public function __construct(string $key, mixed $newValue, mixed $oldValue = null, ?Setting $setting = null)
    {
        $this->key = $key;
        $this->newValue = $newValue;
        $this->oldValue = $oldValue;
        $this->setting = $setting;
    }
}