<?php

namespace App\Services\LivewireFormReset;

use Illuminate\Support\Facades\File;

/**
 * 標準表單重置修復類別
 * 
 * 提供通用的表單重置修復功能，適用於大多數 Livewire 元件
 */
class StandardFormResetFix extends BaseFormResetFix
{
    /**
     * 取得策略名稱
     */
    public function getStrategyName(): string
    {
        return 'StandardFormResetFix';
    }

    /**
     * 檢查是否支援此元件類型
     */
    public function supports(array $componentInfo): bool
    {
        // 標準修復支援所有元件類型
        return true;
    }

    /**
     * 取得 wire:model 模式
     */
    protected function getWireModelPattern(): string
    {
        return '/wire:model\.(?:lazy|live)(?:\.debounce\.\d+ms)?\s*=\s*["\']([^"\']+)["\']/';
    }

    /**
     * 取得重置方法模式
     */
    protected function getResetMethodPattern(): string
    {
        return '/public\s+function\s+(resetFilters|resetForm|clearFilters)\s*\(\s*\)\s*:\s*void/';
    }

    /**
     * 應用特定修復
     */
    protected function applySpecificFixes(): bool
    {
        $fixed = false;

        // 修復重置方法實作
        if ($this->fixResetMethodImplementation()) {
            $fixed = true;
        }

        // 添加缺少的重置方法
        if ($this->addMissingResetMethods()) {
            $fixed = true;
        }

        // 優化現有重置方法
        if ($this->optimizeExistingResetMethods()) {
            $fixed = true;
        }

        // 修復表單驗證重置
        if ($this->fixFormValidationReset()) {
            $fixed = true;
        }

        return $fixed;
    }

    /**
     * 執行特定驗證
     */
    protected function performSpecificValidation(): bool
    {
        // 驗證重置方法存在且正確實作
        if (!$this->validateResetMethodsExist()) {
            return false;
        }

        // 驗證重置方法包含必要的邏輯
        if (!$this->validateResetMethodLogic()) {
            return false;
        }

        // 驗證表單狀態重置
        if (!$this->validateFormStateReset()) {
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
            'complex_form_structure',
        ];

        return in_array($issue['type'], $supportedIssueTypes);
    }

    /**
     * 修復重置方法實作
     */
    protected function fixResetMethodImplementation(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['path']);
        $originalContent = $content;

        $resetMethods = $this->componentInfo['reset_methods'] ?? [];

        foreach ($resetMethods as $method) {
            $methodBody = $method['method_body'] ?? '';
            
            // 檢查方法是否需要改進
            if ($this->needsResetMethodImprovement($methodBody)) {
                $content = $this->improveResetMethod($content, $method);
            }
        }

        if ($content !== $originalContent) {
            File::put($this->componentInfo['path'], $content);
            $this->fixedItems[] = [
                'type' => 'reset_method_implementation',
                'file' => $this->componentInfo['relative_path'],
                'description' => '改進重置方法實作',
            ];
            return true;
        }

        return false;
    }

    /**
     * 添加缺少的重置方法
     */
    protected function addMissingResetMethods(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['path']);
        $resetMethods = $this->componentInfo['reset_methods'] ?? [];
        $resetButtons = $this->componentInfo['view_reset_buttons'] ?? [];

        // 如果有重置按鈕但沒有重置方法，添加方法
        if (!empty($resetButtons) && empty($resetMethods)) {
            $methodName = $this->determineResetMethodName($resetButtons);
            $newMethod = $this->generateResetMethod($methodName);
            
            $content = $this->insertMethodIntoClass($content, $newMethod);
            
            File::put($this->componentInfo['path'], $content);
            $this->fixedItems[] = [
                'type' => 'missing_reset_method',
                'file' => $this->componentInfo['relative_path'],
                'description' => "添加缺少的 {$methodName} 方法",
            ];
            return true;
        }

        return false;
    }

    /**
     * 優化現有重置方法
     */
    protected function optimizeExistingResetMethods(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['path']);
        $originalContent = $content;

        // 移除不安全的重置方式
        $content = $this->removeUnsafeResetMethods($content);

        // 添加錯誤處理
        $content = $this->addErrorHandlingToResetMethods($content);

        if ($content !== $originalContent) {
            File::put($this->componentInfo['path'], $content);
            $this->fixedItems[] = [
                'type' => 'optimize_reset_methods',
                'file' => $this->componentInfo['relative_path'],
                'description' => '優化現有重置方法',
            ];
            return true;
        }

        return false;
    }

    /**
     * 修復表單驗證重置
     */
    protected function fixFormValidationReset(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['path']);
        $originalContent = $content;

        // 在重置方法中添加驗證錯誤清除
        $content = $this->addValidationErrorClear($content);

        if ($content !== $originalContent) {
            File::put($this->componentInfo['path'], $content);
            $this->fixedItems[] = [
                'type' => 'form_validation_reset',
                'file' => $this->componentInfo['relative_path'],
                'description' => '添加表單驗證錯誤清除',
            ];
            return true;
        }

        return false;
    }

    /**
     * 檢查重置方法是否需要改進
     */
    protected function needsResetMethodImprovement(string $methodBody): bool
    {
        // 檢查是否缺少基本重置邏輯
        if (!preg_match('/\$this->reset\s*\(/', $methodBody) && 
            !preg_match('/\$this->[a-zA-Z_][a-zA-Z0-9_]*\s*=/', $methodBody)) {
            return true;
        }

        // 檢查是否使用了不安全的重置方式
        if (strpos($methodBody, 'window.location.reload()') !== false) {
            return true;
        }

        // 檢查是否缺少刷新機制
        if (!$this->hasDispatchRefresh($methodBody)) {
            return true;
        }

        return false;
    }

    /**
     * 改進重置方法
     */
    protected function improveResetMethod(string $content, array $method): string
    {
        $methodName = $method['name'];
        $properties = $this->extractResetableProperties();
        
        $improvedMethodBody = $this->generateImprovedResetMethodBody($methodName, $properties);
        
        // 替換整個方法
        $pattern = "/(public\s+function\s+{$methodName}\s*\([^)]*\)[^{]*{)([^}]*)(})/s";
        $replacement = '$1' . $improvedMethodBody . '$3';
        
        return preg_replace($pattern, $replacement, $content);
    }

    /**
     * 確定重置方法名稱
     */
    protected function determineResetMethodName(array $resetButtons): string
    {
        foreach ($resetButtons as $button) {
            if (strpos($button['content'], 'resetFilters') !== false) {
                return 'resetFilters';
            }
            if (strpos($button['content'], 'resetForm') !== false) {
                return 'resetForm';
            }
            if (strpos($button['content'], 'clearFilters') !== false) {
                return 'clearFilters';
            }
        }

        // 根據元件類型決定預設方法名
        $componentType = $this->componentInfo['classification']['component_type'] ?? 'GENERIC';
        
        return match ($componentType) {
            'LIST_FILTER', 'ACTIVITY_LOG' => 'resetFilters',
            'FORM_MODAL', 'SETTINGS_FORM' => 'resetForm',
            default => 'resetFilters',
        };
    }

    /**
     * 產生重置方法
     */
    protected function generateResetMethod(string $methodName): string
    {
        $properties = $this->extractResetableProperties();
        $methodBody = $this->generateImprovedResetMethodBody($methodName, $properties);

        return "
    /**
     * 重置表單或篩選條件
     */
    public function {$methodName}(): void
    {{$methodBody}
    }";
    }

    /**
     * 產生改進的重置方法主體
     */
    protected function generateImprovedResetMethodBody(string $methodName, array $properties): string
    {
        $resetStatements = [];

        // 重置屬性
        foreach ($properties as $property) {
            $defaultValue = $this->getPropertyDefaultValue($property);
            $resetStatements[] = "        \$this->{$property} = {$defaultValue};";
        }

        // 重置分頁（如果使用 WithPagination）
        if ($this->usesWithPagination()) {
            $resetStatements[] = "        \$this->resetPage();";
        }

        // 清除驗證錯誤
        $resetStatements[] = "        \$this->resetValidation();";

        $resetCode = implode("\n", $resetStatements);

        return "
        // 記錄重置操作
        \\Log::info('🔄 {$methodName} - 方法被呼叫', [
            'timestamp' => now()->toISOString(),
            'user' => auth()->user()->username ?? 'unknown',
        ]);
        
        // 重置屬性
{$resetCode}
        
        // 強制重新渲染元件以確保前端同步
        \$this->dispatch('\$refresh');
        
        // 發送前端刷新事件
        \$this->dispatch('{$methodName}-completed');
        
        // 記錄重置完成
        \\Log::info('✅ {$methodName} - 重置完成');
";
    }

    /**
     * 提取可重置的屬性
     */
    protected function extractResetableProperties(): array
    {
        $properties = [];
        $publicProperties = $this->componentInfo['properties'] ?? [];

        foreach ($publicProperties as $property) {
            $propertyName = $property['name'];
            
            // 排除不應該重置的屬性
            if (!in_array($propertyName, ['perPage', 'sortField', 'sortDirection'])) {
                $properties[] = $propertyName;
            }
        }

        return $properties;
    }

    /**
     * 取得屬性預設值
     */
    protected function getPropertyDefaultValue(string $propertyName): string
    {
        // 根據屬性名稱推斷預設值
        if (str_contains($propertyName, 'Filter') || str_contains($propertyName, 'search')) {
            return "''";
        }
        
        if (str_contains($propertyName, 'selected') && str_contains($propertyName, 's')) {
            return '[]';
        }
        
        if (str_contains($propertyName, 'show') || str_contains($propertyName, 'is')) {
            return 'false';
        }

        return "''";
    }

    /**
     * 檢查是否使用 WithPagination
     */
    protected function usesWithPagination(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['path']);
        return strpos($content, 'WithPagination') !== false;
    }

    /**
     * 將方法插入類別中
     */
    protected function insertMethodIntoClass(string $content, string $method): string
    {
        // 尋找類別的最後一個方法或屬性後插入
        $pattern = '/(\s+)(public\s+function\s+render\s*\([^)]*\)[^{]*{[^}]*})\s*}/';
        
        if (preg_match($pattern, $content)) {
            // 在 render 方法之前插入
            $replacement = '$1' . $method . "\n\n$1$2\n}";
            return preg_replace($pattern, $replacement, $content);
        }

        // 如果找不到 render 方法，在類別結束前插入
        $pattern = '/(\s+)}(\s*)$/';
        $replacement = '$1' . $method . "\n$1}\n$2";
        
        return preg_replace($pattern, $replacement, $content);
    }

    /**
     * 移除不安全的重置方式
     */
    protected function removeUnsafeResetMethods(string $content): string
    {
        // 移除 window.location.reload()
        $content = preg_replace('/window\.location\.reload\(\);?\s*/', '', $content);
        
        // 移除其他不安全的 JavaScript 重置
        $content = preg_replace('/location\.href\s*=\s*location\.href;?\s*/', '', $content);
        
        return $content;
    }

    /**
     * 為重置方法添加錯誤處理
     */
    protected function addErrorHandlingToResetMethods(string $content): string
    {
        $resetMethods = $this->componentInfo['reset_methods'] ?? [];

        foreach ($resetMethods as $method) {
            $methodName = $method['name'];
            
            // 檢查是否已經有錯誤處理
            if (!preg_match("/function\s+{$methodName}[^{]*{[^}]*try\s*{/", $content)) {
                $content = $this->wrapMethodWithErrorHandling($content, $methodName);
            }
        }

        return $content;
    }

    /**
     * 用錯誤處理包裝方法
     */
    protected function wrapMethodWithErrorHandling(string $content, string $methodName): string
    {
        $pattern = "/(public\s+function\s+{$methodName}\s*\([^)]*\)[^{]*{)([^}]*)(})/s";
        
        $replacement = '$1' . "
        try {\$2
        } catch (\\Exception \$e) {
            \\Log::error('重置方法執行失敗', [
                'method' => '{$methodName}',
                'error' => \$e->getMessage(),
                'component' => static::class,
            ]);
            
            \$this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '重置操作失敗，請重試'
            ]);
        }" . '$3';

        return preg_replace($pattern, $replacement, $content);
    }

    /**
     * 添加驗證錯誤清除
     */
    protected function addValidationErrorClear(string $content): string
    {
        $resetMethods = $this->componentInfo['reset_methods'] ?? [];

        foreach ($resetMethods as $method) {
            $methodName = $method['name'];
            $methodBody = $method['method_body'] ?? '';
            
            // 檢查是否已經有驗證重置
            if (!preg_match('/\$this->resetValidation\(\)/', $methodBody)) {
                $pattern = "/(public\s+function\s+{$methodName}\s*\([^)]*\)[^{]*{[^}]*)(})/s";
                $replacement = '$1' . "\n        \$this->resetValidation();\n    $2";
                $content = preg_replace($pattern, $replacement, $content);
            }
        }

        return $content;
    }

    /**
     * 驗證重置方法存在
     */
    protected function validateResetMethodsExist(): bool
    {
        $resetMethods = $this->componentInfo['reset_methods'] ?? [];
        $resetButtons = $this->componentInfo['view_reset_buttons'] ?? [];

        // 如果有重置按鈕，必須有對應的重置方法
        if (!empty($resetButtons) && empty($resetMethods)) {
            $this->progress['errors'][] = '有重置按鈕但缺少重置方法';
            return false;
        }

        return true;
    }

    /**
     * 驗證重置方法邏輯
     */
    protected function validateResetMethodLogic(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return true;
        }

        $content = File::get($this->componentInfo['path']);
        $resetMethods = $this->componentInfo['reset_methods'] ?? [];

        foreach ($resetMethods as $method) {
            $methodName = $method['name'];
            
            // 檢查方法是否包含基本的重置邏輯
            if (!preg_match("/function\s+{$methodName}[^{]*{[^}]*(?:\\\$this->reset|\\\$this->[a-zA-Z_][a-zA-Z0-9_]*\s*=)/", $content)) {
                $this->progress['errors'][] = "重置方法 {$methodName} 缺少重置邏輯";
                return false;
            }
        }

        return true;
    }

    /**
     * 驗證表單狀態重置
     */
    protected function validateFormStateReset(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return true;
        }

        $content = File::get($this->componentInfo['path']);
        
        // 檢查是否有驗證重置
        if (strpos($content, 'resetValidation') === false && 
            count($this->componentInfo['view_form_elements'] ?? []) > 0) {
            $this->progress['errors'][] = '表單元件缺少驗證錯誤重置';
            return false;
        }

        return true;
    }
}