<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

/**
 * 完整測試資料種子檔案
 * 
 * 建立完整的測試資料，確保 admin 帳號可以登入並測試所有功能
 * 包含：權限、角色、使用者、角色權限關聯
 */
class CompleteTestDataSeeder extends Seeder
{
    /**
     * 執行完整測試資料種子
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('=== 開始建立完整測試資料 ===');
        $this->command->info('');

        // 清空現有資料（如果需要）
        $this->clearExistingData();

        // 按照依賴順序建立資料
        $this->createPermissions();
        $this->createRoles();
        $this->createUsers();
        $this->assignRolePermissions();
        $this->assignUserRoles();
        
        // 建立通路管理測試資料
        $this->createChannelTestData();

        $this->displayCompletionInfo();
    }

    /**
     * 清空現有資料
     */
    private function clearExistingData(): void
    {
        $this->command->info('清理現有資料...');
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        DB::table('user_roles')->truncate();
        DB::table('role_permissions')->truncate();
        DB::table('users')->truncate();
        DB::table('roles')->truncate();
        DB::table('permissions')->truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info('✓ 現有資料已清理');
    }

    /**
     * 建立系統權限
     */
    private function createPermissions(): void
    {
        $this->command->info('建立系統權限...');

        // 在種子期間暫時停用權限觀察者
        Permission::unsetEventDispatcher();

        $permissions = [
            // === 儀表板模組 (2個權限) ===
            [
                'name' => 'dashboard.view',
                'display_name' => '檢視儀表板',
                'description' => '可以存取管理後台儀表板',
                'module' => 'dashboard',
                'type' => 'view'
            ],
            [
                'name' => 'dashboard.stats',
                'display_name' => '檢視統計資訊',
                'description' => '可以檢視系統統計資訊和圖表',
                'module' => 'dashboard',
                'type' => 'view'
            ],

            // === 使用者管理模組 (6個權限) ===
            [
                'name' => 'users.view',
                'display_name' => '檢視使用者',
                'description' => '可以檢視使用者列表和詳細資訊',
                'module' => 'users',
                'type' => 'view'
            ],
            [
                'name' => 'users.create',
                'display_name' => '建立使用者',
                'description' => '可以建立新的使用者帳號',
                'module' => 'users',
                'type' => 'create'
            ],
            [
                'name' => 'users.edit',
                'display_name' => '編輯使用者',
                'description' => '可以編輯使用者資訊和設定',
                'module' => 'users',
                'type' => 'edit'
            ],
            [
                'name' => 'users.delete',
                'display_name' => '刪除使用者',
                'description' => '可以刪除使用者帳號',
                'module' => 'users',
                'type' => 'delete'
            ],
            [
                'name' => 'users.assign_roles',
                'display_name' => '指派使用者角色',
                'description' => '可以為使用者指派或移除角色',
                'module' => 'users',
                'type' => 'assign'
            ],
            [
                'name' => 'users.export',
                'display_name' => '匯出使用者',
                'description' => '可以匯出使用者資料',
                'module' => 'users',
                'type' => 'export'
            ],

            // === 角色管理模組 (5個權限) ===
            [
                'name' => 'roles.view',
                'display_name' => '檢視角色',
                'description' => '可以檢視角色列表和詳細資訊',
                'module' => 'roles',
                'type' => 'view'
            ],
            [
                'name' => 'roles.create',
                'display_name' => '建立角色',
                'description' => '可以建立新的角色',
                'module' => 'roles',
                'type' => 'create'
            ],
            [
                'name' => 'roles.edit',
                'display_name' => '編輯角色',
                'description' => '可以編輯角色資訊和權限設定',
                'module' => 'roles',
                'type' => 'edit'
            ],
            [
                'name' => 'roles.delete',
                'display_name' => '刪除角色',
                'description' => '可以刪除角色',
                'module' => 'roles',
                'type' => 'delete'
            ],
            [
                'name' => 'roles.manage_permissions',
                'display_name' => '管理角色權限',
                'description' => '可以為角色指派或移除權限',
                'module' => 'roles',
                'type' => 'manage'
            ],

            // === 權限管理模組 (4個權限) ===
            [
                'name' => 'permissions.view',
                'display_name' => '檢視權限',
                'description' => '可以檢視權限列表和詳細資訊',
                'module' => 'permissions',
                'type' => 'view'
            ],
            [
                'name' => 'permissions.create',
                'display_name' => '建立權限',
                'description' => '可以建立新的權限',
                'module' => 'permissions',
                'type' => 'create'
            ],
            [
                'name' => 'permissions.edit',
                'display_name' => '編輯權限',
                'description' => '可以編輯權限資訊',
                'module' => 'permissions',
                'type' => 'edit'
            ],
            [
                'name' => 'permissions.delete',
                'display_name' => '刪除權限',
                'description' => '可以刪除權限',
                'module' => 'permissions',
                'type' => 'delete'
            ],

            // === 個人資料模組 (2個權限) ===
            [
                'name' => 'profile.view',
                'display_name' => '檢視個人資料',
                'description' => '可以檢視自己的個人資料',
                'module' => 'profile',
                'type' => 'view'
            ],
            [
                'name' => 'profile.edit',
                'display_name' => '編輯個人資料',
                'description' => '可以編輯自己的個人資料',
                'module' => 'profile',
                'type' => 'edit'
            ],

            // === 活動日誌模組 (3個權限) ===
            [
                'name' => 'activity_logs.view',
                'display_name' => '檢視活動日誌',
                'description' => '可以檢視系統活動日誌',
                'module' => 'activity_logs',
                'type' => 'view'
            ],
            [
                'name' => 'activity_logs.export',
                'display_name' => '匯出活動日誌',
                'description' => '可以匯出活動日誌資料',
                'module' => 'activity_logs',
                'type' => 'export'
            ],
            [
                'name' => 'activity_logs.delete',
                'display_name' => '刪除活動日誌',
                'description' => '可以刪除舊的活動日誌記錄',
                'module' => 'activity_logs',
                'type' => 'delete'
            ],

            // === 通知管理模組 (5個權限) ===
            [
                'name' => 'notifications.view',
                'display_name' => '檢視通知',
                'description' => '可以檢視系統通知',
                'module' => 'notifications',
                'type' => 'view'
            ],
            [
                'name' => 'notifications.create',
                'display_name' => '建立通知',
                'description' => '可以建立和發送通知',
                'module' => 'notifications',
                'type' => 'create'
            ],
            [
                'name' => 'notifications.edit',
                'display_name' => '編輯通知',
                'description' => '可以編輯通知內容和設定',
                'module' => 'notifications',
                'type' => 'edit'
            ],
            [
                'name' => 'notifications.delete',
                'display_name' => '刪除通知',
                'description' => '可以刪除通知記錄',
                'module' => 'notifications',
                'type' => 'delete'
            ],
            [
                'name' => 'notifications.send',
                'display_name' => '發送通知',
                'description' => '可以發送通知給使用者',
                'module' => 'notifications',
                'type' => 'send'
            ],

            // === 系統設定模組 (4個權限) ===
            [
                'name' => 'settings.view',
                'display_name' => '檢視設定',
                'description' => '可以檢視系統設定',
                'module' => 'settings',
                'type' => 'view'
            ],
            [
                'name' => 'settings.edit',
                'display_name' => '編輯設定',
                'description' => '可以修改系統設定',
                'module' => 'settings',
                'type' => 'edit'
            ],
            [
                'name' => 'settings.backup',
                'display_name' => '備份設定',
                'description' => '可以備份和還原系統設定',
                'module' => 'settings',
                'type' => 'backup'
            ],
            [
                'name' => 'settings.reset',
                'display_name' => '重置設定',
                'description' => '可以重置系統設定為預設值',
                'module' => 'settings',
                'type' => 'reset'
            ],

            // === 系統管理模組 (4個權限) ===
            [
                'name' => 'system.logs',
                'display_name' => '檢視系統日誌',
                'description' => '可以檢視系統日誌和錯誤記錄',
                'module' => 'system',
                'type' => 'view'
            ],
            [
                'name' => 'system.maintenance',
                'display_name' => '系統維護',
                'description' => '可以執行系統維護操作',
                'module' => 'system',
                'type' => 'manage'
            ],
            [
                'name' => 'system.monitor',
                'display_name' => '系統監控',
                'description' => '可以監控系統效能和狀態',
                'module' => 'system',
                'type' => 'monitor'
            ],
            [
                'name' => 'system.security',
                'display_name' => '安全管理',
                'description' => '可以管理系統安全設定和事件',
                'module' => 'system',
                'type' => 'security'
            ],

            // === 通路管理模組 (16個權限) ===
            // 代理管理權限 (7個)
            [
                'name' => 'channels.agents.view',
                'display_name' => '檢視代理',
                'description' => '可以檢視代理列表和詳細資訊',
                'module' => 'channels',
                'type' => 'view'
            ],
            [
                'name' => 'channels.agents.create',
                'display_name' => '建立代理',
                'description' => '可以建立新的代理',
                'module' => 'channels',
                'type' => 'create'
            ],
            [
                'name' => 'channels.agents.edit',
                'display_name' => '編輯代理',
                'description' => '可以編輯代理資訊',
                'module' => 'channels',
                'type' => 'edit'
            ],
            [
                'name' => 'channels.agents.delete',
                'display_name' => '刪除代理',
                'description' => '可以刪除代理',
                'module' => 'channels',
                'type' => 'delete'
            ],
            [
                'name' => 'channels.agents.manage_hierarchy',
                'display_name' => '管理代理層級',
                'description' => '可以管理代理的層級結構和上下層關係',
                'module' => 'channels',
                'type' => 'manage'
            ],
            [
                'name' => 'channels.agents.self_manage',
                'display_name' => '代理自主管理',
                'description' => '代理可以管理自己的下層代理和玩家',
                'module' => 'channels',
                'type' => 'self_manage'
            ],
            [
                'name' => 'channels.agents.export',
                'display_name' => '匯出代理資料',
                'description' => '可以匯出代理網絡結構和統計資料',
                'module' => 'channels',
                'type' => 'export'
            ],

            // 玩家管理權限 (6個)
            [
                'name' => 'channels.players.view',
                'display_name' => '檢視玩家',
                'description' => '可以檢視玩家列表和詳細資訊',
                'module' => 'channels',
                'type' => 'view'
            ],
            [
                'name' => 'channels.players.create',
                'display_name' => '建立玩家',
                'description' => '可以建立新的玩家',
                'module' => 'channels',
                'type' => 'create'
            ],
            [
                'name' => 'channels.players.edit',
                'display_name' => '編輯玩家',
                'description' => '可以編輯玩家資訊',
                'module' => 'channels',
                'type' => 'edit'
            ],
            [
                'name' => 'channels.players.delete',
                'display_name' => '刪除玩家',
                'description' => '可以刪除玩家',
                'module' => 'channels',
                'type' => 'delete'
            ],
            [
                'name' => 'channels.players.assign_agent',
                'display_name' => '指派玩家代理',
                'description' => '可以變更玩家的隸屬代理',
                'module' => 'channels',
                'type' => 'assign'
            ],
            [
                'name' => 'channels.players.export',
                'display_name' => '匯出玩家資料',
                'description' => '可以匯出玩家資料',
                'module' => 'channels',
                'type' => 'export'
            ],

            // 點數管理權限 (3個)
            [
                'name' => 'channels.points.view',
                'display_name' => '檢視點數',
                'description' => '可以檢視點數分配和交易記錄',
                'module' => 'channels',
                'type' => 'view'
            ],
            [
                'name' => 'channels.points.allocate',
                'display_name' => '分配點數',
                'description' => '可以分配點數給代理或玩家',
                'module' => 'channels',
                'type' => 'allocate'
            ],
            [
                'name' => 'channels.points.recover',
                'display_name' => '回收點數',
                'description' => '可以從代理或玩家回收點數',
                'module' => 'channels',
                'type' => 'recover'
            ],
        ];

        // 使用 withoutEvents 方法來避免觸發觀察者
        Permission::withoutEvents(function () use ($permissions) {
            foreach ($permissions as $permission) {
                Permission::create($permission);
            }
        });

        $this->command->info("✓ 已建立 " . count($permissions) . " 個權限");
    }

    /**
     * 建立系統角色
     */
    private function createRoles(): void
    {
        $this->command->info('建立系統角色...');

        $roles = [
            [
                'name' => 'admin',
                'display_name' => '系統管理員',
                'description' => '擁有系統完整管理權限的管理員角色',
                'is_active' => true,
            ],
            [
                'name' => 'manager',
                'display_name' => '部門主管',
                'description' => '部門主管角色，擁有部分管理權限',
                'is_active' => true,
            ],
            [
                'name' => 'user',
                'display_name' => '一般使用者',
                'description' => '系統一般使用者，擁有基本操作權限',
                'is_active' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

        $this->command->info("✓ 已建立 " . count($roles) . " 個角色");
    }

    /**
     * 建立測試使用者
     */
    private function createUsers(): void
    {
        $this->command->info('建立測試使用者...');

        $users = [
            // 管理員帳號
            [
                'username' => 'admin',
                'name' => '系統管理員',
                'email' => 'admin@system.local',
                'password' => Hash::make('admin123'),
                'theme_preference' => 'light',
                'locale' => 'zh_TW',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            // 測試使用者
            [
                'username' => 'manager',
                'name' => '部門主管',
                'email' => 'manager@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'light',
                'locale' => 'zh_TW',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'username' => 'testuser',
                'name' => '測試使用者',
                'email' => 'testuser@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'dark',
                'locale' => 'zh_TW',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'username' => 'inactive_user',
                'name' => '停用使用者',
                'email' => 'inactive@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'light',
                'locale' => 'zh_TW',
                'is_active' => false,
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        $this->command->info("✓ 已建立 " . count($users) . " 個使用者");
    }

    /**
     * 指派角色權限
     */
    private function assignRolePermissions(): void
    {
        $this->command->info('指派角色權限...');

        // 取得所有權限
        $allPermissions = Permission::all();
        
        // 管理員角色 - 擁有所有權限
        $adminRole = Role::where('name', 'admin')->first();
        $adminRole->permissions()->sync($allPermissions->pluck('id')->toArray());
        $this->command->info("✓ 管理員角色已指派 {$allPermissions->count()} 個權限");

        // 部門主管角色 - 擁有部分管理權限
        $managerRole = Role::where('name', 'manager')->first();
        $managerPermissions = [
            // 儀表板權限
            'dashboard.view',
            'dashboard.stats',
            
            // 使用者檢視權限
            'users.view',
            'users.create',
            'users.edit',
            
            // 角色檢視權限
            'roles.view',
            
            // 權限檢視權限
            'permissions.view',
            
            // 個人資料權限
            'profile.view',
            'profile.edit',
            
            // 活動日誌權限
            'activity_logs.view',
            'activity_logs.export',
            
            // 通知權限
            'notifications.view',
            'notifications.create',
            'notifications.edit',
            'notifications.send',
            
            // 設定檢視權限
            'settings.view',
        ];
        
        $managerPermissionIds = Permission::whereIn('name', $managerPermissions)->pluck('id')->toArray();
        $managerRole->permissions()->sync($managerPermissionIds);
        $this->command->info("✓ 部門主管角色已指派 " . count($managerPermissionIds) . " 個權限");

        // 一般使用者角色 - 擁有基本權限
        $userRole = Role::where('name', 'user')->first();
        $userPermissions = [
            // 儀表板基本檢視
            'dashboard.view',
            
            // 個人資料權限
            'profile.view',
            'profile.edit',
            
            // 通知檢視權限
            'notifications.view',
        ];
        
        $userPermissionIds = Permission::whereIn('name', $userPermissions)->pluck('id')->toArray();
        $userRole->permissions()->sync($userPermissionIds);
        $this->command->info("✓ 一般使用者角色已指派 " . count($userPermissionIds) . " 個權限");
    }

    /**
     * 指派使用者角色
     */
    private function assignUserRoles(): void
    {
        $this->command->info('指派使用者角色...');

        // 指派管理員角色
        $adminUser = User::where('username', 'admin')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $adminUser->roles()->attach($adminRole->id);
        $this->command->info("✓ admin 使用者已指派管理員角色");

        // 指派部門主管角色
        $managerUser = User::where('username', 'manager')->first();
        $managerRole = Role::where('name', 'manager')->first();
        $managerUser->roles()->attach($managerRole->id);
        $this->command->info("✓ manager 使用者已指派部門主管角色");

        // 指派一般使用者角色
        $testUser = User::where('username', 'testuser')->first();
        $inactiveUser = User::where('username', 'inactive_user')->first();
        $userRole = Role::where('name', 'user')->first();
        
        $testUser->roles()->attach($userRole->id);
        $inactiveUser->roles()->attach($userRole->id);
        $this->command->info("✓ 測試使用者已指派一般使用者角色");
    }

    /**
     * 建立通路管理測試資料
     */
    private function createChannelTestData(): void
    {
        $this->command->info('建立通路管理測試資料...');
        
        // 檢查是否有通路管理相關的 Model
        if (!class_exists(\App\Models\Agent::class) || 
            !class_exists(\App\Models\Player::class) || 
            !class_exists(\App\Models\PointTransaction::class)) {
            $this->command->warn('⚠️  通路管理 Model 不存在，跳過通路管理測試資料建立');
            return;
        }

        try {
            $this->createAgents();
            $this->createPlayers();
            $this->createPointTransactions();
            $this->command->info("✓ 通路管理測試資料建立完成");
        } catch (\Exception $e) {
            $this->command->warn("⚠️  通路管理測試資料建立失敗: " . $e->getMessage());
        }
    }

    /**
     * 建立代理測試資料
     */
    private function createAgents(): void
    {
        $adminUser = \App\Models\User::where('username', 'admin')->first();
        
        // 第一層代理（根代理）
        $rootAgents = [
            [
                'name' => '總代理A',
                'username' => 'agent001',
                'account' => 'A_agent001',
                'email' => 'agent001@example.com',
                'phone' => '0912345001',
                'prefix' => 'A',
                'level' => 1,
                'parent_id' => null,
                'total_points' => 1000000.00,
                'allocated_points' => 600000.00,
                'remaining_points' => 400000.00,
                'is_active' => true,
                'created_by' => $adminUser->id,
                'notes' => '第一層總代理A，負責北區業務',
            ],
            [
                'name' => '總代理B',
                'username' => 'agent002',
                'account' => 'B_agent002',
                'email' => 'agent002@example.com',
                'phone' => '0912345002',
                'prefix' => 'B',
                'level' => 1,
                'parent_id' => null,
                'total_points' => 800000.00,
                'allocated_points' => 500000.00,
                'remaining_points' => 300000.00,
                'is_active' => true,
                'created_by' => $adminUser->id,
                'notes' => '第一層總代理B，負責南區業務',
            ],
        ];

        foreach ($rootAgents as $agentData) {
            \App\Models\Agent::create($agentData);
        }

        // 第二層代理
        $agentA = \App\Models\Agent::where('username', 'agent001')->first();
        $agentB = \App\Models\Agent::where('username', 'agent002')->first();

        $secondLevelAgents = [
            // A 總代理的下層代理
            [
                'name' => '代理A1',
                'username' => 'sub001',
                'account' => 'A_sub001',
                'email' => 'sub001@example.com',
                'phone' => '0912345101',
                'prefix' => null,
                'level' => 2,
                'parent_id' => $agentA->id,
                'total_points' => 200000.00,
                'allocated_points' => 120000.00,
                'remaining_points' => 80000.00,
                'is_active' => true,
                'created_by' => $adminUser->id,
                'notes' => '總代理A的下層代理，負責台北地區',
            ],
            [
                'name' => '代理A2',
                'username' => 'sub002',
                'account' => 'A_sub002',
                'email' => 'sub002@example.com',
                'phone' => '0912345102',
                'prefix' => null,
                'level' => 2,
                'parent_id' => $agentA->id,
                'total_points' => 150000.00,
                'allocated_points' => 90000.00,
                'remaining_points' => 60000.00,
                'is_active' => true,
                'created_by' => $adminUser->id,
                'notes' => '總代理A的下層代理，負責桃園地區',
            ],
            // B 總代理的下層代理
            [
                'name' => '代理B1',
                'username' => 'sub003',
                'account' => 'B_sub003',
                'email' => 'sub003@example.com',
                'phone' => '0912345103',
                'prefix' => null,
                'level' => 2,
                'parent_id' => $agentB->id,
                'total_points' => 180000.00,
                'allocated_points' => 100000.00,
                'remaining_points' => 80000.00,
                'is_active' => true,
                'created_by' => $adminUser->id,
                'notes' => '總代理B的下層代理，負責台中地區',
            ],
            [
                'name' => '代理B2',
                'username' => 'sub004',
                'account' => 'B_sub004',
                'email' => 'sub004@example.com',
                'phone' => '0912345104',
                'prefix' => null,
                'level' => 2,
                'parent_id' => $agentB->id,
                'total_points' => 120000.00,
                'allocated_points' => 70000.00,
                'remaining_points' => 50000.00,
                'is_active' => false, // 測試停用狀態
                'created_by' => $adminUser->id,
                'notes' => '總代理B的下層代理，負責高雄地區（已停用）',
            ],
        ];

        foreach ($secondLevelAgents as $agentData) {
            \App\Models\Agent::create($agentData);
        }

        $this->command->info("✓ 已建立 " . (count($rootAgents) + count($secondLevelAgents)) . " 個代理");
    }

    /**
     * 建立玩家測試資料
     */
    private function createPlayers(): void
    {
        $adminUser = \App\Models\User::where('username', 'admin')->first();
        $agents = \App\Models\Agent::all();

        $players = [
            // 代理A1的玩家
            [
                'name' => '玩家001',
                'username' => 'player001',
                'account' => 'A_player001',
                'email' => 'player001@example.com',
                'phone' => '0987654001',
                'agent_id' => $agents->where('username', 'sub001')->first()->id,
                'points' => 50000.00,
                'is_active' => true,
                'created_by' => $adminUser->id,
                'notes' => '活躍玩家，經常參與遊戲',
            ],
            [
                'name' => '玩家002',
                'username' => 'player002',
                'account' => 'A_player002',
                'email' => 'player002@example.com',
                'phone' => '0987654002',
                'agent_id' => $agents->where('username', 'sub001')->first()->id,
                'points' => 25000.00,
                'is_active' => true,
                'created_by' => $adminUser->id,
                'notes' => '中等活躍度玩家',
            ],
            // 代理A2的玩家
            [
                'name' => '玩家003',
                'username' => 'player003',
                'account' => 'A_player003',
                'email' => 'player003@example.com',
                'phone' => '0987654003',
                'agent_id' => $agents->where('username', 'sub002')->first()->id,
                'points' => 75000.00,
                'is_active' => true,
                'created_by' => $adminUser->id,
                'notes' => '高價值玩家',
            ],
            // 代理B1的玩家
            [
                'name' => '玩家004',
                'username' => 'player004',
                'account' => 'B_player004',
                'email' => 'player004@example.com',
                'phone' => '0987654004',
                'agent_id' => $agents->where('username', 'sub003')->first()->id,
                'points' => 30000.00,
                'is_active' => true,
                'created_by' => $adminUser->id,
                'notes' => '新加入玩家',
            ],
            [
                'name' => '玩家005',
                'username' => 'player005',
                'account' => 'B_player005',
                'email' => 'player005@example.com',
                'phone' => '0987654005',
                'agent_id' => $agents->where('username', 'sub003')->first()->id,
                'points' => 15000.00,
                'is_active' => false, // 測試停用狀態
                'created_by' => $adminUser->id,
                'notes' => '暫停使用的玩家',
            ],
            // 代理B2的玩家（代理已停用，但玩家資料保留）
            [
                'name' => '玩家006',
                'username' => 'player006',
                'account' => 'B_player006',
                'email' => 'player006@example.com',
                'phone' => '0987654006',
                'agent_id' => $agents->where('username', 'sub004')->first()->id,
                'points' => 5000.00,
                'is_active' => true,
                'created_by' => $adminUser->id,
                'notes' => '隸屬於停用代理的玩家',
            ],
        ];

        foreach ($players as $playerData) {
            \App\Models\Player::create($playerData);
        }

        $this->command->info("✓ 已建立 " . count($players) . " 個玩家");
    }

    /**
     * 建立點數交易測試資料
     */
    private function createPointTransactions(): void
    {
        $adminUser = \App\Models\User::where('username', 'admin')->first();
        $agents = \App\Models\Agent::all();
        $players = \App\Models\Player::all();

        $transactions = [
            // 代理點數分配記錄
            [
                'agent_id' => $agents->where('username', 'agent001')->first()->id,
                'player_id' => null,
                'type' => \App\Models\PointTransaction::TYPE_AGENT_ALLOCATION,
                'amount' => 200000.00,
                'balance_before' => 800000.00,
                'balance_after' => 600000.00,
                'description' => '分配點數給下層代理A1',
                'reference_id' => $agents->where('username', 'sub001')->first()->id,
                'created_by' => $adminUser->id,
            ],
            [
                'agent_id' => $agents->where('username', 'agent001')->first()->id,
                'player_id' => null,
                'type' => \App\Models\PointTransaction::TYPE_AGENT_ALLOCATION,
                'amount' => 150000.00,
                'balance_before' => 600000.00,
                'balance_after' => 450000.00,
                'description' => '分配點數給下層代理A2',
                'reference_id' => $agents->where('username', 'sub002')->first()->id,
                'created_by' => $adminUser->id,
            ],
            // 玩家點數分配記錄
            [
                'agent_id' => null,
                'player_id' => $players->where('username', 'player001')->first()->id,
                'type' => \App\Models\PointTransaction::TYPE_PLAYER_ALLOCATION,
                'amount' => 50000.00,
                'balance_before' => 0.00,
                'balance_after' => 50000.00,
                'description' => '初始點數分配',
                'reference_id' => null,
                'created_by' => $adminUser->id,
            ],
            [
                'agent_id' => null,
                'player_id' => $players->where('username', 'player002')->first()->id,
                'type' => \App\Models\PointTransaction::TYPE_PLAYER_ALLOCATION,
                'amount' => 30000.00,
                'balance_before' => 0.00,
                'balance_after' => 30000.00,
                'description' => '初始點數分配',
                'reference_id' => null,
                'created_by' => $adminUser->id,
            ],
            // 玩家消費記錄
            [
                'agent_id' => null,
                'player_id' => $players->where('username', 'player002')->first()->id,
                'type' => \App\Models\PointTransaction::TYPE_PLAYER_CONSUMPTION,
                'amount' => -5000.00,
                'balance_before' => 30000.00,
                'balance_after' => 25000.00,
                'description' => '遊戲消費',
                'reference_id' => null,
                'created_by' => $adminUser->id,
            ],
            // 系統調整記錄
            [
                'agent_id' => null,
                'player_id' => $players->where('username', 'player003')->first()->id,
                'type' => \App\Models\PointTransaction::TYPE_SYSTEM_ADJUSTMENT,
                'amount' => 5000.00,
                'balance_before' => 70000.00,
                'balance_after' => 75000.00,
                'description' => '系統補償調整',
                'reference_id' => null,
                'created_by' => $adminUser->id,
            ],
        ];

        foreach ($transactions as $transactionData) {
            \App\Models\PointTransaction::create($transactionData);
        }

        $this->command->info("✓ 已建立 " . count($transactions) . " 筆點數交易記錄");
    }

    /**
     * 顯示完成資訊
     */
    private function displayCompletionInfo(): void
    {
        $this->command->info('');
        $this->command->info('=== 完整測試資料建立完成 ===');
        $this->command->info('');
        
        // 統計資訊
        $permissionCount = Permission::count();
        $roleCount = Role::count();
        $userCount = User::count();
        
        // 通路管理統計
        $agentCount = 0;
        $playerCount = 0;
        $transactionCount = 0;
        
        if (class_exists(\App\Models\Agent::class)) {
            $agentCount = \App\Models\Agent::count();
        }
        if (class_exists(\App\Models\Player::class)) {
            $playerCount = \App\Models\Player::count();
        }
        if (class_exists(\App\Models\PointTransaction::class)) {
            $transactionCount = \App\Models\PointTransaction::count();
        }
        
        $this->command->info('📊 資料統計:');
        $this->command->info("   • 權限: {$permissionCount} 個");
        $this->command->info("   • 角色: {$roleCount} 個");
        $this->command->info("   • 使用者: {$userCount} 個");
        
        if ($agentCount > 0 || $playerCount > 0 || $transactionCount > 0) {
            $this->command->info('   • 通路管理:');
            $this->command->info("     - 代理: {$agentCount} 個");
            $this->command->info("     - 玩家: {$playerCount} 個");
            $this->command->info("     - 交易記錄: {$transactionCount} 筆");
        }
        
        $this->command->info('');
        
        $this->command->info('🔑 測試帳號:');
        $this->command->info('   • 管理員: admin / admin123 (擁有所有權限)');
        $this->command->info('   • 部門主管: manager / password123 (部分管理權限)');
        $this->command->info('   • 一般使用者: testuser / password123 (基本權限)');
        $this->command->info('   • 停用使用者: inactive_user / password123 (已停用)');
        $this->command->info('');
        
        $this->command->info('🌐 登入資訊:');
        $this->command->info('   • 管理後台: /admin/login');
        $this->command->info('   • 建議使用 admin 帳號進行完整功能測試');
        $this->command->info('');
        
        $this->command->info('🔍 權限模組分佈:');
        $moduleStats = Permission::select('module')
                                ->selectRaw('COUNT(*) as count')
                                ->groupBy('module')
                                ->orderBy('module')
                                ->get();
        
        foreach ($moduleStats as $stat) {
            $this->command->info("   • {$stat->module}: {$stat->count} 個權限");
        }
        
        $this->command->info('');
        $this->command->warn('⚠️  安全提醒:');
        $this->command->warn('   1. 請在生產環境中修改預設密碼');
        $this->command->warn('   2. 建議建立專屬的管理員帳號');
        $this->command->warn('   3. 定期檢查和更新權限設定');
        $this->command->info('');
        
        $this->command->info('✅ 系統現在可以完整測試所有功能！');
    }
}