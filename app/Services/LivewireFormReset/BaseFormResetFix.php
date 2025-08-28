<?php

namespace App\Services\LivewireFormReset;

use App\Services\LivewireFormReset\Contracts\FormResetFixInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * 表單重置修復基礎類別
 * 
 * 提供所有修復類別的共用功能和標準實作
 */
abstract class BaseFormResetFix implements FormResetFixInterface
{
    /**
     * 元件資訊
     */
    protected array $componentInfo = [];

    /**
     * 識別出的問題
     */
    protected array $issues = [];

    /**
     * 已修復的項目
     */
    protected array $fixedItems = [];

    /**
     * 修復進度
     */
    protected array $progress = [
        'total_steps' => 0,
        'completed_steps' => 0,
        'current_step' => '',
        'status' => 'pending',
        'errors' => [],
    ];

    /**
     * 備份檔案路徑
     */
    protected array $backupFiles = [];

    /**
     * 問題識別器
     */
    protected IssueIdentifier $issueIdentifier;

    /**
     * 建構函式
     */
    public function __construct()
    {
        $this->issueIdentifier = new IssueIdentifier();
    }

    /**
     * 設定元件資訊
     */
    public function setComponentInfo(array $componentInfo): FormResetFixInterface
    {
        $this->componentInfo = $componentInfo;
        return $this;
    }

    /**
     * 識別問題
     */
    public function identifyIssues(): array
    {
        if (empty($this->componentInfo)) {
            throw new \InvalidArgumentException('元件資訊未設定');
        }

        $this->issues = $this->issueIdentifier->identifyAllIssues($this->componentInfo);
        
        // 篩選此修復策略支援的問題
        $this->issues = array_filter($this->issues, [$this, 'canFixIssue']);

        return $this->issues;
    }

    /**
     * 應用標準修復
     */
    public function applyStandardFix(): bool
    {
        try {
            $this->initializeProgress();
            
            // 建立備份
            $this->createBackup();
            $this->updateProgress('建立檔案備份');

            // 應用 wire:model 修復
            if ($this->applyWireModelFix()) {
                $this->updateProgress('修復 wire:model 指令');
            }

            // 添加 wire:key 屬性
            if ($this->addWireKeyAttributes()) {
                $this->updateProgress('添加 wire:key 屬性');
            }

            // 應用刷新機制
            if ($this->applyRefreshMechanism()) {
                $this->updateProgress('添加刷新機制');
            }

            // 應用特定修復
            if ($this->applySpecificFixes()) {
                $this->updateProgress('應用特定修復');
            }

            $this->progress['status'] = 'completed';
            $this->logSuccess('修復完成');

            return true;

        } catch (\Exception $e) {
            $this->progress['status'] = 'failed';
            $this->progress['errors'][] = $e->getMessage();
            $this->logError('修復失敗', $e);
            
            // 嘗試回滾
            $this->rollbackFix();
            
            return false;
        }
    }

    /**
     * 驗證修復結果
     */
    public function validateFix(): bool
    {
        try {
            // 檢查檔案語法
            if (!$this->validateFileSyntax()) {
                return false;
            }

            // 檢查修復項目
            if (!$this->validateFixedItems()) {
                return false;
            }

            // 執行特定驗證
            if (!$this->performSpecificValidation()) {
                return false;
            }

            return true;

        } catch (\Exception $e) {
            $this->logError('驗證失敗', $e);
            return false;
        }
    }

    /**
     * 產生修復報告
     */
    public function generateReport(): array
    {
        return [
            'component' => [
                'name' => $this->componentInfo['class_name'] ?? 'Unknown',
                'path' => $this->componentInfo['relative_path'] ?? '',
                'type' => $this->componentInfo['classification']['component_type'] ?? 'GENERIC',
            ],
            'strategy' => $this->getStrategyName(),
            'issues_found' => count($this->issues),
            'issues_fixed' => count($this->fixedItems),
            'progress' => $this->progress,
            'fixes_applied' => $this->fixedItems,
            'backup_files' => $this->backupFiles,
            'validation_passed' => $this->validateFix(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * 回滾修復變更
     */
    public function rollbackFix(): bool
    {
        try {
            foreach ($this->backupFiles as $originalPath => $backupPath) {
                if (File::exists($backupPath)) {
                    File::copy($backupPath, $originalPath);
                    File::delete($backupPath);
                    $this->logInfo("已回滾檔案: {$originalPath}");
                }
            }

            $this->fixedItems = [];
            $this->progress['status'] = 'rolled_back';
            
            return true;

        } catch (\Exception $e) {
            $this->logError('回滾失敗', $e);
            return false;
        }
    }

    /**
     * 取得修復進度
     */
    public function getProgress(): array
    {
        return $this->progress;
    }

    /**
     * 抽象方法：取得 wire:model 模式
     */
    abstract protected function getWireModelPattern(): string;

    /**
     * 抽象方法：取得重置方法模式
     */
    abstract protected function getResetMethodPattern(): string;

    /**
     * 抽象方法：應用特定修復
     */
    abstract protected function applySpecificFixes(): bool;

    /**
     * 抽象方法：執行特定驗證
     */
    abstract protected function performSpecificValidation(): bool;

    /**
     * 抽象方法：檢查是否能修復此問題
     */
    abstract protected function canFixIssue(array $issue): bool;

    /**
     * 應用 wire:model 修復
     */
    protected function applyWireModelFix(): bool
    {
        $fixed = false;

        // 修復 PHP 檔案中的 wire:model
        if (isset($this->componentInfo['path']) && File::exists($this->componentInfo['path'])) {
            $content = File::get($this->componentInfo['path']);
            $originalContent = $content;

            $content = $this->replaceWireModelInContent($content);

            if ($content !== $originalContent) {
                File::put($this->componentInfo['path'], $content);
                $this->fixedItems[] = [
                    'type' => 'wire_model_php',
                    'file' => $this->componentInfo['relative_path'],
                    'description' => '修復 PHP 檔案中的 wire:model 引用',
                ];
                $fixed = true;
            }
        }

        // 修復視圖檔案中的 wire:model
        if (isset($this->componentInfo['view_path']) && File::exists($this->componentInfo['view_path'])) {
            $content = File::get($this->componentInfo['view_path']);
            $originalContent = $content;

            $content = $this->replaceWireModelInContent($content);

            if ($content !== $originalContent) {
                File::put($this->componentInfo['view_path'], $content);
                $this->fixedItems[] = [
                    'type' => 'wire_model_view',
                    'file' => $this->componentInfo['view_relative_path'] ?? '',
                    'description' => '修復視圖檔案中的 wire:model 指令',
                ];
                $fixed = true;
            }
        }

        return $fixed;
    }

    /**
     * 添加 wire:key 屬性
     */
    protected function addWireKeyAttributes(): bool
    {
        if (!isset($this->componentInfo['view_path']) || !File::exists($this->componentInfo['view_path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['view_path']);
        $originalContent = $content;

        // 為缺少 wire:key 的表單元素添加屬性
        $formElements = $this->componentInfo['view_form_elements'] ?? [];
        
        foreach ($formElements as $element) {
            if ($element['has_wire_model'] && !$this->hasWireKeyInElement($element['content'])) {
                $wireKeyValue = $this->generateWireKeyValue($element);
                $content = $this->addWireKeyToElement($content, $element, $wireKeyValue);
            }
        }

        if ($content !== $originalContent) {
            File::put($this->componentInfo['view_path'], $content);
            $this->fixedItems[] = [
                'type' => 'wire_key_attributes',
                'file' => $this->componentInfo['view_relative_path'] ?? '',
                'description' => '為表單元素添加 wire:key 屬性',
            ];
            return true;
        }

        return false;
    }

    /**
     * 應用刷新機制
     */
    protected function applyRefreshMechanism(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['path']);
        $originalContent = $content;

        // 為重置方法添加刷新機制
        $resetMethods = $this->componentInfo['reset_methods'] ?? [];
        
        foreach ($resetMethods as $method) {
            if (!$this->hasDispatchRefresh($method['method_body'] ?? '')) {
                $content = $this->addDispatchRefreshToMethod($content, $method);
            }
        }

        // 添加 JavaScript 監聽器到視圖
        if (isset($this->componentInfo['view_path'])) {
            $this->addJavaScriptListener();
        }

        if ($content !== $originalContent) {
            File::put($this->componentInfo['path'], $content);
            $this->fixedItems[] = [
                'type' => 'refresh_mechanism',
                'file' => $this->componentInfo['relative_path'],
                'description' => '添加強制刷新機制',
            ];
            return true;
        }

        return false;
    }

    /**
     * 建立備份
     */
    protected function createBackup(): void
    {
        $timestamp = now()->format('Y-m-d_H-i-s');

        // 備份 PHP 檔案
        if (isset($this->componentInfo['path']) && File::exists($this->componentInfo['path'])) {
            $backupPath = $this->componentInfo['path'] . ".backup_{$timestamp}";
            File::copy($this->componentInfo['path'], $backupPath);
            $this->backupFiles[$this->componentInfo['path']] = $backupPath;
        }

        // 備份視圖檔案
        if (isset($this->componentInfo['view_path']) && File::exists($this->componentInfo['view_path'])) {
            $backupPath = $this->componentInfo['view_path'] . ".backup_{$timestamp}";
            File::copy($this->componentInfo['view_path'], $backupPath);
            $this->backupFiles[$this->componentInfo['view_path']] = $backupPath;
        }
    }

    /**
     * 初始化進度
     */
    protected function initializeProgress(): void
    {
        $this->progress = [
            'total_steps' => 5, // 備份、wire:model、wire:key、刷新機制、特定修復
            'completed_steps' => 0,
            'current_step' => '開始修復',
            'status' => 'in_progress',
            'errors' => [],
        ];
    }

    /**
     * 更新進度
     */
    protected function updateProgress(string $stepDescription): void
    {
        $this->progress['completed_steps']++;
        $this->progress['current_step'] = $stepDescription;
        
        $this->logInfo("修復進度: {$stepDescription} ({$this->progress['completed_steps']}/{$this->progress['total_steps']})");
    }

    /**
     * 替換內容中的 wire:model
     */
    protected function replaceWireModelInContent(string $content): string
    {
        $patterns = [
            '/wire:model\.lazy\s*=\s*(["\'][^"\']+["\'])/' => 'wire:model.defer=$1',
            '/wire:model\.live(?:\.debounce\.\d+ms)?\s*=\s*(["\'][^"\']+["\'])/' => 'wire:model.defer=$1',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    /**
     * 檢查元素是否有 wire:key
     */
    protected function hasWireKeyInElement(string $elementContent): bool
    {
        return strpos($elementContent, 'wire:key') !== false;
    }

    /**
     * 產生 wire:key 值
     */
    protected function generateWireKeyValue(array $element): string
    {
        $elementType = $element['type'];
        $lineNumber = $element['line_number'] ?? 'unknown';
        
        return "{$elementType}-{$lineNumber}";
    }

    /**
     * 為元素添加 wire:key
     */
    protected function addWireKeyToElement(string $content, array $element, string $wireKeyValue): string
    {
        $elementContent = $element['content'];
        
        // 在開始標籤中添加 wire:key
        if (preg_match('/^<(\w+)([^>]*)>/', $elementContent, $matches)) {
            $tagName = $matches[1];
            $attributes = $matches[2];
            
            $newElement = "<{$tagName}{$attributes} wire:key=\"{$wireKeyValue}\">";
            $content = str_replace($elementContent, $newElement, $content);
        }

        return $content;
    }

    /**
     * 檢查是否有 dispatch refresh
     */
    protected function hasDispatchRefresh(string $methodBody): bool
    {
        return strpos($methodBody, '$this->dispatch(\'$refresh\')') !== false ||
               strpos($methodBody, '$this->dispatch("$refresh")') !== false;
    }

    /**
     * 為方法添加 dispatch refresh
     */
    protected function addDispatchRefreshToMethod(string $content, array $method): string
    {
        $methodName = $method['name'];
        
        // 尋找方法結束的位置（最後一個 } 之前）
        $pattern = "/(public\s+function\s+{$methodName}\s*\([^)]*\)[^{]*{[^}]*)(})/";
        
        $replacement = '$1' . "\n        \n        // 強制重新渲染元件以確保前端同步\n        \$this->dispatch('\$refresh');\n        \n        // 發送前端刷新事件\n        \$this->dispatch('{$methodName}-completed');\n    $2";
        
        return preg_replace($pattern, $replacement, $content);
    }

    /**
     * 添加 JavaScript 監聽器
     */
    protected function addJavaScriptListener(): void
    {
        if (!isset($this->componentInfo['view_path']) || !File::exists($this->componentInfo['view_path'])) {
            return;
        }

        $content = File::get($this->componentInfo['view_path']);
        
        // 檢查是否已經有 JavaScript 監聽器
        if (strpos($content, 'livewire:init') !== false) {
            return;
        }

        $componentName = strtolower($this->componentInfo['class_name'] ?? 'component');
        
        $jsCode = "\n<script>\n    document.addEventListener('livewire:init', () => {\n        Livewire.on('{$componentName}-completed', () => {\n            console.log('🔄 收到 {$componentName} 重置完成事件');\n            \n            // 可以在這裡添加額外的前端處理邏輯\n            setTimeout(() => {\n                // 確保 DOM 更新完成\n                console.log('✅ {$componentName} 重置同步完成');\n            }, 100);\n        });\n    });\n</script>";

        // 在檔案末尾添加 JavaScript
        $content .= $jsCode;
        
        File::put($this->componentInfo['view_path'], $content);
        
        $this->fixedItems[] = [
            'type' => 'javascript_listener',
            'file' => $this->componentInfo['view_relative_path'] ?? '',
            'description' => '添加 JavaScript 事件監聽器',
        ];
    }

    /**
     * 驗證檔案語法
     */
    protected function validateFileSyntax(): bool
    {
        // 驗證 PHP 檔案語法
        if (isset($this->componentInfo['path']) && File::exists($this->componentInfo['path'])) {
            $output = [];
            $returnCode = 0;
            exec("php -l {$this->componentInfo['path']}", $output, $returnCode);
            
            if ($returnCode !== 0) {
                $this->progress['errors'][] = 'PHP 語法錯誤: ' . implode(' ', $output);
                return false;
            }
        }

        return true;
    }

    /**
     * 驗證修復項目
     */
    protected function validateFixedItems(): bool
    {
        foreach ($this->fixedItems as $item) {
            if (!$this->validateFixedItem($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 驗證單個修復項目
     */
    protected function validateFixedItem(array $item): bool
    {
        switch ($item['type']) {
            case 'wire_model_php':
            case 'wire_model_view':
                return $this->validateWireModelFix($item);
                
            case 'wire_key_attributes':
                return $this->validateWireKeyFix($item);
                
            case 'refresh_mechanism':
                return $this->validateRefreshMechanismFix($item);
                
            default:
                return true;
        }
    }

    /**
     * 驗證 wire:model 修復
     */
    protected function validateWireModelFix(array $item): bool
    {
        $filePath = $item['type'] === 'wire_model_php' ? 
            $this->componentInfo['path'] : 
            $this->componentInfo['view_path'];

        if (!File::exists($filePath)) {
            return false;
        }

        $content = File::get($filePath);
        
        // 檢查是否還有 wire:model.lazy 或 wire:model.live
        if (preg_match('/wire:model\.(?:lazy|live)/', $content)) {
            $this->progress['errors'][] = '仍然存在未修復的 wire:model.lazy 或 wire:model.live';
            return false;
        }

        return true;
    }

    /**
     * 驗證 wire:key 修復
     */
    protected function validateWireKeyFix(array $item): bool
    {
        if (!isset($this->componentInfo['view_path']) || !File::exists($this->componentInfo['view_path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['view_path']);
        
        // 檢查是否有 wire:key 屬性
        if (!preg_match('/wire:key\s*=/', $content)) {
            $this->progress['errors'][] = '未找到添加的 wire:key 屬性';
            return false;
        }

        return true;
    }

    /**
     * 驗證刷新機制修復
     */
    protected function validateRefreshMechanismFix(array $item): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['path']);
        
        // 檢查是否有 dispatch refresh
        if (!preg_match('/\$this->dispatch\([\'\"]\$refresh[\'\"]\)/', $content)) {
            $this->progress['errors'][] = '未找到添加的刷新機制';
            return false;
        }

        return true;
    }

    /**
     * 記錄成功訊息
     */
    protected function logSuccess(string $message): void
    {
        Log::info("[FormResetFix] {$message}", [
            'component' => $this->componentInfo['class_name'] ?? 'Unknown',
            'strategy' => $this->getStrategyName(),
        ]);
    }

    /**
     * 記錄資訊訊息
     */
    protected function logInfo(string $message): void
    {
        Log::info("[FormResetFix] {$message}", [
            'component' => $this->componentInfo['class_name'] ?? 'Unknown',
        ]);
    }

    /**
     * 記錄錯誤訊息
     */
    protected function logError(string $message, ?\Exception $exception = null): void
    {
        Log::error("[FormResetFix] {$message}", [
            'component' => $this->componentInfo['class_name'] ?? 'Unknown',
            'strategy' => $this->getStrategyName(),
            'exception' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);
    }
}