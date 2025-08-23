<?php

namespace App\Listeners;

use App\Events\ActivityLogged;
use App\Jobs\AnalyzeSecurityEventJob;
use App\Services\SecurityAnalyzer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * 安全事件監聽器
 * 
 * 監聽活動記錄事件並觸發安全分析
 */
class SecurityEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * 安全分析器
     */
    protected SecurityAnalyzer $analyzer;

    /**
     * 建立監聽器實例
     */
    public function __construct(SecurityAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    /**
     * 處理事件
     */
    public function handle(ActivityLogged $event): void
    {
        $activity = $event->activity;

        try {
            // 快速檢查是否需要進行安全分析
            if ($this->shouldAnalyze($activity)) {
                // 對於高風險操作，立即進行同步分析
                if ($this->isHighRiskOperation($activity)) {
                    $this->performImmediateAnalysis($activity);
                } else {
                    // 其他操作使用非同步分析
                    AnalyzeSecurityEventJob::dispatch($activity->id);
                }
            }

        } catch (\Exception $e) {
            Log::error('Security event listener failed', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 判斷是否需要進行安全分析
     */
    protected function shouldAnalyze($activity): bool
    {
        // 排除一些低風險的操作
        $lowRiskTypes = [
            'dashboard.view',
            'profile.view',
            'logout'
        ];

        return !in_array($activity->type, $lowRiskTypes);
    }

    /**
     * 判斷是否為高風險操作
     */
    protected function isHighRiskOperation($activity): bool
    {
        $highRiskTypes = [
            'login', // 登入操作需要立即檢查
            'users.delete',
            'roles.delete',
            'permissions.delete',
            'system.settings',
            'security'
        ];

        foreach ($highRiskTypes as $type) {
            if (str_contains($activity->type, $type)) {
                return true;
            }
        }

        // 檢查是否為失敗操作
        return $activity->result === 'failed';
    }

    /**
     * 執行立即安全分析
     */
    protected function performImmediateAnalysis($activity): void
    {
        $analysis = $this->analyzer->analyzeActivity($activity);
        
        // 更新風險等級
        $activity->update([
            'risk_level' => SecurityAnalyzer::RISK_LEVELS[$analysis['risk_level']]
        ]);

        // 生成安全警報
        if (!empty($analysis['security_events'])) {
            $alert = $this->analyzer->generateSecurityAlert($activity, $analysis['security_events']);
            
            if ($alert && $alert->severity === 'critical') {
                // 對於嚴重警報，可以觸發即時通知
                Log::critical('Critical security alert generated', [
                    'alert_id' => $alert->id,
                    'activity_id' => $activity->id,
                    'description' => $alert->description
                ]);
            }
        }
    }
}