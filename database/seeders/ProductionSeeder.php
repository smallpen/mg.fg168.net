<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 生產環境種子檔案
 * 
 * 專為生產環境設計的精簡種子檔案
 * 只包含系統運行必需的基礎資料
 */
class ProductionSeeder extends Seeder
{
    /**
     * 執行生產環境種子
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('=== 開始初始化生產環境 ===');
        $this->command->info('');

        // 按照依賴順序執行核心種子檔案
        $seeders = [
            PermissionSeeder::class => '建立系統權限',
            RoleSeeder::class => '建立系統角色',
            UserSeeder::class => '建立管理員帳號',
            SettingsSeeder::class => '初始化系統設定',
        ];

        foreach ($seeders as $seederClass => $description) {
            $this->command->info("正在執行: {$description}...");
            $this->call($seederClass);
        }

        $this->displayProductionInfo();
    }

    /**
     * 顯示生產環境部署資訊
     */
    private function displayProductionInfo(): void
    {
        $this->command->info('');
        $this->command->info('=== 生產環境初始化完成 ===');
        $this->command->info('');
        $this->command->info('🎉 系統已準備就緒！');
        $this->command->info('');
        $this->command->info('📋 系統資訊:');
        $this->command->info('   • 權限系統: 已建立完整權限結構');
        $this->command->info('   • 角色系統: 已建立基本角色 (管理員、主管、使用者)');
        $this->command->info('   • 管理帳號: admin / admin123');
        $this->command->info('   • 系統設定: 已載入預設配置');
        $this->command->info('');
        $this->command->info('🔐 安全提醒:');
        $this->command->warn('   1. 立即登入並修改預設管理員密碼');
        $this->command->warn('   2. 建立專屬的管理員帳號');
        $this->command->warn('   3. 檢查並調整系統設定');
        $this->command->warn('   4. 設定適當的檔案權限');
        $this->command->info('');
        $this->command->info('🌐 存取資訊:');
        $this->command->info('   • 管理後台: /admin/login');
        $this->command->info('   • 使用者名稱: admin');
        $this->command->info('   • 預設密碼: admin123');
        $this->command->info('');
        $this->command->info('系統現在可以安全使用了！');
    }
}