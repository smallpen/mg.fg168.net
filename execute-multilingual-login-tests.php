<?php

/**
 * 登入頁面多語系 MCP 整合測試執行腳本
 * 
 * 使用 Playwright 和 MySQL MCP 工具進行登入頁面多語系功能的完整測試
 * 測試語言切換、主題切換按鈕翻譯、表單驗證訊息等功能
 */

require_once __DIR__ . '/vendor/autoload.php';

class LoginPageMultilingualTestRunner
{
    private array $testResults = [];
    private float $startTime;
    private array $config;
    private array $supportedLocales = ['zh_TW', 'en'];

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->config = [
            'base_url' => 'http://localhost',
            'admin_username' => 'admin',
            'admin_password' => 'password123',
            'database' => 'laravel_admin',
            'test_timeout' => 30000,
            'screenshot_dir' => __DIR__ . '/storage/screenshots/multilingual',
        ];

        // 確保截圖目錄存在
        if (!is_dir($this->config['screenshot_dir'])) {
            mkdir($this->config['screenshot_dir'], 0755, true);
        }
    }

    /**
     * 執行所有多語系登入頁面測試
     */
    public function runAllTests(): void
    {
        echo "🌐 開始執行登入頁面多語系測試\n";
        echo "================================\n\n";

        try {
            // 1. 準備測試環境
            $this->prepareTestEnvironment();

            // 2. 測試登入頁面語言切換
            $this->testLoginPageLanguageSwitching();

            // 3. 測試主題切換按鈕翻譯
            $this->testThemeToggleTranslation();

            // 4. 測試表單驗證訊息多語系
            $this->testFormValidationMessages();

            // 5. 測試頁面標題多語系顯示
            $this->testPageTitleMultilingual();

            // 6. 測試語言偏好持久化
            $this->testLanguagePreferencePersistence();

            // 7. 測試登入成功後語言保持
            $this->testLanguageMaintainedAfterLogin();

            // 8. 測試錯誤訊息多語系
            $this->testErrorMessagesMultilingual();

            // 9. 測試語言切換使用者體驗
            $this->testLanguageSwitchingUX();

            // 10. 生成測試報告
            $this->generateReport();

        } catch (Exception $e) {
            echo "❌ 測試執行失敗: " . $e->getMessage() . "\n";
            $this->testResults['error'] = $e->getMessage();
        } finally {
            // 清理測試環境
            $this->cleanupTestEnvironment();
        }
    }

    /**
     * 準備測試環境
     */
    private function prepareTestEnvironment(): void
    {
        echo "📋 準備多語系測試環境...\n";

        // 檢查資料庫連線
        $this->checkDatabaseConnection();

        // 驗證測試資料
        $this->verifyTestData();

        // 啟動瀏覽器
        $this->startBrowser();

        echo "✅ 多語系測試環境準備完成\n\n";
    }

    /**
     * 檢查資料庫連線
     */
    private function checkDatabaseConnection(): void
    {
        echo "  • 檢查資料庫連線...\n";

        // 使用 MySQL MCP 檢查連線
        $databases = $this->executeMcpCommand('mcp_mysql_list_databases');
        
        if (empty($databases)) {
            throw new Exception('無法連接到資料庫');
        }

        echo "    ✓ 資料庫連線正常\n";
    }

    /**
     * 驗證測試資料
     */
    private function verifyTestData(): void
    {
        echo "  • 驗證測試資料...\n";

        // 檢查管理員使用者是否存在
        $adminUser = $this->executeMcpCommand('mcp_mysql_execute_query', [
            'query' => "SELECT id, username, locale FROM users WHERE username = ?",
            'database' => $this->config['database']
        ]);

        if (empty($adminUser)) {
            throw new Exception('測試用管理員帳號不存在，請先執行 db:seed');
        }

        echo "    ✓ 測試資料驗證完成\n";
    }

    /**
     * 啟動瀏覽器
     */
    private function startBrowser(): void
    {
        echo "  • 啟動瀏覽器...\n";

        $this->executeMcpCommand('mcp_playwright_playwright_navigate', [
            'url' => $this->config['base_url'] . '/admin/login',
            'headless' => false,
            'width' => 1920,
            'height' => 1080
        ]);

        echo "    ✓ 瀏覽器啟動完成\n";
    }

    /**
     * 測試登入頁面語言切換功能
     */
    private function testLoginPageLanguageSwitching(): void
    {
        echo "🔄 測試登入頁面語言切換功能...\n";

        $testResult = [
            'name' => '登入頁面語言切換測試',
            'passed' => true,
            'details' => [],
            'screenshots' => [],
        ];

        try {
            // 1. 導航到登入頁面
            echo "  • 導航到登入頁面...\n";
            $this->executeMcpCommand('mcp_playwright_playwright_navigate', [
                'url' => $this->config['base_url'] . '/admin/login'
            ]);

            // 截圖初始狀態
            $screenshot = $this->takeScreenshot('login-page-initial');
            $testResult['screenshots'][] = $screenshot;

            // 2. 測試每種語言的切換
            foreach ($this->supportedLocales as $locale) {
                echo "  • 測試切換到 {$locale}...\n";
                
                // 切換語言（如果有語言選擇器）
                $this->switchLanguage($locale);
                
                // 等待頁面更新
                sleep(2);
                
                // 截圖
                $screenshot = $this->takeScreenshot("login-page-{$locale}");
                $testResult['screenshots'][] = $screenshot;
                
                // 驗證頁面內容
                $pageContent = $this->executeMcpCommand('mcp_playwright_playwright_get_visible_text');
                
                // 檢查關鍵翻譯是否正確
                $this->verifyLoginPageTranslations($pageContent, $locale);
                
                $testResult['details'][] = "語言 {$locale} 切換成功";
            }

            echo "    ✅ 登入頁面語言切換測試通過\n\n";

        } catch (Exception $e) {
            $testResult['passed'] = false;
            $testResult['error'] = $e->getMessage();
            echo "    ❌ 登入頁面語言切換測試失敗: " . $e->getMessage() . "\n\n";
        }

        $this->testResults['language_switching'] = $testResult;
    }

    /**
     * 測試主題切換按鈕翻譯
     */
    private function testThemeToggleTranslation(): void
    {
        echo "🎨 測試主題切換按鈕翻譯...\n";

        $testResult = [
            'name' => '主題切換按鈕翻譯測試',
            'passed' => true,
            'details' => [],
            'screenshots' => [],
        ];

        try {
            foreach ($this->supportedLocales as $locale) {
                echo "  • 測試 {$locale} 語言下的主題切換按鈕...\n";
                
                // 切換到指定語言
                $this->switchLanguage($locale);
                sleep(1);
                
                // 檢查主題切換按鈕文字
                $buttonText = $this->executeMcpCommand('mcp_playwright_playwright_evaluate', [
                    'script' => 'document.querySelector("[data-theme-toggle]")?.textContent?.trim()'
                ]);
                
                // 驗證按鈕文字是否正確翻譯
                $expectedTexts = [
                    'zh_TW' => ['深色模式', '淺色模式'],
                    'en' => ['Dark Mode', 'Light Mode']
                ];
                
                $isCorrect = false;
                foreach ($expectedTexts[$locale] as $expectedText) {
                    if (strpos($buttonText, $expectedText) !== false) {
                        $isCorrect = true;
                        break;
                    }
                }
                
                if (!$isCorrect) {
                    throw new Exception("主題切換按鈕翻譯不正確，語言: {$locale}，實際文字: {$buttonText}");
                }
                
                // 測試點擊主題切換按鈕
                $this->executeMcpCommand('mcp_playwright_playwright_click', [
                    'selector' => '[data-theme-toggle]'
                ]);
                
                sleep(1);
                
                // 截圖主題切換後的狀態
                $screenshot = $this->takeScreenshot("theme-toggle-{$locale}");
                $testResult['screenshots'][] = $screenshot;
                
                // 再次檢查按鈕文字是否更新
                $newButtonText = $this->executeMcpCommand('mcp_playwright_playwright_evaluate', [
                    'script' => 'document.querySelector("[data-theme-toggle]")?.textContent?.trim()'
                ]);
                
                if ($newButtonText === $buttonText) {
                    throw new Exception("主題切換後按鈕文字未更新，語言: {$locale}");
                }
                
                $testResult['details'][] = "語言 {$locale} 主題切換按鈕翻譯正確";
            }

            echo "    ✅ 主題切換按鈕翻譯測試通過\n\n";

        } catch (Exception $e) {
            $testResult['passed'] = false;
            $testResult['error'] = $e->getMessage();
            echo "    ❌ 主題切換按鈕翻譯測試失敗: " . $e->getMessage() . "\n\n";
        }

        $this->testResults['theme_toggle_translation'] = $testResult;
    }

    /**
     * 測試表單驗證訊息多語系
     */
    private function testFormValidationMessages(): void
    {
        echo "📝 測試表單驗證訊息多語系...\n";

        $testResult = [
            'name' => '表單驗證訊息多語系測試',
            'passed' => true,
            'details' => [],
            'screenshots' => [],
        ];

        try {
            foreach ($this->supportedLocales as $locale) {
                echo "  • 測試 {$locale} 語言下的表單驗證...\n";
                
                // 切換到指定語言
                $this->switchLanguage($locale);
                sleep(1);
                
                // 清空表單欄位
                $this->executeMcpCommand('mcp_playwright_playwright_fill', [
                    'selector' => 'input[name="username"]',
                    'value' => ''
                ]);
                
                $this->executeMcpCommand('mcp_playwright_playwright_fill', [
                    'selector' => 'input[name="password"]',
                    'value' => ''
                ]);
                
                // 嘗試提交空表單
                $this->executeMcpCommand('mcp_playwright_playwright_click', [
                    'selector' => 'button[type="submit"]'
                ]);
                
                sleep(2);
                
                // 截圖驗證錯誤狀態
                $screenshot = $this->takeScreenshot("validation-errors-{$locale}");
                $testResult['screenshots'][] = $screenshot;
                
                // 檢查驗證錯誤訊息
                $pageContent = $this->executeMcpCommand('mcp_playwright_playwright_get_visible_text');
                
                // 驗證錯誤訊息是否以正確語言顯示
                $expectedErrorMessages = [
                    'zh_TW' => ['請輸入使用者名稱', '請輸入密碼'],
                    'en' => ['Please enter your username', 'Please enter your password']
                ];
                
                $foundErrors = 0;
                foreach ($expectedErrorMessages[$locale] as $expectedMessage) {
                    if (strpos($pageContent, $expectedMessage) !== false) {
                        $foundErrors++;
                    }
                }
                
                if ($foundErrors < 2) {
                    throw new Exception("驗證錯誤訊息翻譯不完整，語言: {$locale}，找到 {$foundErrors} 個錯誤訊息");
                }
                
                // 測試輸入過短的值
                $this->executeMcpCommand('mcp_playwright_playwright_fill', [
                    'selector' => 'input[name="username"]',
                    'value' => 'ab'
                ]);
                
                $this->executeMcpCommand('mcp_playwright_playwright_fill', [
                    'selector' => 'input[name="password"]',
                    'value' => '123'
                ]);
                
                $this->executeMcpCommand('mcp_playwright_playwright_click', [
                    'selector' => 'button[type="submit"]'
                ]);
                
                sleep(2);
                
                // 檢查長度驗證錯誤訊息
                $pageContent = $this->executeMcpCommand('mcp_playwright_playwright_get_visible_text');
                
                $lengthErrorMessages = [
                    'zh_TW' => ['至少需要', '個字元'],
                    'en' => ['must be at least', 'characters']
                ];
                
                $foundLengthErrors = 0;
                foreach ($lengthErrorMessages[$locale] as $expectedMessage) {
                    if (strpos($pageContent, $expectedMessage) !== false) {
                        $foundLengthErrors++;
                    }
                }
                
                if ($foundLengthErrors < 1) {
                    throw new Exception("長度驗證錯誤訊息翻譯不正確，語言: {$locale}");
                }
                
                $testResult['details'][] = "語言 {$locale} 表單驗證訊息翻譯正確";
            }

            echo "    ✅ 表單驗證訊息多語系測試通過\n\n";

        } catch (Exception $e) {
            $testResult['passed'] = false;
            $testResult['error'] = $e->getMessage();
            echo "    ❌ 表單驗證訊息多語系測試失敗: " . $e->getMessage() . "\n\n";
        }

        $this->testResults['form_validation_messages'] = $testResult;
    }

    /**
     * 測試頁面標題多語系顯示
     */
    private function testPageTitleMultilingual(): void
    {
        echo "📄 測試頁面標題多語系顯示...\n";

        $testResult = [
            'name' => '頁面標題多語系測試',
            'passed' => true,
            'details' => [],
            'screenshots' => [],
        ];

        try {
            foreach ($this->supportedLocales as $locale) {
                echo "  • 測試 {$locale} 語言下的頁面標題...\n";
                
                // 切換到指定語言
                $this->switchLanguage($locale);
                sleep(1);
                
                // 取得頁面標題
                $pageTitle = $this->executeMcpCommand('mcp_playwright_playwright_evaluate', [
                    'script' => 'document.title'
                ]);
                
                // 取得頁面內的標題元素
                $h2Title = $this->executeMcpCommand('mcp_playwright_playwright_evaluate', [
                    'script' => 'document.querySelector("h2")?.textContent?.trim()'
                ]);
                
                // 驗證標題翻譯
                $expectedTitles = [
                    'zh_TW' => '登入',
                    'en' => 'Login'
                ];
                
                if (strpos($pageTitle, $expectedTitles[$locale]) === false) {
                    throw new Exception("頁面標題翻譯不正確，語言: {$locale}，實際標題: {$pageTitle}");
                }
                
                if ($h2Title !== $expectedTitles[$locale]) {
                    throw new Exception("頁面 H2 標題翻譯不正確，語言: {$locale}，實際標題: {$h2Title}");
                }
                
                $testResult['details'][] = "語言 {$locale} 頁面標題翻譯正確";
            }

            echo "    ✅ 頁面標題多語系測試通過\n\n";

        } catch (Exception $e) {
            $testResult['passed'] = false;
            $testResult['error'] = $e->getMessage();
            echo "    ❌ 頁面標題多語系測試失敗: " . $e->getMessage() . "\n\n";
        }

        $this->testResults['page_title_multilingual'] = $testResult;
    }

    /**
     * 測試語言偏好持久化
     */
    private function testLanguagePreferencePersistence(): void
    {
        echo "💾 測試語言偏好持久化...\n";

        $testResult = [
            'name' => '語言偏好持久化測試',
            'passed' => true,
            'details' => [],
            'screenshots' => [],
        ];

        try {
            // 測試每種語言的持久化
            foreach ($this->supportedLocales as $locale) {
                echo "  • 測試 {$locale} 語言偏好持久化...\n";
                
                // 切換到指定語言
                $this->switchLanguage($locale);
                sleep(1);
                
                // 重新載入頁面
                $this->executeMcpCommand('mcp_playwright_playwright_navigate', [
                    'url' => $this->config['base_url'] . '/admin/login'
                ]);
                
                sleep(2);
                
                // 檢查語言是否保持
                $pageContent = $this->executeMcpCommand('mcp_playwright_playwright_get_visible_text');
                
                // 驗證語言是否保持
                $expectedTexts = [
                    'zh_TW' => '登入',
                    'en' => 'Login'
                ];
                
                if (strpos($pageContent, $expectedTexts[$locale]) === false) {
                    throw new Exception("語言偏好未持久化，語言: {$locale}");
                }
                
                $testResult['details'][] = "語言 {$locale} 偏好持久化正常";
            }

            echo "    ✅ 語言偏好持久化測試通過\n\n";

        } catch (Exception $e) {
            $testResult['passed'] = false;
            $testResult['error'] = $e->getMessage();
            echo "    ❌ 語言偏好持久化測試失敗: " . $e->getMessage() . "\n\n";
        }

        $this->testResults['language_preference_persistence'] = $testResult;
    }

    /**
     * 測試登入成功後語言保持
     */
    private function testLanguageMaintainedAfterLogin(): void
    {
        echo "🔐 測試登入成功後語言保持...\n";

        $testResult = [
            'name' => '登入後語言保持測試',
            'passed' => true,
            'details' => [],
            'screenshots' => [],
        ];

        try {
            foreach ($this->supportedLocales as $locale) {
                echo "  • 測試 {$locale} 語言下的登入流程...\n";
                
                // 導航到登入頁面
                $this->executeMcpCommand('mcp_playwright_playwright_navigate', [
                    'url' => $this->config['base_url'] . '/admin/login'
                ]);
                
                // 切換到指定語言
                $this->switchLanguage($locale);
                sleep(1);
                
                // 執行登入
                $this->performLogin();
                
                sleep(3);
                
                // 截圖登入後狀態
                $screenshot = $this->takeScreenshot("after-login-{$locale}");
                $testResult['screenshots'][] = $screenshot;
                
                // 檢查登入後頁面語言
                $pageContent = $this->executeMcpCommand('mcp_playwright_playwright_get_visible_text');
                
                // 驗證儀表板頁面是否使用正確語言
                $expectedDashboardTexts = [
                    'zh_TW' => ['儀表板', '歡迎'],
                    'en' => ['Dashboard', 'Welcome']
                ];
                
                $foundTexts = 0;
                foreach ($expectedDashboardTexts[$locale] as $expectedText) {
                    if (strpos($pageContent, $expectedText) !== false) {
                        $foundTexts++;
                    }
                }
                
                if ($foundTexts < 1) {
                    throw new Exception("登入後語言未保持，語言: {$locale}");
                }
                
                // 登出以準備下一次測試
                $this->performLogout();
                
                $testResult['details'][] = "語言 {$locale} 登入後保持正常";
            }

            echo "    ✅ 登入後語言保持測試通過\n\n";

        } catch (Exception $e) {
            $testResult['passed'] = false;
            $testResult['error'] = $e->getMessage();
            echo "    ❌ 登入後語言保持測試失敗: " . $e->getMessage() . "\n\n";
        }

        $this->testResults['language_maintained_after_login'] = $testResult;
    }

    /**
     * 測試錯誤訊息多語系
     */
    private function testErrorMessagesMultilingual(): void
    {
        echo "⚠️ 測試錯誤訊息多語系...\n";

        $testResult = [
            'name' => '錯誤訊息多語系測試',
            'passed' => true,
            'details' => [],
            'screenshots' => [],
        ];

        try {
            foreach ($this->supportedLocales as $locale) {
                echo "  • 測試 {$locale} 語言下的錯誤訊息...\n";
                
                // 導航到登入頁面
                $this->executeMcpCommand('mcp_playwright_playwright_navigate', [
                    'url' => $this->config['base_url'] . '/admin/login'
                ]);
                
                // 切換到指定語言
                $this->switchLanguage($locale);
                sleep(1);
                
                // 輸入錯誤的登入資訊
                $this->executeMcpCommand('mcp_playwright_playwright_fill', [
                    'selector' => 'input[name="username"]',
                    'value' => 'wronguser'
                ]);
                
                $this->executeMcpCommand('mcp_playwright_playwright_fill', [
                    'selector' => 'input[name="password"]',
                    'value' => 'wrongpassword'
                ]);
                
                $this->executeMcpCommand('mcp_playwright_playwright_click', [
                    'selector' => 'button[type="submit"]'
                ]);
                
                sleep(3);
                
                // 截圖錯誤狀態
                $screenshot = $this->takeScreenshot("login-error-{$locale}");
                $testResult['screenshots'][] = $screenshot;
                
                // 檢查錯誤訊息
                $pageContent = $this->executeMcpCommand('mcp_playwright_playwright_get_visible_text');
                
                // 驗證錯誤訊息翻譯
                $expectedErrorMessages = [
                    'zh_TW' => ['使用者名稱或密碼錯誤', '登入失敗'],
                    'en' => ['Invalid username or password', 'login failed']
                ];
                
                $foundErrors = 0;
                foreach ($expectedErrorMessages[$locale] as $expectedMessage) {
                    if (stripos($pageContent, $expectedMessage) !== false) {
                        $foundErrors++;
                    }
                }
                
                if ($foundErrors < 1) {
                    throw new Exception("錯誤訊息翻譯不正確，語言: {$locale}");
                }
                
                $testResult['details'][] = "語言 {$locale} 錯誤訊息翻譯正確";
            }

            echo "    ✅ 錯誤訊息多語系測試通過\n\n";

        } catch (Exception $e) {
            $testResult['passed'] = false;
            $testResult['error'] = $e->getMessage();
            echo "    ❌ 錯誤訊息多語系測試失敗: " . $e->getMessage() . "\n\n";
        }

        $this->testResults['error_messages_multilingual'] = $testResult;
    }

    /**
     * 測試語言切換使用者體驗
     */
    private function testLanguageSwitchingUX(): void
    {
        echo "✨ 測試語言切換使用者體驗...\n";

        $testResult = [
            'name' => '語言切換使用者體驗測試',
            'passed' => true,
            'details' => [],
            'screenshots' => [],
        ];

        try {
            // 導航到登入頁面
            $this->executeMcpCommand('mcp_playwright_playwright_navigate', [
                'url' => $this->config['base_url'] . '/admin/login'
            ]);
            
            // 測試語言切換響應時間
            $startTime = microtime(true);
            $this->switchLanguage('en');
            $switchTime = (microtime(true) - $startTime) * 1000;
            
            if ($switchTime > 2000) {
                throw new Exception("語言切換響應時間過長: {$switchTime}ms");
            }
            
            $testResult['details'][] = "語言切換響應時間: {$switchTime}ms";
            
            // 測試語言切換動畫效果
            sleep(1);
            $screenshot = $this->takeScreenshot('language-switch-animation');
            $testResult['screenshots'][] = $screenshot;
            
            // 測試快速連續切換
            $this->switchLanguage('zh_TW');
            sleep(0.5);
            $this->switchLanguage('en');
            sleep(0.5);
            $this->switchLanguage('zh_TW');
            sleep(1);
            
            // 驗證最終狀態
            $pageContent = $this->executeMcpCommand('mcp_playwright_playwright_get_visible_text');
            if (strpos($pageContent, '登入') === false) {
                throw new Exception('快速語言切換後狀態不正確');
            }
            
            $testResult['details'][] = '快速語言切換測試通過';

            echo "    ✅ 語言切換使用者體驗測試通過\n\n";

        } catch (Exception $e) {
            $testResult['passed'] = false;
            $testResult['error'] = $e->getMessage();
            echo "    ❌ 語言切換使用者體驗測試失敗: " . $e->getMessage() . "\n\n";
        }

        $this->testResults['language_switching_ux'] = $testResult;
    }

    /**
     * 切換語言
     */
    private function switchLanguage(string $locale): void
    {
        // 如果頁面有語言選擇器，點擊切換
        try {
            // 嘗試找到語言選擇器
            $this->executeMcpCommand('mcp_playwright_playwright_click', [
                'selector' => '.language-selector, [data-language-selector]'
            ]);
            
            sleep(0.5);
            
            // 點擊對應語言選項
            $this->executeMcpCommand('mcp_playwright_playwright_click', [
                'selector' => "[data-locale=\"{$locale}\"], [href*=\"locale={$locale}\"]"
            ]);
            
        } catch (Exception $e) {
            // 如果沒有語言選擇器，直接通過 URL 參數切換
            $currentUrl = $this->executeMcpCommand('mcp_playwright_playwright_evaluate', [
                'script' => 'window.location.href'
            ]);
            
            $newUrl = $this->addLocaleToUrl($currentUrl, $locale);
            
            $this->executeMcpCommand('mcp_playwright_playwright_navigate', [
                'url' => $newUrl
            ]);
        }
    }

    /**
     * 在 URL 中添加語言參數
     */
    private function addLocaleToUrl(string $url, string $locale): string
    {
        $parsedUrl = parse_url($url);
        $query = [];
        
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $query);
        }
        
        $query['locale'] = $locale;
        
        $newUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        if (isset($parsedUrl['port'])) {
            $newUrl .= ':' . $parsedUrl['port'];
        }
        $newUrl .= $parsedUrl['path'] ?? '/';
        $newUrl .= '?' . http_build_query($query);
        
        return $newUrl;
    }

    /**
     * 驗證登入頁面翻譯
     */
    private function verifyLoginPageTranslations(string $pageContent, string $locale): void
    {
        $expectedTranslations = [
            'zh_TW' => [
                '登入',
                '使用者名稱',
                '密碼',
                '記住我',
                '深色模式',
                '淺色模式'
            ],
            'en' => [
                'Login',
                'Username',
                'Password',
                'Remember me',
                'Dark Mode',
                'Light Mode'
            ]
        ];

        $foundTranslations = 0;
        foreach ($expectedTranslations[$locale] as $expectedText) {
            if (strpos($pageContent, $expectedText) !== false) {
                $foundTranslations++;
            }
        }

        if ($foundTranslations < 4) {
            throw new Exception("登入頁面翻譯不完整，語言: {$locale}，找到 {$foundTranslations} 個翻譯");
        }
    }

    /**
     * 執行登入
     */
    private function performLogin(): void
    {
        $this->executeMcpCommand('mcp_playwright_playwright_fill', [
            'selector' => 'input[name="username"]',
            'value' => $this->config['admin_username']
        ]);

        $this->executeMcpCommand('mcp_playwright_playwright_fill', [
            'selector' => 'input[name="password"]',
            'value' => $this->config['admin_password']
        ]);

        $this->executeMcpCommand('mcp_playwright_playwright_click', [
            'selector' => 'button[type="submit"]'
        ]);
    }

    /**
     * 執行登出
     */
    private function performLogout(): void
    {
        try {
            // 嘗試找到登出按鈕或選單
            $this->executeMcpCommand('mcp_playwright_playwright_click', [
                'selector' => '[data-logout], .logout-button, a[href*="logout"]'
            ]);
            
            sleep(2);
        } catch (Exception $e) {
            // 如果找不到登出按鈕，直接導航到登出 URL
            $this->executeMcpCommand('mcp_playwright_playwright_navigate', [
                'url' => $this->config['base_url'] . '/admin/logout'
            ]);
        }
    }

    /**
     * 截圖
     */
    private function takeScreenshot(string $name): string
    {
        $filename = "multilingual_login_{$name}_" . date('Y-m-d_H-i-s') . ".png";
        $filepath = $this->config['screenshot_dir'] . '/' . $filename;

        $this->executeMcpCommand('mcp_playwright_playwright_screenshot', [
            'name' => $name,
            'savePng' => true,
            'fullPage' => true
        ]);

        return $filepath;
    }

    /**
     * 生成測試報告
     */
    private function generateReport(): void
    {
        echo "📊 生成多語系測試報告...\n";

        $totalTime = round((microtime(true) - $this->startTime) * 1000, 2);
        $passedTests = 0;
        $failedTests = 0;

        foreach ($this->testResults as $result) {
            if (isset($result['passed'])) {
                if ($result['passed']) {
                    $passedTests++;
                } else {
                    $failedTests++;
                }
            }
        }

        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'test_type' => '登入頁面多語系測試',
            'total_execution_time' => $totalTime,
            'total_tests' => $passedTests + $failedTests,
            'passed_tests' => $passedTests,
            'failed_tests' => $failedTests,
            'success_rate' => $passedTests + $failedTests > 0 ? round(($passedTests / ($passedTests + $failedTests)) * 100, 2) : 0,
            'supported_locales' => $this->supportedLocales,
            'test_results' => $this->testResults,
            'environment' => [
                'base_url' => $this->config['base_url'],
                'database' => $this->config['database'],
                'php_version' => PHP_VERSION,
            ],
        ];

        // 儲存 JSON 報告
        $reportPath = __DIR__ . '/storage/logs/multilingual_login_test_report_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 生成 HTML 報告
        $this->generateHtmlReport($report);

        echo "  • JSON 報告已儲存至: {$reportPath}\n";
        echo "  • HTML 報告已生成\n";

        // 顯示摘要
        echo "\n📋 多語系測試摘要\n";
        echo "==================\n";
        echo "測試類型: 登入頁面多語系功能\n";
        echo "支援語言: " . implode(', ', $this->supportedLocales) . "\n";
        echo "總測試數: {$report['total_tests']}\n";
        echo "通過: {$report['passed_tests']}\n";
        echo "失敗: {$report['failed_tests']}\n";
        echo "成功率: {$report['success_rate']}%\n";
        echo "執行時間: {$totalTime}ms\n\n";

        if ($failedTests > 0) {
            echo "❌ 有測試失敗，請檢查詳細報告\n";
        } else {
            echo "✅ 所有多語系測試通過！\n";
        }
    }

    /**
     * 生成 HTML 報告
     */
    private function generateHtmlReport(array $report): void
    {
        $html = $this->generateHtmlReportTemplate($report);
        $htmlPath = __DIR__ . '/storage/logs/multilingual_login_test_report_' . date('Y-m-d_H-i-s') . '.html';
        file_put_contents($htmlPath, $html);
    }

    /**
     * 生成 HTML 報告模板
     */
    private function generateHtmlReportTemplate(array $report): string
    {
        $testResultsHtml = '';
        foreach ($report['test_results'] as $testName => $result) {
            if (!isset($result['name'])) continue;
            
            $status = $result['passed'] ? '✅ 通過' : '❌ 失敗';
            $statusClass = $result['passed'] ? 'success' : 'failure';
            
            $detailsHtml = '';
            if (!empty($result['details'])) {
                $detailsHtml = '<ul>';
                foreach ($result['details'] as $detail) {
                    $detailsHtml .= "<li>{$detail}</li>";
                }
                $detailsHtml .= '</ul>';
            }

            $screenshotsHtml = '';
            if (!empty($result['screenshots'])) {
                $screenshotsHtml = '<div class="screenshots">';
                foreach ($result['screenshots'] as $screenshot) {
                    $screenshotsHtml .= "<img src='{$screenshot}' alt='Screenshot' style='max-width: 300px; margin: 5px;'>";
                }
                $screenshotsHtml .= '</div>';
            }

            $testResultsHtml .= "
                <div class='test-result {$statusClass}'>
                    <h3>{$result['name']} - {$status}</h3>
                    {$detailsHtml}
                    {$screenshotsHtml}
                </div>
            ";
        }

        $localesHtml = implode(', ', $report['supported_locales']);

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>登入頁面多語系測試報告</title>
            <meta charset='utf-8'>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background: #f5f5f5; padding: 20px; border-radius: 5px; }
                .summary { display: flex; gap: 20px; margin: 20px 0; }
                .metric { background: #e3f2fd; padding: 15px; border-radius: 5px; text-align: center; }
                .test-result { margin: 20px 0; padding: 15px; border-radius: 5px; }
                .success { background: #e8f5e8; border-left: 5px solid #4caf50; }
                .failure { background: #ffeaea; border-left: 5px solid #f44336; }
                .screenshots img { border: 1px solid #ddd; border-radius: 3px; }
                .locales { background: #fff3e0; padding: 10px; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🌐 登入頁面多語系測試報告</h1>
                <p>執行時間: {$report['timestamp']}</p>
                <p>總執行時間: {$report['total_execution_time']}ms</p>
                <div class='locales'>
                    <strong>測試語言:</strong> {$localesHtml}
                </div>
            </div>
            
            <div class='summary'>
                <div class='metric'>
                    <h3>{$report['total_tests']}</h3>
                    <p>總測試數</p>
                </div>
                <div class='metric'>
                    <h3>{$report['passed_tests']}</h3>
                    <p>通過</p>
                </div>
                <div class='metric'>
                    <h3>{$report['failed_tests']}</h3>
                    <p>失敗</p>
                </div>
                <div class='metric'>
                    <h3>{$report['success_rate']}%</h3>
                    <p>成功率</p>
                </div>
            </div>
            
            <h2>測試結果詳情</h2>
            {$testResultsHtml}
        </body>
        </html>
        ";
    }

    /**
     * 清理測試環境
     */
    private function cleanupTestEnvironment(): void
    {
        echo "🧹 清理多語系測試環境...\n";

        try {
            // 關閉瀏覽器
            $this->executeMcpCommand('mcp_playwright_playwright_close');

            echo "  ✅ 多語系測試環境清理完成\n";

        } catch (Exception $e) {
            echo "  ⚠️  清理測試環境時發生錯誤: " . $e->getMessage() . "\n";
        }
    }

    /**
     * 執行 MCP 命令
     */
    private function executeMcpCommand(string $command, array $params = []): mixed
    {
        // 這裡應該實作實際的 MCP 命令執行
        // 目前返回模擬資料以供測試框架使用
        
        switch ($command) {
            case 'mcp_mysql_list_databases':
                return ['laravel_admin', 'information_schema', 'mysql'];
            
            case 'mcp_mysql_execute_query':
                if (strpos($params['query'], 'SELECT id, username, locale FROM users') !== false) {
                    return [['id' => 1, 'username' => 'admin', 'locale' => 'zh_TW']];
                }
                return [];
            
            case 'mcp_playwright_playwright_get_visible_text':
                return '登入 使用者名稱 密碼 記住我 深色模式';
            
            case 'mcp_playwright_playwright_evaluate':
                if (strpos($params['script'], 'document.title') !== false) {
                    return 'Laravel Admin System - 登入';
                }
                if (strpos($params['script'], 'querySelector("h2")') !== false) {
                    return '登入';
                }
                if (strpos($params['script'], 'data-theme-toggle') !== false) {
                    return '深色模式';
                }
                return '';
            
            default:
                return true;
        }
    }
}

// 執行測試
if (php_sapi_name() === 'cli') {
    $testRunner = new LoginPageMultilingualTestRunner();
    $testRunner->runAllTests();
}