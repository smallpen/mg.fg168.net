<?php

namespace Tests\Unit;

use Tests\MultilingualTestCase;
use Tests\Traits\MultilingualTestData;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\File;

/**
 * 語言檔案載入測試
 * 
 * 測試語言檔案的載入、翻譯功能和完整性檢查
 */
class LanguageFileLoadingTest extends MultilingualTestCase
{
    use MultilingualTestData;

    /**
     * 設定測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試用的語言檔案
        $this->createTestLanguageFiles();
    }

    /**
     * 清理測試環境
     */
    protected function tearDown(): void
    {
        // 清理測試語言檔案
        $this->cleanupTestLanguageFiles();
        
        parent::tearDown();
    }

    /**
     * 測試語言檔案是否正確載入
     */
    public function test_language_files_load_correctly(): void
    {
        foreach ($this->supportedLocales as $locale) {
            $this->setTestLocale($locale);
            
            // 測試基本翻譯載入
            $this->assertTrue(Lang::has('test_translations.common.save'));
            $this->assertTrue(Lang::has('test_translations.messages.save_success'));
            $this->assertTrue(Lang::has('test_translations.navigation.dashboard'));
        }
    }

    /**
     * 測試翻譯內容是否正確
     */
    public function test_translation_content_is_correct(): void
    {
        // 測試中文翻譯
        $this->setTestLocale('zh_TW');
        $this->assertEquals('儲存', __('test_translations.common.save'));
        $this->assertEquals('資料已成功儲存', __('test_translations.messages.save_success'));
        $this->assertEquals('儀表板', __('test_translations.navigation.dashboard'));
        
        // 測試英文翻譯
        $this->setTestLocale('en');
        $this->assertEquals('Save', __('test_translations.common.save'));
        $this->assertEquals('Data saved successfully', __('test_translations.messages.save_success'));
        $this->assertEquals('Dashboard', __('test_translations.navigation.dashboard'));
    }

    /**
     * 測試翻譯參數替換功能
     */
    public function test_translation_parameter_replacement(): void
    {
        foreach ($this->supportedLocales as $locale) {
            $this->setTestLocale($locale);
            
            $translation = __('test_translations.validation.min', ['attribute' => 'password', 'min' => 8]);
            $this->assertStringContainsString('password', $translation);
            $this->assertStringContainsString('8', $translation);
        }
    }

    /**
     * 測試語言切換功能
     */
    public function test_language_switching(): void
    {
        // 初始設定為中文
        $this->setTestLocale('zh_TW');
        $this->assertEquals('儲存', __('test_translations.common.save'));
        
        // 切換到英文
        $this->setTestLocale('en');
        $this->assertEquals('Save', __('test_translations.common.save'));
        
        // 切換回中文
        $this->setTestLocale('zh_TW');
        $this->assertEquals('儲存', __('test_translations.common.save'));
    }

    /**
     * 測試不存在的翻譯鍵
     */
    public function test_missing_translation_keys(): void
    {
        foreach ($this->supportedLocales as $locale) {
            $this->setTestLocale($locale);
            
            // 測試不存在的鍵應該返回鍵本身
            $this->assertEquals('test_translations.nonexistent.key', __('test_translations.nonexistent.key'));
        }
    }

    /**
     * 測試語言回退機制
     */
    public function test_language_fallback(): void
    {
        // 建立只有中文版本的翻譯
        $this->createLanguageFile('fallback_test', 'zh_TW', [
            'message' => '只有中文版本'
        ]);
        
        // 重新載入語言檔案
        $this->reloadLanguageFiles();
        
        // 在英文環境下應該回退到中文
        $this->setTestLocale('en');
        
        // 由於 Laravel 的回退機制，應該能取得中文版本
        // 注意：這個測試可能需要根據實際的回退機制調整
        $translation = __('fallback_test.message');
        $this->assertNotEquals('fallback_test.message', $translation);
        
        // 清理
        File::delete(lang_path('zh_TW/fallback_test.php'));
    }

    /**
     * 測試語言檔案完整性檢查
     */
    public function test_language_file_completeness(): void
    {
        // 建立不完整的語言檔案
        $this->createIncompleteLanguageFiles();
        
        // 重新載入語言檔案
        $this->reloadLanguageFiles();
        
        // 檢查語言檔案完整性
        $report = $this->generateLanguageCompletenessReport();
        
        // 驗證報告包含缺少的鍵
        $this->assertArrayHasKey('missing_keys', $report);
        $this->assertArrayHasKey('en', $report['missing_keys']);
        
        // 清理
        $this->cleanupTestLanguageFiles('incomplete_test');
    }

    /**
     * 測試多餘鍵的檢測
     */
    public function test_extra_keys_detection(): void
    {
        // 建立有多餘鍵的語言檔案
        $this->createExtraKeysLanguageFiles();
        
        // 重新載入語言檔案
        $this->reloadLanguageFiles();
        
        // 檢查語言檔案完整性
        $report = $this->generateLanguageCompletenessReport();
        
        // 驗證報告包含多餘的鍵
        $this->assertArrayHasKey('extra_keys', $report);
        $this->assertArrayHasKey('en', $report['extra_keys']);
        
        // 清理
        $this->cleanupTestLanguageFiles('extra_keys_test');
    }

    /**
     * 測試巢狀翻譯鍵
     */
    public function test_nested_translation_keys(): void
    {
        foreach ($this->supportedLocales as $locale) {
            $this->setTestLocale($locale);
            
            // 測試多層巢狀鍵
            $this->assertTrue(Lang::has('test_translations.common.save'));
            $this->assertTrue(Lang::has('test_translations.validation.required'));
            $this->assertTrue(Lang::has('test_translations.navigation.dashboard'));
        }
    }

    /**
     * 測試語言檔案統計資訊
     */
    public function test_language_file_statistics(): void
    {
        $stats = $this->getLanguageFileStats();
        
        // 驗證統計資訊結構
        foreach ($this->supportedLocales as $locale) {
            $this->assertArrayHasKey($locale, $stats);
            $this->assertArrayHasKey('files', $stats[$locale]);
            $this->assertArrayHasKey('keys', $stats[$locale]);
            $this->assertArrayHasKey('files_detail', $stats[$locale]);
            
            // 驗證至少有測試檔案
            $this->assertGreaterThan(0, $stats[$locale]['files']);
            $this->assertGreaterThan(0, $stats[$locale]['keys']);
        }
    }

    /**
     * 測試大量語言資料載入效能
     */
    public function test_large_language_data_performance(): void
    {
        // 建立大量測試資料
        $this->createLargeTestLanguageData(100); // 減少數量以避免測試過慢
        
        // 重新載入語言檔案
        $this->reloadLanguageFiles();
        
        $startTime = microtime(true);
        
        // 測試載入效能
        foreach ($this->supportedLocales as $locale) {
            $this->setTestLocale($locale);
            
            // 載入一些翻譯
            for ($i = 1; $i <= 10; $i++) {
                __("large_test.key_{$i}");
            }
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // 驗證執行時間在合理範圍內（1秒內）
        $this->assertLessThan(1.0, $executionTime, '大量語言資料載入時間過長');
        
        // 清理
        $this->cleanupTestLanguageFiles('large_test');
    }

    /**
     * 測試巢狀結構語言資料
     */
    public function test_nested_structure_language_data(): void
    {
        // 建立巢狀結構測試資料
        $this->createNestedTestLanguageData(3, 3);
        
        // 重新載入語言檔案
        $this->reloadLanguageFiles();
        
        foreach ($this->supportedLocales as $locale) {
            $this->setTestLocale($locale);
            
            // 測試巢狀鍵存在
            $this->assertTrue(Lang::has('nested_test.level_0_key_1.level_1_key_1.level_2_key_1'));
            
            // 測試巢狀翻譯內容
            $translation = __('nested_test.level_0_key_1.level_1_key_1.level_2_key_1');
            $this->assertNotEquals('nested_test.level_0_key_1.level_1_key_1.level_2_key_1', $translation);
        }
        
        // 清理
        $this->cleanupTestLanguageFiles('nested_test');
    }

    /**
     * 測試語言檔案快取機制
     */
    public function test_language_file_caching(): void
    {
        // 第一次載入
        $this->setTestLocale('zh_TW');
        $firstLoad = __('test_translations.common.save');
        
        // 第二次載入（應該使用快取）
        $secondLoad = __('test_translations.common.save');
        
        $this->assertEquals($firstLoad, $secondLoad);
        $this->assertEquals('儲存', $firstLoad);
    }

    /**
     * 測試語言檔案重新載入
     */
    public function test_language_file_reloading(): void
    {
        // 建立初始語言檔案
        $this->createLanguageFile('reload_test', 'zh_TW', [
            'message' => '原始訊息'
        ]);
        
        // 重新載入語言檔案
        $this->reloadLanguageFiles();
        
        $this->setTestLocale('zh_TW');
        $this->assertEquals('原始訊息', __('reload_test.message'));
        
        // 修改語言檔案
        $this->createLanguageFile('reload_test', 'zh_TW', [
            'message' => '修改後的訊息'
        ]);
        
        // 重新載入語言檔案
        $this->reloadLanguageFiles();
        
        $this->assertEquals('修改後的訊息', __('reload_test.message'));
        
        // 清理
        $this->cleanupTestLanguageFiles('reload_test');
    }

    /**
     * 測試語言檔案錯誤處理
     */
    public function test_language_file_error_handling(): void
    {
        // 建立語法錯誤的語言檔案
        $invalidFilePath = lang_path('zh_TW/invalid_syntax.php');
        File::put($invalidFilePath, "<?php\n\nreturn [\n    'key' => 'value'\n    // 缺少結束括號");
        
        // 嘗試載入應該不會導致致命錯誤
        $this->setTestLocale('zh_TW');
        
        // 不存在的翻譯應該返回鍵本身
        $this->assertEquals('invalid_syntax.key', __('invalid_syntax.key'));
        
        // 清理
        if (File::exists($invalidFilePath)) {
            File::delete($invalidFilePath);
        }
    }

    /**
     * 測試多語系翻譯一致性
     */
    public function test_multilingual_translation_consistency(): void
    {
        $testKeys = [
            'test_translations.common.save',
            'test_translations.common.cancel',
            'test_translations.messages.save_success',
            'test_translations.navigation.dashboard'
        ];
        
        foreach ($testKeys as $key) {
            // 驗證翻譯在所有語言中都存在
            $this->assertTranslationExistsInAllLocales($key);
            
            // 驗證翻譯內容在不同語言中是不同的
            $this->assertTranslationDiffersAcrossLocales($key);
        }
    }

    /**
     * 測試語言檔案完整性報告生成
     */
    public function test_language_completeness_report_generation(): void
    {
        $report = $this->generateLanguageCompletenessReport();
        
        // 驗證報告結構
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('missing_keys', $report);
        $this->assertArrayHasKey('extra_keys', $report);
        $this->assertArrayHasKey('files', $report);
        
        // 驗證摘要資訊
        foreach ($this->supportedLocales as $locale) {
            if ($locale !== $this->defaultTestLocale) {
                $this->assertArrayHasKey($locale, $report['summary']);
                $this->assertArrayHasKey('total_files', $report['summary'][$locale]);
                $this->assertArrayHasKey('total_missing_keys', $report['summary'][$locale]);
                $this->assertArrayHasKey('total_extra_keys', $report['summary'][$locale]);
            }
        }
    }
}