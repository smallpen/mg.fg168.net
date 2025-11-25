<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\Player;
use App\Models\PointTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChannelDataReset extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'channel:data-reset 
                            {--force : 強制執行，不詢問確認}
                            {--seed : 重置後重新建立測試資料}
                            {--backup : 重置前建立備份}';

    /**
     * The console command description.
     */
    protected $description = '清理和重置通路管理系統資料';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 通路管理系統資料重置工具');
        $this->info('=' . str_repeat('=', 50));

        // 顯示當前資料統計
        $this->displayCurrentStatistics();

        // 確認操作
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  此操作將清除所有通路管理資料，是否繼續？')) {
                $this->info('操作已取消');
                return 0;
            }
        }

        try {
            // 建立備份（如果指定）
            if ($this->option('backup')) {
                $this->createBackup();
            }

            // 執行資料重置
            $this->resetData();

            // 重新建立測試資料（如果指定）
            if ($this->option('seed')) {
                $this->seedData();
            }

            $this->info('✅ 資料重置完成');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ 資料重置失敗: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * 顯示當前資料統計
     */
    private function displayCurrentStatistics(): void
    {
        $agentCount = Agent::count();
        $playerCount = Player::count();
        $transactionCount = PointTransaction::count();
        $totalPoints = Agent::sum('total_points');

        $this->info('📊 當前資料統計:');
        $this->info("   - 代理商: {$agentCount} 個");
        $this->info("   - 玩家: {$playerCount} 個");
        $this->info("   - 交易記錄: {$transactionCount} 筆");
        $this->info("   - 總點數: " . number_format($totalPoints, 2));
        $this->info('');
    }

    /**
     * 建立備份
     */
    private function createBackup(): void
    {
        $this->info('💾 建立資料備份...');
        
        try {
            $this->call('channel:backup-restore', [
                'action' => 'backup',
                '--verify' => true
            ]);
            $this->info('✅ 備份建立完成');
        } catch (\Exception $e) {
            $this->warn('⚠️  備份建立失敗: ' . $e->getMessage());
            if (!$this->confirm('是否繼續執行重置？')) {
                throw new \Exception('操作已取消');
            }
        }
    }

    /**
     * 重置資料
     */
    private function resetData(): void
    {
        $this->info('🗑️  清理資料...');
        
        DB::beginTransaction();
        
        try {
            // 按照依賴關係順序刪除
            $this->info('   - 清理點數交易記錄...');
            PointTransaction::truncate();
            
            $this->info('   - 清理玩家資料...');
            Player::truncate();
            
            $this->info('   - 清理代理商資料...');
            Agent::truncate();
            
            // 清理監控警報資料
            $this->info('   - 清理監控警報資料...');
            DB::table('channel_monitoring_alerts')->truncate();
            
            DB::commit();
            $this->info('✅ 資料清理完成');
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('資料清理失敗: ' . $e->getMessage());
        }
    }

    /**
     * 重新建立測試資料
     */
    private function seedData(): void
    {
        $this->info('🌱 重新建立測試資料...');
        
        try {
            $this->call('db:seed', [
                '--class' => 'ChannelSeeder'
            ]);
            $this->info('✅ 測試資料建立完成');
        } catch (\Exception $e) {
            throw new \Exception('測試資料建立失敗: ' . $e->getMessage());
        }
    }
}
