<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * 系統部署命令
 * 
 * 一鍵執行系統部署和初始化
 */
class SystemDeploy extends Command
{
    /**
     * 命令名稱和簽名
     */
    protected $signature = 'system:deploy 
                            {--fresh : 重新建立資料庫}
                            {--force : 強制執行，不詢問確認}
                            {--check-only : 只執行資料完整性檢查}';

    /**
     * 命令描述
     */
    protected $description = '執行系統部署和初始化';

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $this->displayHeader();

        // 只執行檢查
        if ($this->option('check-only')) {
            return $this->runIntegrityCheck();
        }

        // 確認執行
        if (!$this->option('force') && !$this->confirmExecution()) {
            $this->info('部署已取消。');
            return 0;
        }

        // 執行部署
        return $this->runDeployment();
    }

    /**
     * 顯示標題
     */
    private function displayHeader(): void
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                Laravel Admin System                         ║');
        $this->info('║                    系統部署工具                              ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');
    }

    /**
     * 確認執行
     */
    private function confirmExecution(): bool
    {
        $environment = app()->environment();
        
        $this->warn("當前環境: {$environment}");
        
        if ($environment === 'production') {
            $this->error('⚠️  這是生產環境！');
            $this->warn('此操作將會影響生產資料。');
        }

        if ($this->option('fresh')) {
            $this->error('⚠️  將會重新建立資料庫！');
            $this->warn('所有現有資料將會被刪除。');
        }

        return $this->confirm('確定要繼續執行部署嗎？');
    }

    /**
     * 執行部署
     */
    private function runDeployment(): int
    {
        try {
            $this->info('開始執行系統部署...');
            $this->info('');

            // 重新建立資料庫（如果指定）
            if ($this->option('fresh')) {
                $this->info('🔄 重新建立資料庫...');
                Artisan::call('migrate:fresh', [], $this->getOutput());
            } else {
                $this->info('🔄 執行資料庫遷移...');
                Artisan::call('migrate', ['--force' => true], $this->getOutput());
            }

            // 執行部署種子
            $this->info('🌱 初始化系統資料...');
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\DeploymentSeeder',
                '--force' => true
            ], $this->getOutput());

            // 清除快取
            $this->info('🧹 清除系統快取...');
            Artisan::call('cache:clear', [], $this->getOutput());
            Artisan::call('config:clear', [], $this->getOutput());
            Artisan::call('route:clear', [], $this->getOutput());
            Artisan::call('view:clear', [], $this->getOutput());

            // 優化系統（生產環境）
            if (app()->environment('production')) {
                $this->info('⚡ 優化系統效能...');
                Artisan::call('config:cache', [], $this->getOutput());
                Artisan::call('route:cache', [], $this->getOutput());
                Artisan::call('view:cache', [], $this->getOutput());
            }

            $this->displaySuccess();
            return 0;

        } catch (\Exception $e) {
            $this->error('部署失敗: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * 執行完整性檢查
     */
    private function runIntegrityCheck(): int
    {
        try {
            $this->info('執行資料完整性檢查...');
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\DataIntegritySeeder'
            ], $this->getOutput());
            
            return 0;
        } catch (\Exception $e) {
            $this->error('檢查失敗: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * 顯示成功訊息
     */
    private function displaySuccess(): void
    {
        $this->info('');
        $this->info('✅ 系統部署完成！');
        $this->info('');
        $this->info('🚀 後續步驟:');
        $this->info('   1. 訪問 /admin/login');
        $this->info('   2. 使用 admin / admin123 登入');
        $this->info('   3. 立即修改預設密碼');
        $this->info('');
        $this->info('💡 提示: 使用 php artisan system:deploy --check-only 檢查資料完整性');
    }
}