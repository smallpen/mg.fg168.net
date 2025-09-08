<?php

namespace App\Services\LivewireFormReset;

use Illuminate\Support\Facades\File;

/**
 * 監控控制修復類別
 * 
 * 專門處理監控和效能控制元件的即時更新問題
 */
class MonitoringControlFix extends StandardFormResetFix
{
    /**
     * 取得策略名稱
     */
    public function getStrategyName(): string
    {
        return 'MonitoringControlFix';
    }

    /**
     * 檢查是否支援此元件類型
     */
    public function supports(array $componentInfo): bool
    {
        $componentType = $componentInfo['classification']['component_type'] ?? '';
        $supportedTypes = ['MONITORING_CONTROL', 'DASHBOARD'];
        
        // 也檢查類別名稱是否包含監控相關關鍵字
        $className = $componentInfo['class_name'] ?? '';
        $hasMonitoringKeywords = preg_match('/(?:Monitor|Performance|Stats|Chart|Dashboard)/i', $className);
        
        return in_array($componentType, $supportedTypes) || $hasMonitoringKeywords;
    }

    /**
     * 應用特定修復
     */
    protected function applySpecificFixes(): bool
    {
        $fixed = false;

        // 先執行標準修復
        if (parent::applySpecificFixes()) {
            $fixed = true;
        }

        // 修復時間週期控制
        if ($this->fixTimePeriodControl()) {
            $fixed = true;
        }

        // 修復自動刷新控制
        if ($this->fixAutoRefreshControl()) {
            $fixed = true;
        }

        // 修復即時資料更新
        if ($this->fixRealTimeDataUpdate()) {
            $fixed = true;
        }

        // 修復圖表重置功能
        if ($this->fixChartResetFunctionality()) {
            $fixed = true;
        }

        // 添加效能監控重置
        if ($this->addPerformanceMonitoringReset()) {
            $fixed = true;
        }

        return $fixed;
    }

    /**
     * 執行特定驗證
     */
    protected function performSpecificValidation(): bool
    {
        // 先執行標準驗證
        if (!parent::performSpecificValidation()) {
            return false;
        }

        // 驗證時間控制功能
        if (!$this->validateTimeControlFunctionality()) {
            return false;
        }

        // 驗證自動刷新功能
        if (!$this->validateAutoRefreshFunctionality()) {
            return false;
        }

        // 驗證資料更新機制
        if (!$this->validateDataUpdateMechanism()) {
            return false;
        }

        return true;
    }

    /**
     * 檢查是否能修復此問題
     */
    protected function canFixIssue(array $issue): bool
    {
        $supportedIssueTypes = [
            'missing_wire_key',
            'incorrect_wire_model',
            'missing_refresh_mechanism',
            'time_period_control_issues',
            'auto_refresh_issues',
            'real_time_update_issues',
            'chart_reset_issues',
        ];

        return in_array($issue['type'], $supportedIssueTypes);
    }

    /**
     * 修復時間週期控制
     */
    protected function fixTimePeriodControl(): bool
    {
        $fixed = false;

        // 修復 PHP 檔案中的時間週期控制
        if ($this->fixTimePeriodControlInPhp()) {
            $fixed = true;
        }

        // 修復視圖檔案中的時間週期選擇器
        if ($this->fixTimePeriodControlInView()) {
            $fixed = true;
        }

        return $fixed;
    }

    /**
     * 修復 PHP 檔案中的時間週期控制
     */
    protected function fixTimePeriodControlInPhp(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['path']);
        $originalContent = $content;

        // 檢查是否有時間週期相關屬性
        if (preg_match('/public\s+string\s+\$(?:selectedPeriod|timePeriod|period)/', $content)) {
            // 添加 updatedSelectedPeriod 方法
            if (!preg_match('/function\s+updatedSelectedPeriod/', $content)) {
                $updatedMethod = $this->generateUpdatedPeriodMethod();
                $content = $this->insertMethodIntoClass($content, $updatedMethod);
            }

            // 改進現有的 updatedSelectedPeriod 方法
            $content = $this->improveUpdatedPeriodMethod($content);
        }

        if ($content !== $originalContent) {
            File::put($this->componentInfo['path'], $content);
            $this->fixedItems[] = [
                'type' => 'time_period_control_php',
                'file' => $this->componentInfo['relative_path'],
                'description' => '修復 PHP 中的時間週期控制',
            ];
            return true;
        }

        return false;
    }

    /**
     * 修復視圖檔案中的時間週期選擇器
     */
    protected function fixTimePeriodControlInView(): bool
    {
        if (!isset($this->componentInfo['view_path']) || !File::exists($this->componentInfo['view_path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['view_path']);
        $originalContent = $content;

        // 修復時間週期選擇器的 wire:model
        $content = preg_replace(
            '/wire:model\.live\s*=\s*(["\']selectedPeriod["\'])/',
            'wire:model.defer=$1',
            $content
        );

        // 添加 wire:change 事件以立即更新
        $content = preg_replace(
            '/(<select[^>]*wire:model\.defer\s*=\s*["\']selectedPeriod["\'][^>]*)(>)/',
            '$1 wire:change="refreshData"$2',
            $content
        );

        // 為時間週期選擇器添加 wire:key
        $content = preg_replace(
            '/(<select[^>]*wire:model[^>]*selectedPeriod[^>]*)(>)/',
            '$1 wire:key="period-selector"$2',
            $content
        );

        if ($content !== $originalContent) {
            File::put($this->componentInfo['view_path'], $content);
            $this->fixedItems[] = [
                'type' => 'time_period_control_view',
                'file' => $this->componentInfo['view_relative_path'] ?? '',
                'description' => '修復視圖中的時間週期選擇器',
            ];
            return true;
        }

        return false;
    }

    /**
     * 修復自動刷新控制
     */
    protected function fixAutoRefreshControl(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['path']);
        $originalContent = $content;

        // 檢查是否有自動刷新相關屬性
        if (preg_match('/public\s+bool\s+\$autoRefresh/', $content) || 
            preg_match('/public\s+int\s+\$refreshInterval/', $content)) {
            
            // 添加自動刷新控制方法
            if (!preg_match('/function\s+toggleAutoRefresh/', $content)) {
                $toggleMethod = $this->generateToggleAutoRefreshMethod();
                $content = $this->insertMethodIntoClass($content, $toggleMethod);
            }

            // 添加刷新間隔更新方法
            if (!preg_match('/function\s+updatedRefreshInterval/', $content)) {
                $intervalMethod = $this->generateUpdatedRefreshIntervalMethod();
                $content = $this->insertMethodIntoClass($content, $intervalMethod);
            }

            // 改進現有方法
            $content = $this->improveAutoRefreshMethods($content);
        }

        if ($content !== $originalContent) {
            File::put($this->componentInfo['path'], $content);
            $this->fixedItems[] = [
                'type' => 'auto_refresh_control',
                'file' => $this->componentInfo['relative_path'],
                'description' => '修復自動刷新控制功能',
            ];
            return true;
        }

        return false;
    }

    /**
     * 修復即時資料更新
     */
    protected function fixRealTimeDataUpdate(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['path']);
        $originalContent = $content;

        // 添加資料刷新方法
        if (!preg_match('/function\s+refreshData/', $content)) {
            $refreshMethod = $this->generateRefreshDataMethod();
            $content = $this->insertMethodIntoClass($content, $refreshMethod);
        }

        // 改進現有的 refreshData 方法
        $content = $this->improveRefreshDataMethod($content);

        // 添加載入狀態管理
        $content = $this->addLoadingStateManagement($content);

        if ($content !== $originalContent) {
            File::put($this->componentInfo['path'], $content);
            $this->fixedItems[] = [
                'type' => 'real_time_data_update',
                'file' => $this->componentInfo['relative_path'],
                'description' => '修復即時資料更新功能',
            ];
            return true;
        }

        return false;
    }

    /**
     * 修復圖表重置功能
     */
    protected function fixChartResetFunctionality(): bool
    {
        if (!isset($this->componentInfo['view_path']) || !File::exists($this->componentInfo['view_path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['view_path']);
        $originalContent = $content;

        // 檢查是否有圖表元素
        if (preg_match('/<canvas|<div[^>]*chart/', $content)) {
            // 添加圖表重置按鈕
            if (!preg_match('/wire:click\s*=\s*["\']resetChart["\']/', $content)) {
                $resetButton = $this->generateChartResetButton();
                
                // 在圖表容器附近添加重置按鈕
                $content = preg_replace(
                    '/(<div[^>]*class[^>]*chart[^>]*>)/',
                    $resetButton . "\n$1",
                    $content,
                    1
                );
            }

            // 為圖表元素添加 wire:key
            $content = preg_replace(
                '/(<canvas[^>]*)(>)/',
                '$1 wire:key="monitoring-chart"$2',
                $content
            );
        }

        if ($content !== $originalContent) {
            File::put($this->componentInfo['view_path'], $content);
            $this->fixedItems[] = [
                'type' => 'chart_reset_functionality',
                'file' => $this->componentInfo['view_relative_path'] ?? '',
                'description' => '修復圖表重置功能',
            ];
            return true;
        }

        return false;
    }

    /**
     * 添加效能監控重置
     */
    protected function addPerformanceMonitoringReset(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['path']);
        $originalContent = $content;

        // 添加效能監控重置方法
        if (!preg_match('/function\s+resetMonitoring/', $content)) {
            $resetMethod = $this->generateResetMonitoringMethod();
            $content = $this->insertMethodIntoClass($content, $resetMethod);
        }

        // 改進現有的重置方法以包含監控重置
        $content = $this->improveResetMethodsForMonitoring($content);

        if ($content !== $originalContent) {
            File::put($this->componentInfo['path'], $content);
            $this->fixedItems[] = [
                'type' => 'performance_monitoring_reset',
                'file' => $this->componentInfo['relative_path'],
                'description' => '添加效能監控重置功能',
            ];
            return true;
        }

        return false;
    }

    /**
     * 產生 updatedSelectedPeriod 方法
     */
    protected function generateUpdatedPeriodMethod(): string
    {
        return "
    /**
     * 時間週期更新時刷新資料
     */
    public function updatedSelectedPeriod(): void
    {
        // 記錄時間週期變更
        \\Log::info('⏰ 時間週期已變更', [
            'new_period' => \$this->selectedPeriod,
            'component' => static::class,
        ]);
        
        // 刷新資料
        \$this->refreshData();
        
        // 發送週期變更事件
        \$this->dispatch('period-changed', period: \$this->selectedPeriod);
    }";
    }

    /**
     * 產生 toggleAutoRefresh 方法
     */
    protected function generateToggleAutoRefreshMethod(): string
    {
        return "
    /**
     * 切換自動刷新狀態
     */
    public function toggleAutoRefresh(): void
    {
        \$this->autoRefresh = !\$this->autoRefresh;
        
        // 記錄自動刷新狀態變更
        \\Log::info('🔄 自動刷新狀態變更', [
            'auto_refresh' => \$this->autoRefresh,
            'interval' => \$this->refreshInterval ?? 30,
        ]);
        
        // 發送自動刷新狀態變更事件
        \$this->dispatch('auto-refresh-toggled', enabled: \$this->autoRefresh);
        
        // 如果啟用自動刷新，立即刷新一次資料
        if (\$this->autoRefresh) {
            \$this->refreshData();
        }
    }";
    }

    /**
     * 產生 updatedRefreshInterval 方法
     */
    protected function generateUpdatedRefreshIntervalMethod(): string
    {
        return "
    /**
     * 刷新間隔更新時的處理
     */
    public function updatedRefreshInterval(): void
    {
        // 驗證間隔值
        if (\$this->refreshInterval < 5) {
            \$this->refreshInterval = 5;
        } elseif (\$this->refreshInterval > 300) {
            \$this->refreshInterval = 300;
        }
        
        // 記錄間隔變更
        \\Log::info('⏱️ 刷新間隔已變更', [
            'new_interval' => \$this->refreshInterval,
            'auto_refresh' => \$this->autoRefresh ?? false,
        ]);
        
        // 發送間隔變更事件
        \$this->dispatch('refresh-interval-changed', interval: \$this->refreshInterval);
    }";
    }

    /**
     * 產生 refreshData 方法
     */
    protected function generateRefreshDataMethod(): string
    {
        return "
    /**
     * 刷新監控資料
     */
    public function refreshData(): void
    {
        try {
            // 設定載入狀態
            \$this->isLoading = true;
            
            // 記錄資料刷新開始
            \\Log::info('📊 開始刷新監控資料', [
                'period' => \$this->selectedPeriod ?? 'default',
                'timestamp' => now()->toISOString(),
            ]);
            
            // 這裡應該實作具體的資料載入邏輯
            // 例如：\$this->loadChartData();
            
            // 強制重新渲染
            \$this->dispatch('\$refresh');
            
            // 發送資料刷新完成事件
            \$this->dispatch('data-refreshed');
            
            // 記錄刷新完成
            \\Log::info('✅ 監控資料刷新完成');
            
        } catch (\\Exception \$e) {
            \\Log::error('❌ 監控資料刷新失敗', [
                'error' => \$e->getMessage(),
                'component' => static::class,
            ]);
            
            \$this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '資料刷新失敗，請重試'
            ]);
        } finally {
            \$this->isLoading = false;
        }
    }";
    }

    /**
     * 產生圖表重置按鈕
     */
    protected function generateChartResetButton(): string
    {
        return '
            <div class="mb-4 flex justify-end">
                <button type="button" 
                        wire:click="resetChart" 
                        wire:key="chart-reset-button"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    重置圖表
                </button>
            </div>';
    }

    /**
     * 產生重置監控方法
     */
    protected function generateResetMonitoringMethod(): string
    {
        return "
    /**
     * 重置監控設定和資料
     */
    public function resetMonitoring(): void
    {
        // 記錄監控重置開始
        \\Log::info('🔄 開始重置監控設定', [
            'component' => static::class,
            'timestamp' => now()->toISOString(),
        ]);
        
        // 重置時間週期
        \$this->selectedPeriod = '1h';
        
        // 重置自動刷新設定
        \$this->autoRefresh = false;
        \$this->refreshInterval = 30;
        
        // 重置載入狀態
        \$this->isLoading = false;
        
        // 清除快取資料
        \$this->chartData = [];
        \$this->stats = [];
        
        // 刷新資料
        \$this->refreshData();
        
        // 強制重新渲染
        \$this->dispatch('\$refresh');
        
        // 發送重置完成事件
        \$this->dispatch('monitoring-reset');
        
        // 顯示成功訊息
        \$this->dispatch('show-toast', [
            'type' => 'success',
            'message' => '監控設定已重置'
        ]);
        
        // 記錄重置完成
        \\Log::info('✅ 監控設定重置完成');
    }";
    }

    /**
     * 改進 updatedSelectedPeriod 方法
     */
    protected function improveUpdatedPeriodMethod(string $content): string
    {
        if (preg_match('/function\s+updatedSelectedPeriod/', $content)) {
            // 確保方法包含資料刷新
            if (!preg_match('/function\s+updatedSelectedPeriod[^{]*{[^}]*refreshData/', $content)) {
                $pattern = '/(public\s+function\s+updatedSelectedPeriod\s*\([^)]*\)[^{]*{)([^}]*)(})/s';
                $replacement = '$1$2' . "\n        \$this->refreshData();\n    $3";
                $content = preg_replace($pattern, $replacement, $content);
            }
        }

        return $content;
    }

    /**
     * 改進自動刷新方法
     */
    protected function improveAutoRefreshMethods(string $content): string
    {
        // 改進 toggleAutoRefresh 方法
        if (preg_match('/function\s+toggleAutoRefresh/', $content)) {
            if (!preg_match('/function\s+toggleAutoRefresh[^{]*{[^}]*dispatch/', $content)) {
                $pattern = '/(public\s+function\s+toggleAutoRefresh\s*\([^)]*\)[^{]*{)([^}]*)(})/s';
                $replacement = '$1$2' . "\n        \$this->dispatch('auto-refresh-toggled', enabled: \$this->autoRefresh);\n    $3";
                $content = preg_replace($pattern, $replacement, $content);
            }
        }

        return $content;
    }

    /**
     * 改進 refreshData 方法
     */
    protected function improveRefreshDataMethod(string $content): string
    {
        if (preg_match('/function\s+refreshData/', $content)) {
            // 確保方法包含錯誤處理
            if (!preg_match('/function\s+refreshData[^{]*{[^}]*try\s*{/', $content)) {
                $pattern = '/(public\s+function\s+refreshData\s*\([^)]*\)[^{]*{)([^}]*)(})/s';
                $replacement = '$1' . "\n        try {\$2\n        } catch (\\Exception \$e) {\n            \\Log::error('資料刷新失敗', ['error' => \$e->getMessage()]);\n        }\n    \$3";
                $content = preg_replace($pattern, $replacement, $content);
            }
        }

        return $content;
    }

    /**
     * 添加載入狀態管理
     */
    protected function addLoadingStateManagement(string $content): string
    {
        // 檢查是否已經有載入狀態屬性
        if (!preg_match('/public\s+bool\s+\$isLoading/', $content)) {
            // 在類別開始處添加載入狀態屬性
            $pattern = '/(class\s+[a-zA-Z_][a-zA-Z0-9_]*\s+extends\s+[^\s{]+\s*{)([^}]*)/s';
            $replacement = '$1' . "\n    /**\n     * 載入狀態\n     */\n    public bool \$isLoading = false;\n$2";
            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        return $content;
    }

    /**
     * 改進重置方法以包含監控重置
     */
    protected function improveResetMethodsForMonitoring(string $content): string
    {
        $resetMethods = ['resetFilters', 'resetForm', 'reset'];

        foreach ($resetMethods as $methodName) {
            if (preg_match("/function\s+{$methodName}/", $content)) {
                // 檢查是否已經包含監控重置
                if (!preg_match("/function\s+{$methodName}[^{]*{[^}]*selectedPeriod/", $content)) {
                    $pattern = "/(public\s+function\s+{$methodName}\s*\([^)]*\)[^{]*{)([^}]*)(})/s";
                    $replacement = '$1$2' . "\n        // 重置監控設定\n        \$this->selectedPeriod = '1h';\n        \$this->autoRefresh = false;\n    $3";
                    $content = preg_replace($pattern, $replacement, $content, 1);
                }
            }
        }

        return $content;
    }

    /**
     * 驗證時間控制功能
     */
    protected function validateTimeControlFunctionality(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return true;
        }

        $content = File::get($this->componentInfo['path']);

        // 檢查時間週期屬性
        if (preg_match('/public\s+string\s+\$selectedPeriod/', $content)) {
            // 檢查是否有對應的更新方法
            if (!preg_match('/function\s+updatedSelectedPeriod/', $content)) {
                $this->progress['errors'][] = '時間週期屬性缺少對應的更新方法';
                return false;
            }
        }

        return true;
    }

    /**
     * 驗證自動刷新功能
     */
    protected function validateAutoRefreshFunctionality(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return true;
        }

        $content = File::get($this->componentInfo['path']);

        // 檢查自動刷新屬性
        if (preg_match('/public\s+bool\s+\$autoRefresh/', $content)) {
            // 檢查是否有切換方法
            if (!preg_match('/function\s+toggleAutoRefresh/', $content)) {
                $this->progress['errors'][] = '自動刷新屬性缺少切換方法';
                return false;
            }
        }

        return true;
    }

    /**
     * 驗證資料更新機制
     */
    protected function validateDataUpdateMechanism(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return true;
        }

        $content = File::get($this->componentInfo['path']);

        // 檢查是否有資料刷新方法
        if (!preg_match('/function\s+refreshData/', $content)) {
            $this->progress['errors'][] = '監控元件缺少資料刷新方法';
            return false;
        }

        return true;
    }

    /**
     * 產生改進的重置方法主體（覆寫父類別方法）
     */
    protected function generateImprovedResetMethodBody(string $methodName, array $properties): string
    {
        $resetStatements = [];

        // 重置一般屬性
        foreach ($properties as $property) {
            $defaultValue = $this->getPropertyDefaultValue($property);
            $resetStatements[] = "        \$this->{$property} = {$defaultValue};";
        }

        // 重置監控特定屬性
        $resetStatements[] = "        \$this->selectedPeriod = '1h';";
        $resetStatements[] = "        \$this->autoRefresh = false;";
        $resetStatements[] = "        \$this->refreshInterval = 30;";
        $resetStatements[] = "        \$this->isLoading = false;";

        // 清除快取資料
        $resetStatements[] = "        \$this->chartData = [];";
        $resetStatements[] = "        \$this->stats = [];";

        $resetCode = implode("\n", $resetStatements);

        return "
        // 記錄監控重置操作
        \\Log::info('🔄 {$methodName} - 監控元件重置開始', [
            'timestamp' => now()->toISOString(),
            'user' => auth()->user()->username ?? 'unknown',
            'component' => static::class,
        ]);
        
        // 重置所有監控設定和資料
{$resetCode}
        
        // 刷新資料
        \$this->refreshData();
        
        // 強制重新渲染以確保前端同步
        \$this->dispatch('\$refresh');
        
        // 發送監控重置完成事件
        \$this->dispatch('{$methodName}-completed');
        \$this->dispatch('monitoring-reset');
        
        // 顯示成功訊息
        \$this->dispatch('show-toast', [
            'type' => 'success',
            'message' => '監控設定已重置'
        ]);
        
        // 記錄重置完成
        \\Log::info('✅ {$methodName} - 監控元件重置完成');
";
    }
}