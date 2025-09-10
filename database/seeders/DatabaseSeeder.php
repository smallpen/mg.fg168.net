<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 主要資料庫種子檔案
 * 
 * 根據環境自動選擇適當的種子策略：
 * - 生產環境: 只建立必要的基礎資料
 * - 開發/測試環境: 建立完整的測試資料
 * 
 * 使用方式：
 * - 標準執行: php artisan db:seed
 * - 完整測試資料: php artisan db:seed --class=CompleteTestDataSeeder
 */
class DatabaseSeeder extends Seeder
{
    /**
     * 執行資料庫種子
     */
    public function run(): void
    {
        $environment = app()->environment();
        
        $this->command->info('');
        $this->command->info('=== Laravel 管理系統資料庫初始化 ===');
        $this->command->info("當前環境: {$environment}");
        $this->command->info('');
        
        if ($environment === 'production') {
            // 生產環境 - 只建立必要資料
            $this->runProductionSeeds();
        } else {
            // 開發/測試環境 - 建立完整資料
            $this->runDevelopmentSeeds();
        }
    }

    /**
     * 執行生產環境種子
     */
    private function runProductionSeeds(): void
    {
        $this->command->info('🔒 執行生產環境種子（精簡模式）...');
        $this->command->info('');
        
        // 按照依賴順序執行核心種子檔案
        $this->call([
            PermissionSeeder::class,  // 建立系統權限
            RoleSeeder::class,        // 建立角色並指派權限
            UserSeeder::class,        // 建立管理員帳號
            SettingsSeeder::class,    // 建立系統設定
        ]);
        
        $this->command->info('');
        $this->command->info('✅ 生產環境初始化完成');
        $this->command->info('🔑 預設管理員: admin / admin123');
        $this->command->warn('⚠️  請立即修改預設密碼！');
    }

    /**
     * 執行開發環境種子
     */
    private function runDevelopmentSeeds(): void
    {
        $this->command->info('🛠️  執行開發環境種子（完整模式）...');
        $this->command->info('');
        
        // 使用完整測試資料 seeder
        $this->call(CompleteTestDataSeeder::class);
        
        $this->command->info('');
        $this->command->info('✅ 開發環境初始化完成');
        $this->command->info('');
        $this->command->info('💡 提示：');
        $this->command->info('   • 如需重建測試資料: php artisan db:seed --class=CompleteTestDataSeeder');
        $this->command->info('   • 如需單獨執行某個 seeder: php artisan db:seed --class=PermissionSeeder');
    }
}