<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * LoadingStateService 載入狀態管理服務
 * 
 * 統一管理各種載入狀態和進度追蹤
 */
class LoadingStateService
{
    protected string $cachePrefix = 'loading_state_';
    protected int $defaultCacheTtl = 300; // 5 分鐘
    
    /**
     * 開始載入操作
     */
    public function startLoading(
        string $operationId,
        string $type = 'default',
        array $config = []
    ): void {
        $loadingState = [
            'id' => $operationId,
            'type' => $type,
            'status' => 'loading',
            'progress' => 0,
            'message' => $config['message'] ?? '載入中...',
            'started_at' => now()->timestamp,
            'estimated_duration' => $config['estimatedDuration'] ?? 0,
            'config' => $config
        ];
        
        $cacheKey = $this->getCacheKey($operationId);
        Cache::put($cacheKey, $loadingState, $this->defaultCacheTtl);
        
        // 在測試環境中記錄快取鍵
        $this->recordTestKey($cacheKey);
        
        // 觸發載入開始事件
        event('loading.started', $loadingState);
    }
    
    /**
     * 更新載入進度
     */
    public function updateProgress(
        string $operationId,
        int $progress,
        ?string $message = null
    ): void {
        $loadingState = $this->getLoadingState($operationId);
        
        if (!$loadingState) {
            return;
        }
        
        $loadingState['progress'] = max(0, min(100, $progress));
        $loadingState['updated_at'] = now()->timestamp;
        
        if ($message) {
            $loadingState['message'] = $message;
        }
        
        // 計算預估剩餘時間
        if ($progress > 0 && $loadingState['estimated_duration'] > 0) {
            $elapsedTime = now()->timestamp - $loadingState['started_at'];
            $estimatedTotal = ($elapsedTime / $progress) * 100;
            $loadingState['estimated_remaining'] = max(0, $estimatedTotal - $elapsedTime);
        }
        
        Cache::put(
            $this->getCacheKey($operationId),
            $loadingState,
            $this->defaultCacheTtl
        );
        
        // 觸發進度更新事件
        event('loading.progress', $loadingState);
        
        // 如果完成，自動結束載入
        if ($progress >= 100) {
            $this->finishLoading($operationId, '載入完成');
        }
    }
    
    /**
     * 完成載入操作
     */
    public function finishLoading(
        string $operationId,
        string $message = '載入完成',
        bool $success = true
    ): void {
        $loadingState = $this->getLoadingState($operationId);
        
        if (!$loadingState) {
            return;
        }
        
        $loadingState['status'] = $success ? 'completed' : 'failed';
        $loadingState['progress'] = 100;
        $loadingState['message'] = $message;
        $loadingState['finished_at'] = now()->timestamp;
        $loadingState['duration'] = now()->timestamp - $loadingState['started_at'];
        
        Cache::put(
            $this->getCacheKey($operationId),
            $loadingState,
            60 // 保留 1 分鐘以供查詢
        );
        
        // 觸發載入完成事件
        event('loading.finished', $loadingState);
    }
    
    /**
     * 取得載入狀態
     */
    public function getLoadingState(string $operationId): ?array
    {
        return Cache::get($this->getCacheKey($operationId));
    }
    
    /**
     * 檢查是否正在載入
     */
    public function isLoading(string $operationId): bool
    {
        $state = $this->getLoadingState($operationId);
        return $state && $state['status'] === 'loading';
    }
    
    /**
     * 取得所有活躍的載入操作
     */
    public function getActiveLoadings(): array
    {
        // 在測試環境中使用不同的方法
        if (app()->environment('testing')) {
            return $this->getActiveLoadingsForTesting();
        }
        
        $pattern = $this->cachePrefix . '*';
        $keys = Cache::getRedis()->keys($pattern);
        $loadings = [];
        
        foreach ($keys as $key) {
            $state = Cache::get($key);
            if ($state && $state['status'] === 'loading') {
                $loadings[] = $state;
            }
        }
        
        return $loadings;
    }
    
    /**
     * 測試環境中取得活躍載入操作
     */
    protected function getActiveLoadingsForTesting(): array
    {
        static $testOperations = [];
        
        // 清理已完成或不存在的操作
        foreach ($testOperations as $id => $operation) {
            $state = $this->getLoadingState($id);
            if (!$state || $state['status'] !== 'loading') {
                unset($testOperations[$id]);
            }
        }
        
        // 返回當前活躍的操作狀態
        $loadings = [];
        foreach ($testOperations as $id => $operation) {
            $state = $this->getLoadingState($id);
            if ($state && $state['status'] === 'loading') {
                $loadings[] = $state;
            }
        }
        
        return $loadings;
    }
    
    /**
     * 記錄測試操作
     */
    protected function recordTestOperation(string $operationId): void
    {
        if (app()->environment('testing')) {
            static $testOperations = [];
            $testOperations[$operationId] = true;
        }
    }
    
    /**
     * 清除載入狀態
     */
    public function clearLoadingState(string $operationId): void
    {
        Cache::forget($this->getCacheKey($operationId));
    }
    
    /**
     * 清除所有載入狀態
     */
    public function clearAllLoadingStates(): void
    {
        if (app()->environment('testing')) {
            // 在測試環境中，清除所有以前綴開頭的快取鍵
            Cache::flush();
            return;
        }
        
        $pattern = $this->cachePrefix . '*';
        $keys = Cache::getRedis()->keys($pattern);
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
    
    /**
     * 設定載入步驟
     */
    public function setLoadingSteps(string $operationId, array $steps): void
    {
        $loadingState = $this->getLoadingState($operationId);
        
        if (!$loadingState) {
            return;
        }
        
        $loadingState['steps'] = $steps;
        $loadingState['current_step'] = 0;
        
        Cache::put(
            $this->getCacheKey($operationId),
            $loadingState,
            $this->defaultCacheTtl
        );
    }
    
    /**
     * 更新當前步驟
     */
    public function updateCurrentStep(
        string $operationId,
        int $stepIndex,
        ?string $message = null
    ): void {
        $loadingState = $this->getLoadingState($operationId);
        
        if (!$loadingState || !isset($loadingState['steps'])) {
            return;
        }
        
        $loadingState['current_step'] = $stepIndex;
        
        if ($message) {
            $loadingState['message'] = $message;
        } elseif (isset($loadingState['steps'][$stepIndex])) {
            $loadingState['message'] = $loadingState['steps'][$stepIndex];
        }
        
        // 根據步驟計算進度
        $totalSteps = count($loadingState['steps']);
        $loadingState['progress'] = (int) (($stepIndex + 1) / $totalSteps * 100);
        
        Cache::put(
            $this->getCacheKey($operationId),
            $loadingState,
            $this->defaultCacheTtl
        );
        
        // 觸發步驟更新事件
        event('loading.step', $loadingState);
    }
    
    /**
     * 記錄載入錯誤
     */
    public function recordError(
        string $operationId,
        string $error,
        array $context = []
    ): void {
        $loadingState = $this->getLoadingState($operationId);
        
        if (!$loadingState) {
            return;
        }
        
        $loadingState['status'] = 'error';
        $loadingState['error'] = $error;
        $loadingState['error_context'] = $context;
        $loadingState['error_at'] = now()->timestamp;
        
        Cache::put(
            $this->getCacheKey($operationId),
            $loadingState,
            $this->defaultCacheTtl
        );
        
        // 觸發錯誤事件
        event('loading.error', $loadingState);
    }
    
    /**
     * 取得載入統計
     */
    public function getLoadingStats(): array
    {
        $activeLoadings = $this->getActiveLoadings();
        
        $stats = [
            'total_active' => count($activeLoadings),
            'by_type' => [],
            'average_progress' => 0,
            'longest_running' => null
        ];
        
        if (empty($activeLoadings)) {
            return $stats;
        }
        
        $totalProgress = 0;
        $longestDuration = 0;
        
        foreach ($activeLoadings as $loading) {
            $type = $loading['type'];
            $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
            
            $totalProgress += $loading['progress'];
            
            $duration = now()->timestamp - $loading['started_at'];
            if ($duration > $longestDuration) {
                $longestDuration = $duration;
                $stats['longest_running'] = $loading;
            }
        }
        
        $stats['average_progress'] = (int) ($totalProgress / count($activeLoadings));
        
        return $stats;
    }
    
    /**
     * 取得快取鍵
     */
    protected function getCacheKey(string $operationId): string
    {
        return $this->cachePrefix . $operationId;
    }
    
    /**
     * 生成唯一操作 ID
     */
    public function generateOperationId(string $prefix = 'op'): string
    {
        return $prefix . '_' . uniqid() . '_' . now()->timestamp;
    }
    
    /**
     * 批次開始載入操作
     */
    public function startBatchLoading(array $operations): array
    {
        $operationIds = [];
        
        foreach ($operations as $operation) {
            $operationId = $this->generateOperationId($operation['prefix'] ?? 'batch');
            $this->startLoading(
                $operationId,
                $operation['type'] ?? 'default',
                $operation['config'] ?? []
            );
            $operationIds[] = $operationId;
        }
        
        return $operationIds;
    }
    
    /**
     * 批次更新進度
     */
    public function updateBatchProgress(array $progressUpdates): void
    {
        foreach ($progressUpdates as $operationId => $progress) {
            if (is_array($progress)) {
                $this->updateProgress(
                    $operationId,
                    $progress['progress'],
                    $progress['message'] ?? null
                );
            } else {
                $this->updateProgress($operationId, $progress);
            }
        }
    }
}