<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use App\Livewire\Admin\Activities\ActivityExport;
use App\Models\Activity;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Illuminate\Support\Facades\Storage;

/**
 * 活動記錄匯出元件功能測試
 */
class ActivityExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立權限
        $exportPermission = Permission::create([
            'name' => 'activity_logs.export',
            'display_name' => '匯出活動記錄',
            'module' => 'activity_logs',
        ]);

        // 建立管理員角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
        ]);
        
        $this->adminRole->permissions()->attach($exportPermission->id);

        // 建立管理員使用者
        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'name' => '管理員',
            'email' => 'admin@example.com',
        ]);
        
        $this->adminUser->roles()->attach($this->adminRole->id);
        
        // 設定假的儲存磁碟
        Storage::fake('local');
    }

    /**
     * 測試元件初始化
     */
    public function test_component_initialization(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ActivityExport::class)
            ->assertSet('exportFormat', 'csv')
            ->assertSet('timeRange', '7d')
            ->assertSet('includeUserDetails', true)
            ->assertSet('includeProperties', true)
            ->assertSet('includeRelatedData', false)
            ->assertSet('isExporting', false)
            ->assertViewIs('livewire.admin.activities.activity-export');
    }

    /**
     * 測試權限檢查
     */
    public function test_permission_check(): void
    {
        // 建立沒有權限的使用者
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(ActivityExport::class);
    }

    /**
     * 測試匯出格式選擇
     */
    public function test_export_format_selection(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ActivityExport::class)
            ->set('exportFormat', 'json')
            ->assertSet('exportFormat', 'json')
            ->set('exportFormat', 'pdf')
            ->assertSet('exportFormat', 'pdf');
    }

    /**
     * 測試時間範圍設定
     */
    public function test_time_range_settings(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityExport::class);

        // 測試預設時間範圍
        $component->assertSet('timeRange', '7d');

        // 測試切換到自訂範圍
        $component->set('timeRange', 'custom')
            ->assertSet('timeRange', 'custom');

        // 測試自訂日期設定
        $component->set('dateFrom', '2024-01-01')
            ->set('dateTo', '2024-01-31')
            ->assertSet('dateFrom', '2024-01-01')
            ->assertSet('dateTo', '2024-01-31');
    }

    /**
     * 測試篩選條件設定
     */
    public function test_filter_settings(): void
    {
        $this->actingAs($this->adminUser);

        // 建立測試使用者
        $testUser = User::factory()->create(['name' => '測試使用者']);

        Livewire::test(ActivityExport::class)
            ->set('userFilter', $testUser->id)
            ->set('typeFilter', 'login')
            ->set('moduleFilter', 'auth')
            ->set('resultFilter', 'success')
            ->set('ipFilter', '192.168.1.1')
            ->set('riskLevelFilter', 'high')
            ->set('securityEventsOnly', true)
            ->assertSet('userFilter', $testUser->id)
            ->assertSet('typeFilter', 'login')
            ->assertSet('moduleFilter', 'auth')
            ->assertSet('resultFilter', 'success')
            ->assertSet('ipFilter', '192.168.1.1')
            ->assertSet('riskLevelFilter', 'high')
            ->assertSet('securityEventsOnly', true);
    }

    /**
     * 測試匯出選項設定
     */
    public function test_export_options(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ActivityExport::class)
            ->set('includeUserDetails', false)
            ->set('includeProperties', false)
            ->set('includeRelatedData', true)
            ->assertSet('includeUserDetails', false)
            ->assertSet('includeProperties', false)
            ->assertSet('includeRelatedData', true);
    }

    /**
     * 測試統計資訊更新
     */
    public function test_statistics_update(): void
    {
        $this->actingAs($this->adminUser);

        // 建立測試活動記錄
        Activity::factory()->count(10)->create([
            'user_id' => $this->adminUser->id,
            'created_at' => now()->subDays(3),
        ]);

        $component = Livewire::test(ActivityExport::class);

        // 驗證統計資訊已更新
        $this->assertGreaterThan(0, $component->get('totalRecords'));
        $this->assertGreaterThan(0, $component->get('estimatedSize'));
        $this->assertNotEmpty($component->get('estimatedTime'));
    }

    /**
     * 測試重設篩選條件
     */
    public function test_reset_filters(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ActivityExport::class)
            ->set('userFilter', '1')
            ->set('typeFilter', 'login')
            ->set('moduleFilter', 'auth')
            ->set('resultFilter', 'success')
            ->set('securityEventsOnly', true)
            ->call('resetFilters')
            ->assertSet('userFilter', '')
            ->assertSet('typeFilter', '')
            ->assertSet('moduleFilter', '')
            ->assertSet('resultFilter', '')
            ->assertSet('securityEventsOnly', false)
            ->assertSet('timeRange', '7d');
    }

    /**
     * 測試小量資料直接匯出
     */
    public function test_direct_export_small_data(): void
    {
        $this->actingAs($this->adminUser);

        // 建立少量測試資料
        Activity::factory()->count(5)->create([
            'user_id' => $this->adminUser->id,
            'type' => 'test_action',
            'description' => '測試操作',
        ]);

        $component = Livewire::test(ActivityExport::class)
            ->set('exportFormat', 'csv')
            ->call('startExport');

        // 驗證匯出狀態
        $component->assertSet('isExporting', false)
            ->assertSet('exportProgress', 100)
            ->assertNotEmpty('downloadUrl');

        // 驗證通知
        $component->assertDispatched('notify', function ($event) {
            return $event['type'] === 'success' && str_contains($event['message'], '匯出完成');
        });
    }

    /**
     * 測試匯出設定驗證
     */
    public function test_export_validation(): void
    {
        $this->actingAs($this->adminUser);

        // 測試無效的日期範圍
        Livewire::test(ActivityExport::class)
            ->set('timeRange', 'custom')
            ->set('dateFrom', '2024-01-31')
            ->set('dateTo', '2024-01-01')
            ->call('startExport')
            ->assertDispatched('notify', function ($event) {
                return $event['type'] === 'error' && str_contains($event['message'], '開始日期不能晚於結束日期');
            });

        // 測試空的日期範圍
        Livewire::test(ActivityExport::class)
            ->set('timeRange', 'custom')
            ->set('dateFrom', '')
            ->set('dateTo', '')
            ->call('startExport')
            ->assertDispatched('notify', function ($event) {
                return $event['type'] === 'error' && str_contains($event['message'], '請選擇有效的日期範圍');
            });
    }

    /**
     * 測試記錄數量限制
     */
    public function test_record_limit_check(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityExport::class);
        
        // 設定較小的限制進行測試
        $component->set('maxRecords', 3);

        // 建立超過限制的資料
        Activity::factory()->count(5)->create([
            'user_id' => $this->adminUser->id,
        ]);

        $component->call('startExport')
            ->assertDispatched('notify', function ($event) {
                return $event['type'] === 'error' && str_contains($event['message'], '超過限制');
            });
    }

    /**
     * 測試匯出歷史功能
     */
    public function test_export_history(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityExport::class);

        // 模擬匯出歷史
        $component->set('exportHistory', [
            [
                'filename' => 'test_export.csv',
                'format' => 'csv',
                'record_count' => 100,
                'file_size' => 1024,
                'completed_at' => now(),
            ]
        ]);

        // 測試清除歷史
        $component->call('clearExportHistory')
            ->assertSet('exportHistory', [])
            ->assertDispatched('notify', function ($event) {
                return $event['type'] === 'success' && str_contains($event['message'], '匯出歷史已清除');
            });
    }

    /**
     * 測試計算屬性
     */
    public function test_computed_properties(): void
    {
        $this->actingAs($this->adminUser);

        // 建立測試使用者和活動
        $testUser = User::factory()->create(['name' => '測試使用者']);
        Activity::factory()->create([
            'user_id' => $testUser->id,
            'type' => 'login',
            'module' => 'auth',
        ]);

        $component = Livewire::test(ActivityExport::class);

        // 測試使用者選項
        $userOptions = $component->get('userOptions');
        $this->assertIsArray($userOptions);
        $this->assertArrayHasKey($testUser->id, $userOptions);

        // 測試類型選項
        $typeOptions = $component->get('typeOptions');
        $this->assertIsArray($typeOptions);
        $this->assertArrayHasKey('login', $typeOptions);

        // 測試模組選項
        $moduleOptions = $component->get('moduleOptions');
        $this->assertIsArray($moduleOptions);
        $this->assertArrayHasKey('auth', $moduleOptions);
    }

    /**
     * 測試檔案大小格式化
     */
    public function test_file_size_formatting(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityExport::class);

        // 測試不同大小的格式化
        $this->assertEquals('0 B', $component->formatFileSize(0));
        $this->assertEquals('1.0 KB', $component->formatFileSize(1024));
        $this->assertEquals('1.0 MB', $component->formatFileSize(1024 * 1024));
        $this->assertEquals('1.0 GB', $component->formatFileSize(1024 * 1024 * 1024));
    }

    /**
     * 測試取消匯出功能
     */
    public function test_cancel_export(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityExport::class)
            ->set('isExporting', true)
            ->set('exportJobId', 'test-job-id')
            ->call('cancelExport')
            ->assertSet('isExporting', false)
            ->assertSet('exportProgress', 0)
            ->assertSet('exportStatus', '')
            ->assertSet('exportJobId', null)
            ->assertDispatched('notify', function ($event) {
                return $event['type'] === 'info' && str_contains($event['message'], '匯出已取消');
            });
    }

    /**
     * 測試下載匯出檔案
     */
    public function test_download_export(): void
    {
        $this->actingAs($this->adminUser);

        // 測試沒有下載連結的情況
        Livewire::test(ActivityExport::class)
            ->set('downloadUrl', null)
            ->call('downloadExport')
            ->assertDispatched('notify', function ($event) {
                return $event['type'] === 'error' && str_contains($event['message'], '下載連結不存在');
            });

        // 測試有下載連結的情況
        Livewire::test(ActivityExport::class)
            ->set('downloadUrl', 'http://example.com/test.csv')
            ->call('downloadExport')
            ->assertDispatched('download-file');
    }
}