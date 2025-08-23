<?php

namespace App\Listeners;

use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Log;

/**
 * 系統事件監聽器
 * 
 * 監聽並記錄各種系統事件
 */
class SystemEventListener
{
    /**
     * 活動記錄服務
     *
     * @var ActivityLogger
     */
    protected ActivityLogger $activityLogger;

    /**
     * 建構函式
     *
     * @param ActivityLogger $activityLogger
     */
    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    /**
     * 處理使用者登入事件
     *
     * @param Login $event
     * @return void
     */
    public function handleLogin(Login $event): void
    {
        $this->activityLogger->logLogin($event->user->id, [
            'guard' => $event->guard,
            'remember' => request()->boolean('remember'),
        ]);
    }

    /**
     * 處理使用者登出事件
     *
     * @param Logout $event
     * @return void
     */
    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            $this->activityLogger->logLogout($event->user->id, [
                'guard' => $event->guard,
            ]);
        }
    }

    /**
     * 處理登入失敗事件
     *
     * @param Failed $event
     * @return void
     */
    public function handleLoginFailed(Failed $event): void
    {
        $credentials = $event->credentials;
        $username = $credentials['username'] ?? $credentials['email'] ?? 'unknown';
        
        $this->activityLogger->logLoginFailed($username, [
            'guard' => $event->guard,
            'reason' => 'invalid_credentials',
        ]);
    }

    /**
     * 處理使用者註冊事件
     *
     * @param Registered $event
     * @return void
     */
    public function handleRegistered(Registered $event): void
    {
        $this->activityLogger->log(
            'user_registered',
            "新使用者註冊：{$event->user->display_name}",
            [
                'module' => 'auth',
                'subject_id' => $event->user->id,
                'subject_type' => get_class($event->user),
                'properties' => [
                    'username' => $event->user->username,
                    'email' => $event->user->email,
                    'registration_method' => 'web',
                ],
                'result' => 'success',
                'risk_level' => 3,
            ]
        );
    }

    /**
     * 處理密碼重設事件
     *
     * @param PasswordReset $event
     * @return void
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->activityLogger->logPasswordChanged($event->user->id, [
            'method' => 'password_reset',
            'forced' => false,
        ]);
    }

    /**
     * 處理工作失敗事件
     *
     * @param JobFailed $event
     * @return void
     */
    public function handleJobFailed(JobFailed $event): void
    {
        $this->activityLogger->logSystemEvent(
            'job_failed',
            [
                'job_name' => $event->job->resolveName(),
                'queue' => $event->job->getQueue(),
                'exception' => $event->exception->getMessage(),
                'attempts' => $event->job->attempts(),
            ]
        );
    }

    /**
     * 處理工作完成事件
     *
     * @param JobProcessed $event
     * @return void
     */
    public function handleJobProcessed(JobProcessed $event): void
    {
        // 只記錄重要的工作完成事件
        $importantJobs = [
            'App\\Jobs\\LogActivityJob',
            'App\\Jobs\\LogActivitiesBatchJob',
            'App\\Jobs\\ProcessSettingsBatch',
        ];

        $jobName = $event->job->resolveName();
        
        if (in_array($jobName, $importantJobs)) {
            $this->activityLogger->logSystemEvent(
                'job_completed',
                [
                    'job_name' => $jobName,
                    'queue' => $event->job->getQueue(),
                    'attempts' => $event->job->attempts(),
                ]
            );
        }
    }

    /**
     * 處理快取命中事件
     *
     * @param CacheHit $event
     * @return void
     */
    public function handleCacheHit(CacheHit $event): void
    {
        // 只記錄重要的快取事件
        if ($this->isImportantCacheKey($event->key)) {
            $this->activityLogger->logSystemEvent(
                'cache_hit',
                [
                    'key' => $event->key,
                    'tags' => $event->tags,
                ]
            );
        }
    }

    /**
     * 處理快取未命中事件
     *
     * @param CacheMissed $event
     * @return void
     */
    public function handleCacheMissed(CacheMissed $event): void
    {
        // 只記錄重要的快取事件
        if ($this->isImportantCacheKey($event->key)) {
            $this->activityLogger->logSystemEvent(
                'cache_missed',
                [
                    'key' => $event->key,
                    'tags' => $event->tags,
                ]
            );
        }
    }

    /**
     * 處理快取鍵刪除事件
     *
     * @param KeyForgotten $event
     * @return void
     */
    public function handleCacheKeyForgotten(KeyForgotten $event): void
    {
        if ($this->isImportantCacheKey($event->key)) {
            $this->activityLogger->logSystemEvent(
                'cache_key_forgotten',
                [
                    'key' => $event->key,
                    'tags' => $event->tags,
                ]
            );
        }
    }

    /**
     * 處理資料庫查詢事件（僅在除錯模式下）
     *
     * @param QueryExecuted $event
     * @return void
     */
    public function handleQueryExecuted(QueryExecuted $event): void
    {
        // 只在除錯模式下記錄慢查詢
        if (config('app.debug') && $event->time > 1000) { // 超過 1 秒的查詢
            $this->activityLogger->logSystemEvent(
                'slow_query',
                [
                    'sql' => $event->sql,
                    'bindings' => $event->bindings,
                    'time' => $event->time,
                    'connection' => $event->connectionName,
                ]
            );
        }
    }

    /**
     * 記錄系統啟動事件
     *
     * @return void
     */
    public function logSystemStartup(): void
    {
        $this->activityLogger->logSystemEvent(
            'system_startup',
            [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'environment' => app()->environment(),
                'debug_mode' => config('app.debug'),
            ]
        );
    }

    /**
     * 記錄系統關閉事件
     *
     * @return void
     */
    public function logSystemShutdown(): void
    {
        $this->activityLogger->logSystemEvent(
            'system_shutdown',
            [
                'uptime' => $this->getSystemUptime(),
                'memory_usage' => memory_get_peak_usage(true),
            ]
        );
    }

    /**
     * 記錄維護模式事件
     *
     * @param bool $enabled
     * @return void
     */
    public function logMaintenanceMode(bool $enabled): void
    {
        $this->activityLogger->logSystemEvent(
            $enabled ? 'maintenance_mode_enabled' : 'maintenance_mode_disabled',
            [
                'enabled' => $enabled,
                'timestamp' => now()->toISOString(),
            ]
        );
    }

    /**
     * 檢查是否為重要的快取鍵
     *
     * @param string $key
     * @return bool
     */
    protected function isImportantCacheKey(string $key): bool
    {
        $importantPatterns = [
            'user_permissions_',
            'role_permissions_',
            'system_settings_',
            'activity_log_',
        ];

        foreach ($importantPatterns as $pattern) {
            if (str_starts_with($key, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 取得系統運行時間
     *
     * @return int
     */
    protected function getSystemUptime(): int
    {
        // 這是一個簡化的實作，實際可能需要更複雜的邏輯
        return time() - (int) cache()->get('system_start_time', time());
    }
}