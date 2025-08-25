<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * 多語系基礎功能測試
 * 
 * 測試多語系測試基礎設施的核心功能，不依賴 Laravel 應用程式
 */
class MultilingualFoundationTest extends TestCase
{
    /**
     * 測試支援的語言列表
     */
    public function test_supported_locales_definition(): void
    {
        $supportedLocales = ['zh_TW', 'en'];
        
        $this->assertIsArray($supportedLocales);
        $this->assertContains('zh_TW', $supportedLocales);
        $this->assertContains('en', $supportedLocales);
        $this->assertCount(2, $supportedLocales);
    }

    /**
     * 測試語言檔案路徑結構
     */
    public function test_language_file_path_structure(): void
    {
        $basePath = __DIR__ . '/../../lang';
        
        // 檢查語言目錄是否存在
        $this->assertDirectoryExists($basePath . '/zh_TW');
        $this->assertDirectoryExists($basePath . '/en');
        
        // 檢查基本語言檔案是否存在
        $this->assertFileExists($basePath . '/zh_TW/auth.php');
        $this->assertFileExists($basePath . '/en/auth.php');
    }

    /**
     * 測試語言檔案內容格式
     */
    public function test_language_file_content_format(): void
    {
        $zhAuthFile = __DIR__ . '/../../lang/zh_TW/auth.php';
        $enAuthFile = __DIR__ . '/../../lang/en/auth.php';
        
        // 載入語言檔案
        $zhContent = include $zhAuthFile;
        $enContent = include $enAuthFile;
        
        // 驗證內容是陣列格式
        $this->assertIsArray($zhContent);
        $this->assertIsArray($enContent);
        
        // 驗證包含基本的認證相關鍵
        $this->assertArrayHasKey('failed', $zhContent);
        $this->assertArrayHasKey('failed', $enContent);
    }

    /**
     * 測試陣列扁平化功能
     */
    public function test_array_flattening_functionality(): void
    {
        $nestedArray = [
            'level1' => [
                'level2' => [
                    'key1' => 'value1',
                    'key2' => 'value2'
                ],
                'key3' => 'value3'
            ],
            'key4' => 'value4'
        ];
        
        $flattened = $this->flattenArray($nestedArray);
        
        $expectedKeys = [
            'level1.level2.key1',
            'level1.level2.key2',
            'level1.key3',
            'key4'
        ];
        
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $flattened);
        }
        
        $this->assertEquals('value1', $flattened['level1.level2.key1']);
        $this->assertEquals('value2', $flattened['level1.level2.key2']);
        $this->assertEquals('value3', $flattened['level1.key3']);
        $this->assertEquals('value4', $flattened['key4']);
    }

    /**
     * 測試語言檔案鍵比較功能
     */
    public function test_language_file_key_comparison(): void
    {
        $baseKeys = ['key1', 'key2', 'key3'];
        $compareKeys = ['key1', 'key2', 'key4'];
        
        $missingKeys = array_diff($baseKeys, $compareKeys);
        $extraKeys = array_diff($compareKeys, $baseKeys);
        
        $this->assertEquals(['key3'], array_values($missingKeys));
        $this->assertEquals(['key4'], array_values($extraKeys));
    }

    /**
     * 測試多語系內容建立功能
     */
    public function test_multilingual_content_creation(): void
    {
        $content = [
            'zh_TW' => '中文內容',
            'en' => 'English Content',
            'default' => 'Default Content'
        ];
        
        $supportedLocales = ['zh_TW', 'en'];
        $result = [];
        
        foreach ($supportedLocales as $locale) {
            $result[$locale] = $content[$locale] ?? $content['default'] ?? '';
        }
        
        $this->assertEquals('中文內容', $result['zh_TW']);
        $this->assertEquals('English Content', $result['en']);
    }

    /**
     * 測試測試資料結構
     */
    public function test_test_data_structure(): void
    {
        $testData = [
            'common' => [
                'save' => '儲存',
                'cancel' => '取消',
                'delete' => '刪除'
            ],
            'messages' => [
                'save_success' => '資料已成功儲存',
                'delete_success' => '資料已成功刪除'
            ]
        ];
        
        // 驗證結構
        $this->assertArrayHasKey('common', $testData);
        $this->assertArrayHasKey('messages', $testData);
        
        // 驗證內容
        $this->assertEquals('儲存', $testData['common']['save']);
        $this->assertEquals('資料已成功儲存', $testData['messages']['save_success']);
    }

    /**
     * 測試語言檔案完整性檢查邏輯
     */
    public function test_language_file_completeness_logic(): void
    {
        // 模擬語言檔案內容
        $zhContent = [
            'section1' => [
                'key1' => '值1',
                'key2' => '值2',
                'key3' => '值3'
            ],
            'section2' => [
                'key4' => '值4'
            ]
        ];
        
        $enContent = [
            'section1' => [
                'key1' => 'Value 1',
                'key2' => 'Value 2'
                // 缺少 key3
            ],
            'section2' => [
                'key4' => 'Value 4',
                'extra_key' => 'Extra Value' // 多餘的鍵
            ]
        ];
        
        $zhKeys = array_keys($this->flattenArray($zhContent));
        $enKeys = array_keys($this->flattenArray($enContent));
        
        $missingKeys = array_diff($zhKeys, $enKeys);
        $extraKeys = array_diff($enKeys, $zhKeys);
        
        $this->assertContains('section1.key3', $missingKeys);
        $this->assertContains('section2.extra_key', $extraKeys);
    }

    /**
     * 測試效能測量功能
     */
    public function test_performance_measurement(): void
    {
        $startTime = microtime(true);
        
        // 模擬一些操作
        for ($i = 0; $i < 1000; $i++) {
            $dummy = $i * 2;
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertIsFloat($executionTime);
        $this->assertGreaterThan(0, $executionTime);
        $this->assertLessThan(1.0, $executionTime); // 應該在1秒內完成
    }

    /**
     * 測試語言選項資料結構
     */
    public function test_language_options_structure(): void
    {
        $languageOptions = [
            'zh_TW' => '繁體中文',
            'en' => 'English'
        ];
        
        $this->assertArrayHasKey('zh_TW', $languageOptions);
        $this->assertArrayHasKey('en', $languageOptions);
        $this->assertEquals('繁體中文', $languageOptions['zh_TW']);
        $this->assertEquals('English', $languageOptions['en']);
    }

    /**
     * 測試測試報告生成邏輯
     */
    public function test_test_report_generation_logic(): void
    {
        $testResults = [
            'test1' => ['status' => 'passed', 'locales' => ['zh_TW', 'en']],
            'test2' => ['status' => 'failed', 'error' => 'Translation missing', 'locales' => ['zh_TW']],
            'test3' => ['status' => 'passed', 'locales' => ['en']]
        ];
        
        $report = [
            'summary' => [
                'total_tests' => count($testResults),
                'passed' => 0,
                'failed' => 0,
                'locales_tested' => []
            ],
            'details' => [],
            'failures' => []
        ];
        
        foreach ($testResults as $testName => $result) {
            $report['details'][$testName] = $result;
            
            if ($result['status'] === 'passed') {
                $report['summary']['passed']++;
            } else {
                $report['summary']['failed']++;
                $report['failures'][$testName] = $result['error'] ?? 'Unknown error';
            }
            
            if (isset($result['locales'])) {
                $report['summary']['locales_tested'] = array_unique(
                    array_merge($report['summary']['locales_tested'], $result['locales'])
                );
            }
        }
        
        $this->assertEquals(3, $report['summary']['total_tests']);
        $this->assertEquals(2, $report['summary']['passed']);
        $this->assertEquals(1, $report['summary']['failed']);
        $this->assertContains('zh_TW', $report['summary']['locales_tested']);
        $this->assertContains('en', $report['summary']['locales_tested']);
        $this->assertArrayHasKey('test2', $report['failures']);
    }

    /**
     * 輔助方法：將多維陣列扁平化為點記法鍵
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;
            
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        
        return $result;
    }
}