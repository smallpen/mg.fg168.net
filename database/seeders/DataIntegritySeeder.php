<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Setting;

/**
 * 資料完整性驗證種子檔案
 * 
 * 驗證系統資料的完整性和一致性
 * 可用於部署後的資料檢查
 */
class DataIntegritySeeder extends Seeder
{
    /**
     * 執行資料完整性檢查
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('=== 開始資料完整性檢查 ===');
        $this->command->info('');

        $issues = [];

        // 檢查權限
        $issues = array_merge($issues, $this->checkPermissions());
        
        // 檢查角色
        $issues = array_merge($issues, $this->checkRoles());
        
        // 檢查使用者
        $issues = array_merge($issues, $this->checkUsers());
        
        // 檢查設定
        $issues = array_merge($issues, $this->checkSettings());

        // 顯示檢查結果
        $this->displayResults($issues);
    }

    /**
     * 檢查權限完整性
     */
    private function checkPermissions(): array
    {
        $issues = [];
        $permissionCount = Permission::count();
        
        $this->command->info("檢查權限... 找到 {$permissionCount} 個權限");
        
        if ($permissionCount < 30) {
            $issues[] = "權限數量不足: 預期至少 30 個，實際 {$permissionCount} 個";
        }

        // 檢查核心權限模組
        $coreModules = ['dashboard', 'users', 'roles', 'permissions', 'profile'];
        foreach ($coreModules as $module) {
            $modulePermissions = Permission::where('module', $module)->count();
            if ($modulePermissions === 0) {
                $issues[] = "缺少核心模組權限: {$module}";
            }
        }

        return $issues;
    }

    /**
     * 檢查角色完整性
     */
    private function checkRoles(): array
    {
        $issues = [];
        $roleCount = Role::count();
        
        $this->command->info("檢查角色... 找到 {$roleCount} 個角色");
        
        // 檢查必要角色
        $requiredRoles = ['admin', 'manager', 'user'];
        foreach ($requiredRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) {
                $issues[] = "缺少必要角色: {$roleName}";
            } else {
                $permissionCount = $role->permissions()->count();
                $this->command->line("  {$role->display_name}: {$permissionCount} 個權限");
                
                if ($roleName === 'admin' && $permissionCount < 30) {
                    $issues[] = "管理員角色權限不足: 預期至少 30 個，實際 {$permissionCount} 個";
                }
            }
        }

        return $issues;
    }

    /**
     * 檢查使用者完整性
     */
    private function checkUsers(): array
    {
        $issues = [];
        $userCount = User::count();
        
        $this->command->info("檢查使用者... 找到 {$userCount} 個使用者");
        
        // 檢查管理員帳號
        $admin = User::where('username', 'admin')->first();
        if (!$admin) {
            $issues[] = "缺少預設管理員帳號: admin";
        } else {
            $this->command->line("  管理員: {$admin->name} ({$admin->email})");
            
            if (!$admin->is_active) {
                $issues[] = "管理員帳號未啟用";
            }
            
            if (!$admin->hasRole('admin')) {
                $issues[] = "管理員帳號未指派管理員角色";
            }
        }

        return $issues;
    }

    /**
     * 檢查系統設定完整性
     */
    private function checkSettings(): array
    {
        $issues = [];
        $settingCount = Setting::count();
        
        $this->command->info("檢查系統設定... 找到 {$settingCount} 個設定");
        
        if ($settingCount === 0) {
            $issues[] = "系統設定為空，可能需要執行 SettingsSeeder";
        }

        return $issues;
    }

    /**
     * 顯示檢查結果
     */
    private function displayResults(array $issues): void
    {
        $this->command->info('');
        
        if (empty($issues)) {
            $this->command->info('✅ 資料完整性檢查通過！');
            $this->command->info('所有必要的資料都已正確建立。');
        } else {
            $this->command->error('❌ 發現資料完整性問題:');
            foreach ($issues as $issue) {
                $this->command->error("  • {$issue}");
            }
            $this->command->info('');
            $this->command->warn('建議執行以下命令修復問題:');
            $this->command->warn('php artisan migrate:fresh --seed');
        }
        
        $this->command->info('');
        $this->displaySystemSummary();
    }

    /**
     * 顯示系統摘要
     */
    private function displaySystemSummary(): void
    {
        $stats = [
            '權限總數' => Permission::count(),
            '角色總數' => Role::count(),
            '使用者總數' => User::count(),
            '系統設定' => Setting::count(),
        ];

        $this->command->info('=== 系統資料摘要 ===');
        foreach ($stats as $label => $count) {
            $this->command->info("  {$label}: {$count}");
        }
        
        // 顯示角色權限分佈
        $this->command->info('');
        $this->command->info('=== 角色權限分佈 ===');
        $roles = Role::with('permissions')->get();
        foreach ($roles as $role) {
            $permissionCount = $role->permissions->count();
            $this->command->info("  {$role->display_name}: {$permissionCount} 個權限");
        }
    }
}