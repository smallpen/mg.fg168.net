<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Database\Seeders\DevelopmentSeeder;

/**
 * 快速設定開發測試資料命令
 * 
 * 提供便捷的方式來重建開發環境的測試資料
 */
class SetupDevelopmentData extends Command
{
    /**
     * 命令名稱和參數
     *
     * @var string
     */
    protected $signature = 'dev:setup 
                            {--fresh : 清空資料庫並重新建立所有資料}
                            {--users-only : 只重建使用者資料}
                            {--force : 強制執行，不詢問確認}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '快速設定開發環境測試資料';

    /**
     * 執行命令
     */
    public function handle()
    {
        $this->info('🚀 開發環境資料設定工具');
        $this->info('');

        // 檢查是否為開發環境
        if (app()->environment('production')) {
            $this->error('❌ 此命令不能在生產環境中執行！');
            return 1;
        }

        $fresh = $this->option('fresh');
        $usersOnly = $this->option('users-only');
        $force = $this->option('force');

        // 顯示將要執行的操作
        $this->displayOperations($fresh, $usersOnly);

        // 確認執行
        if (!$force && !$this->confirm('確定要繼續嗎？')) {
            $this->info('操作已取消');
            return 0;
        }

        try {
            if ($fresh) {
                $this->setupFreshData();
            } elseif ($usersOnly) {
                $this->setupUsersOnly();
            } else {
                $this->setupDevelopmentData();
            }

            $this->info('');
            $this->info('✅ 開發資料設定完成！');
            $this->displayQuickCommands();

        } catch (\Exception $e) {
            $this->error('❌ 設定過程中發生錯誤：' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * 顯示將要執行的操作
     */
    private function displayOperations(bool $fresh, bool $usersOnly): void
    {
        $this->info('📋 將要執行的操作：');
        
        if ($fresh) {
            $this->warn('  • 清空整個資料庫');
            $this->info('  • 重新執行所有遷移');
            $this->info('  • 建立基本權限和角色');
            $this->info('  • 建立完整的測試使用者資料');
        } elseif ($usersOnly) {
            $this->info('  • 清除現有使用者資料');
            $this->info('  • 重新建立測試使用者');
        } else {
            $this->info('  • 建立/更新開發測試資料');
            $this->info('  • 保留現有資料，只更新或新增');
        }
        
        $this->info('');
    }

    /**
     * 設定全新的資料庫
     */
    private function setupFreshData(): void
    {
        $this->info('🔄 正在清空資料庫...');
        Artisan::call('migrate:fresh');
        $this->info('✓ 資料庫已清空並重新建立');

        $this->info('🌱 正在建立基本資料...');
        Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
        $this->info('✓ 基本資料已建立');

        $this->info('👥 正在建立測試使用者...');
        Artisan::call('db:seed', ['--class' => 'DevelopmentSeeder']);
        $this->info('✓ 測試使用者已建立');
    }

    /**
     * 只重建使用者資料
     */
    private function setupUsersOnly(): void
    {
        $this->info('🗑️ 正在清除使用者資料...');
        
        // 清除使用者相關資料
        DB::table('user_roles')->delete();
        DB::table('users')->delete();
        
        $this->info('✓ 使用者資料已清除');

        $this->info('👥 正在重建使用者資料...');
        Artisan::call('db:seed', ['--class' => 'DevelopmentSeeder']);
        $this->info('✓ 使用者資料已重建');
    }

    /**
     * 設定開發資料（保留現有資料）
     */
    private function setupDevelopmentData(): void
    {
        $this->info('🌱 正在設定開發資料...');
        Artisan::call('db:seed', ['--class' => 'DevelopmentSeeder']);
        $this->info('✓ 開發資料已設定');
    }

    /**
     * 顯示快速命令
     */
    private function displayQuickCommands(): void
    {
        $this->info('');
        $this->info('⚡ 常用快速命令：');
        $this->info('');
        $this->info('  🔄 完全重建：');
        $this->info('    php artisan dev:setup --fresh --force');
        $this->info('');
        $this->info('  👥 只重建使用者：');
        $this->info('    php artisan dev:setup --users-only --force');
        $this->info('');
        $this->info('  📊 檢查資料：');
        $this->info('    php artisan tinker');
        $this->info('    >>> User::count()');
        $this->info('    >>> User::with("roles")->get()');
        $this->info('');
        $this->info('  🌐 訪問管理後台：');
        $this->info('    http://localhost/admin/login');
        $this->info('    帳號: admin / 密碼: password123');
    }
}
