<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\MultilingualLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * 多語系日誌記錄器測試
 */
class MultilingualLoggerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 多語系日誌記錄器
     */
    private MultilingualLogger $logger;

    /**
     * 設定測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new MultilingualLogger();
        
        // 清除快取
        Cache::flush();
    }

    /**
     * 測試記錄缺少翻譯鍵
     */
    public function test_log_missing_translation_key(): void
    {
        // 模擬日誌記錄
        Log::shouldReceive('channel')
            ->with('multilingual_errors')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('warning')
            ->with('Missing translation key detected', \Mockery::type('array'))
            ->once();

        // 執行測試
        $this->logger->logMissingTranslationKey('test.missing.key', 'zh_TW', [
            'additional_context' => 'test_data'
        ]);

        // 驗證統計計數器
        $this->assertTrue(Cache::has('multilingual_stats:missing_keys:zh_TW:' . md5('test.missing.key')));
        $this->assertTrue(Cache::has('multilingual_stats:missing_keys_daily:' . now()->format('Y-m-d')));
    }

    /**
     * 測試記錄語言切換失敗
     */
    public function test_log_language_switch_failure(): void
    {
        Log::shouldReceive('channel')
            ->with('multilingual_errors')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('error')
            ->with('Language switch failed', \Mockery::type('array'))
            ->once();

        $this->logger->logLanguageSwitchFailure('invalid_locale', 'Unsupported locale', [
            'supported_locales' => ['zh_TW', 'en']
        ]);
    }

    /**
     * 測試記錄語言檔案載入錯誤
     */
    public function test_log_language_file_load_error(): void
    {
        Log::shouldReceive('channel')
            ->with('multilingual_errors')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('error')
            ->with('Language file load error', \Mockery::type('array'))
            ->once();

        $this->logger->logLanguageFileLoadError('admin', 'zh_TW', 'File not found', [
            'file_path' => '/path/to/file'
        ]);
    }

    /**
     * 測試記錄語言回退使用
     */
    public function test_log_language_fallback(): void
    {
        Log::shouldReceive('channel')
            ->with('multilingual')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('info')
            ->with('Language fallback used', \Mockery::type('array'))
            ->once();

        $this->logger->logLanguageFallback('test.key', 'zh_TW', 'en');

        // 驗證統計計數器
        $this->assertTrue(Cache::has('multilingual_stats:fallback:zh_TW:en'));
        $this->assertTrue(Cache::has('multilingual_stats:fallback_daily:' . now()->format('Y-m-d')));
    }

    /**
     * 測試記錄語言切換成功
     */
    public function test_log_language_switch_success(): void
    {
        Log::shouldReceive('channel')
            ->with('multilingual')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('info')
            ->with('Language switched successfully', \Mockery::type('array'))
            ->once();

        $this->logger->logLanguageSwitchSuccess('zh_TW', 'en', 'user_action');

        // 驗證統計計數器
        $this->assertTrue(Cache::has('multilingual_stats:switch:zh_TW:en:user_action'));
        $this->assertTrue(Cache::has('multilingual_stats:switch_daily:' . now()->format('Y-m-d')));
    }

    /**
     * 測試記錄語言檔案效能
     */
    public function test_log_language_file_performance(): void
    {
        // 測試慢載入（超過閾值）
        Log::shouldReceive('channel')
            ->with('multilingual_performance')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('warning')
            ->with('Slow language file load', \Mockery::type('array'))
            ->once();

        $this->logger->logLanguageFilePerformance('admin', 'zh_TW', 150.5, false);
    }

    /**
     * 測試記錄語言檔案效能（快速載入）
     */
    public function test_log_language_file_performance_fast(): void
    {
        // 測試快速載入（未超過閾值）
        Log::shouldReceive('channel')
            ->with('multilingual_performance')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('debug')
            ->with('Language file loaded', \Mockery::type('array'))
            ->once();

        $this->logger->logLanguageFilePerformance('admin', 'zh_TW', 50.0, true);
    }

    /**
     * 測試記錄翻譯參數錯誤
     */
    public function test_log_translation_parameter_error(): void
    {
        Log::shouldReceive('channel')
            ->with('multilingual_errors')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('warning')
            ->with('Translation parameter mismatch', \Mockery::type('array'))
            ->once();

        $this->logger->logTranslationParameterError(
            'test.key',
            ['name', 'email'],
            ['name' => 'John', 'age' => 25]
        );
    }

    /**
     * 測試記錄語言偏好設定更新
     */
    public function test_log_language_preference_update(): void
    {
        Log::shouldReceive('channel')
            ->with('multilingual')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('info')
            ->with('Language preference updated', \Mockery::type('array'))
            ->once();

        $this->logger->logLanguagePreferenceUpdate('en', 'user_action', [
            'previous_locale' => 'zh_TW'
        ]);
    }

    /**
     * 測試取得統計資訊
     */
    public function test_get_statistics(): void
    {
        // 設定一些測試統計資料
        $today = now()->format('Y-m-d');
        Cache::put("multilingual_stats:missing_keys_daily:{$today}", 5);
        Cache::put("multilingual_stats:fallback_daily:{$today}", 3);
        Cache::put("multilingual_stats:switch_daily:{$today}", 10);

        $stats = $this->logger->getStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('daily_stats', $stats);
        $this->assertArrayHasKey('current_locale', $stats);
        $this->assertArrayHasKey('supported_locales', $stats);
        $this->assertArrayHasKey('cache_stats', $stats);

        $this->assertEquals(5, $stats['daily_stats']['missing_keys']);
        $this->assertEquals(3, $stats['daily_stats']['fallback_usage']);
        $this->assertEquals(10, $stats['daily_stats']['language_switches']);
    }

    /**
     * 測試清除統計資料
     */
    public function test_clear_statistics(): void
    {
        // 設定一些測試統計資料
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');
        
        Cache::put("multilingual_stats:missing_keys_daily:{$today}", 5);
        Cache::put("multilingual_stats:missing_keys_daily:{$yesterday}", 3);
        Cache::put("multilingual_stats:fallback_daily:{$today}", 2);
        Cache::put("multilingual_stats:switch_daily:{$today}", 8);

        // 驗證資料存在
        $this->assertTrue(Cache::has("multilingual_stats:missing_keys_daily:{$today}"));
        $this->assertTrue(Cache::has("multilingual_stats:missing_keys_daily:{$yesterday}"));

        // 清除統計資料
        $this->logger->clearStatistics(1);

        // 驗證資料已清除
        $this->assertFalse(Cache::has("multilingual_stats:missing_keys_daily:{$today}"));
        $this->assertFalse(Cache::has("multilingual_stats:missing_keys_daily:{$yesterday}"));
        $this->assertFalse(Cache::has("multilingual_stats:fallback_daily:{$today}"));
        $this->assertFalse(Cache::has("multilingual_stats:switch_daily:{$today}"));
    }

    /**
     * 測試統計計數器增加
     */
    public function test_increment_counters(): void
    {
        // 記錄多個相同的事件
        $this->logger->logMissingTranslationKey('test.key', 'zh_TW');
        $this->logger->logMissingTranslationKey('test.key', 'zh_TW');
        $this->logger->logMissingTranslationKey('test.key', 'zh_TW');

        // 驗證計數器正確增加
        $keyHash = md5('test.key');
        $cacheKey = "multilingual_stats:missing_keys:zh_TW:{$keyHash}";
        $dailyKey = "multilingual_stats:missing_keys_daily:" . now()->format('Y-m-d');

        $this->assertEquals(3, Cache::get($cacheKey));
        $this->assertEquals(3, Cache::get($dailyKey));
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