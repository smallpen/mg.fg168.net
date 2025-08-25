<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\MultilingualLogger;
use App\Services\LanguageFallbackHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\App;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * 多語系日誌記錄整合測試
 */
class MultilingualLoggingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 設定測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * 測試語言切換時的日誌記錄
     */
    public function test_language_switch_logging_in_middleware(): void
    {
        // 模擬日誌記錄
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->with('Language switched successfully', \Mockery::type('array'));

        // 發送帶有語言參數的請求
        $response = $this->get('/admin/login?locale=en');

        $response->assertStatus(200);
        
        // 驗證語言已切換
        $this->assertEquals('en', App::getLocale());
        
        // 驗證統計快取
        $this->assertTrue(Cache::has('multilingual_stats:switch_daily:' . now()->format('Y-m-d')));
    }

    /**
     * 測試不支援語言的錯誤日誌記錄
     */
    public function test_unsupported_locale_error_logging(): void
    {
        // 模擬日誌記錄
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('error')->with('Language switch failed', \Mockery::type('array'));

        // 發送不支援的語言請求
        $response = $this->get('/admin/login?locale=invalid_locale');

        $response->assertStatus(200);
        
        // 驗證回退到預設語言
        $this->assertEquals('zh_TW', App::getLocale());
    }

    /**
     * 測試翻譯回退機制的日誌記錄
     */
    public function test_translation_fallback_logging(): void
    {
        $fallbackHandler = app(LanguageFallbackHandler::class);
        
        // 模擬日誌記錄
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->with('Translation fallback used', \Mockery::type('array'));
        Log::shouldReceive('warning')->with('Missing translation key', \Mockery::type('array'));

        // 嘗試翻譯不存在的鍵
        $result = $fallbackHandler->translate('nonexistent.translation.key');

        // 驗證返回鍵值本身
        $this->assertEquals('nonexistent.translation.key', $result);
        
        // 驗證統計快取
        $this->assertTrue(Cache::has('multilingual_stats:fallback_daily:' . now()->format('Y-m-d')));
    }

    /**
     * 測試語言檔案載入效能日誌記錄
     */
    public function test_language_file_performance_logging(): void
    {
        $logger = app(MultilingualLogger::class);
        
        // 模擬慢載入
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('warning')->with('Slow language file load', \Mockery::type('array'));

        $logger->logLanguageFilePerformance('admin', 'zh_TW', 150.0, false);
    }

    /**
     * 測試使用者語言偏好更新日誌記錄
     */
    public function test_user_language_preference_update_logging(): void
    {
        // 建立測試使用者
        $user = \App\Models\User::factory()->create([
            'locale' => 'zh_TW'
        ]);

        $this->actingAs($user);

        // 模擬日誌記錄
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->with('Language preference updated', \Mockery::type('array'));
        Log::shouldReceive('info')->with('Language switched successfully', \Mockery::type('array'));

        // 切換語言
        $response = $this->get('/admin/dashboard?locale=en');

        $response->assertStatus(200);
        
        // 驗證使用者語言偏好已更新
        $this->assertEquals('en', $user->fresh()->locale);
    }

    /**
     * 測試翻譯參數錯誤日誌記錄
     */
    public function test_translation_parameter_error_logging(): void
    {
        $logger = app(MultilingualLogger::class);
        
        // 模擬日誌記錄
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('warning')->with('Translation parameter mismatch', \Mockery::type('array'));

        $logger->logTranslationParameterError(
            'test.message',
            ['name', 'email'],
            ['name' => 'John', 'age' => 25, 'extra' => 'data']
        );
    }

    /**
     * 測試多語系統計資訊收集
     */
    public function test_multilingual_statistics_collection(): void
    {
        $logger = app(MultilingualLogger::class);
        
        // 模擬各種事件
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('warning')->times(3);
        Log::shouldReceive('info')->times(2);

        // 記錄多個事件
        $logger->logMissingTranslationKey('test.key1', 'zh_TW');
        $logger->logMissingTranslationKey('test.key2', 'zh_TW');
        $logger->logMissingTranslationKey('test.key3', 'en');
        
        $logger->logLanguageSwitchSuccess('zh_TW', 'en', 'user_action');
        $logger->logLanguageFallback('test.key', 'zh_TW', 'en');

        // 取得統計資訊
        $stats = $logger->getStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('daily_stats', $stats);
        
        // 驗證統計數據
        $this->assertEquals(3, $stats['daily_stats']['missing_keys']);
        $this->assertEquals(1, $stats['daily_stats']['fallback_usage']);
        $this->assertEquals(1, $stats['daily_stats']['language_switches']);
    }

    /**
     * 測試語言檔案載入錯誤日誌記錄
     */
    public function test_language_file_load_error_logging(): void
    {
        $logger = app(MultilingualLogger::class);
        
        // 模擬日誌記錄
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('error')->with('Language file load error', \Mockery::type('array'));

        $logger->logLanguageFileLoadError('nonexistent', 'zh_TW', 'File not found');
    }

    /**
     * 測試日誌上下文資訊完整性
     */
    public function test_log_context_completeness(): void
    {
        $logger = app(MultilingualLogger::class);
        
        // 驗證日誌包含必要的上下文資訊
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('warning')->with('Missing translation key detected', \Mockery::on(function ($context) {
            return isset($context['type']) &&
                   isset($context['translation_key']) &&
                   isset($context['locale']) &&
                   isset($context['url']) &&
                   isset($context['method']) &&
                   isset($context['ip_address']) &&
                   isset($context['session_id']);
        }));

        $logger->logMissingTranslationKey('test.key', 'zh_TW');
    }

    /**
     * 測試日誌記錄的效能影響
     */
    public function test_logging_performance_impact(): void
    {
        $logger = app(MultilingualLogger::class);
        
        // 模擬日誌記錄
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('warning')->times(100);

        $startTime = microtime(true);
        
        // 記錄大量事件
        for ($i = 0; $i < 100; $i++) {
            $logger->logMissingTranslationKey("test.key.{$i}", 'zh_TW');
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // 轉換為毫秒
        
        // 驗證日誌記錄不會造成顯著的效能影響（應該在 100ms 內完成）
        $this->assertLessThan(100, $duration, '日誌記錄效能影響過大');
    }

    /**
     * 清理測試環境
     */
    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}