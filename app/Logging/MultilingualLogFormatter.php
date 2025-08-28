<?php

namespace App\Logging;

use Monolog\Logger as MonologLogger;
use Illuminate\Log\Logger as LaravelLogger;
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
     * @param LaravelLogger $logger
     * @return void
     */
    public function __invoke(LaravelLogger $logger): void
    {
        // 設定自定義格式
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true, // allowInlineLineBreaks
            false // ignoreEmptyContextAndExtra
        );

        // 取得底層的 Monolog Logger
        $monologLogger = $logger->getLogger();
        
        // 為所有處理器設定格式化器
        foreach ($monologLogger->getHandlers() as $handler) {
            $handler->setFormatter($formatter);
        }

        // 添加 Web 處理器以獲取請求資訊
        $monologLogger->pushProcessor(new WebProcessor());
        
        // 添加內省處理器以獲取呼叫位置資訊
        $monologLogger->pushProcessor(new IntrospectionProcessor());
        
        // 添加自定義處理器 - 使用 callable
        $monologLogger->pushProcessor(function($record) {
            return $this->addMultilingualContext($record);
        });
    }

    /**
     * 添加多語系相關的上下文資訊
     *
     * @param array|\Monolog\LogRecord $record
     * @return array|\Monolog\LogRecord
     */
    public function addMultilingualContext($record)
    {
        try {
            // 處理 Monolog 3.x 的 LogRecord 物件
            if ($record instanceof \Monolog\LogRecord) {
                $extra = $record->extra;
                $extra['multilingual_context'] = [
                    'current_locale' => app()->getLocale(),
                    'default_locale' => config('app.locale'),
                    'fallback_locale' => config('app.fallback_locale'),
                ];
                
                // 添加使用者語言偏好（如果已登入）
                if (auth()->check()) {
                    $extra['multilingual_context']['user_locale'] = auth()->user()->locale ?? null;
                    $extra['multilingual_context']['user_id'] = auth()->id();
                }
                
                // 添加 session 語言設定
                if (session()->has('locale')) {
                    $extra['multilingual_context']['session_locale'] = session('locale');
                }
                
                // 添加瀏覽器語言偏好
                if (request()->hasHeader('Accept-Language')) {
                    $extra['multilingual_context']['browser_language'] = request()->header('Accept-Language');
                }
                
                // 添加時間戳記
                $extra['multilingual_context']['timestamp'] = now()->toISOString();
                
                // 使用 Monolog 3.x 的正確方式來更新 extra 資料
                return new \Monolog\LogRecord(
                    $record->datetime,
                    $record->channel,
                    $record->level,
                    $record->message,
                    $record->context,
                    $extra
                );
            }
            
            // 處理舊版本的陣列格式
            if (is_array($record)) {
                if (!isset($record['extra'])) {
                    $record['extra'] = [];
                }
                
                $record['extra']['multilingual_context'] = [
                    'current_locale' => app()->getLocale(),
                    'default_locale' => config('app.locale'),
                    'fallback_locale' => config('app.fallback_locale'),
                ];
                
                // 添加使用者語言偏好（如果已登入）
                if (auth()->check()) {
                    $record['extra']['multilingual_context']['user_locale'] = auth()->user()->locale ?? null;
                    $record['extra']['multilingual_context']['user_id'] = auth()->id();
                }
                
                // 添加 session 語言設定
                if (session()->has('locale')) {
                    $record['extra']['multilingual_context']['session_locale'] = session('locale');
                }
                
                // 添加瀏覽器語言偏好
                if (request()->hasHeader('Accept-Language')) {
                    $record['extra']['multilingual_context']['browser_language'] = request()->header('Accept-Language');
                }
                
                // 添加時間戳記
                $record['extra']['multilingual_context']['timestamp'] = now()->toISOString();
            }
        } catch (\Exception $e) {
            // 如果處理過程中發生錯誤，記錄錯誤但不影響原始日誌記錄
            error_log("MultilingualLogFormatter error: " . $e->getMessage());
        }
        
        return $record;
    }
}