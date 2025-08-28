<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 主要資料庫種子檔案
 * 
 * 根據環境自動選擇適當的種子策略
 * - 生產環境: 只建立必要的基礎資料
 * - 開發環境: 建立完整的測試資料
 */
class DatabaseSeeder extends Seeder
{
    /**
     * 執行資料庫種子
     */
    public function run(): void
    {
        $environment = app()->environment();
        
        $this->command->info("當前環境: {$environment}");
        
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
        $this->command->info('執行生產環境種子...');
        $this->call(ProductionSeeder::class);
    }

    /**
     * 執行開發環境種子
     */
    private function runDevelopmentSeeds(): void
    {
        $this->command->info('執行開發環境種子...');
        
        // 按照依賴順序執行種子檔案
        $this->call([
            PermissionSeeder::class,  // 建立權限
            RoleSeeder::class,        // 建立角色並指派權限
            UserSeeder::class,        // 建立使用者並指派角色
            SettingsSeeder::class,    // 建立系統設定
        ]);

        // 開發環境額外的測試資料
        if (class_exists(DevelopmentSeeder::class)) {
            $this->call(DevelopmentSeeder::class);
        }
    }
}