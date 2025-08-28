<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 部署專用種子檔案
 * 
 * 專為系統部署設計的一鍵初始化種子檔案
 * 包含完整的初始化流程和驗證
 */
class DeploymentSeeder extends Seeder
{
    /**
     * 執行部署種子
     */
    public function run(): void
    {
        $this->displayWelcome();
        
        // 執行生產環境種子
        $this->call(ProductionSeeder::class);
        
        // 執行資料完整性檢查
        $this->call(DataIntegritySeeder::class);
        
        $this->displayDeploymentComplete();
    }

    /**
     * 顯示歡迎訊息
     */
    private function displayWelcome(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════════════╗');
        $this->command->info('║                    系統部署初始化                            ║');
        $this->command->info('║                Laravel Admin System                         ║');
        $this->command->info('╚══════════════════════════════════════════════════════════════╝');
        $this->command->info('');
        $this->command->info('正在初始化系統資料...');
    }

    /**
     * 顯示部署完成訊息
     */
    private function displayDeploymentComplete(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════════════╗');
        $this->command->info('║                    部署完成                                  ║');
        $this->command->info('╚══════════════════════════════════════════════════════════════╝');
        $this->command->info('');
        $this->command->info('🎉 系統已成功部署並初始化！');
        $this->command->info('');
        $this->command->info('📋 快速開始:');
        $this->command->info('   1. 訪問管理後台: /admin/login');
        $this->command->info('   2. 使用預設帳號登入: admin / admin123');
        $this->command->info('   3. 立即修改預設密碼');
        $this->command->info('   4. 建立您的專屬管理員帳號');
        $this->command->info('');
        $this->command->info('🔧 後續設定建議:');
        $this->command->info('   • 檢查系統設定頁面');
        $this->command->info('   • 設定郵件服務');
        $this->command->info('   • 配置檔案上傳設定');
        $this->command->info('   • 檢查權限和角色設定');
        $this->command->info('');
        $this->command->info('📚 更多資訊請參考系統文檔。');
        $this->command->info('');
    }
}