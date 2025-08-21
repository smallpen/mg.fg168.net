<?php

namespace Tests\Feature\Livewire\Admin\Roles;

use App\Livewire\Admin\Roles\RoleStatistics;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Mockery;

/**
 * RoleStatistics 元件功能測試
 * 
 * 測試角色統計元件的完整功能
 */
class RoleStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Role $adminRole;
    protected Role $testRole;
    protected array $testPermissions;
    protected RoleStatisticsService $mockStatisticsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立管理員角色和權限
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $viewPermission = Permission::factory()->create(['name' => 'roles.view']);
        $this->adminRole->permissions()->attach($viewPermission);
        
        // 建立管理員使用者
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);
        
        // 建立測試角色
        $this->testRole = Role::factory()->create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'description' => '用於測試的角色'
        ]);
        
        // 建立測試權限
        $this->testPermissions = [
            Permission::factory()->create(['name' => 'users.view', 'module' => 'users']),
            Permission::factory()->create(['name' => 'users.create', 'module' => 'users']),
            Permission::factory()->create(['name' => 'posts.view', 'module' => 'posts']),
            Permission::factory()->create(['name' => 'posts.create', 'module' => 'posts']),
        ];
        
        // 為測試角色分配權限和使用者
        $this->testRole->permissions()->attach(collect($this->testPermissions)->pluck('id'));
        $users = User::factory()->count(5)->create();
        $this->testRole->users()->attach($users);
        
        // 建立模擬統計服務
        $this->mockStatisticsService = Mockery::mock(RoleStatisticsService::class);
        $this->app->instance(RoleStatisticsService::class, $this->mockStatisticsService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function 元件初始狀態正確()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(RoleStatistics::class);

        $component->assertSet('role', null)
                 ->assertSet('statistics', [])
                 ->assertSet('permissionDistribution', [])
                 ->assertSet('systemStatistics', [])
                 ->assertSet('usageTrends', [])
                 ->assertSet('mode', 'system')
                 ->assertSet('trendDays', 30)
                 ->assertSet('loading', false)
                 ->assertSet('autoRefresh', false);
    }

    /** @test */
    public function 系統模式正確載入統計資料()
    {
        $this->actingAs($this->admin);

        // 設定模擬服務回傳值
        $systemStats = [
            'overview' => [
                'total_roles' => 10,
                'active_roles' => 8,
                'system_roles' => 3,
                'custom_roles' => 7,
            ]
        ];

        $permissionDistribution = [
            'chart_data' => [
                'labels' => ['users', 'posts', 'roles'],
                'data' => [15, 10, 8],
                'colors' => ['#3B82F6', '#10B981', '#F59E0B']
            ]
        ];

        $usageTrends = [
            'role_creations' => [
                'chart_data' => [
                    'labels' => ['2024-01-01', '2024-01-02'],
                    'data' => [2, 3]
                ]
            ],
            'role_assignments' => [
                'chart_data' => [
                    'data' => [5, 7]
                ]
            ]
        ];

        $this->mockStatisticsService
            ->shouldReceive('getSystemRoleStatistics')
            ->once()
            ->andReturn($systemStats);

        $this->mockStatisticsService
            ->shouldReceive('getPermissionDistribution')
            ->once()
            ->andReturn($permissionDistribution);

        $this->mockStatisticsService
            ->shouldReceive('getRoleUsageTrends')
            ->with(30)
            ->once()
            ->andReturn($usageTrends);

        $component = Livewire::test(RoleStatistics::class, ['mode' => 'system']);

        $component->assertSet('mode', 'system')
                 ->assertSet('systemStatistics', $systemStats)
                 ->assertSet('permissionDistribution', $permissionDistribution)
                 ->assertSet('usageTrends', $usageTrends);
    }

    /** @test */
    public function 角色模式正確載入統計資料()
    {
        $this->actingAs($this->admin);

        // 設定模擬服務回傳值
        $roleStats = [
            'basic' => [
                'user_count' => 5,
                'direct_permission_count' => 4,
                'inherited_permission_count' => 2,
                'total_permission_count' => 6,
            ]
        ];

        $permissionDistribution = [
            'chart_data' => [
                'labels' => ['users', 'posts'],
                'data' => [2, 2],
                'colors' => ['#3B82F6', '#10B981']
            ]
        ];

        $this->mockStatisticsService
            ->shouldReceive('getRoleStatistics')
            ->with($this->testRole)
            ->once()
            ->andReturn($roleStats);

        $this->mockStatisticsService
            ->shouldReceive('getPermissionDistribution')
            ->with($this->testRole)
            ->once()
            ->andReturn($permissionDistribution);

        $component = Livewire::test(RoleStatistics::class, [
            'role' => $this->testRole,
            'mode' => 'role'
        ]);

        $component->assertSet('mode', 'role')
                 ->assertSet('role.id', $this->testRole->id)
                 ->assertSet('statistics', $roleStats)
                 ->assertSet('permissionDistribution', $permissionDistribution);
    }

    /** @test */
    public function 重新整理統計資料功能正常()
    {
        $this->actingAs($this->admin);

        $this->mockStatisticsService
            ->shouldReceive('getSystemRoleStatistics')
            ->twice()
            ->andReturn(['overview' => ['total_roles' => 10]]);

        $this->mockStatisticsService
            ->shouldReceive('getPermissionDistribution')
            ->twice()
            ->andReturn(['chart_data' => []]);

        $this->mockStatisticsService
            ->shouldReceive('getRoleUsageTrends')
            ->twice()
            ->andReturn(['role_creations' => ['chart_data' => []]]);

        $component = Livewire::test(RoleStatistics::class, ['mode' => 'system']);

        $component->call('refreshStatistics')
                 ->assertDispatched('statistics-refreshed');
    }

    /** @test */
    public function 清除快取並重新載入功能正常()
    {
        $this->actingAs($this->admin);

        $this->mockStatisticsService
            ->shouldReceive('getSystemRoleStatistics')
            ->twice()
            ->andReturn(['overview' => ['total_roles' => 10]]);

        $this->mockStatisticsService
            ->shouldReceive('getPermissionDistribution')
            ->twice()
            ->andReturn(['chart_data' => []]);

        $this->mockStatisticsService
            ->shouldReceive('getRoleUsageTrends')
            ->twice()
            ->andReturn(['role_creations' => ['chart_data' => []]]);

        $this->mockStatisticsService
            ->shouldReceive('clearAllCache')
            ->once();

        $component = Livewire::test(RoleStatistics::class, ['mode' => 'system']);

        $component->call('clearCacheAndRefresh');
    }

    /** @test */
    public function 角色模式清除快取功能正常()
    {
        $this->actingAs($this->admin);

        $this->mockStatisticsService
            ->shouldReceive('getRoleStatistics')
            ->twice()
            ->andReturn(['basic' => ['user_count' => 5]]);

        $this->mockStatisticsService
            ->shouldReceive('getPermissionDistribution')
            ->twice()
            ->andReturn(['chart_data' => []]);

        $this->mockStatisticsService
            ->shouldReceive('clearRoleCache')
            ->with($this->testRole)
            ->once();

        $component = Livewire::test(RoleStatistics::class, [
            'role' => $this->testRole,
            'mode' => 'role'
        ]);

        $component->call('clearCacheAndRefresh');
    }

    /** @test */
    public function 切換顯示模式功能正常()
    {
        $this->actingAs($this->admin);

        // 設定兩種模式的模擬回傳值
        $this->mockStatisticsService
            ->shouldReceive('getSystemRoleStatistics')
            ->once()
            ->andReturn(['overview' => ['total_roles' => 10]]);

        $this->mockStatisticsService
            ->shouldReceive('getPermissionDistribution')
            ->twice()
            ->andReturn(['chart_data' => []]);

        $this->mockStatisticsService
            ->shouldReceive('getRoleUsageTrends')
            ->once()
            ->andReturn(['role_creations' => ['chart_data' => []]]);

        $this->mockStatisticsService
            ->shouldReceive('getRoleStatistics')
            ->once()
            ->andReturn(['basic' => ['user_count' => 5]]);

        $component = Livewire::test(RoleStatistics::class, [
            'role' => $this->testRole,
            'mode' => 'role'
        ]);

        $component->call('switchMode', 'system')
                 ->assertSet('mode', 'system');
    }

    /** @test */
    public function 設定趨勢統計天數功能正常()
    {
        $this->actingAs($this->admin);

        $this->mockStatisticsService
            ->shouldReceive('getSystemRoleStatistics')
            ->once()
            ->andReturn(['overview' => ['total_roles' => 10]]);

        $this->mockStatisticsService
            ->shouldReceive('getPermissionDistribution')
            ->once()
            ->andReturn(['chart_data' => []]);

        $this->mockStatisticsService
            ->shouldReceive('getRoleUsageTrends')
            ->with(30)
            ->once()
            ->andReturn(['role_creations' => ['chart_data' => []]]);

        $this->mockStatisticsService
            ->shouldReceive('getRoleUsageTrends')
            ->with(7)
            ->once()
            ->andReturn(['role_creations' => ['chart_data' => []]]);

        $component = Livewire::test(RoleStatistics::class, ['mode' => 'system']);

        $component->call('setTrendDays', 7)
                 ->assertSet('trendDays', 7);
    }

    /** @test */
    public function 自動重新整理切換功能正常()
    {
        $this->actingAs($this->admin);

        $this->mockStatisticsService
            ->shouldReceive('getSystemRoleStatistics')
            ->once()
            ->andReturn(['overview' => ['total_roles' => 10]]);

        $this->mockStatisticsService
            ->shouldReceive('getPermissionDistribution')
            ->once()
            ->andReturn(['chart_data' => []]);

        $this->mockStatisticsService
            ->shouldReceive('getRoleUsageTrends')
            ->once()
            ->andReturn(['role_creations' => ['chart_data' => []]]);

        $component = Livewire::test(RoleStatistics::class, ['mode' => 'system']);

        // 開啟自動重新整理
        $component->call('toggleAutoRefresh')
                 ->assertSet('autoRefresh', true)
                 ->assertDispatched('start-auto-refresh');

        // 關閉自動重新整理
        $component->call('toggleAutoRefresh')
                 ->assertSet('autoRefresh', false)
                 ->assertDispatched('stop-auto-refresh');
    }

    /** @test */
    public function 自動重新整理事件處理正常()
    {
        $this->actingAs($this->admin);

        $this->mockStatisticsService
            ->shouldReceive('getSystemRoleStatistics')
            ->twice()
            ->andReturn(['overview' => ['total_roles' => 10]]);

        $this->mockStatisticsService
            ->shouldReceive('getPermissionDistribution')
            ->twice()
            ->andReturn(['chart_data' => []]);

        $this->mockStatisticsService
            ->shouldReceive('getRoleUsageTrends')
            ->twice()
            ->andReturn(['role_creations' => ['chart_data' => []]]);

        $component = Livewire::test(RoleStatistics::class, ['mode' => 'system']);

        $component->set('autoRefresh', true)
                 ->dispatch('auto-refresh-tick');
    }

    /** @test */
    public function 角色更新事件處理正常()
    {
        $this->actingAs($this->admin);

        $this->mockStatisticsService
            ->shouldReceive('getRoleStatistics')
            ->twice()
            ->andReturn(['basic' => ['user_count' => 5]]);

        $this->mockStatisticsService
            ->shouldReceive('getPermissionDistribution')
            ->twice()
            ->andReturn(['chart_data' => []]);

        $component = Livewire::test(RoleStatistics::class, [
            'role' => $this->testRole,
            'mode' => 'role'
        ]);

        $component->dispatch('role-updated', $this->testRole->id);
    }

    /** @test */
    public function 權限更新事件處理正常()
    {
        $this->actingAs($this->admin);

        $this->mockStatisticsService
            ->shouldReceive('getRoleStatistics')
            ->twice()
            ->andReturn(['basic' => ['user_count' => 5]]);

        $this->mockStatisticsService
            ->shouldReceive('getPermissionDistribution')
            ->twice()
            ->andReturn(['chart_data' => []]);

        $component = Livewire::test(RoleStatistics::class, [
            'role' => $this->testRole,
            'mode' => 'role'
        ]);

        $component->dispatch('permissions-updated', $this->testRole->id);
    }

    /** @test */
    public function 角色資訊計算屬性正確()
    {
        $this->actingAs($this->admin);

        $this->mockStatisticsService
            ->shouldReceive('getRoleStatistics')
            ->once()
            ->andReturn(['basic' => ['user_count' => 5]]);

        $this->mockStatisticsService
            ->shouldReceive('getPermissionDistribution')
            ->once()
            ->andReturn(['chart_data' => []]);

        $component = Livewire::test(RoleStatistics::class, [
            'role' => $this->testRole,
            'mode' => 'role'
        ]);

        $roleInfo = $component->get('roleInfo');

        $this->assertEquals($this->testRole->id, $roleInfo['id']);
        $this->assertEquals($this->testRole->name, $roleInfo['name']);
        $this->assertEquals($this->testRole->display_name, $roleInfo['display_name']);
        $this->assertEquals($this->testRole->description, $roleInfo['description']);
    }

    /** @test */
    public function 權限分佈圖表資料正確()
    {
        $this->actingAs($this->admin);

        $permissionDistribution = [
            'chart_data' => [
                'labels' => ['users', 'posts'],
                'data' => [2, 2],
                'colors' => ['#3B82F6', '#10B981']
            ]
        ];

        $this->mockStatisticsService
            ->shouldReceive('getSystemRoleStatistics')
            ->once()
            ->andReturn(['overview' => ['total_roles' => 10]]);

        $this->mockStatisticsService
            ->shouldReceive('getPermissionDistribution')
            ->once()
            ->andReturn($permissionDistribution);

        $this->mockStatisticsService
            ->shouldReceive('getRoleUsageTrends')
            ->once()
            ->andReturn(['role_creations' => ['chart_data' => []]]);

        $component = Livewire::test(RoleStatistics::class, ['mode' => 'system']);

        $chartData = $component->get('permissionChartData');

        $this->assertEquals(['users', 'posts'], $chartData['labels']);
        $this->assertEquals([2, 2], $chartData['datasets'][0]['data']);
        $this->assertEquals(['#3B82F6', '#10B981'], $chartData['datasets'][0]['backgroundColor']);
    }

    /** @test */
    public function 使用趨勢圖表資料正確()
    {
        $this->actingAs($this->admin);

        $usageTrends = [
            'role_creations' => [
                'chart_data' => [
                    'labels' => ['2024-01-01', '2024-01-02'],
                    'data' => [2, 3]
                ]
            ],
            'role_assignments' => [
                'chart_data' => [
                    'data' => [5, 7]
                ]
            ]
        ];

        $this->mockStatisticsService
            ->shouldReceive('getSystemRoleStatistics')
            ->once()
            ->andReturn(['overview' => ['total_roles' => 10]]);

        $this->mockStatisticsService
            ->shouldReceive('getPermissionDistribution')
            ->once()
            ->andReturn(['chart_data' => []]);

        $this->mockStatisticsService
            ->shouldReceive('getRoleUsageTrends')
            ->once()
            ->andReturn($usageTrends);

        $component = Livewire::test(RoleStatistics::class, ['mode' => 'system']);

        $chartData = $component->get('usageTrendChartData');

        $this->assertEquals(['2024-01-01', '2024-01-02'], $chartData['labels']);
        $this->assertEquals([2, 3], $chartData['datasets'][0]['data']);
        $this->assertEquals([5, 7], $chartData['datasets'][1]['data']);
    }

    /** @test */
    public function 統計卡片資料正確_系統模式()
    {
        $this->actingAs($this->admin);

        $systemStats = [
            'overview' => [
                'total_roles' => 15,
                'active_roles' => 12,
                'system_roles' => 3,
                'custom_roles' => 12,
            ]
        ];

        $this->mockStatisticsService
            ->shouldReceive('getSystemRoleStatistics')
            ->once()
            ->andReturn($systemStats);

        $this->mockStatisticsService
            ->shouldReceive('getPermissionDistribution')
            ->once()
            ->andReturn(['chart_data' => []]);

        $this->mockStatisticsService
            ->shouldReceive('getRoleUsageTrends')
            ->once()
            ->andReturn(['role_creations' => ['chart_data' => []]]);

        $component = Livewire::test(RoleStatistics::class, ['mode' => 'system']);

        $cards = $component->get('statisticsCards');

        $this->assertCount(4, $cards);
        $this->assertEquals(15, $cards[0]['value']); // 總角色數
        $this->assertEquals(12, $cards[1]['value']); // 啟用角色
        $this->assertEquals(3, $cards[2]['value']);  // 系統角色
        $this->assertEquals(12, $cards[3]['value']); // 自訂角色
    }

    /** @test */
    public function 統計卡片資料正確_角色模式()
    {
        $this->actingAs($this->admin);

        $roleStats = [
            'basic' => [
                'user_count' => 8,
                'direct_permission_count' => 6,
                'inherited_permission_count' => 3,
                'total_permission_count' => 9,
            ]
        ];

        $this->mockStatisticsService
            ->shouldReceive('getRoleStatistics')
            ->once()
            ->andReturn($roleStats);

        $this->mockStatisticsService
            ->shouldReceive('getPermissionDistribution')
            ->once()
            ->andReturn(['chart_data' => []]);

        $component = Livewire::test(RoleStatistics::class, [
            'role' => $this->testRole,
            'mode' => 'role'
        ]);

        $cards = $component->get('statisticsCards');

        $this->assertCount(4, $cards);
        $this->assertEquals(8, $cards[0]['value']); // 使用者數量
        $this->assertEquals(6, $cards[1]['value']); // 直接權限
        $this->assertEquals(3, $cards[2]['value']); // 繼承權限
        $this->assertEquals(9, $cards[3]['value']); // 總權限數
    }

    /** @test */
    public function 載入錯誤處理正常()
    {
        $this->actingAs($this->admin);

        $this->mockStatisticsService
            ->shouldReceive('getSystemRoleStatistics')
            ->once()
            ->andThrow(new \Exception('統計服務錯誤'));

        $component = Livewire::test(RoleStatistics::class, ['mode' => 'system']);

        $component->assertHasErrors();
    }

    /** @test */
    public function 權限檢查正常運作()
    {
        // 建立沒有權限的使用者
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(RoleStatistics::class)
            ->assertForbidden();
    }
}