<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UserCacheService;
use App\Repositories\UserRepository;

class WarmUserCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm-users {--clear : 清除現有快取後重新預熱}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '預熱使用者管理相關快取';

    /**
     * Execute the console command.
     */
    public function handle(UserCacheService $cacheService, UserRepository $userRepository)
    {
        $this->info('開始預熱使用者快取...');

        // 如果指定清除選項，先清除現有快取
        if ($this->option('clear')) {
            $this->info('清除現有快取...');
            $cacheService->clearAll();
        }

        // 預熱快取
        $this->info('預熱角色列表快取...');
        $roles = $userRepository->getAvailableRoles();
        $this->line("已快取 {$roles->count()} 個角色");

        $this->info('預熱使用者統計快取...');
        $stats = $userRepository->getUserStats();
        $this->line("已快取統計資料：總使用者 {$stats['total_users']} 人");

        // 預熱常用查詢
        $this->info('預熱常用查詢快取...');
        $commonFilters = [
            ['status' => 'all', 'role' => 'all'],
            ['status' => 'active', 'role' => 'all'],
            ['status' => 'inactive', 'role' => 'all'],
        ];

        foreach ($commonFilters as $filters) {
            $filters['sort_field'] = 'created_at';
            $filters['sort_direction'] = 'desc';
            $users = $userRepository->getPaginatedUsers($filters, 15);
            $this->line("已快取查詢：狀態={$filters['status']}, 角色={$filters['role']}, 結果={$users->total()}筆");
        }

        $this->info('快取預熱完成！');

        // 顯示快取統計
        $cacheStats = $cacheService->getCacheStats();
        $this->info('快取狀態：');
        foreach ($cacheStats as $name => $stat) {
            $status = $stat['exists'] ? '✓' : '✗';
            $this->line("  {$status} {$name}: {$stat['key']}");
        }

        return Command::SUCCESS;
    }
}
