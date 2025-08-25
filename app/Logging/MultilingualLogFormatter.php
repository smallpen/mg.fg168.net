<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\IntrospectionProcessor;

/**
 * 多語系日誌格式化器
 * 
 * 為多語系相關日誌提供統一的格式化和額外的上下文資訊
 */
class MultilingualLogFormatter
{
    /**
     * 自定義日誌頻道
     *
     * @param Logger $logger
     * @return void
     */
    public function __invoke(Logger $logger): void
    {
        // 設定自定義格式
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );

        // 為所有處理器設定格式化器
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter($formatter);
        }

        // 添加 Web 處理器以獲取請求資訊
        $logger->pushProcessor(new WebProcessor());
        
        // 添加內省處理器以獲取呼叫位置資訊
        $logger->pushProcessor(new IntrospectionProcessor());
        
        // 添加自定義處理器
        $logger->pushProcessor([$this, 'addMultilingualContext']);
    }

    /**
     * 添加多語系相關的上下文資訊
     *
     * @param array $record
     * @return array
     */
    public function addMultilingualContext(array $record): array
    {
        $record['extra']['current_locale'] = app()->getLocale();
        $record['extra']['default_locale'] = config('app.locale');
        $record['extra']['fallback_locale'] = config('app.fallback_locale');
        
        // 添加使用者語言偏好（如果已登入）
        if (auth()->check()) {
            $record['extra']['user_locale'] = auth()->user()->locale ?? null;
            $record['extra']['user_id'] = auth()->id();
        }
        
        // 添加 session 語言設定
        if (session()->has('locale')) {
            $record['extra']['session_locale'] = session('locale');
        }
        
        // 添加瀏覽器語言偏好
        if (request()->hasHeader('Accept-Language')) {
            $record['extra']['browser_language'] = request()->header('Accept-Language');
        }
        
        // 添加時間戳記
        $record['extra']['timestamp'] = now()->toISOString();
        
        return $record;
    }
}