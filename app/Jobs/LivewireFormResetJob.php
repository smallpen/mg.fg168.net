<?php

namespace App\Jobs;

use App\Services\LivewireFormReset\FixExecutor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Livewire 表單重置修復任務
 * 
 * 用於佇列處理單個元件的修復操作
 */
class LivewireFormResetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任務最大執行時間（秒）
     */
    public $timeout = 300;

    /**
     * 最大重試次數
     */
    public $tries = 3;

    /**
     * 重試延遲時間（秒）
     */
    public $retryAfter = 60;

    /**
     * 元件資訊
     */
    protected array $componentInfo;

    /**
     * 修復選項
     */
    protected array $options;

    /**
     * 建構函式
     */
    public function __construct(array $componentInfo, array $options = [])
    {
        $this->componentInfo = $componentInfo;
        $this->options = $options;
        
        // 設定佇列名稱
        $this->onQueue($options['queue_name'] ?? 'livewire-form-reset');
    }

    /**
     * 執行任務
     */
    public function handle(): void
    {
        try {
            Log::info("[LivewireFormResetJob] 開始處理元件: {$this->componentInfo['class_name']}");
            
            $executor = new FixExecutor();
            $result = $executor->executeSingleFix($this->componentInfo, $this->options);
            
            // 記錄結果
            $this->logResult($result);
            
            if ($result['status'] === 'failed') {
                throw new Exception("修復失敗: {$result['error']}");
            }
            
            Log::info("[LivewireFormResetJob] 成功完成元件修復: {$this->componentInfo['class_name']}");
            
        } catch (Exception $e) {
            Log::error("[LivewireFormResetJob] 任務執行失敗", [
                'component' => $this->componentInfo['class_name'],
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            
            // 重新拋出異常以觸發重試機制
            throw $e;
        }
    }

    /**
     * 任務失敗處理
     */
    public function failed(Exception $exception): void
    {
        Log::error("[LivewireFormResetJob] 任務最終失敗", [
            'component' => $this->componentInfo['class_name'],
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
        
        // 可以在這裡添加失敗通知邏輯
        $this->notifyFailure($exception);
    }

    /**
     * 記錄修復結果
     */
    protected function logResult(array $result): void
    {
        $logLevel = $result['status'] === 'success' ? 'info' : 'warning';
        
        Log::log($logLevel, "[LivewireFormResetJob] 修復結果", [
            'component' => $result['component'],
            'status' => $result['status'],
            'issues_found' => $result['issues_found'],
            'fixes_applied' => $result['fixes_applied'],
            'execution_time' => $result['execution_time'],
            'validation_passed' => $result['validation_passed'] ?? null,
        ]);
    }

    /**
     * 通知失敗
     */
    protected function notifyFailure(Exception $exception): void
    {
        // 這裡可以實作失敗通知邏輯
        // 例如發送郵件、Slack 通知等
        
        Log::critical("[LivewireFormResetJob] 需要人工介入", [
            'component' => $this->componentInfo['class_name'],
            'final_error' => $exception->getMessage(),
            'component_path' => $this->componentInfo['relative_path'] ?? '',
            'component_type' => $this->componentInfo['classification']['component_type'] ?? 'UNKNOWN',
        ]);
    }

    /**
     * 取得任務標識
     */
    public function getJobIdentifier(): string
    {
        return "livewire-form-reset-{$this->componentInfo['class_name']}";
    }

    /**
     * 取得任務描述
     */
    public function getJobDescription(): string
    {
        return "修復 Livewire 元件表單重置功能: {$this->componentInfo['class_name']}";
    }
}