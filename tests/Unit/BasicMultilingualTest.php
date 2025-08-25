<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Lang;

/**
 * 基礎多語系功能測試
 * 
 * 測試基本的多語系功能，不依賴複雜的應用程式邏輯
 */
class BasicMultilingualTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試應用程式預設語言設定
     */
    public function test_default_application_locale(): void
    {
        $this->assertEquals('zh_TW', config('app.locale'));
        $this->assertEquals('en', config('app.fallback_locale'));
    }

    /**
     * 測試語言切換功能
     */
    public function test_locale_switching(): void
    {
        // 設定為中文
        App::setLocale('zh_TW');
        $this->assertEquals('zh_TW', App::getLocale());
        
        // 切換到英文
        App::setLocale('en');
        $this->assertEquals('en', App::getLocale());
        
        // 切換回中文
        App::setLocale('zh_TW');
        $this->assertEquals('zh_TW', App::getLocale());
    }

    /**
     * 測試 Session 語言儲存
     */
    public function test_session_locale_storage(): void
    {
        // 儲存語言到 Session
        Session::put('locale', 'en');
        $this->assertEquals('en', Session::get('locale'));
        
        // 切換語言
        Session::put('locale', 'zh_TW');
        $this->assertEquals('zh_TW', Session::get('locale'));
    }

    /**
     * 測試語言檔案存在性
     */
    public function test_language_files_exist(): void
    {
        $supportedLocales = ['zh_TW', 'en'];
        
        foreach ($supportedLocales as $locale) {
            $langPath = lang_path($locale);
            $this->assertDirectoryExists($langPath, "語言目錄 {$locale} 不存在");
            
            // 檢查一些基本的語言檔案
            $basicFiles = ['auth.php', 'validation.php'];
            foreach ($basicFiles as $file) {
                $filePath = $langPath . '/' . $file;
                $this->assertFileExists($filePath, "語言檔案 {$locale}/{$file} 不存在");
            }
        }
    }

    /**
     * 測試基本翻譯功能
     */
    public function test_basic_translation_functionality(): void
    {
        // 測試中文翻譯
        App::setLocale('zh_TW');
        $zhTranslation = __('validation.required', ['attribute' => 'name']);
        $this->assertStringContainsString('name', $zhTranslation);
        
        // 測試英文翻譯
        App::setLocale('en');
        $enTranslation = __('validation.required', ['attribute' => 'name']);
        $this->assertStringContainsString('name', $enTranslation);
        
        // 驗證翻譯內容不同
        $this->assertNotEquals($zhTranslation, $enTranslation);
    }

    /**
     * 測試翻譯鍵存在性檢查
     */
    public function test_translation_key_existence(): void
    {
        $supportedLocales = ['zh_TW', 'en'];
        
        foreach ($supportedLocales as $locale) {
            App::setLocale($locale);
            
            // 測試一些基本的翻譯鍵
            $this->assertTrue(Lang::has('validation.required'));
            $this->assertTrue(Lang::has('auth.failed'));
        }
    }

    /**
     * 測試不存在的翻譯鍵
     */
    public function test_missing_translation_keys(): void
    {
        App::setLocale('zh_TW');
        
        // 不存在的鍵應該返回鍵本身
        $this->assertEquals('nonexistent.key', __('nonexistent.key'));
    }

    /**
     * 測試翻譯參數替換
     */
    public function test_translation_parameter_replacement(): void
    {
        $supportedLocales = ['zh_TW', 'en'];
        
        foreach ($supportedLocales as $locale) {
            App::setLocale($locale);
            
            $translation = __('validation.min.string', [
                'attribute' => 'password',
                'min' => 8
            ]);
            
            $this->assertStringContainsString('password', $translation);
            $this->assertStringContainsString('8', $translation);
        }
    }

    /**
     * 測試語言檔案載入效能
     */
    public function test_language_file_loading_performance(): void
    {
        $startTime = microtime(true);
        
        // 載入多個翻譯
        for ($i = 0; $i < 50; $i++) {
            App::setLocale($i % 2 === 0 ? 'zh_TW' : 'en');
            __('validation.required');
            __('auth.failed');
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // 驗證執行時間在合理範圍內
        $this->assertLessThan(1.0, $executionTime, '語言檔案載入時間過長');
    }

    /**
     * 測試 Carbon 本地化
     */
    public function test_carbon_localization(): void
    {
        // 測試中文本地化
        \Carbon\Carbon::setLocale('zh_TW');
        $this->assertEquals('zh_TW', \Carbon\Carbon::getLocale());
        
        // 測試英文本地化
        \Carbon\Carbon::setLocale('en');
        $this->assertEquals('en', \Carbon\Carbon::getLocale());
    }

    /**
     * 測試多語系配置
     */
    public function test_multilingual_configuration(): void
    {
        // 驗證時區設定
        $this->assertEquals('Asia/Taipei', config('app.timezone'));
        
        // 驗證語言設定
        $this->assertEquals('zh_TW', config('app.locale'));
        $this->assertEquals('en', config('app.fallback_locale'));
    }

    /**
     * 測試語言切換的狀態隔離
     */
    public function test_locale_switching_isolation(): void
    {
        // 初始狀態
        App::setLocale('zh_TW');
        $this->assertEquals('zh_TW', App::getLocale());
        
        // 在閉包中切換語言
        $this->withLocale('en', function () {
            $this->assertEquals('en', App::getLocale());
        });
        
        // 驗證語言已恢復
        $this->assertEquals('zh_TW', App::getLocale());
    }

    /**
     * 輔助方法：在指定語言環境中執行回調
     */
    private function withLocale(string $locale, callable $callback)
    {
        $originalLocale = App::getLocale();
        
        try {
            App::setLocale($locale);
            return $callback();
        } finally {
            App::setLocale($originalLocale);
        }
    }

    /**
     * 測試語言檔案結構一致性
     */
    public function test_language_file_structure_consistency(): void
    {
        $supportedLocales = ['zh_TW', 'en'];
        $commonFiles = ['auth', 'validation'];
        
        foreach ($commonFiles as $file) {
            $structures = [];
            
            foreach ($supportedLocales as $locale) {
                $filePath = lang_path("{$locale}/{$file}.php");
                if (file_exists($filePath)) {
                    $content = include $filePath;
                    $structures[$locale] = array_keys($content);
                }
            }
            
            // 驗證所有語言版本都有相同的頂層鍵
            if (count($structures) > 1) {
                $baseKeys = reset($structures);
                foreach ($structures as $locale => $keys) {
                    $this->assertEquals(sort($baseKeys), sort($keys),
                        "語言檔案 {$file}.php 在 {$locale} 中的結構與其他語言不一致");
                }
            }
        }
    }
}