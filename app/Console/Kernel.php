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