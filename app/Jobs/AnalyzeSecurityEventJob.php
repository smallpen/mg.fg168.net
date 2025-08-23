<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Services\SecurityAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 安全事件分析任務
 * 
 * 非同步分析活動記錄的安全風險並生成警報
 */
class AnalyzeSecurityEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任務超時時間（秒）
     */
    public int $timeout = 300;

    /**
     * 最大重試次數
     */
    public int $tries = 3;

    /**
     * 要分析的活動 ID
     */
    protected int $activityId;

    /**
     * 建立新的任務實例
     */
    public function __construct(int $activityId)
    {
        $this->activityId = $activityId;
        $this->onQueue('security-analysis');
    }

    /**
     * 執行任務
     */
    public function handle(SecurityAnalyzer $analyzer): void
    {
        try {
            $activity = Activity::find($this->activityId);
            
            if (!$activity) {
                Log::warning('Activity not found for security analysis', [
                    'activity_id' => $this->activityId
                ]);
                return;
            }

            // 分析活動安全風險
            $analysis = $analyzer->analyzeActivity($activity);
            
            // 更新活動的風險等級
            $activity->update([
                'risk_level' => $analyzer::RISK_LEVELS[$analysis['risk_level']]
            ]);

            // 如果檢測到安全事件，生成警報
            if (!empty($analysis['security_events'])) {
                $alert = $analyzer->generateSecurityAlert($activity, $analysis['security_events']);
                
                if ($alert) {
                    Log::info('Security alert generated', [
                        'alert_id' => $alert->id,
                        'activity_id' => $activity->id,
                        'risk_level' => $analysis['risk_level']
                    ]);
                }
            }

            Log::debug('Security analysis completed', [
                'activity_id' => $activity->id,
                'risk_score' => $analysis['risk_score'],
                'risk_level' => $analysis['risk_level'],
                'security_events_count' => count($analysis['security_events'])
            ]);

        } catch (\Exception $e) {
            Log::error('Security analysis failed', [
                'activity_id' => $this->activityId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * 任務失敗時的處理
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Security analysis job failed permanently', [
            'activity_id' => $this->activityId,
            'error' => $exception->getMessage()
        ]);
    }
}