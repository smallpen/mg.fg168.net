<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Observers\PermissionSecurityObserver;

/**
 * 權限種子檔案
 * 
 * 建立系統基本權限
 */
class PermissionSeeder extends Seeder
{
    /**
     * 執行權限種子
     */
    public function run(): void
    {
        // 在種子期間暫時停用安全觀察者
        Permission::unsetEventDispatcher();
        
        // 或者可以使用 withoutEvents 方法
        Permission::withoutEvents(function () {
            $this->seedPermissions();
        });
    }
    
    /**
     * 建立權限資料
     */
    private function seedPermissions(): void
    {
        // 定義系統核心權限 - 完整的權限結構（共 49 個權限）
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

            // === 通路系統管理權限 (5個) ===
            [
                'name' => 'channels.system.manage',
                'display_name' => '系統管理',
                'description' => '可以存取通路管理系統管理員功能',
                'module' => 'channels',
                'type' => 'manage'
            ],
            [
                'name' => 'channels.system.audit',
                'display_name' => '系統稽核',
                'description' => '可以執行系統稽核和監控功能',
                'module' => 'channels',
                'type' => 'audit'
            ],
            [
                'name' => 'channels.system.adjust_points',
                'display_name' => '系統級點數調整',
                'description' => '可以進行系統級的點數調整操作',
                'module' => 'channels',
                'type' => 'adjust'
            ],
            [
                'name' => 'channels.system.export',
                'display_name' => '系統資料匯出',
                'description' => '可以匯出系統級的報表和資料',
                'module' => 'channels',
                'type' => 'export'
            ],
            [
                'name' => 'channels.system.monitor',
                'display_name' => '系統監控',
                'description' => '可以監控系統健康度和異常狀況',
                'module' => 'channels',
                'type' => 'monitor'
            ],
        ];

        // 建立或更新權限記錄
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // 統計資訊
        $this->command->info('');
        $this->command->info('=== 權限建立完成 ===');
        $this->command->info("✓ 總計權限: " . count($permissions) . " 個");
        
        // 按模組統計
        $moduleStats = collect($permissions)->groupBy('module')->map->count();
        $this->command->info('');
        $this->command->info('=== 模組權限分佈 ===');
        foreach ($moduleStats as $module => $count) {
            $this->command->info("  {$module}: {$count} 個權限");
        }
    }
}