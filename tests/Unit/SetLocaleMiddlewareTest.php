<?php

namespace Tests\Unit;

use Tests\MultilingualTestCase;
use Tests\Traits\MultilingualTestHelpers;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

/**
 * SetLocale 中介軟體測試
 * 
 * 測試語言設定中介軟體的各種功能
 */
class SetLocaleMiddlewareTest extends MultilingualTestCase
{
    use MultilingualTestHelpers;

    /**
     * SetLocale 中介軟體實例
     */
    protected SetLocale $middleware;

    /**
     * 設定測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->middleware = new SetLocale();
    }

    /**
     * 測試預設語言設定
     */
    public function test_default_locale_setting(): void
    {
        $request = Request::create('/');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        // 驗證預設語言設定
        $this->assertEquals('zh_TW', App::getLocale());
        $this->assertEquals('zh_TW', Session::get('locale'));
    }

    /**
     * 測試 URL 參數語言切換
     */
    public function test_url_parameter_locale_switching(): void
    {
        $request = Request::create('/?locale=en');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        // 驗證語言已切換
        $this->assertEquals('en', App::getLocale());
        $this->assertEquals('en', Session::get('locale'));
    }

    /**
     * 測試無效的 URL 參數語言
     */
    public function test_invalid_url_parameter_locale(): void
    {
        $request = Request::create('/?locale=invalid');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        // 應該使用預設語言
        $this->assertEquals('zh_TW', App::getLocale());
        $this->assertEquals('zh_TW', Session::get('locale'));
    }

    /**
     * 測試 Session 語言偏好
     */
    public function test_session_locale_preference(): void
    {
        // 設定 Session 語言偏好
        Session::put('locale', 'en');
        
        $request = Request::create('/');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        // 驗證使用 Session 中的語言
        $this->assertEquals('en', App::getLocale());
        $this->assertEquals('en', Session::get('locale'));
    }

    /**
     * 測試使用者語言偏好
     */
    public function test_user_locale_preference(): void
    {
        // 建立有語言偏好的使用者
        $user = $this->createUserWithLocale('en');
        $this->actingAs($user);
        
        $request = Request::create('/');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        // 驗證使用使用者的語言偏好
        $this->assertEquals('en', App::getLocale());
        $this->assertEquals('en', Session::get('locale'));
    }

    /**
     * 測試瀏覽器語言偵測
     */
    public function test_browser_language_detection(): void
    {
        // 清除 Session
        Session::forget('locale');
        
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'en-US,en;q=0.9,zh-TW;q=0.8');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        // 驗證偵測到英文
        $this->assertEquals('en', App::getLocale());
        $this->assertEquals('en', Session::get('locale'));
    }

    /**
     * 測試中文瀏覽器語言偵測
     */
    public function test_chinese_browser_language_detection(): void
    {
        // 清除 Session
        Session::forget('locale');
        
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'zh-TW,zh;q=0.9,en;q=0.8');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        // 驗證偵測到中文
        $this->assertEquals('zh_TW', App::getLocale());
        $this->assertEquals('zh_TW', Session::get('locale'));
    }

    /**
     * 測試語言優先順序：URL 參數 > Session > 使用者偏好 > 瀏覽器 > 預設
     */
    public function test_locale_priority_order(): void
    {
        // 設定使用者偏好為英文
        $user = $this->createUserWithLocale('en');
        $this->actingAs($user);
        
        // 設定 Session 為中文
        Session::put('locale', 'zh_TW');
        
        // 設定瀏覽器偏好為英文
        $request = Request::create('/?locale=en'); // URL 參數為英文
        $request->headers->set('Accept-Language', 'zh-TW,zh;q=0.9');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        // URL 參數應該有最高優先權
        $this->assertEquals('en', App::getLocale());
    }

    /**
     * 測試 Carbon 本地化設定
     */
    public function test_carbon_locale_setting(): void
    {
        $request = Request::create('/?locale=en');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        // 驗證 Carbon 語言設定
        $this->assertEquals('en', Carbon::getLocale());
        
        // 測試中文設定
        $request = Request::create('/?locale=zh_TW');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        $this->assertEquals('zh_TW', Carbon::getLocale());
    }

    /**
     * 測試使用者語言偏好更新
     */
    public function test_user_locale_preference_update(): void
    {
        // 建立中文偏好的使用者
        $user = $this->createUserWithLocale('zh_TW');
        $this->actingAs($user);
        
        // 透過 URL 參數切換到英文
        $request = Request::create('/?locale=en');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        // 驗證使用者偏好已更新
        $this->assertEquals('en', $user->fresh()->locale);
    }

    /**
     * 測試使用者語言偏好不重複更新
     */
    public function test_user_locale_preference_no_duplicate_update(): void
    {
        // 建立英文偏好的使用者
        $user = $this->createUserWithLocale('en');
        $this->actingAs($user);
        
        // 記錄更新前的時間戳
        $originalUpdatedAt = $user->updated_at;
        
        // 使用相同的語言
        $request = Request::create('/?locale=en');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        // 驗證使用者記錄沒有被不必要地更新
        $this->assertEquals($originalUpdatedAt, $user->fresh()->updated_at);
    }

    /**
     * 測試複雜的 Accept-Language 標頭解析
     */
    public function test_complex_accept_language_parsing(): void
    {
        Session::forget('locale');
        
        // 測試複雜的 Accept-Language 標頭
        $testCases = [
            'en-US,en;q=0.9,zh-TW;q=0.8,zh;q=0.7' => 'en',
            'zh-TW,zh;q=0.9,en-US;q=0.8,en;q=0.7' => 'zh_TW',
            'fr-FR,fr;q=0.9,en;q=0.8,zh-TW;q=0.7' => 'en', // 法文不支援，回退到英文
            'de-DE,de;q=0.9,fr;q=0.8' => 'zh_TW', // 都不支援，使用預設
            'zh,en' => 'zh_TW', // 簡化格式
            'en' => 'en' // 最簡格式
        ];
        
        foreach ($testCases as $acceptLanguage => $expectedLocale) {
            $request = Request::create('/');
            $request->headers->set('Accept-Language', $acceptLanguage);
            
            $this->middleware->handle($request, function ($req) {
                return response('OK');
            });
            
            $this->assertEquals($expectedLocale, App::getLocale(), 
                "Accept-Language: {$acceptLanguage} 應該解析為 {$expectedLocale}");
            
            // 重置狀態
            Session::forget('locale');
        }
    }

    /**
     * 測試空的 Accept-Language 標頭
     */
    public function test_empty_accept_language_header(): void
    {
        Session::forget('locale');
        
        $request = Request::create('/');
        $request->headers->remove('Accept-Language');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        // 應該使用預設語言
        $this->assertEquals('zh_TW', App::getLocale());
    }

    /**
     * 測試中介軟體在不同請求方法下的行為
     */
    public function test_middleware_behavior_with_different_request_methods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        
        foreach ($methods as $method) {
            $request = Request::create('/?locale=en', $method);
            
            $this->middleware->handle($request, function ($req) {
                return response('OK');
            });
            
            $this->assertEquals('en', App::getLocale(), 
                "語言設定在 {$method} 請求中應該正常工作");
        }
    }

    /**
     * 測試中介軟體效能
     */
    public function test_middleware_performance(): void
    {
        $startTime = microtime(true);
        
        // 執行多次中介軟體處理
        for ($i = 0; $i < 100; $i++) {
            $request = Request::create('/?locale=' . ($i % 2 === 0 ? 'zh_TW' : 'en'));
            
            $this->middleware->handle($request, function ($req) {
                return response('OK');
            });
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // 驗證執行時間在合理範圍內（1秒內）
        $this->assertLessThan(1.0, $executionTime, '中介軟體執行時間過長');
    }

    /**
     * 測試並發請求下的語言設定
     */
    public function test_concurrent_locale_setting(): void
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
            $request = Request::create('/?locale=' . $requestData['locale']);
            
            $this->middleware->handle($request, function ($req) {
                return response('OK');
            });
            
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
     * 測試語言設定的邊界條件
     */
    public function test_locale_setting_edge_cases(): void
    {
        $edgeCases = [
            ['locale' => '', 'expected' => 'zh_TW'], // 空字串
            ['locale' => null, 'expected' => 'zh_TW'], // null
            ['locale' => 'ZH_TW', 'expected' => 'zh_TW'], // 大寫
            ['locale' => 'zh_tw', 'expected' => 'zh_TW'], // 小寫
            ['locale' => 'zh-TW', 'expected' => 'zh_TW'], // 連字號
            ['locale' => 'zh_TW_extra', 'expected' => 'zh_TW'], // 額外字元
        ];
        
        foreach ($edgeCases as $case) {
            $request = Request::create('/?locale=' . $case['locale']);
            
            $this->middleware->handle($request, function ($req) {
                return response('OK');
            });
            
            $this->assertEquals($case['expected'], App::getLocale(),
                "語言參數 '{$case['locale']}' 應該解析為 '{$case['expected']}'");
        }
    }

    /**
     * 測試中介軟體錯誤處理
     */
    public function test_middleware_error_handling(): void
    {
        // 建立一個會拋出異常的請求處理器
        $request = Request::create('/?locale=en');
        
        try {
            $this->middleware->handle($request, function ($req) {
                throw new \Exception('Test exception');
            });
        } catch (\Exception $e) {
            // 即使發生異常，語言設定也應該已經完成
            $this->assertEquals('en', App::getLocale());
            $this->assertEquals('Test exception', $e->getMessage());
        }
    }
}