<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\LanguageFallbackHandler;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * 語言回退處理器測試
 */
class LanguageFallbackHandlerTest extends TestCase
{
    use RefreshDatabase;
    
    private LanguageFallbackHandler $handler;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new LanguageFallbackHandler();
        
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
     * 測試基本翻譯功能
     */
    public function test_basic_translation(): void
    {
        // 設定當前語言為中文
        App::setLocale('zh_TW');
        
        // 測試存在的翻譯鍵
        $result = $this->handler->translate('auth.login.title');
        $this->assertNotEmpty($result);
        $this->assertNotEquals('auth.login.title', $result);
    }
    
    /**
     * 測試回退機制
     */
    public function test_fallback_mechanism(): void
    {
        // 設定當前語言為中文
        App::setLocale('zh_TW');
        
        // 測試不存在的翻譯鍵，應該回退到英文或返回鍵值本身
        $result = $this->handler->translate('nonexistent.key');
        
        // 結果應該是鍵值本身（因為所有語言都沒有這個鍵）
        $this->assertEquals('nonexistent.key', $result);
    }
    
    /**
     * 測試參數替換功能
     */
    public function test_parameter_replacement(): void
    {
        App::setLocale('zh_TW');
        
        // 測試 :key 格式的參數替換
        $result = $this->handler->translate('test.message', ['name' => '測試使用者']);
        
        // 如果翻譯鍵不存在，應該在原始鍵中替換參數
        $testKey = 'Hello :name, welcome!';
        $result = $this->handler->translate($testKey, ['name' => '測試使用者']);
        $this->assertEquals('Hello 測試使用者, welcome!', $result);
        
        // 測試 {key} 格式的參數替換
        $testKey = 'Hello {name}, welcome!';
        $result = $this->handler->translate($testKey, ['name' => '測試使用者']);
        $this->assertEquals('Hello 測試使用者, welcome!', $result);
    }
    
    /**
     * 測試翻譯鍵存在性檢查
     */
    public function test_has_translation(): void
    {
        App::setLocale('zh_TW');
        
        // 測試存在的翻譯鍵
        $this->assertTrue($this->handler->hasTranslation('auth.login.title'));
        
        // 測試不存在的翻譯鍵
        $this->assertFalse($this->handler->hasTranslation('nonexistent.key'));
    }
    
    /**
     * 測試翻譯狀態取得
     */
    public function test_get_translation_status(): void
    {
        App::setLocale('zh_TW');
        
        $status = $this->handler->getTranslationStatus('auth.login.title');
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('zh_TW', $status);
        $this->assertArrayHasKey('en', $status);
    }
    
    /**
     * 測試回退鏈設定
     */
    public function test_fallback_chain_configuration(): void
    {
        // 測試預設回退鏈
        $defaultChain = $this->handler->getFallbackChain();
        $this->assertIsArray($defaultChain);
        $this->assertContains('zh_TW', $defaultChain);
        $this->assertContains('en', $defaultChain);
        
        // 測試自定義回退鏈
        $customChain = ['en', 'zh_TW'];
        $this->handler->setFallbackChain($customChain);
        $this->assertEquals($customChain, $this->handler->getFallbackChain());
    }
    
    /**
     * 測試預設語言設定
     */
    public function test_default_locale_configuration(): void
    {
        // 測試預設語言
        $this->assertEquals('zh_TW', $this->handler->getDefaultLocale());
        
        // 測試設定新的預設語言
        $this->handler->setDefaultLocale('en');
        $this->assertEquals('en', $this->handler->getDefaultLocale());
    }
    
    /**
     * 測試日誌記錄功能
     */
    public function test_logging_functionality(): void
    {
        // 啟用日誌記錄
        $this->handler->setLogging(true);
        
        // 模擬日誌記錄
        Log::shouldReceive('channel')
            ->with('multilingual')
            ->andReturnSelf();
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Missing translation key', \Mockery::type('array'));
        
        // 測試不存在的翻譯鍵（應該觸發日誌記錄）
        $this->handler->translate('definitely.nonexistent.key');
    }
    
    /**
     * 測試快取清除功能
     */
    public function test_cache_clearing(): void
    {
        // 設定一些快取
        Cache::put('lang_fallback.zh_TW.test', ['key' => 'value'], 3600);
        Cache::put('lang_fallback.en.test', ['key' => 'value'], 3600);
        
        // 確認快取存在
        $this->assertTrue(Cache::has('lang_fallback.zh_TW.test'));
        $this->assertTrue(Cache::has('lang_fallback.en.test'));
        
        // 清除特定語言的快取
        $this->handler->clearCache('zh_TW', 'test');
        
        // 清除所有快取
        $this->handler->clearCache();
    }
    
    /**
     * 測試統計資訊取得
     */
    public function test_get_statistics(): void
    {
        $stats = $this->handler->getFallbackStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('fallback_chain', $stats);
        $this->assertArrayHasKey('default_locale', $stats);
        $this->assertArrayHasKey('cache_time', $stats);
        $this->assertArrayHasKey('logging_enabled', $stats);
    }
    
    /**
     * 測試指定語言翻譯
     */
    public function test_translate_with_specific_locale(): void
    {
        // 設定當前語言為中文
        App::setLocale('zh_TW');
        
        // 使用指定語言進行翻譯
        $result = $this->handler->translate('auth.login.title', [], 'en');
        
        // 結果應該不為空（如果翻譯存在）
        $this->assertNotEmpty($result);
    }
    
    /**
     * 測試錯誤處理
     */
    public function test_error_handling(): void
    {
        // 測試無效的翻譯鍵格式
        $result = $this->handler->translate('invalid_key_without_dot');
        $this->assertEquals('invalid_key_without_dot', $result);
        
        // 測試空翻譯鍵
        $result = $this->handler->translate('');
        $this->assertEquals('', $result);
    }
    
    /**
     * 測試複雜參數替換
     */
    public function test_complex_parameter_replacement(): void
    {
        $template = 'Hello :name, you have :count messages and :amount dollars.';
        $params = [
            'name' => 'John',
            'count' => 5,
            'amount' => 100.50
        ];
        
        $result = $this->handler->translate($template, $params);
        $expected = 'Hello John, you have 5 messages and 100.5 dollars.';
        
        $this->assertEquals($expected, $result);
    }
    
    /**
     * 測試大小寫參數替換
     */
    public function test_case_sensitive_parameter_replacement(): void
    {
        $template = 'Hello :NAME and :name';
        $params = [
            'name' => 'john',
            'NAME' => 'JOHN'
        ];
        
        $result = $this->handler->translate($template, $params);
        $expected = 'Hello JOHN and john';
        
        $this->assertEquals($expected, $result);
    }
    
    /**
     * 測試多語言環境下的回退
     */
    public function test_multilingual_fallback(): void
    {
        // 測試中文環境
        App::setLocale('zh_TW');
        $this->handler = new LanguageFallbackHandler(); // 重新初始化以更新回退鏈
        
        $chain = $this->handler->getFallbackChain();
        $this->assertEquals('zh_TW', $chain[0]);
        
        // 測試英文環境
        App::setLocale('en');
        $this->handler = new LanguageFallbackHandler(); // 重新初始化以更新回退鏈
        
        $chain = $this->handler->getFallbackChain();
        $this->assertEquals('en', $chain[0]);
    }
}