<?php

namespace App\Jobs;

use App\Services\ActivityBackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 自動備份活動記錄任務
 */
class AutoBackupActivityLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任務執行超時時間（秒）
     *
     * @var int
     */
    public $timeout = 3600; // 1 小時

    /**
     * 任務重試次數
     *
     * @var int
     */
    public $tries = 3;

    /**
     * 備份選項
     *
     * @var array
     */
    protected array $options;

    /**
     * 建立新的任務實例
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'days_back' => 30,
            'cleanup_old_backups' => true,
            'cleanup_days' => 90,
        ], $options);
    }

    /**
     * 執行任務
     *
     * @param ActivityBackupService $backupService
     * @return void
     */
    public function handle(ActivityBackupService $backupService): void
    {
        Log::info('開始執行自動活動記錄備份', $this->options);

        try {
            // 準備備份選項
            $backupOptions = [];
            
            if (isset($this->options['days_back'])) {
                $daysBack = (int) $this->options['days_back'];
                $backupOptions['date_from'] = Carbon::now()->subDays($daysBack)->startOfDay();
                $backupOptions['date_to'] = Carbon::now()->endOfDay();
            }

            // 執行備份
            $result = $backupService->backupActivityLogs($backupOptions);

            if ($result['success']) {
                Log::info('自動活動記錄備份完成', [
                    'backup_name' => $result['backup_name'],
                    'record_count' => $result['data_export']['record_count'] ?? 0,
                    'file_size_mb' => $result['data_export']['file_size_mb'] ?? 0,
                    'compression_ratio' => $result['compression']['compression_ratio'] ?? 0,
                ]);

                // 清理舊備份
                if ($this->options['cleanup_old_backups']) {
                    $cleanupDays = (int) $this->options['cleanup_days'];
                    $cleanupResult = $backupService->cleanupOldActivityBackups($cleanupDays);
                    
                    if ($cleanupResult['success']) {
                        Log::info('自動清理舊備份完成', [
                            'deleted_count' => $cleanupResult['deleted_count'],
                            'deleted_size_mb' => $cleanupResult['deleted_size_mb'],
                        ]);
                    } else {
                        Log::warning('自動清理舊備份失敗', $cleanupResult['errors']);
                    }
                }

            } else {
                Log::error('自動活動記錄備份失敗', [
                    'error' => $result['error'] ?? '未知錯誤',
                    'result' => $result,
                ]);
                
                throw new \Exception('備份失敗: ' . ($result['error'] ?? '未知錯誤'));
            }

        } catch (\Exception $e) {
            Log::error('自動活動記錄備份任務執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'options' => $this->options,
            ]);
            
            throw $e;
        }
    }

    /**
     * 任務失敗時的處理
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('自動活動記錄備份任務最終失敗', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'options' => $this->options,
        ]);
    }

    /**
     * 計算任務重試延遲時間
     *
     * @return array
     */
    public function backoff(): array
    {
        return [60, 300, 900]; // 1分鐘、5分鐘、15分鐘
    }
}