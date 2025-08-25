<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * 定義應用程式的命令排程
     */
    protected function schedule(Schedule $schedule): void
    {
        // 每日凌晨 2 點執行完整備份
        $schedule->command('backup:system --type=full --cleanup')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->runInBackground();

        // 每 4 小時執行資料庫備份
        $schedule->command('backup:system --type=database')
                 ->cron('0 */4 * * *')
                 ->withoutOverlapping()
                 ->runInBackground();

        // 每 15 分鐘執行系統監控和警報檢查
        $schedule->command('monitor:system --check=alerts')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping()
                 ->runInBackground();

        // 每小時收集效能指標
        $schedule->command('monitor:system --check=performance')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground();

        // 每 5 分鐘執行健康檢查
        $schedule->command('monitor:system --check=health')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->runInBackground();

        // 每週日凌晨 1 點清理舊日誌檔案（保留 30 天）
        $schedule->call(function () {
            $logPath = storage_path('logs');
            $files = glob($logPath . '/*.log');
            $cutoffTime = time() - (30 * 24 * 60 * 60); // 30 天前
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoffTime) {
                    unlink($file);
                }
            }
        })->weekly()->sundays()->at('01:00');

        // 每小時清理過期的 Session
        $schedule->command('sessions:cleanup')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground();

        // 每月第一天凌晨 3 點清理權限審計日誌（保留 365 天）
        $schedule->command('permission:audit-cleanup --days=365 --force')
                 ->monthlyOn(1, '03:00')
                 ->withoutOverlapping()
                 ->runInBackground();

        // 每週日凌晨 4 點清理一般審計日誌（保留 90 天）
        $schedule->command('audit:cleanup --days=90')
                 ->weekly()
                 ->sundays()
                 ->at('04:00')
                 ->withoutOverlapping()
                 ->runInBackground();

        // 權限系統效能優化
        $schedule->command('permission:optimize --warmup --cleanup')
                ->daily()
                ->at('02:30')
                ->withoutOverlapping()
                ->runInBackground()
                ->description('每日權限系統效能優化');

        // 權限效能分析報告
        $schedule->command('permission:optimize --analyze --report')
                ->weekly()
                ->sundays()
                ->at('03:30')
                ->withoutOverlapping()
                ->runInBackground()
                ->description('每週權限效能分析報告');

        // 清理過期快取
        $schedule->call(function () {
            // 檢查是否需要清除快取
            if (\Illuminate\Support\Facades\Cache::get('permission_cache_needs_clear', false)) {
                // 簡單清除快取，不依賴服務
                \Illuminate\Support\Facades\Cache::flush();
                \Illuminate\Support\Facades\Cache::forget('permission_cache_needs_clear');
                \Illuminate\Support\Facades\Log::info('自動清除權限快取');
            }
        })->everyFiveMinutes()->description('檢查並清理權限快取');

        // 每日凌晨 3 點執行活動記錄備份
        $schedule->command('activity:backup --days-back=30 --cleanup --cleanup-days=90')
                 ->dailyAt('03:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->description('每日活動記錄備份');

        // 每週日凌晨 5 點執行完整活動記錄備份（包含所有資料）
        $schedule->command('activity:backup --cleanup --cleanup-days=180')
                 ->weekly()
                 ->sundays()
                 ->at('05:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->description('每週完整活動記錄備份');

        // 語言效能監控排程任務
        
        // 每 30 分鐘預熱語言檔案快取
        $schedule->command('language:performance warmup')
                 ->everyThirtyMinutes()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->description('預熱語言檔案快取');

        // 每小時檢查語言效能警報
        $schedule->command('language:performance alerts --format=json')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->description('檢查語言效能警報');

        // 每日凌晨 1:30 清理語言效能資料（保留 48 小時）
        $schedule->command('language:performance clear-data --hours=48')
                 ->dailyAt('01:30')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->description('清理舊的語言效能資料');

        // 每週一凌晨 2:30 清理語言檔案快取並重新預熱
        $schedule->command('language:performance clear-cache')
                 ->weekly()
                 ->mondays()
                 ->at('02:30')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->description('每週清理語言快取');

        $schedule->command('language:performance warmup')
                 ->weekly()
                 ->mondays()
                 ->at('02:35')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->description('每週重新預熱語言快取');
    }

    /**
     * 註冊應用程式的命令
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}