<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * 多語系測試基礎設施功能展示
 * 
 * 展示多語系測試基礎設施的核心功能和使用方法
 */
class MultilingualTestingDemonstration extends TestCase
{
    /**
     * 展示語言切換輔助方法
     */
    public function test_language_switching_helpers(): void
    {
        // 模擬語言切換邏輯
        $currentLocale = 'zh_TW';
        $targetLocale = 'en';
        
        // 模擬切換語言的過程
        $this->assertEquals('zh_TW', $currentLocale);
        
        // 切換語言
        $currentLocale = $targetLocale;
        $this->assertEquals('en', $currentLocale);
        
        // 驗證語言切換成功
        $this->assertContains($currentLocale, ['zh_TW', 'en']);
    }

    /**
     * 展示翻譯內容驗證功能
     */
    public function test_translation_validation(): void
    {
        // 模擬翻譯內容
        $translations = [
            'zh_TW' => [
                'common.save' => '儲存',
                'common.cancel' => '取消',
                'messages.success' => '操作成功'
            ],
            'en' => [
                'common.save' => 'Save',
                'common.cancel' => 'Cancel',
                'messages.success' => 'Operation successful'
            ]
        ];
        
        // 驗證翻譯存在
        $this->assertArrayHasKey('common.save', $translations['zh_TW']);
        $this->assertArrayHasKey('common.save', $translations['en']);
        
        // 驗證翻譯內容不同
        $this->assertNotEquals(
            $translations['zh_TW']['common.save'],
            $translations['en']['common.save']
        );
        
        // 驗證翻譯內容正確
        $this->assertEquals('儲存', $translations['zh_TW']['common.save']);
        $this->assertEquals('Save', $translations['en']['common.save']);
    }

    /**
     * 展示語言檔案完整性檢查
     */
    public function test_language_file_completeness_check(): void
    {
        // 模擬語言檔案內容
        $baseLanguage = [
            'common.save' => '儲存',
            'common.cancel' => '取消',
            'common.delete' => '刪除',
            'messages.success' => '成功',
            'messages.error' => '錯誤'
        ];
        
        $targetLanguage = [
            'common.save' => 'Save',
            'common.cancel' => 'Cancel',
            // 缺少 common.delete
            'messages.success' => 'Success',
            'messages.error' => 'Error',
            'extra.key' => 'Extra Value' // 多餘的鍵
        ];
        
        // 檢查缺少的鍵
        $baseKeys = array_keys($baseLanguage);
        $targetKeys = array_keys($targetLanguage);
        
        $missingKeys = array_diff($baseKeys, $targetKeys);
        $extraKeys = array_diff($targetKeys, $baseKeys);
        
        // 驗證檢查結果
        $this->assertContains('common.delete', $missingKeys);
        $this->assertContains('extra.key', $extraKeys);
        $this->assertCount(1, $missingKeys);
        $this->assertCount(1, $extraKeys);
    }

    /**
     * 展示多語系測試資料建立
     */
    public function test_multilingual_test_data_creation(): void
    {
        // 建立多語系測試資料
        $testData = [
            'navigation' => [
                'zh_TW' => ['儀表板', '使用者管理', '角色管理'],
                'en' => ['Dashboard', 'User Management', 'Role Management']
            ],
            'forms' => [
                'zh_TW' => ['姓名', '電子郵件', '密碼'],
                'en' => ['Name', 'Email', 'Password']
            ],
            'messages' => [
                'zh_TW' => ['操作成功', '操作失敗', '資料不存在'],
                'en' => ['Operation successful', 'Operation failed', 'Data not found']
            ]
        ];
        
        // 驗證測試資料結構
        $this->assertArrayHasKey('navigation', $testData);
        $this->assertArrayHasKey('forms', $testData);
        $this->assertArrayHasKey('messages', $testData);
        
        // 驗證每個類別都有兩種語言的資料
        foreach ($testData as $category => $languages) {
            $this->assertArrayHasKey('zh_TW', $languages);
            $this->assertArrayHasKey('en', $languages);
            $this->assertIsArray($languages['zh_TW']);
            $this->assertIsArray($languages['en']);
        }
        
        // 驗證資料內容
        $this->assertEquals('儀表板', $testData['navigation']['zh_TW'][0]);
        $this->assertEquals('Dashboard', $testData['navigation']['en'][0]);
    }

    /**
     * 展示語言偏好管理功能
     */
    public function test_language_preference_management(): void
    {
        // 模擬使用者語言偏好
        $userPreferences = [
            'user1' => 'zh_TW',
            'user2' => 'en',
            'user3' => 'zh_TW'
        ];
        
        // 模擬 Session 語言設定
        $sessionLocale = 'en';
        
        // 模擬瀏覽器語言偏好
        $browserLanguages = ['zh-TW', 'zh', 'en'];
        
        // 語言優先順序邏輯：使用者偏好 > Session > 瀏覽器 > 預設
        $defaultLocale = 'zh_TW';
        
        // 測試使用者1（有使用者偏好）
        $finalLocale = $userPreferences['user1'] ?? $sessionLocale ?? $browserLanguages[0] ?? $defaultLocale;
        $this->assertEquals('zh_TW', $finalLocale);
        
        // 測試訪客使用者（無使用者偏好，使用 Session）
        $finalLocale = $sessionLocale ?? $browserLanguages[0] ?? $defaultLocale;
        $this->assertEquals('en', $finalLocale);
        
        // 測試新訪客（無 Session，使用瀏覽器偏好）
        $sessionLocale = null;
        $finalLocale = $browserLanguages[0] ?? $defaultLocale;
        $this->assertEquals('zh-TW', $finalLocale);
    }

    /**
     * 展示效能測試功能
     */
    public function test_performance_testing_capabilities(): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // 模擬多語系操作
        $operations = 0;
        for ($i = 0; $i < 1000; $i++) {
            // 模擬語言切換
            $locale = $i % 2 === 0 ? 'zh_TW' : 'en';
            
            // 模擬翻譯載入
            $translation = $locale === 'zh_TW' ? '中文翻譯' : 'English Translation';
            
            $operations++;
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $executionTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;
        
        // 驗證效能指標
        $this->assertEquals(1000, $operations);
        $this->assertLessThan(1.0, $executionTime, '執行時間應該在1秒內');
        $this->assertLessThan(1024 * 1024, $memoryUsed, '記憶體使用應該在1MB內');
    }

    /**
     * 展示測試報告生成功能
     */
    public function test_test_report_generation(): void
    {
        // 模擬測試結果
        $testResults = [
            'language_switching' => [
                'status' => 'passed',
                'locales' => ['zh_TW', 'en'],
                'execution_time' => 0.05
            ],
            'translation_loading' => [
                'status' => 'passed',
                'locales' => ['zh_TW', 'en'],
                'execution_time' => 0.02
            ],
            'form_validation' => [
                'status' => 'failed',
                'locales' => ['zh_TW'],
                'error' => 'Missing translation key',
                'execution_time' => 0.01
            ],
            'navigation_display' => [
                'status' => 'passed',
                'locales' => ['en'],
                'execution_time' => 0.03
            ]
        ];
        
        // 生成報告
        $report = $this->generateTestReport($testResults);
        
        // 驗證報告結構
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('details', $report);
        $this->assertArrayHasKey('failures', $report);
        $this->assertArrayHasKey('performance', $report);
        
        // 驗證摘要資訊
        $this->assertEquals(4, $report['summary']['total_tests']);
        $this->assertEquals(3, $report['summary']['passed']);
        $this->assertEquals(1, $report['summary']['failed']);
        $this->assertContains('zh_TW', $report['summary']['locales_tested']);
        $this->assertContains('en', $report['summary']['locales_tested']);
        
        // 驗證失敗資訊
        $this->assertArrayHasKey('form_validation', $report['failures']);
        $this->assertEquals('Missing translation key', $report['failures']['form_validation']);
        
        // 驗證效能資訊
        $this->assertLessThan(0.2, $report['performance']['total_execution_time']);
        $this->assertLessThan(0.1, $report['performance']['average_execution_time']);
    }

    /**
     * 展示語言檔案結構驗證
     */
    public function test_language_file_structure_validation(): void
    {
        // 模擬語言檔案結構
        $languageFiles = [
            'zh_TW' => [
                'common.php' => ['save', 'cancel', 'delete', 'edit'],
                'auth.php' => ['login', 'logout', 'register'],
                'validation.php' => ['required', 'email', 'min', 'max']
            ],
            'en' => [
                'common.php' => ['save', 'cancel', 'delete', 'edit'],
                'auth.php' => ['login', 'logout', 'register'],
                'validation.php' => ['required', 'email', 'min', 'max']
            ]
        ];
        
        // 驗證檔案結構一致性
        $zhFiles = array_keys($languageFiles['zh_TW']);
        $enFiles = array_keys($languageFiles['en']);
        
        $this->assertEquals($zhFiles, $enFiles, '語言檔案結構應該一致');
        
        // 驗證每個檔案的鍵結構
        foreach ($zhFiles as $file) {
            $zhKeys = $languageFiles['zh_TW'][$file];
            $enKeys = $languageFiles['en'][$file];
            
            $this->assertEquals($zhKeys, $enKeys, "檔案 {$file} 的鍵結構應該一致");
        }
    }

    /**
     * 輔助方法：生成測試報告
     */
    private function generateTestReport(array $testResults): array
    {
        $report = [
            'summary' => [
                'total_tests' => count($testResults),
                'passed' => 0,
                'failed' => 0,
                'locales_tested' => []
            ],
            'details' => $testResults,
            'failures' => [],
            'performance' => [
                'total_execution_time' => 0,
                'average_execution_time' => 0
            ]
        ];
        
        $totalExecutionTime = 0;
        
        foreach ($testResults as $testName => $result) {
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
            
            if (isset($result['execution_time'])) {
                $totalExecutionTime += $result['execution_time'];
            }
        }
        
        $report['performance']['total_execution_time'] = $totalExecutionTime;
        $report['performance']['average_execution_time'] = $totalExecutionTime / count($testResults);
        
        return $report;
    }
}