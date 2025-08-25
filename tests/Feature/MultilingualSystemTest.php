<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\MultilingualTestHelpers;
use Tests\Traits\MultilingualTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

/**
 * 多語系系統整合測試
 * 
 * 測試多語系功能的整合運作，包括語言切換、翻譯載入等
 */
class MultilingualSystemTest extends TestCase
{
    use RefreshDatabase, MultilingualTestHelpers, MultilingualTestData;

    /**
     * 支援的語言列表
     */
    protected array $supportedLocales = ['zh_TW', 'en'];

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
     * 測試語言切換功能
     */
    public function test_language_switching_functionality(): void
    {
        // 測試切換到英文
        $response = $this->get('/?locale=en');
        $response->assertSuccessful();
        $this->assertEquals('en', App::getLocale());
        
        // 測試切換到中文
        $response = $this->get('/?locale=zh_TW');
        $response->assertSuccessful();
        $this->assertEquals('zh_TW', App::getLocale());
    }

    /**
     * 測試無效語言參數處理
     */
    public function test_invalid_locale_parameter_handling(): void
    {
        // 測試無效的語言參數
        $response = $this->get('/?locale=invalid');
        $response->assertSuccessful();
        
        // 應該使用預設語言
        $this->assertEquals('zh_TW', App::getLocale());
    }

    /**
     * 測試 Session 語言持久化
     */
    public function test_session_locale_persistence(): void
    {
        // 設定語言
        $this->get('/?locale=en');
        $this->assertEquals('en', Session::get('locale'));
        
        // 新的請求應該保持語言設定
        $response = $this->get('/');
        $response->assertSuccessful();
        $this->assertEquals('en', App::getLocale());
    }

    /**
     * 測試使用者語言偏好
     */
    public function test_user_locale_preference(): void
    {
        // 建立有語言偏好的使用者
        $user = $this->createUserWithLocale('en');
        
        // 登入使用者
        $this->actingAs($user);
        
        // 訪問頁面應該使用使用者的語言偏好
        $response = $this->get('/');
        $response->assertSuccessful();
        $this->assertEquals('en', App::getLocale());
    }

    /**
     * 測試使用者語言偏好更新
     */
    public function test_user_locale_preference_update(): void
    {
        // 建立中文偏好的使用者
        $user = $this->createUserWithLocale('zh_TW');
        $this->actingAs($user);
        
        // 切換到英文
        $response = $this->get('/?locale=en');
        $response->assertSuccessful();
        
        // 驗證使用者偏好已更新
        $this->assertEquals('en', $user->fresh()->locale);
    }

    /**
     * 測試瀏覽器語言偵測
     */
    public function test_browser_language_detection(): void
    {
        // 清除 Session
        Session::forget('locale');
        
        // 設定瀏覽器語言偏好為英文
        $response = $this->withHeaders([
            'Accept-Language' => 'en-US,en;q=0.9'
        ])->get('/');
        
        $response->assertSuccessful();
        $this->assertEquals('en', App::getLocale());
    }

    /**
     * 測試中文瀏覽器語言偵測
     */
    public function test_chinese_browser_language_detection(): void
    {
        // 清除 Session
        Session::forget('locale');
        
        // 設定瀏覽器語言偏好為中文
        $response = $this->withHeaders([
            'Accept-Language' => 'zh-TW,zh;q=0.9,en;q=0.8'
        ])->get('/');
        
        $response->assertSuccessful();
        $this->assertEquals('zh_TW', App::getLocale());
    }

    /**
     * 測試語言優先順序
     */
    public function test_locale_priority_order(): void
    {
        // 建立英文偏好的使用者
        $user = $this->createUserWithLocale('en');
        $this->actingAs($user);
        
        // 設定 Session 為中文
        Session::put('locale', 'zh_TW');
        
        // URL 參數應該有最高優先權
        $response = $this->withHeaders([
            'Accept-Language' => 'zh-TW'
        ])->get('/?locale=en');
        
        $response->assertSuccessful();
        $this->assertEquals('en', App::getLocale());
    }

    /**
     * 測試多個使用者的語言偏好隔離
     */
    public function test_multiple_users_locale_isolation(): void
    {
        // 建立不同語言偏好的使用者
        $users = $this->createUsersWithDifferentLocales(['zh_TW', 'en']);
        
        // 測試第一個使用者（中文）
        $this->actingAs($users[0]);
        $response = $this->get('/');
        $response->assertSuccessful();
        $this->assertEquals('zh_TW', App::getLocale());
        
        // 切換到第二個使用者（英文）
        $this->actingAs($users[1]);
        $response = $this->get('/');
        $response->assertSuccessful();
        $this->assertEquals('en', App::getLocale());
    }

    /**
     * 測試語言切換的效能
     */
    public function test_language_switching_performance(): void
    {
        $startTime = microtime(true);
        
        // 執行多次語言切換
        for ($i = 0; $i < 20; $i++) {
            $locale = $i % 2 === 0 ? 'zh_TW' : 'en';
            $response = $this->get("/?locale={$locale}");
            $response->assertSuccessful();
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // 驗證執行時間在合理範圍內
        $this->assertLessThan(2.0, $executionTime, '語言切換效能過慢');
    }

    /**
     * 測試並發語言切換
     */
    public function test_concurrent_language_switching(): void
    {
        $results = [];
        
        // 模擬並發請求
        $requests = [
            ['locale' => 'zh_TW', 'expected' => 'zh_TW'],
            ['locale' => 'en', 'expected' => 'en'],
            ['locale' => 'zh_TW', 'expected' => 'zh_TW'],
            ['locale' => 'en', 'expected' => 'en'],
        ];
        
        foreach ($requests as $requestData) {
            $response = $this->get('/?locale=' . $requestData['locale']);
            $response->assertSuccessful();
            
            $results[] = [
                'expected' => $requestData['expected'],
                'actual' => App::getLocale()
            ];
        }
        
        // 驗證每個請求都得到正確的語言設定
        foreach ($results as $result) {
            $this->assertEquals($result['expected'], $result['actual']);
        }
    }

    /**
     * 測試語言切換的邊界條件
     */
    public function test_language_switching_edge_cases(): void
    {
        $edgeCases = [
            ['locale' => '', 'expected' => 'zh_TW'], // 空字串
            ['locale' => 'ZH_TW', 'expected' => 'zh_TW'], // 大寫
            ['locale' => 'zh_tw', 'expected' => 'zh_TW'], // 小寫
            ['locale' => 'invalid', 'expected' => 'zh_TW'], // 無效語言
        ];
        
        foreach ($edgeCases as $case) {
            $response = $this->get('/?locale=' . $case['locale']);
            $response->assertSuccessful();
            
            $this->assertEquals($case['expected'], App::getLocale(),
                "語言參數 '{$case['locale']}' 應該解析為 '{$case['expected']}'");
        }
    }

    /**
     * 測試語言切換後的翻譯載入
     */
    public function test_translation_loading_after_language_switch(): void
    {
        // 切換到中文並測試翻譯
        $this->get('/?locale=zh_TW');
        $this->assertEquals('zh_TW', App::getLocale());
        
        // 測試中文翻譯
        $zhTranslation = __('test_translations.common.save');
        $this->assertEquals('儲存', $zhTranslation);
        
        // 切換到英文並測試翻譯
        $this->get('/?locale=en');
        $this->assertEquals('en', App::getLocale());
        
        // 測試英文翻譯
        $enTranslation = __('test_translations.common.save');
        $this->assertEquals('Save', $enTranslation);
    }

    /**
     * 測試複雜的 Accept-Language 標頭處理
     */
    public function test_complex_accept_language_handling(): void
    {
        Session::forget('locale');
        
        $testCases = [
            'en-US,en;q=0.9,zh-TW;q=0.8' => 'en',
            'zh-TW,zh;q=0.9,en;q=0.8' => 'zh_TW',
            'fr-FR,fr;q=0.9,en;q=0.8' => 'en', // 法文不支援，回退到英文
            'de-DE,de;q=0.9' => 'zh_TW', // 德文不支援，使用預設
        ];
        
        foreach ($testCases as $acceptLanguage => $expectedLocale) {
            $response = $this->withHeaders([
                'Accept-Language' => $acceptLanguage
            ])->get('/');
            
            $response->assertSuccessful();
            $this->assertEquals($expectedLocale, App::getLocale(),
                "Accept-Language: {$acceptLanguage} 應該解析為 {$expectedLocale}");
            
            // 重置狀態
            Session::forget('locale');
        }
    }

    /**
     * 測試語言設定的狀態管理
     */
    public function test_locale_state_management(): void
    {
        // 初始狀態
        $this->assertEquals('zh_TW', config('app.locale'));
        
        // 切換語言
        $this->get('/?locale=en');
        $this->assertEquals('en', App::getLocale());
        $this->assertEquals('en', Session::get('locale'));
        
        // 重新整理應用程式
        $this->refreshApplication();
        
        // 語言設定應該持久化
        $response = $this->get('/');
        $response->assertSuccessful();
        $this->assertEquals('en', App::getLocale());
    }

    /**
     * 測試語言切換的錯誤處理
     */
    public function test_language_switching_error_handling(): void
    {
        // 測試各種可能的錯誤情況
        $errorCases = [
            '/?locale=', // 空值
            '/?locale=null', // null 字串
            '/?locale=undefined', // undefined 字串
            '/?locale=123', // 數字
            '/?locale=zh_CN', // 不支援的中文變體
        ];
        
        foreach ($errorCases as $url) {
            $response = $this->get($url);
            $response->assertSuccessful();
            
            // 應該回退到預設語言
            $this->assertEquals('zh_TW', App::getLocale());
        }
    }

    /**
     * 測試語言切換的記憶體使用
     */
    public function test_language_switching_memory_usage(): void
    {
        $initialMemory = memory_get_usage(true);
        
        // 執行多次語言切換
        for ($i = 0; $i < 10; $i++) {
            $locale = $i % 2 === 0 ? 'zh_TW' : 'en';
            $this->get("/?locale={$locale}");
            
            // 載入一些翻譯
            __('test_translations.common.save');
            __('test_translations.messages.save_success');
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // 記憶體增長應該在合理範圍內（5MB以內）
        $this->assertLessThan(5 * 1024 * 1024, $memoryIncrease, 
            '語言切換導致過多記憶體使用');
    }

    /**
     * 測試語言切換的完整流程
     */
    public function test_complete_language_switching_flow(): void
    {
        // 1. 初始狀態檢查
        $this->assertEquals('zh_TW', config('app.locale'));
        
        // 2. 透過 URL 參數切換語言
        $response = $this->get('/?locale=en');
        $response->assertSuccessful();
        $this->assertEquals('en', App::getLocale());
        $this->assertEquals('en', Session::get('locale'));
        
        // 3. 建立使用者並登入
        $user = $this->createUserWithLocale('zh_TW');
        $this->actingAs($user);
        
        // 4. 使用者語言偏好應該覆蓋 Session
        $response = $this->get('/');
        $response->assertSuccessful();
        $this->assertEquals('zh_TW', App::getLocale());
        
        // 5. URL 參數應該有最高優先權
        $response = $this->get('/?locale=en');
        $response->assertSuccessful();
        $this->assertEquals('en', App::getLocale());
        
        // 6. 使用者偏好應該已更新
        $this->assertEquals('en', $user->fresh()->locale);
        
        // 7. 登出後應該使用 Session 設定
        auth()->logout();
        $response = $this->get('/');
        $response->assertSuccessful();
        $this->assertEquals('en', App::getLocale());
    }
}