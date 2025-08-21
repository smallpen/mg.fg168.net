<?php

namespace App\Console\Commands;

use App\Services\RoleStatisticsCacheManager;
use App\Services\RoleStatisticsService;
use Illuminate\Console\Command;

/**
 * 角色統計快取管理命令
 */
class RoleStatsCacheCommand extends Command
{
    /**
     * 命令名稱和簽名
     */
    protected $signature = 'role-stats:cache 
                            {action : 操作類型 (clear|warm|status)}
                            {--force : 強制執行操作}';

    /**
     * 命令描述
     */
    protected $description = '管理角色統計快取 (清除、預熱、狀態檢查)';

    /**
     * 快取管理服務
     */
    private RoleStatisticsCacheManager $cacheManager;

    /**
     * 統計服務
     */
    private RoleStatisticsService $statisticsService;

    /**
     * 建立命令實例
     */
    public function __construct(
        RoleStatisticsCacheManager $cacheManager,
        RoleStatisticsService $statisticsService
    ) {
        parent::__construct();
        $this->cacheManager = $cacheManager;
        $this->statisticsService = $statisticsService;
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'clear' => $this->clearCache(),
            'warm' => $this->warmCache(),
            'status' => $this->showStatus(),
            default => $this->showHelp(),
        };
    }

    /**
     * 清除快取
     */
    private function clearCache(): int
    {
        if (!$this->option('force') && !$this->confirm('確定要清除所有角色統計快取嗎？')) {
            $this->info('操作已取消');
            return 0;
        }

        $this->info('正在清除角色統計快取...');
        
        try {
            $this->cacheManager->clearAllCache();
            $this->info('✅ 角色統計快取已清除');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ 清除快取失敗: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * 預熱快取
     */
    private function warmCache(): int
    {
        $this->info('正在預熱角色統計快取...');
        
        try {
            $this->cacheManager->warmUpCache();
            $this->info('✅ 角色統計快取預熱完成');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ 預熱快取失敗: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * 顯示快取狀態
     */
    private function showStatus(): int
    {
        $this->info('角色統計快取狀態:');
        
        try {
            $status = $this->cacheManager->getCacheStatus();
            
            // 系統快取狀態
            $this->line('');
            $this->line('<comment>系統快取狀態:</comment>');
            foreach ($status['system_cache'] as $name => $cached) {
                $icon = $cached ? '✅' : '❌';
                $this->line("  {$icon} {$name}: " . ($cached ? '已快取' : '未快取'));
            }
            
            // 角色快取統計
            $this->line('');
            $this->line('<comment>角色快取統計:</comment>');
            $this->line("  📊 總角色數: {$status['total_roles']}");
            $this->line("  💾 已快取角色數: {$status['role_cache_count']}");
            $this->line("  📈 快取覆蓋率: {$status['cache_coverage']}%");
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ 取得快取狀態失敗: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * 顯示幫助資訊
     */
    private function showHelp(): int
    {
        $this->error('無效的操作類型');
        $this->line('');
        $this->line('<comment>可用操作:</comment>');
        $this->line('  <info>clear</info>  - 清除所有角色統計快取');
        $this->line('  <info>warm</info>   - 預熱角色統計快取');
        $this->line('  <info>status</info> - 顯示快取狀態');
        $this->line('');
        $this->line('<comment>範例:</comment>');
        $this->line('  <info>php artisan role-stats:cache clear --force</info>');
        $this->line('  <info>php artisan role-stats:cache warm</info>');
        $this->line('  <info>php artisan role-stats:cache status</info>');
        
        return 1;
    }
}