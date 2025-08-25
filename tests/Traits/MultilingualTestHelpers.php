<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use App\Models\User;

/**
 * 多語系測試輔助方法 Trait
 * 
 * 提供語言切換和多語系功能測試的輔助方法
 */
trait MultilingualTestHelpers
{
    /**
     * 建立具有指定語言偏好的測試使用者
     *
     * @param string $locale
     * @param array $attributes
     * @return User
     */
    protected function createUserWithLocale(string $locale = 'zh_TW', array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'locale' => $locale,
            'name' => $locale === 'zh_TW' ? '測試使用者' : 'Test User',
            'email' => 'test@example.com'
        ], $attributes));
    }

    /**
     * 建立多個不同語言偏好的測試使用者
     *
     * @param array $locales
     * @return \Illuminate\Support\Collection
     */
    protected function createUsersWithDifferentLocales(array $locales = ['zh_TW', 'en']): \Illuminate\Support\Collection
    {
        $users = collect();
        
        foreach ($locales as $index => $locale) {
            $users->push($this->createUserWithLocale($locale, [
                'email' => "test{$index}@example.com"
            ]));
        }
        
        return $users;
    }

    /**
     * 模擬語言切換請求
     *
     * @param string $targetLocale
     * @param string $currentUrl
     * @return \Illuminate\Testing\TestResponse
     */
    protected function switchLanguage(string $targetLocale, string $currentUrl = '/'): \Illuminate\Testing\TestResponse
    {
        return $this->get($currentUrl . '?locale=' . $targetLocale);
    }

    /**
     * 驗證頁面語言切換功能
     *
     * @param string $url
     * @param array $expectedTexts
     * @return void
     */
    protected function assertPageLanguageSwitching(string $url, array $expectedTexts): void
    {
        foreach ($expectedTexts as $locale => $texts) {
            // 切換到指定語言
            $this->switchLanguage($locale, $url);
            
            // 訪問頁面
            $response = $this->get($url);
            $response->assertSuccessful();
            
            // 驗證頁面內容包含預期的翻譯文字
            foreach ($texts as $text) {
                $response->assertSee($text);
            }
        }
    }

    /**
     * 驗證表單語言切換功能
     *
     * @param string $url
     * @param array $formSelectors
     * @param array $expectedLabels
     * @return void
     */
    protected function assertFormLanguageSwitching(string $url, array $formSelectors, array $expectedLabels): void
    {
        foreach ($expectedLabels as $locale => $labels) {
            // 切換到指定語言
            $this->switchLanguage($locale, $url);
            
            // 訪問表單頁面
            $response = $this->get($url);
            $response->assertSuccessful();
            
            // 驗證表單標籤
            foreach ($formSelectors as $selector => $expectedLabel) {
                if (isset($labels[$expectedLabel])) {
                    $response->assertSee($labels[$expectedLabel]);
                }
            }
        }
    }

    /**
     * 驗證導航選單語言切換功能
     *
     * @param string $url
     * @param array $navigationItems
     * @return void
     */
    protected function assertNavigationLanguageSwitching(string $url, array $navigationItems): void
    {
        foreach ($navigationItems as $locale => $items) {
            // 切換到指定語言
            $this->switchLanguage($locale, $url);
            
            // 訪問頁面
            $response = $this->get($url);
            $response->assertSuccessful();
            
            // 驗證導航項目
            foreach ($items as $item) {
                $response->assertSee($item);
            }
        }
    }

    /**
     * 驗證錯誤訊息語言切換功能
     *
     * @param string $url
     * @param array $formData
     * @param array $expectedErrors
     * @return void
     */
    protected function assertErrorMessageLanguageSwitching(string $url, array $formData, array $expectedErrors): void
    {
        foreach ($expectedErrors as $locale => $errors) {
            // 切換到指定語言
            $this->switchLanguage($locale, $url);
            
            // 提交無效的表單資料
            $response = $this->post($url, $formData);
            
            // 驗證錯誤訊息
            foreach ($errors as $error) {
                $response->assertSee($error);
            }
        }
    }

    /**
     * 驗證成功訊息語言切換功能
     *
     * @param string $url
     * @param array $formData
     * @param array $expectedMessages
     * @return void
     */
    protected function assertSuccessMessageLanguageSwitching(string $url, array $formData, array $expectedMessages): void
    {
        foreach ($expectedMessages as $locale => $message) {
            // 切換到指定語言
            $this->switchLanguage($locale, $url);
            
            // 提交有效的表單資料
            $response = $this->post($url, $formData);
            
            // 驗證成功訊息
            $response->assertSee($message);
        }
    }

    /**
     * 驗證資料表格語言切換功能
     *
     * @param string $url
     * @param array $expectedHeaders
     * @param array $expectedActions
     * @return void
     */
    protected function assertDataTableLanguageSwitching(string $url, array $expectedHeaders, array $expectedActions = []): void
    {
        foreach ($expectedHeaders as $locale => $headers) {
            // 切換到指定語言
            $this->switchLanguage($locale, $url);
            
            // 訪問資料表格頁面
            $response = $this->get($url);
            $response->assertSuccessful();
            
            // 驗證表格標題
            foreach ($headers as $header) {
                $response->assertSee($header);
            }
            
            // 驗證操作按鈕
            if (isset($expectedActions[$locale])) {
                foreach ($expectedActions[$locale] as $action) {
                    $response->assertSee($action);
                }
            }
        }
    }

    /**
     * 驗證分頁控制項語言切換功能
     *
     * @param string $url
     * @param array $expectedPaginationTexts
     * @return void
     */
    protected function assertPaginationLanguageSwitching(string $url, array $expectedPaginationTexts): void
    {
        foreach ($expectedPaginationTexts as $locale => $texts) {
            // 切換到指定語言
            $this->switchLanguage($locale, $url);
            
            // 訪問有分頁的頁面
            $response = $this->get($url);
            $response->assertSuccessful();
            
            // 驗證分頁文字
            foreach ($texts as $text) {
                $response->assertSee($text);
            }
        }
    }

    /**
     * 驗證模態對話框語言切換功能
     *
     * @param string $url
     * @param string $modalTrigger
     * @param array $expectedModalTexts
     * @return void
     */
    protected function assertModalLanguageSwitching(string $url, string $modalTrigger, array $expectedModalTexts): void
    {
        foreach ($expectedModalTexts as $locale => $texts) {
            // 切換到指定語言
            $this->switchLanguage($locale, $url);
            
            // 訪問頁面
            $response = $this->get($url);
            $response->assertSuccessful();
            
            // 驗證模態對話框內容（這裡假設模態內容在頁面中）
            foreach ($texts as $text) {
                $response->assertSee($text);
            }
        }
    }

    /**
     * 驗證麵包屑導航語言切換功能
     *
     * @param string $url
     * @param array $expectedBreadcrumbs
     * @return void
     */
    protected function assertBreadcrumbLanguageSwitching(string $url, array $expectedBreadcrumbs): void
    {
        foreach ($expectedBreadcrumbs as $locale => $breadcrumbs) {
            // 切換到指定語言
            $this->switchLanguage($locale, $url);
            
            // 訪問頁面
            $response = $this->get($url);
            $response->assertSuccessful();
            
            // 驗證麵包屑項目
            foreach ($breadcrumbs as $breadcrumb) {
                $response->assertSee($breadcrumb);
            }
        }
    }

    /**
     * 驗證搜尋功能語言切換
     *
     * @param string $url
     * @param string $searchTerm
     * @param array $expectedPlaceholders
     * @param array $expectedNoResultsMessages
     * @return void
     */
    protected function assertSearchLanguageSwitching(string $url, string $searchTerm, array $expectedPlaceholders, array $expectedNoResultsMessages = []): void
    {
        foreach ($expectedPlaceholders as $locale => $placeholder) {
            // 切換到指定語言
            $this->switchLanguage($locale, $url);
            
            // 訪問搜尋頁面
            $response = $this->get($url);
            $response->assertSuccessful();
            
            // 驗證搜尋框佔位符
            $response->assertSee($placeholder);
            
            // 如果有無結果訊息，進行搜尋測試
            if (isset($expectedNoResultsMessages[$locale])) {
                $searchResponse = $this->get($url . '?search=' . urlencode($searchTerm));
                $searchResponse->assertSee($expectedNoResultsMessages[$locale]);
            }
        }
    }

    /**
     * 驗證語言選擇器功能
     *
     * @param string $url
     * @param array $expectedLanguageOptions
     * @return void
     */
    protected function assertLanguageSelectorFunctionality(string $url, array $expectedLanguageOptions): void
    {
        // 訪問頁面
        $response = $this->get($url);
        $response->assertSuccessful();
        
        // 驗證語言選擇器存在
        $response->assertSee('language-selector', false);
        
        // 驗證語言選項
        foreach ($expectedLanguageOptions as $locale => $label) {
            $response->assertSee($label);
        }
        
        // 測試語言切換
        foreach (array_keys($expectedLanguageOptions) as $locale) {
            $switchResponse = $this->switchLanguage($locale, $url);
            $switchResponse->assertSuccessful();
            
            // 驗證語言已切換
            $this->assertEquals($locale, App::getLocale());
        }
    }

    /**
     * 驗證主題切換按鈕語言功能
     *
     * @param string $url
     * @param array $expectedThemeTexts
     * @return void
     */
    protected function assertThemeToggleLanguageSwitching(string $url, array $expectedThemeTexts): void
    {
        foreach ($expectedThemeTexts as $locale => $texts) {
            // 切換到指定語言
            $this->switchLanguage($locale, $url);
            
            // 訪問頁面
            $response = $this->get($url);
            $response->assertSuccessful();
            
            // 驗證主題切換按鈕文字
            foreach ($texts as $text) {
                $response->assertSee($text);
            }
        }
    }

    /**
     * 驗證日期時間格式語言切換
     *
     * @param string $url
     * @param array $expectedDateFormats
     * @return void
     */
    protected function assertDateTimeLanguageSwitching(string $url, array $expectedDateFormats): void
    {
        foreach ($expectedDateFormats as $locale => $format) {
            // 切換到指定語言
            $this->switchLanguage($locale, $url);
            
            // 設定 Carbon 語言
            \Carbon\Carbon::setLocale($locale);
            
            // 訪問頁面
            $response = $this->get($url);
            $response->assertSuccessful();
            
            // 驗證日期格式（這裡需要根據實際頁面內容調整）
            // 例如檢查是否包含特定的日期格式字串
        }
    }

    /**
     * 驗證語言偏好持久化
     *
     * @param string $locale
     * @param string $testUrl
     * @return void
     */
    protected function assertLanguagePreferencePersistence(string $locale, string $testUrl = '/'): void
    {
        // 切換語言
        $this->switchLanguage($locale, $testUrl);
        
        // 驗證 Session 中的語言設定
        $this->assertEquals($locale, Session::get('locale'));
        
        // 如果有登入使用者，驗證使用者偏好
        if (auth()->check()) {
            $this->assertEquals($locale, auth()->user()->fresh()->locale);
        }
        
        // 模擬新的請求，驗證語言持久化
        $newResponse = $this->get($testUrl);
        $newResponse->assertSuccessful();
        
        // 驗證語言仍然是設定的語言
        $this->assertEquals($locale, App::getLocale());
    }

    /**
     * 驗證瀏覽器語言偵測功能
     *
     * @param string $acceptLanguage
     * @param string $expectedLocale
     * @param string $testUrl
     * @return void
     */
    protected function assertBrowserLanguageDetection(string $acceptLanguage, string $expectedLocale, string $testUrl = '/'): void
    {
        // 清除現有的語言設定
        Session::forget('locale');
        
        // 設定瀏覽器語言偏好
        $response = $this->withHeaders([
            'Accept-Language' => $acceptLanguage
        ])->get($testUrl);
        
        $response->assertSuccessful();
        
        // 驗證偵測到的語言
        $this->assertEquals($expectedLocale, App::getLocale());
    }

    /**
     * 建立多語系測試資料
     *
     * @param string $model
     * @param array $data
     * @return array
     */
    protected function createMultilingualTestData(string $model, array $data): array
    {
        $results = [];
        
        foreach ($data as $locale => $attributes) {
            $results[$locale] = $model::factory()->create($attributes);
        }
        
        return $results;
    }

    /**
     * 驗證多語系資料顯示
     *
     * @param string $url
     * @param array $testData
     * @param string $fieldName
     * @return void
     */
    protected function assertMultilingualDataDisplay(string $url, array $testData, string $fieldName): void
    {
        foreach ($testData as $locale => $data) {
            // 切換到指定語言
            $this->switchLanguage($locale, $url);
            
            // 訪問頁面
            $response = $this->get($url);
            $response->assertSuccessful();
            
            // 驗證資料顯示
            if (isset($data->$fieldName)) {
                $response->assertSee($data->$fieldName);
            }
        }
    }

    /**
     * 產生語言切換測試報告
     *
     * @param array $testResults
     * @return array
     */
    protected function generateLanguageSwitchingReport(array $testResults): array
    {
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
        
        return $report;
    }
}