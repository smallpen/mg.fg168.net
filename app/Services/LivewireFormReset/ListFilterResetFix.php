<?php

namespace App\Services\LivewireFormReset;

use Illuminate\Support\Facades\File;

/**
 * 列表篩選器重置修復類別
 * 
 * 專門處理列表篩選器元件的重置功能修復
 */
class ListFilterResetFix extends StandardFormResetFix
{
    /**
     * 取得策略名稱
     */
    public function getStrategyName(): string
    {
        return 'ListFilterResetFix';
    }

    /**
     * 檢查是否支援此元件類型
     */
    public function supports(array $componentInfo): bool
    {
        $componentType = $componentInfo['classification']['component_type'] ?? '';
        $supportedTypes = ['LIST_FILTER', 'ACTIVITY_LOG', 'USER_MANAGEMENT'];
        
        return in_array($componentType, $supportedTypes);
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

        // 修復搜尋欄位的即時搜尋功能
        if ($this->fixSearchFieldBehavior()) {
            $fixed = true;
        }

        // 修復篩選下拉選單
        if ($this->fixFilterSelects()) {
            $fixed = true;
        }

        // 修復分頁重置
        if ($this->fixPaginationReset()) {
            $fixed = true;
        }

        // 優化批量操作重置
        if ($this->fixBulkOperationReset()) {
            $fixed = true;
        }

        // 添加篩選狀態指示器
        if ($this->addFilterStatusIndicator()) {
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

        // 驗證搜尋功能
        if (!$this->validateSearchFunctionality()) {
            return false;
        }

        // 驗證篩選功能
        if (!$this->validateFilterFunctionality()) {
            return false;
        }

        // 驗證分頁重置
        if (!$this->validatePaginationReset()) {
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
            'missing_reset_method',
            'search_field_issues',
            'filter_select_issues',
            'pagination_reset_issues',
        ];

        return in_array($issue['type'], $supportedIssueTypes);
    }

    /**
     * 修復搜尋欄位行為
     */
    protected function fixSearchFieldBehavior(): bool
    {
        if (!isset($this->componentInfo['view_path']) || !File::exists($this->componentInfo['view_path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['view_path']);
        $originalContent = $content;

        // 將搜尋欄位的 wire:model.live.debounce 改為 wire:model.defer
        $patterns = [
            '/wire:model\.live\.debounce\.\d+ms\s*=\s*(["\']search["\'])/' => 'wire:model.defer=$1',
            '/wire:model\.live\s*=\s*(["\']search["\'])/' => 'wire:model.defer=$1',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        // 為搜尋欄位添加 wire:key
        if (!preg_match('/wire:key\s*=\s*["\']search["\']/', $content)) {
            $content = preg_replace(
                '/(<input[^>]*name\s*=\s*["\']search["\'][^>]*)(>)/',
                '$1 wire:key="search-input"$2',
                $content
            );
        }

        // 添加搜尋按鈕（如果不存在）
        if (!preg_match('/wire:click\s*=\s*["\']search["\']/', $content) && 
            !preg_match('/<button[^>]*type\s*=\s*["\']submit["\']/', $content)) {
            
            $searchButtonHtml = '
                <button type="button" 
                        wire:click="$refresh" 
                        wire:key="search-button"
                        class="ml-2 inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    搜尋
                </button>';

            // 在搜尋輸入框後添加搜尋按鈕
            $content = preg_replace(
                '/(<input[^>]*wire:model[^>]*search[^>]*>)/',
                '$1' . $searchButtonHtml,
                $content
            );
        }

        if ($content !== $originalContent) {
            File::put($this->componentInfo['view_path'], $content);
            $this->fixedItems[] = [
                'type' => 'search_field_behavior',
                'file' => $this->componentInfo['view_relative_path'] ?? '',
                'description' => '修復搜尋欄位行為和添加搜尋按鈕',
            ];
            return true;
        }

        return false;
    }

    /**
     * 修復篩選下拉選單
     */
    protected function fixFilterSelects(): bool
    {
        if (!isset($this->componentInfo['view_path']) || !File::exists($this->componentInfo['view_path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['view_path']);
        $originalContent = $content;

        // 修復篩選下拉選單的 wire:model
        $content = preg_replace(
            '/wire:model\.live\s*=\s*(["\'][^"\']*Filter[^"\']*["\'])/',
            'wire:model.defer=$1',
            $content
        );

        // 為篩選下拉選單添加 wire:key
        $content = preg_replace(
            '/(<select[^>]*wire:model[^>]*Filter[^>]*)(>)/',
            '$1 wire:key="filter-{{ $loop->index ?? \'select\' }}"$2',
            $content
        );

        // 添加篩選變更時的自動搜尋
        $content = preg_replace(
            '/(<select[^>]*wire:model\.defer[^>]*Filter[^>]*)(>)/',
            '$1 wire:change="$refresh"$2',
            $content
        );

        if ($content !== $originalContent) {
            File::put($this->componentInfo['view_path'], $content);
            $this->fixedItems[] = [
                'type' => 'filter_selects',
                'file' => $this->componentInfo['view_relative_path'] ?? '',
                'description' => '修復篩選下拉選單行為',
            ];
            return true;
        }

        return false;
    }

    /**
     * 修復分頁重置
     */
    protected function fixPaginationReset(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['path']);
        $originalContent = $content;

        // 在 updated 方法中添加分頁重置
        $updatedMethods = [
            'updatedSearch' => 'search',
            'updatedStatusFilter' => 'statusFilter', 
            'updatedRoleFilter' => 'roleFilter',
        ];

        foreach ($updatedMethods as $methodName => $propertyName) {
            if (!preg_match("/function\s+{$methodName}/", $content)) {
                $newMethod = $this->generateUpdatedMethod($methodName, $propertyName);
                $content = $this->insertMethodIntoClass($content, $newMethod);
            } else {
                // 確保現有方法包含 resetPage()
                $content = $this->ensureResetPageInUpdatedMethod($content, $methodName);
            }
        }

        if ($content !== $originalContent) {
            File::put($this->componentInfo['path'], $content);
            $this->fixedItems[] = [
                'type' => 'pagination_reset',
                'file' => $this->componentInfo['relative_path'],
                'description' => '修復分頁重置功能',
            ];
            return true;
        }

        return false;
    }

    /**
     * 修復批量操作重置
     */
    protected function fixBulkOperationReset(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['path']);
        $originalContent = $content;

        // 檢查是否有批量操作相關屬性
        if (preg_match('/public\s+array\s+\$selected/', $content)) {
            // 確保 resetFilters 方法重置批量選擇
            $content = $this->addBulkSelectionResetToResetMethod($content);
        }

        if ($content !== $originalContent) {
            File::put($this->componentInfo['path'], $content);
            $this->fixedItems[] = [
                'type' => 'bulk_operation_reset',
                'file' => $this->componentInfo['relative_path'],
                'description' => '修復批量操作重置',
            ];
            return true;
        }

        return false;
    }

    /**
     * 添加篩選狀態指示器
     */
    protected function addFilterStatusIndicator(): bool
    {
        if (!isset($this->componentInfo['view_path']) || !File::exists($this->componentInfo['view_path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['view_path']);
        $originalContent = $content;

        // 檢查是否已經有篩選狀態指示器
        if (strpos($content, 'filter-status-indicator') !== false) {
            return false;
        }

        // 添加篩選狀態指示器
        $indicatorHtml = '
            @if($search || $statusFilter !== \'all\' || $roleFilter !== \'all\')
                <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800" wire:key="filter-status-indicator">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"></path>
                            </svg>
                            <span class="text-sm font-medium text-blue-800 dark:text-blue-200">已套用篩選條件</span>
                        </div>
                        <button type="button" 
                                wire:click="resetFilters" 
                                wire:key="clear-filters-button"
                                class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 font-medium">
                            清除全部
                        </button>
                    </div>
                </div>
            @endif';

        // 在表格或列表前添加指示器
        $content = preg_replace(
            '/(<div[^>]*class[^>]*(?:table|list)[^>]*>)/',
            $indicatorHtml . "\n\n$1",
            $content,
            1
        );

        if ($content !== $originalContent) {
            File::put($this->componentInfo['view_path'], $content);
            $this->fixedItems[] = [
                'type' => 'filter_status_indicator',
                'file' => $this->componentInfo['view_relative_path'] ?? '',
                'description' => '添加篩選狀態指示器',
            ];
            return true;
        }

        return false;
    }

    /**
     * 產生 updated 方法
     */
    protected function generateUpdatedMethod(string $methodName, string $propertyName): string
    {
        return "
    /**
     * {$propertyName} 更新時重置分頁
     */
    public function {$methodName}(): void
    {
        \$this->resetPage();
    }";
    }

    /**
     * 確保 updated 方法包含 resetPage
     */
    protected function ensureResetPageInUpdatedMethod(string $content, string $methodName): string
    {
        if (!preg_match("/function\s+{$methodName}[^{]*{[^}]*resetPage/", $content)) {
            $pattern = "/(public\s+function\s+{$methodName}\s*\([^)]*\)[^{]*{)([^}]*)(})/s";
            $replacement = '$1$2' . "\n        \$this->resetPage();\n    $3";
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    /**
     * 添加批量選擇重置到重置方法
     */
    protected function addBulkSelectionResetToResetMethod(string $content): string
    {
        $resetMethods = ['resetFilters', 'clearFilters'];

        foreach ($resetMethods as $methodName) {
            if (preg_match("/function\s+{$methodName}/", $content)) {
                // 檢查是否已經重置批量選擇
                if (!preg_match("/function\s+{$methodName}[^{]*{[^}]*selected.*=\s*\[\]/", $content)) {
                    $pattern = "/(public\s+function\s+{$methodName}\s*\([^)]*\)[^{]*{)([^}]*)(})/s";
                    $replacement = '$1$2' . "\n        \$this->selectedUsers = [];\n        \$this->selectAll = false;\n    $3";
                    $content = preg_replace($pattern, $replacement, $content);
                }
            }
        }

        return $content;
    }

    /**
     * 驗證搜尋功能
     */
    protected function validateSearchFunctionality(): bool
    {
        if (!isset($this->componentInfo['view_path']) || !File::exists($this->componentInfo['view_path'])) {
            return true;
        }

        $content = File::get($this->componentInfo['view_path']);

        // 檢查搜尋欄位是否使用正確的 wire:model
        if (preg_match('/wire:model\.live[^=]*=\s*["\']search["\']/', $content)) {
            $this->progress['errors'][] = '搜尋欄位仍使用 wire:model.live';
            return false;
        }

        return true;
    }

    /**
     * 驗證篩選功能
     */
    protected function validateFilterFunctionality(): bool
    {
        if (!isset($this->componentInfo['view_path']) || !File::exists($this->componentInfo['view_path'])) {
            return true;
        }

        $content = File::get($this->componentInfo['view_path']);

        // 檢查篩選下拉選單是否使用正確的 wire:model
        if (preg_match('/wire:model\.live[^=]*=\s*["\'][^"\']*Filter[^"\']*["\']/', $content)) {
            $this->progress['errors'][] = '篩選下拉選單仍使用 wire:model.live';
            return false;
        }

        return true;
    }

    /**
     * 驗證分頁重置
     */
    protected function validatePaginationReset(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return true;
        }

        $content = File::get($this->componentInfo['path']);

        // 檢查是否有 WithPagination trait
        if (strpos($content, 'WithPagination') !== false) {
            // 檢查 updated 方法是否包含 resetPage
            $updatedMethods = ['updatedSearch', 'updatedStatusFilter', 'updatedRoleFilter'];
            
            foreach ($updatedMethods as $method) {
                if (preg_match("/function\s+{$method}/", $content)) {
                    if (!preg_match("/function\s+{$method}[^{]*{[^}]*resetPage/", $content)) {
                        $this->progress['errors'][] = "{$method} 方法缺少 resetPage() 呼叫";
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * 取得屬性預設值（覆寫父類別方法）
     */
    protected function getPropertyDefaultValue(string $propertyName): string
    {
        // 列表篩選器特定的預設值
        if (str_contains($propertyName, 'Filter')) {
            return "'all'";
        }
        
        if (str_contains($propertyName, 'search')) {
            return "''";
        }
        
        if (str_contains($propertyName, 'selected')) {
            return '[]';
        }
        
        if (str_contains($propertyName, 'selectAll')) {
            return 'false';
        }

        return parent::getPropertyDefaultValue($propertyName);
    }

    /**
     * 產生改進的重置方法主體（覆寫父類別方法）
     */
    protected function generateImprovedResetMethodBody(string $methodName, array $properties): string
    {
        $resetStatements = [];

        // 重置篩選相關屬性
        foreach ($properties as $property) {
            $defaultValue = $this->getPropertyDefaultValue($property);
            $resetStatements[] = "        \$this->{$property} = {$defaultValue};";
        }

        // 重置分頁
        $resetStatements[] = "        \$this->resetPage();";

        // 清除驗證錯誤
        $resetStatements[] = "        \$this->resetValidation();";

        $resetCode = implode("\n", $resetStatements);

        return "
        // 記錄篩選重置操作
        \\Log::info('🔄 {$methodName} - 篩選重置開始', [
            'timestamp' => now()->toISOString(),
            'user' => auth()->user()->username ?? 'unknown',
            'before_reset' => [
                'search' => \$this->search ?? '',
                'filters' => array_filter([
                    'status' => \$this->statusFilter ?? null,
                    'role' => \$this->roleFilter ?? null,
                ]),
            ]
        ]);
        
        // 重置所有篩選條件
{$resetCode}
        
        // 強制重新渲染以確保前端同步
        \$this->dispatch('\$refresh');
        
        // 發送篩選重置完成事件
        \$this->dispatch('{$methodName}-completed');
        
        // 顯示成功訊息
        \$this->dispatch('show-toast', [
            'type' => 'success',
            'message' => '篩選條件已清除'
        ]);
        
        // 記錄重置完成
        \\Log::info('✅ {$methodName} - 篩選重置完成');
";
    }
}