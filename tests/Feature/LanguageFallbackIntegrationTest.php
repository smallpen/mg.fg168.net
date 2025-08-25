<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\LanguageFallbackHandler;
use App\Facades\LanguageFallback;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * 語言回退機制整合測試
 */
class LanguageFallbackIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // 清除快取
        Cache::flush();
    }
    
    protected function tearDown(): void
    {
        // 清除快取
        Cache::flush();
        parent::tearDown();
    }
    
    /**
     * 測試服務容器註冊
     */
    public function test_service_container_registration(): void
    {
        $handler = app(LanguageFallbackHandler::class);
        $this->assertInstanceOf(LanguageFallbackHandler::class, $handler);
        
        // 測試別名註冊
        $handlerByAlias = app('language.fallback');
        $this->assertInstanceOf(LanguageFallbackHandler::class, $handlerByAlias);
        
        // 測試單例模式
        $this->assertSame($handler, $handlerByAlias);
    }
    
    /**
     * 測試 Facade 功能
     */
    public function test_facade_functionality(): void
    {
        App::setLocale('zh_TW');
        
        // 測試基本翻譯
        $result = LanguageFallback::translate('auth.login.title');
        $this->assertNotEmpty($result);
        
        // 測試翻譯存在性檢查
        $exists = LanguageFallback::hasTranslation('auth.login.title');
        $this->assertTrue($exists);
        
        // 測試不存在的翻譯
        $nonExistent = LanguageFallback::hasTranslation('nonexistent.key');
        $this->assertFalse($nonExistent);
    }
    
    /**
     * 測試全域輔助函數
     */
    public function test_global_helper_functions(): void
    {
        App::setLocale('zh_TW');
        
        // 測試 trans_fallback 函數
        $result = trans_fallback('auth.login.title');
        $this->assertNotEmpty($result);
        
        // 測試 __f 簡短別名
        $result2 = __f('auth.login.title');
        $this->assertEquals($result, $result2);
        
        // 測試 has_trans_fallback 函數
        $exists = has_trans_fallback('auth.login.title');
        $this->assertTrue($exists);
        
        // 測試 trans_status 函數
        $status = trans_status('auth.login.title');
        $this->assertIsArray($status);
        $this->assertArrayHasKey('zh_TW', $status);
        $this->assertArrayHasKey('en', $status);
    }
    
    /**
     * 測試參數替換功能
     */
    public function test_parameter_replacement_integration(): void
    {
        App::setLocale('zh_TW');
        
        // 測試使用 Facade
        $result = LanguageFallback::translate('Hello :name!', ['name' => '測試使用者']);
        $this->assertEquals('Hello 測試使用者!', $result);
        
        // 測試使用全域函數
        $result2 = trans_fallback('Hello :name!', ['name' => '測試使用者']);
        $this->assertEquals($result, $result2);
        
        // 測試使用簡短別名
        $result3 = __f('Hello :name!', ['name' => '測試使用者']);
        $this->assertEquals($result, $result3);
    }
    
    /**
     * 測試語言切換時的回退機制
     */
    public function test_language_switching_fallback(): void
    {
        // 測試中文環境
        App::setLocale('zh_TW');
        $handler = app(LanguageFallbackHandler::class);
        $chain = $handler->getFallbackChain();
        $this->assertEquals('zh_TW', $chain[0]);
        
        // 測試英文環境
        App::setLocale('en');
        $handler = new LanguageFallbackHandler(); // 重新建立以更新回退鏈
        $chain = $handler->getFallbackChain();
        $this->assertEquals('en', $chain[0]);
    }
    
    /**
     * 測試日誌記錄整合
     */
    public function test_logging_integration(): void
    {
        App::setLocale('zh_TW');
        
        // 模擬日誌記錄
        Log::shouldReceive('channel')
            ->with('multilingual')
            ->andReturnSelf();
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Missing translation key', \Mockery::type('array'));
        
        // 觸發缺少翻譯的情況
        $result = LanguageFallback::translate('definitely.nonexistent.key.for.testing');
        $this->assertEquals('definitely.nonexistent.key.for.testing', $result);
    }
    
    /**
     * 測試快取機制整合
     */
    public function test_cache_integration(): void
    {
        App::setLocale('zh_TW');
        
        // 第一次呼叫應該載入並快取
        $result1 = LanguageFallback::translate('auth.login.title');
        
        // 第二次呼叫應該使用快取
        $result2 = LanguageFallback::translate('auth.login.title');
        
        $this->assertEquals($result1, $result2);
        
        // 清除快取
        LanguageFallback::clearCache();
        
        // 清除後仍應該能正常工作
        $result3 = LanguageFallback::translate('auth.login.title');
        $this->assertEquals($result1, $result3);
    }
    
    /**
     * 測試回退鏈配置
     */
    public function test_fallback_chain_configuration(): void
    {
        $handler = app(LanguageFallbackHandler::class);
        
        // 測試預設回退鏈
        $defaultChain = $handler->getFallbackChain();
        $this->assertIsArray($defaultChain);
        $this->assertContains('zh_TW', $defaultChain);
        $this->assertContains('en', $defaultChain);
        
        // 測試自定義回退鏈
        $customChain = ['en', 'zh_TW'];
        $handler->setFallbackChain($customChain);
        $this->assertEquals($customChain, $handler->getFallbackChain());
        
        // 測試使用自定義回退鏈進行翻譯
        $result = $handler->translate('auth.login.title');
        $this->assertNotEmpty($result);
    }
    
    /**
     * 測試預設語言配置
     */
    public function test_default_locale_configuration(): void
    {
        $handler = app(LanguageFallbackHandler::class);
        
        // 測試預設語言
        $this->assertEquals('zh_TW', $handler->getDefaultLocale());
        
        // 測試設定新的預設語言
        $handler->setDefaultLocale('en');
        $this->assertEquals('en', $handler->getDefaultLocale());
        
        // 測試翻譯仍然正常工作
        $result = $handler->translate('auth.login.title');
        $this->assertNotEmpty($result);
    }
    
    /**
     * 測試統計資訊功能
     */
    public function test_statistics_functionality(): void
    {
        $handler = app(LanguageFallbackHandler::class);
        $stats = $handler->getFallbackStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('fallback_chain', $stats);
        $this->assertArrayHasKey('default_locale', $stats);
        $this->assertArrayHasKey('cache_time', $stats);
        $this->assertArrayHasKey('logging_enabled', $stats);
        
        // 驗證統計資訊的值
        $this->assertIsArray($stats['fallback_chain']);
        $this->assertIsString($stats['default_locale']);
        $this->assertIsInt($stats['cache_time']);
        $this->assertIsBool($stats['logging_enabled']);
    }
    
    /**
     * 測試錯誤處理
     */
    public function test_error_handling(): void
    {
        App::setLocale('zh_TW');
        
        // 測試空翻譯鍵
        $result = LanguageFallback::translate('');
        $this->assertEquals('', $result);
        
        // 測試無效格式的翻譯鍵
        $result = LanguageFallback::translate('invalid_key_without_dot');
        $this->assertEquals('invalid_key_without_dot', $result);
        
        // 測試 null 參數
        $result = LanguageFallback::translate('test.key', []);
        $this->assertIsString($result);
    }
    
    /**
     * 測試多語言環境下的完整流程
     */
    public function test_complete_multilingual_workflow(): void
    {
        // 測試中文環境
        App::setLocale('zh_TW');
        $zhResult = trans_fallback('auth.login.title');
        $this->assertNotEmpty($zhResult);
        
        // 測試英文環境
        App::setLocale('en');
        $enResult = trans_fallback('auth.login.title');
        $this->assertNotEmpty($enResult);
        
        // 在不同語言環境下結果可能不同
        // 但都應該是有效的翻譯結果
        $this->assertIsString($zhResult);
        $this->assertIsString($enResult);
        
        // 測試回退到預設語言
        $fallbackResult = trans_fallback('nonexistent.key');
        $this->assertEquals('nonexistent.key', $fallbackResult);
    }
}