<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

/**
 * 檢查開發資料狀態命令
 * 
 * 快速檢查當前資料庫中的測試資料狀態
 */
class CheckDevelopmentData extends Command
{
    /**
     * 命令名稱和參數
     *
     * @var string
     */
    protected $signature = 'dev:check 
                            {--users : 只顯示使用者資料}
                            {--roles : 只顯示角色資料}
                            {--permissions : 只顯示權限資料}
                            {--detailed : 顯示詳細資訊}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '檢查開發環境測試資料狀態';

    /**
     * 執行命令
     */
    public function handle()
    {
        $this->info('📊 開發資料狀態檢查');
        $this->info('');

        $users = $this->option('users');
        $roles = $this->option('roles');
        $permissions = $this->option('permissions');
        $detailed = $this->option('detailed');

        // 如果沒有指定特定選項，顯示所有資料
        if (!$users && !$roles && !$permissions) {
            $this->checkOverview();
            $this->checkUsers($detailed);
            $this->checkRoles($detailed);
            $this->checkPermissions($detailed);
        } else {
            if ($users) $this->checkUsers($detailed);
            if ($roles) $this->checkRoles($detailed);
            if ($permissions) $this->checkPermissions($detailed);
        }

        $this->displayQuickActions();

        return 0;
    }

    /**
     * 檢查資料概覽
     */
    private function checkOverview(): void
    {
        $userCount = User::count();
        $activeUserCount = User::where('is_active', true)->count();
        $roleCount = Role::count();
        $permissionCount = Permission::count();

        $this->info('📈 資料概覽：');
        $this->info("  使用者總數: {$userCount} (啟用: {$activeUserCount}, 停用: " . ($userCount - $activeUserCount) . ")");
        $this->info("  角色總數: {$roleCount}");
        $this->info("  權限總數: {$permissionCount}");
        $this->info('');
    }

    /**
     * 檢查使用者資料
     */
    private function checkUsers(bool $detailed = false): void
    {
        $this->info('👥 使用者資料：');

        $users = User::with('roles')->get();

        if ($users->isEmpty()) {
            $this->warn('  ⚠️  沒有找到使用者資料');
            $this->info('  💡 執行 php artisan dev:setup 來建立測試資料');
            return;
        }

        if ($detailed) {
            $headers = ['使用者名稱', '姓名', '電子郵件', '狀態', '角色', '語言'];
            $rows = [];

            foreach ($users as $user) {
                $roles = $user->roles->pluck('display_name')->join(', ') ?: '無角色';
                $status = $user->is_active ? '✅ 啟用' : '❌ 停用';
                
                $rows[] = [
                    $user->username,
                    $user->name,
                    $user->email ?: '-',
                    $status,
                    $roles,
                    $user->locale
                ];
            }

            $this->table($headers, $rows);
        } else {
            foreach ($users as $user) {
                $status = $user->is_active ? '✅' : '❌';
                $roleCount = $user->roles->count();
                $this->info("  {$status} {$user->username} ({$user->name}) - {$roleCount} 個角色");
            }
        }

        $this->info('');
    }

    /**
     * 檢查角色資料
     */
    private function checkRoles(bool $detailed = false): void
    {
        $this->info('🎭 角色資料：');

        $roles = Role::withCount(['users', 'permissions'])->get();

        if ($roles->isEmpty()) {
            $this->warn('  ⚠️  沒有找到角色資料');
            return;
        }

        if ($detailed) {
            $headers = ['角色名稱', '顯示名稱', '使用者數', '權限數', '描述'];
            $rows = [];

            foreach ($roles as $role) {
                $rows[] = [
                    $role->name,
                    $role->display_name,
                    $role->users_count,
                    $role->permissions_count,
                    $role->description ?: '-'
                ];
            }

            $this->table($headers, $rows);
        } else {
            foreach ($roles as $role) {
                $this->info("  🎭 {$role->display_name} ({$role->name}) - {$role->users_count} 使用者, {$role->permissions_count} 權限");
            }
        }

        $this->info('');
    }

    /**
     * 檢查權限資料
     */
    private function checkPermissions(bool $detailed = false): void
    {
        $this->info('🔐 權限資料：');

        $permissions = Permission::withCount('roles')->get();

        if ($permissions->isEmpty()) {
            $this->warn('  ⚠️  沒有找到權限資料');
            return;
        }

        if ($detailed) {
            $headers = ['權限名稱', '顯示名稱', '模組', '角色數', '描述'];
            $rows = [];

            foreach ($permissions as $permission) {
                $rows[] = [
                    $permission->name,
                    $permission->display_name,
                    $permission->module ?: '-',
                    $permission->roles_count,
                    $permission->description ?: '-'
                ];
            }

            $this->table($headers, $rows);
        } else {
            $groupedPermissions = $permissions->groupBy('module');
            
            foreach ($groupedPermissions as $module => $modulePermissions) {
                $moduleName = $module ?: '其他';
                $this->info("  📁 {$moduleName} ({$modulePermissions->count()} 個權限)");
            }
        }

        $this->info('');
    }

    /**
     * 顯示快速操作
     */
    private function displayQuickActions(): void
    {
        $this->info('⚡ 快速操作：');
        $this->info('');
        $this->info('  🔄 重建所有資料：');
        $this->info('    php artisan dev:setup --fresh --force');
        $this->info('');
        $this->info('  👥 只重建使用者：');
        $this->info('    php artisan dev:setup --users-only --force');
        $this->info('');
        $this->info('  📊 詳細檢查：');
        $this->info('    php artisan dev:check --detailed');
        $this->info('');
        $this->info('  🌐 登入測試：');
        $this->info('    http://localhost/admin/login');
        $this->info('    admin / password123');
    }
}
