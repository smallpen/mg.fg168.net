<?php

namespace App\Providers;

use App\Services\LoggingService;
use App\Services\MonitoringService;
use App\Services\BackupService;
use Illuminate\Support\ServiceProvider;

/**
 * 監控服務提供者
 * 
 * 註冊監控相關的服務和綁定
 */
class MonitoringServiceProvider extends ServiceProvider
{
    /**
     * 註冊服務
     */
    public function register(): void
    {
        // 註冊日誌服務
        $this->app->singleton(LoggingService::class, function ($app) {
            return new LoggingService();
        });

        // 註冊監控服務
        $this->app->singleton(MonitoringService::class, function ($app) {
            return new MonitoringService($app->make(LoggingService::class));
        });

        // 註冊備份服務
        $this->app->singleton(BackupService::class, function ($app) {
            return new BackupService($app->make(LoggingService::class));
        });
    }

    /**
     * 啟動服務
     */
    public function boot(): void
    {
        // 確保日誌目錄存在
        $this->ensureLogDirectoriesExist();

        // 確保備份目錄存在
        $this->ensureBackupDirectoriesExist();

        // 註冊監控相關的事件監聽器
        $this->registerEventListeners();
    }

    /**
     * 確保日誌目錄存在
     */
    protected function ensureLogDirectoriesExist(): void
    {
        $logPath = storage_path('logs');
        
        if (!is_dir($logPath)) {
            try {
                mkdir($logPath, 0755, true);
            } catch (\Exception $e) {
                // 如果無法建立目錄，記錄錯誤但不中斷應用程式
                error_log("無法建立日誌目錄: " . $e->getMessage());
            }
        }

        // 確保日誌檔案有正確的權限（僅在有權限時執行）
        if (is_dir($logPath) && is_writable($logPath)) {
            try {
                chmod($logPath, 0755);
            } catch (\Exception $e) {
                // 權限設定失敗時不中斷應用程式
                error_log("無法設定日誌目錄權限: " . $e->getMessage());
            }
        }
    }

    /**
     * 確保備份目錄存在
     */
    protected function ensureBackupDirectoriesExist(): void
    {
        $backupPaths = [
            storage_path('backups'),
            storage_path('backups/database'),
            storage_path('backups/files'),
        ];

        foreach ($backupPaths as $path) {
            if (!is_dir($path)) {
                try {
                    mkdir($path, 0755, true);
                } catch (\Exception $e) {
                    // 如果無法建立目錄，記錄錯誤但不中斷應用程式
                    error_log("無法建立備份目錄 {$path}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * 註冊事件監聽器
     */
    protected function registerEventListeners(): void
    {
        // 監聽應用程式啟動事件
        $this->app['events']->listen('Illuminate\Foundation\Events\LocaleUpdated', function ($event) {
            // 當語言變更時，可以記錄到日誌
            if ($this->app->bound(LoggingService::class)) {
                $loggingService = $this->app->make(LoggingService::class);
                $loggingService->logAdminActivity(
                    'locale_changed',
                    'system',
                    ['new_locale' => $event->locale]
                );
            }
        });

        // 監聽認證事件
        $this->app['events']->listen('Illuminate\Auth\Events\Login', function ($event) {
            if ($this->app->bound(LoggingService::class)) {
                $loggingService = $this->app->make(LoggingService::class);
                $loggingService->logLoginAttempt(
                    $event->user->username ?? $event->user->email,
                    true
                );
            }
        });

        $this->app['events']->listen('Illuminate\Auth\Events\Failed', function ($event) {
            if ($this->app->bound(LoggingService::class)) {
                $loggingService = $this->app->make(LoggingService::class);
                $loggingService->logLoginAttempt(
                    $event->credentials['username'] ?? $event->credentials['email'] ?? 'unknown',
                    false,
                    '認證失敗'
                );
            }
        });

        $this->app['events']->listen('Illuminate\Auth\Events\Logout', function ($event) {
            if ($this->app->bound(LoggingService::class)) {
                $loggingService = $this->app->make(LoggingService::class);
                $loggingService->logAdminActivity(
                    'logout',
                    'auth',
                    ['user_id' => $event->user->id ?? null],
                    $event->user->id ?? null
                );
            }
        });
    }

    /**
     * 取得提供的服務
     */
    public function provides(): array
    {
        return [
            LoggingService::class,
            MonitoringService::class,
            BackupService::class,
        ];
    }
}