<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use App\Models\SettingChange;
use App\Models\SettingBackup;
use App\Services\SettingsSecurityService;
use App\Http\Middleware\SettingsAccessControl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 設定安全控制測試
 */
class SettingsSecurityControlTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected SettingsSecurityService $securityService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立角色
        $superAdminRole = \App\Models\Role::create([
            'name' => 'super_admin',
            'display_name' => 'Super Administrator',
            'description' => 'Super Administrator with all permissions',
        ]);
        
        // 建立權限
        $settingsPermission = \App\Models\Permission::create([
            'name' => 'settings.manage',
            'display_name' => 'Manage Settings',
            'description' => 'Can manage system settings',
        ]);
        
        $sensitivePermission = \App\Models\Permission::create([
            'name' => 'settings.manage_sensitive',
            'display_name' => 'Manage Sensitive Settings',
            'description' => 'Can manage sensitive system settings',
        ]);
        
        $backupPermission = \App\Models\Permission::create([
            'name' => 'settings.backup',
            'display_name' => 'Backup Settings',
            'description' => 'Can backup and restore settings',
        ]);
        
        // 將權限分配給角色
        $superAdminRole->permissions()->attach([
            $settingsPermission->id,
            $sensitivePermission->id,
            $backupPermission->id,
        ]);
        
        // 建立測試使用者
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('super_admin');
        
        $this->regularUser = User::factory()->create();
        
        $this->securityService = app(SettingsSecurityService::class);
    }

    /**
     * 測試敏感設定加密儲存
     */
    public function test_sensitive_settings_are_encrypted(): void
    {
        $sensitiveKey = 'integration.stripe_secret_key';
        $sensitiveValue = 'sk_test_123456789';
        
        // 測試加密儲存
        $result = $this->securityService->secureStoreSetting($sensitiveKey, $sensitiveValue);
        
        $this->assertTrue($result['success']);
        $this->assertTrue($result['encrypted']);
        $this->assertNotEquals($sensitiveValue, $result['stored_value']);
        
        // 測試解密讀取
        $decryptedValue = $this->securityService->secureReadSetting($sensitiveKey, $result['stored_value']);
        $this->assertEquals($sensitiveValue, $decryptedValue);
    }

    /**
     * 測試非敏感設定不加密
     */
    public function test_non_sensitive_settings_are_not_encrypted(): void
    {
        $normalKey = 'app.name';
        $normalValue = 'Test Application';
        
        // 測試不加密儲存
        $result = $this->securityService->secureStoreSetting($normalKey, $normalValue);
        
        $this->assertTrue($result['success']);
        $this->assertFalse($result['encrypted']);
        $this->assertEquals($normalValue, $result['stored_value']);
    }

    /**
     * 測試設定變更審計日誌
     */
    public function test_setting_change_audit_logging(): void
    {
        $this->actingAs($this->adminUser);
        
        $settingKey = 'security.password_min_length';
        $oldValue = 8;
        $newValue = 12;
        $reason = '提高密碼安全性';
        
        // 記錄設定變更
        $this->securityService->logSettingChange($settingKey, $oldValue, $newValue, $reason);
        
        // 驗證審計日誌記錄
        $this->assertDatabaseHas('setting_changes', [
            'setting_key' => $settingKey,
            'changed_by' => $this->adminUser->id,
            'reason' => $reason,
        ]);
        
        $change = SettingChange::where('setting_key', $settingKey)->first();
        $this->assertNotNull($change);
        $this->assertEquals($oldValue, $change->old_value);
        $this->assertEquals($newValue, $change->new_value);
        $this->assertNotNull($change->ip_address);
        $this->assertNotNull($change->user_agent);
    }

    /**
     * 測試加密備份建立
     */
    public function test_encrypted_backup_creation(): void
    {
        $this->actingAs($this->adminUser);
        
        // 建立一些測試設定
        Setting::factory()->create([
            'key' => 'integration.stripe_secret_key',
            'value' => json_encode('sk_test_123456789'),
            'category' => 'integration',
        ]);
        
        Setting::factory()->create([
            'key' => 'app.name',
            'value' => json_encode('Test App'),
            'category' => 'basic',
        ]);
        
        // 建立加密備份
        $result = $this->securityService->createEncryptedBackup(
            '測試加密備份',
            '測試用途',
            ['integration']
        );
        
        $this->assertTrue($result['success']);
        $this->assertTrue($result['encrypted']);
        $this->assertNotNull($result['backup_id']);
        
        // 驗證備份記錄
        $backup = SettingBackup::find($result['backup_id']);
        $this->assertNotNull($backup);
        $this->assertTrue($backup->is_encrypted);
        $this->assertEquals('encrypted', $backup->backup_type);
        $this->assertNotNull($backup->checksum);
    }

    /**
     * 測試備份完整性驗證
     */
    public function test_backup_integrity_verification(): void
    {
        $this->actingAs($this->adminUser);
        
        // 建立測試備份
        $backup = SettingBackup::create([
            'name' => '測試備份',
            'description' => '測試用途',
            'settings_data' => [
                ['key' => 'test.setting', 'value' => 'test_value']
            ],
            'created_by' => $this->adminUser->id,
            'is_encrypted' => false,
            'checksum' => hash('sha256', json_encode([
                ['key' => 'test.setting', 'value' => 'test_value']
            ], JSON_SORT_KEYS)),
        ]);
        
        // 驗證完整性
        $isValid = $this->securityService->verifyBackupIntegrity($backup);
        $this->assertTrue($isValid);
        
        // 修改資料後驗證應該失敗
        $backup->settings_data = [
            ['key' => 'test.setting', 'value' => 'modified_value']
        ];
        $backup->save();
        
        $isValid = $this->securityService->verifyBackupIntegrity($backup);
        $this->assertFalse($isValid);
    }

    /**
     * 測試設定存取權限檢查
     */
    public function test_setting_access_permission_check(): void
    {
        // 超級管理員應該可以存取所有設定
        $this->actingAs($this->adminUser);
        $this->assertTrue($this->securityService->checkSettingAccess('security.password_min_length'));
        $this->assertTrue($this->securityService->checkSettingAccess('integration.stripe_secret_key'));
        
        // 一般使用者應該無法存取設定
        $this->actingAs($this->regularUser);
        $this->assertFalse($this->securityService->checkSettingAccess('security.password_min_length'));
        $this->assertFalse($this->securityService->checkSettingAccess('integration.stripe_secret_key'));
    }

    /**
     * 測試 IP 限制功能
     */
    public function test_ip_restriction_functionality(): void
    {
        // 設定允許的 IP
        Setting::factory()->create([
            'key' => 'security.allowed_ips',
            'value' => json_encode("192.168.1.100\n10.0.0.0/8"),
            'category' => 'security',
        ]);
        
        // 清除快取以確保設定生效
        SettingsAccessControl::clearIpCache();
        
        // 模擬不同 IP 的請求
        $middleware = new SettingsAccessControl();
        
        // 測試允許的 IP
        $request = $this->createRequestWithIp('192.168.1.100');
        $this->actingAs($this->adminUser);
        
        // 由於中介軟體需要完整的請求上下文，這裡主要測試 IP 檢查邏輯
        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('isIpAllowed');
        $method->setAccessible(true);
        
        $this->assertTrue($method->invoke($middleware, '192.168.1.100'));
        $this->assertTrue($method->invoke($middleware, '10.0.0.1'));
        $this->assertFalse($method->invoke($middleware, '203.0.113.1'));
    }

    /**
     * 測試審計日誌清理功能
     */
    public function test_audit_log_cleanup(): void
    {
        $this->actingAs($this->adminUser);
        
        // 建立一些舊的審計日誌
        SettingChange::factory()->create([
            'setting_key' => 'test.old.setting',
            'changed_by' => $this->adminUser->id,
            'created_at' => now()->subDays(100),
        ]);
        
        SettingChange::factory()->create([
            'setting_key' => 'test.recent.setting',
            'changed_by' => $this->adminUser->id,
            'created_at' => now()->subDays(10),
        ]);
        
        // 清理 30 天前的日誌
        $deletedCount = $this->securityService->cleanupAuditLogs(30);
        
        $this->assertEquals(1, $deletedCount);
        $this->assertDatabaseMissing('setting_changes', [
            'setting_key' => 'test.old.setting',
        ]);
        $this->assertDatabaseHas('setting_changes', [
            'setting_key' => 'test.recent.setting',
        ]);
    }

    /**
     * 測試安全報告生成
     */
    public function test_security_report_generation(): void
    {
        $this->actingAs($this->adminUser);
        
        // 建立一些測試資料
        Setting::factory()->create([
            'key' => 'integration.stripe_secret_key',
            'value' => json_encode('sk_test_123'),
            'category' => 'integration',
        ]);
        
        SettingChange::factory()->create([
            'setting_key' => 'security.password_min_length',
            'changed_by' => $this->adminUser->id,
            'created_at' => now()->subDays(3),
        ]);
        
        SettingBackup::factory()->create([
            'name' => '測試備份',
            'created_by' => $this->adminUser->id,
            'is_encrypted' => true,
        ]);
        
        // 生成安全報告
        $report = $this->securityService->generateSecurityReport();
        
        $this->assertArrayHasKey('generated_at', $report);
        $this->assertArrayHasKey('total_settings', $report);
        $this->assertArrayHasKey('encrypted_settings', $report);
        $this->assertArrayHasKey('recent_changes', $report);
        $this->assertArrayHasKey('backup_count', $report);
        $this->assertArrayHasKey('encrypted_backups', $report);
        
        $this->assertGreaterThan(0, $report['total_settings']);
        $this->assertGreaterThan(0, $report['recent_changes']);
        $this->assertGreaterThan(0, $report['backup_count']);
        $this->assertGreaterThan(0, $report['encrypted_backups']);
    }

    /**
     * 建立帶有指定 IP 的請求
     */
    protected function createRequestWithIp(string $ip)
    {
        $request = $this->app['request'];
        $request->server->set('REMOTE_ADDR', $ip);
        return $request;
    }
}
