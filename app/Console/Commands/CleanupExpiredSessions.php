<?php

namespace App\Console\Commands;

use App\Services\SessionSecurityService;
use Illuminate\Console\Command;

/**
 * 清理過期 Session 命令
 * 
 * 定期清理過期的使用者 Session
 */
class CleanupExpiredSessions extends Command
{
    /**
     * 命令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'sessions:cleanup 
                            {--force : 強制清理所有過期 Session}
                            {--dry-run : 僅顯示將要清理的 Session 數量，不實際執行}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '清理過期的使用者 Session';

    /**
     * Session 安全服務
     */
    protected SessionSecurityService $sessionService;

    /**
     * 建構函式
     */
    public function __construct(SessionSecurityService $sessionService)
    {
        parent::__construct();
        $this->sessionService = $sessionService;
    }

    /**
     * 執行命令
     *
     * @return int
     */
    public function handle()
    {
        $this->info('開始清理過期 Session...');
        
        try {
            // 取得 Session 統計
            $stats = $this->sessionService->getSessionStats();
            
            $this->info("目前 Session 統計：");
            $this->line("  總 Session 數：{$stats['total_sessions']}");
            $this->line("  活躍 Session 數：{$stats['active_sessions']}");
            $this->line("  已認證 Session 數：{$stats['authenticated_sessions']}");
            $this->line("  訪客 Session 數：{$stats['guest_sessions']}");
            
            // 如果是 dry-run 模式
            if ($this->option('dry-run')) {
                $expiredCount = $this->getExpiredSessionCount();
                $this->info("將清理 {$expiredCount} 個過期 Session（dry-run 模式）");
                return Command::SUCCESS;
            }
            
            // 執行清理
            $cleanedCount = $this->sessionService->cleanupExpiredSessions();
            
            if ($cleanedCount > 0) {
                $this->info("成功清理了 {$cleanedCount} 個過期 Session");
                
                // 記錄清理操作
                activity()
                    ->withProperties([
                        'cleaned_sessions' => $cleanedCount,
                        'command' => 'sessions:cleanup',
                        'options' => $this->options(),
                    ])
                    ->log('執行 Session 清理命令');
                    
            } else {
                $this->info('沒有找到需要清理的過期 Session');
            }
            
            // 顯示清理後的統計
            $newStats = $this->sessionService->getSessionStats();
            $this->info("清理後 Session 統計：");
            $this->line("  總 Session 數：{$newStats['total_sessions']}");
            $this->line("  活躍 Session 數：{$newStats['active_sessions']}");
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Session 清理失敗：' . $e->getMessage());
            
            // 記錄錯誤
            activity()
                ->withProperties([
                    'error' => $e->getMessage(),
                    'command' => 'sessions:cleanup',
                ])
                ->log('Session 清理命令執行失敗');
                
            return Command::FAILURE;
        }
    }
    
    /**
     * 取得過期 Session 數量（不實際刪除）
     */
    protected function getExpiredSessionCount(): int
    {
        $expiredTime = now()->subMinutes(SessionSecurityService::SESSION_LIFETIME)->timestamp;
        
        return \DB::table('sessions')
            ->where('last_activity', '<', $expiredTime)
            ->count();
    }
    
    /**
     * 顯示詳細的 Session 資訊
     */
    protected function showDetailedStats(): void
    {
        $this->info('詳細 Session 統計：');
        
        // 按小時分組的活躍 Session
        $hourlyStats = \DB::table('sessions')
            ->selectRaw('HOUR(FROM_UNIXTIME(last_activity)) as hour, COUNT(*) as count')
            ->where('last_activity', '>', now()->subDay()->timestamp)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
            
        $this->table(
            ['小時', 'Session 數量'],
            $hourlyStats->map(function ($stat) {
                return [$stat->hour . ':00', $stat->count];
            })->toArray()
        );
        
        // 按使用者分組的 Session（前 10 名）
        $userStats = \DB::table('sessions')
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->selectRaw('users.name, users.email, COUNT(*) as session_count')
            ->whereNotNull('sessions.user_id')
            ->where('sessions.last_activity', '>', now()->subHour()->timestamp)
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('session_count')
            ->limit(10)
            ->get();
            
        if ($userStats->isNotEmpty()) {
            $this->info('活躍使用者 Session 統計（前 10 名）：');
            $this->table(
                ['使用者', '電子郵件', 'Session 數量'],
                $userStats->map(function ($stat) {
                    return [$stat->name, $stat->email, $stat->session_count];
                })->toArray()
            );
        }
    }
    
    /**
     * 清理特定使用者的 Session
     */
    protected function cleanupUserSessions(int $userId): int
    {
        return \DB::table('sessions')
            ->where('user_id', $userId)
            ->delete();
    }
    
    /**
     * 清理訪客 Session
     */
    protected function cleanupGuestSessions(): int
    {
        $expiredTime = now()->subHours(2)->timestamp; // 訪客 Session 2 小時過期
        
        return \DB::table('sessions')
            ->whereNull('user_id')
            ->where('last_activity', '<', $expiredTime)
            ->delete();
    }
    
    /**
     * 驗證 Session 資料完整性
     */
    protected function validateSessionIntegrity(): array
    {
        $issues = [];
        
        // 檢查孤立的 Session（使用者已被刪除）
        $orphanedSessions = \DB::table('sessions')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->whereNotNull('sessions.user_id')
            ->whereNull('users.id')
            ->count();
            
        if ($orphanedSessions > 0) {
            $issues[] = "發現 {$orphanedSessions} 個孤立的 Session（使用者已被刪除）";
        }
        
        // 檢查異常大的 Session 資料
        $largeSessions = \DB::table('sessions')
            ->whereRaw('LENGTH(payload) > 10000') // 大於 10KB
            ->count();
            
        if ($largeSessions > 0) {
            $issues[] = "發現 {$largeSessions} 個異常大的 Session";
        }
        
        return $issues;
    }
    
    /**
     * 修復 Session 資料問題
     */
    protected function repairSessionIssues(): void
    {
        $this->info('修復 Session 資料問題...');
        
        // 清理孤立的 Session
        $orphanedCount = \DB::table('sessions')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->whereNotNull('sessions.user_id')
            ->whereNull('users.id')
            ->delete();
            
        if ($orphanedCount > 0) {
            $this->info("清理了 {$orphanedCount} 個孤立的 Session");
        }
        
        // 清理異常大的 Session
        $largeSessionCount = \DB::table('sessions')
            ->whereRaw('LENGTH(payload) > 50000') // 大於 50KB 的直接刪除
            ->delete();
            
        if ($largeSessionCount > 0) {
            $this->info("清理了 {$largeSessionCount} 個異常大的 Session");
        }
    }
}