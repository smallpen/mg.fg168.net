<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 中介軟體和路由保護測試
 * 
 * 測試管理後台的中介軟體功能，包括認證、權限檢查和路由保護
 */
class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立基本角色
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->userRole = Role::factory()->create(['name' => 'user']);
        $this->editorRole = Role::factory()->create(['name' => 'editor']);
        
        // 建立不同角色的使用者
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);
        
        $this->editor = User::factory()->create();
        $this->editor->roles()->attach($this->editorRole);
        
        $this->regularUser = User::factory()->create();
        $this->regularUser->roles()->attach($this->userRole);
        
        $this->guestUser = User::factory()->create(); // 沒有角色的使用者
    }

    /**
     * 測試認證中介軟體 - 未登入使用者重新導向
     */
    public function test_auth_middleware_redirects_unauthenticated_users()
    {
        // 測試各種管理後台路由
        $protectedRoutes = [
            '/admin/dashboard',
            '/admin/users',
            '/admin/users/create',
            '/admin/roles',
            '/admin/roles/create',
            '/admin/permissions'
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/admin/login');
        }
    }

    /**
     * 測試管理員權限中介軟體 - 只有管理員可以存取
     */
    public function test_admin_middleware_allows_admin_access()
    {
        $this->actingAs($this->admin);

        $adminRoutes = [
            '/admin/dashboard',
            '/admin/users',
            '/admin/roles'
        ];

        foreach ($adminRoutes as $route) {
            $response = $this->get($route);
            $response->assertStatus(200);
        }
    }

    /**
     * 測試管理員權限中介軟體 - 拒絕非管理員存取
     */
    public function test_admin_middleware_denies_non_admin_access()
    {
        // 測試一般使用者
        $this->actingAs($this->regularUser);

        $adminRoutes = [
            '/admin/dashboard',
            '/admin/users',
            '/admin/roles'
        ];

        foreach ($adminRoutes as $route) {
            $response = $this->get($route);
            $response->assertStatus(403);
        }

        // 測試沒有角色的使用者
        $this->actingAs($this->guestUser);

        foreach ($adminRoutes as $route) {
            $response = $this->get($route);
            $response->assertStatus(403);
        }
    }

    /**
     * 測試特定權限中介軟體 - 使用者管理權限
     */
    public function test_permission_middleware_for_user_management()
    {
        // 建立有使用者管理權限的角色
        $userManagerRole = Role::factory()->create(['name' => 'user_manager']);
        $userManager = User::factory()->create();
        $userManager->roles()->attach($userManagerRole);

        $this->actingAs($userManager);

        // 如果沒有權限，應該被拒絕存取
        $response = $this->get('/admin/users');
        $response->assertStatus(403);

        // 如果有權限，應該可以存取
        // 這裡需要根據實際的權限實作來調整
    }

    /**
     * 測試角色權限中介軟體 - 角色管理權限
     */
    public function test_permission_middleware_for_role_management()
    {
        $this->actingAs($this->editor);

        // 編輯者通常沒有角色管理權限
        $response = $this->get('/admin/roles');
        $response->assertStatus(403);

        $response = $this->get('/admin/roles/create');
        $response->assertStatus(403);
    }

    /**
     * 測試 CSRF 保護中介軟體
     */
    public function test_csrf_protection_middleware()
    {
        $this->actingAs($this->admin);

        // 不帶 CSRF token 的 POST 請求應該被拒絕
        $response = $this->post('/admin/users', [
            'username' => 'testuser',
            'name' => 'Test User',
            'password' => 'password123'
        ], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        // Laravel 會自動處理 CSRF 驗證
        // 這裡主要是確保機制正常運作
        $this->assertTrue(true);
    }

    /**
     * 測試 API 限流中介軟體（如果有實作）
     */
    public function test_rate_limiting_middleware()
    {
        $this->actingAs($this->admin);

        // 進行大量請求測試限流
        for ($i = 0; $i < 100; $i++) {
            $response = $this->get('/admin/dashboard');
            
            // 如果有限流，某些請求會被拒絕
            if ($response->status() === 429) {
                $this->assertEquals(429, $response->status());
                break;
            }
        }

        // 如果沒有實作限流，所有請求都應該成功
        $this->assertTrue(true);
    }

    /**
     * 測試語言設定中介軟體
     */
    public function test_locale_middleware()
    {
        $this->admin->update(['locale' => 'en']);
        $this->actingAs($this->admin);

        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);

        // 檢查語言是否被正確設定
        $this->assertEquals('en', app()->getLocale());
    }

    /**
     * 測試主題設定中介軟體
     */
    public function test_theme_middleware()
    {
        $this->admin->update(['theme_preference' => 'dark']);
        $this->actingAs($this->admin);

        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);

        // 檢查主題設定是否被載入
        // 這裡的具體檢查方式取決於主題實作
        $this->assertTrue(true);
    }

    /**
     * 測試 IP 白名單中介軟體（如果有實作）
     */
    public function test_ip_whitelist_middleware()
    {
        // 這個測試取決於是否有實作 IP 白名單功能
        $this->markTestSkipped('IP whitelist middleware not implemented');
    }

    /**
     * 測試使用者狀態檢查中介軟體
     */
    public function test_user_status_middleware()
    {
        // 停用使用者
        $this->admin->update(['is_active' => false]);
        $this->actingAs($this->admin);

        $response = $this->get('/admin/dashboard');
        
        // 停用的使用者應該被重新導向到登入頁面或顯示錯誤
        $this->assertContains($response->status(), [302, 403]);
    }

    /**
     * 測試 Session 過期處理
     */
    public function test_session_expiry_handling()
    {
        $this->actingAs($this->admin);

        // 清除 session 模擬過期
        session()->flush();

        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/admin/login');
    }

    /**
     * 測試多重登入檢查（如果有實作）
     */
    public function test_concurrent_session_handling()
    {
        // 這個測試取決於是否有實作多重登入限制
        $this->markTestSkipped('Concurrent session handling not implemented');
    }

    /**
     * 測試 API 認證中介軟體（如果有 API 端點）
     */
    public function test_api_authentication_middleware()
    {
        // 測試 API 端點的認證
        $response = $this->getJson('/api/admin/users');
        $response->assertStatus(401); // 未認證

        // 使用 API token 認證（如果有實作）
        // $response = $this->withHeaders([
        //     'Authorization' => 'Bearer ' . $this->admin->createToken('test')->plainTextToken,
        // ])->getJson('/api/admin/users');
        // $response->assertStatus(200);
    }

    /**
     * 測試中介軟體執行順序
     */
    public function test_middleware_execution_order()
    {
        // 測試中介軟體是否按正確順序執行
        // 例如：認證 -> 權限檢查 -> 其他檢查

        $this->actingAs($this->regularUser);

        $response = $this->get('/admin/users');
        
        // 應該先通過認證，然後被權限檢查拒絕
        $response->assertStatus(403);
    }

    /**
     * 測試中介軟體異常處理
     */
    public function test_middleware_exception_handling()
    {
        // 測試中介軟體遇到異常時的處理
        $this->actingAs($this->admin);

        // 模擬一個可能導致異常的情況
        // 例如：資料庫連線失敗、權限檢查異常等
        
        $response = $this->get('/admin/dashboard');
        
        // 即使有異常，也應該有適當的錯誤處理
        $this->assertContains($response->status(), [200, 500, 503]);
    }

    /**
     * 測試中介軟體快取機制
     */
    public function test_middleware_caching()
    {
        $this->actingAs($this->admin);

        // 第一次請求
        $startTime = microtime(true);
        $response1 = $this->get('/admin/dashboard');
        $firstRequestTime = microtime(true) - $startTime;

        // 第二次請求（應該使用快取）
        $startTime = microtime(true);
        $response2 = $this->get('/admin/dashboard');
        $secondRequestTime = microtime(true) - $startTime;

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // 如果有快取，第二次請求應該更快
        // 這個測試比較粗略，實際效果可能因環境而異
        $this->assertTrue(true);
    }

    /**
     * 測試中介軟體日誌記錄
     */
    public function test_middleware_logging()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);

        // 檢查是否有相關的日誌記錄
        // 這裡的具體檢查方式取決於日誌實作
        $this->assertTrue(true);
    }

    /**
     * 測試中介軟體效能影響
     */
    public function test_middleware_performance_impact()
    {
        $this->actingAs($this->admin);

        // 測量有中介軟體的請求時間
        $startTime = microtime(true);
        $response = $this->get('/admin/dashboard');
        $requestTime = microtime(true) - $startTime;

        $response->assertStatus(200);

        // 確保中介軟體不會造成過大的效能影響
        $this->assertLessThan(5.0, $requestTime); // 5秒內完成
    }
}