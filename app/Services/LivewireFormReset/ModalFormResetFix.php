<?php

namespace App\Services\LivewireFormReset;

use Illuminate\Support\Facades\File;

/**
 * 模態表單重置修復類別
 * 
 * 專門處理模態對話框中表單元件的重置功能修復
 */
class ModalFormResetFix extends StandardFormResetFix
{
    /**
     * 取得策略名稱
     */
    public function getStrategyName(): string
    {
        return 'ModalFormResetFix';
    }

    /**
     * 檢查是否支援此元件類型
     */
    public function supports(array $componentInfo): bool
    {
        $componentType = $componentInfo['classification']['component_type'] ?? '';
        $supportedTypes = ['FORM_MODAL'];
        
        // 也檢查類別名稱是否包含 Modal
        $className = $componentInfo['class_name'] ?? '';
        $hasModalInName = stripos($className, 'Modal') !== false;
        
        return in_array($componentType, $supportedTypes) || $hasModalInName;
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

        // 修復模態狀態重置
        if ($this->fixModalStateReset()) {
            $fixed = true;
        }

        // 修復表單驗證重置
        if ($this->fixFormValidationReset()) {
            $fixed = true;
        }

        // 修復確認欄位重置
        if ($this->fixConfirmationFields()) {
            $fixed = true;
        }

        // 添加模態關閉事件處理
        if ($this->addModalCloseEventHandling()) {
            $fixed = true;
        }

        // 修復模態開啟時的狀態初始化
        if ($this->fixModalOpenInitialization()) {
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

        // 驗證模態狀態管理
        if (!$this->validateModalStateManagement()) {
            return false;
        }

        // 驗證表單重置完整性
        if (!$this->validateFormResetCompleteness()) {
            return false;
        }

        // 驗證確認欄位處理
        if (!$this->validateConfirmationFieldHandling()) {
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
            'modal_state_issues',
            'form_validation_issues',
            'confirmation_field_issues',
        ];

        return in_array($issue['type'], $supportedIssueTypes);
    }

    /**
     * 修復模態狀態重置
     */
    protected function fixModalStateReset(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['path']);
        $originalContent = $content;

        // 檢查是否有模態狀態屬性
        $modalProperties = $this->extractModalProperties($content);
        
        if (empty($modalProperties)) {
            return false;
        }

        // 確保 closeModal 方法存在且正確實作
        if (!preg_match('/function\s+closeModal/', $content)) {
            $closeModalMethod = $this->generateCloseModalMethod($modalProperties);
            $content = $this->insertMethodIntoClass($content, $closeModalMethod);
        } else {
            $content = $this->improveCloseModalMethod($content, $modalProperties);
        }

        // 確保 openModal 方法存在且正確實作
        if (!preg_match('/function\s+(?:openModal|showModal)/', $content)) {
            $openModalMethod = $this->generateOpenModalMethod($modalProperties);
            $content = $this->insertMethodIntoClass($content, $openModalMethod);
        }

        // 改進 resetForm 方法以包含模態狀態重置
        $content = $this->improveResetFormForModal($content, $modalProperties);

        if ($content !== $originalContent) {
            File::put($this->componentInfo['path'], $content);
            $this->fixedItems[] = [
                'type' => 'modal_state_reset',
                'file' => $this->componentInfo['relative_path'],
                'description' => '修復模態狀態重置功能',
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

        // 在所有重置方法中添加驗證重置
        $resetMethods = ['resetForm', 'closeModal', 'cancel'];
        
        foreach ($resetMethods as $methodName) {
            if (preg_match("/function\s+{$methodName}/", $content)) {
                $content = $this->addValidationResetToMethod($content, $methodName);
            }
        }

        // 添加特定欄位驗證重置
        $content = $this->addSpecificFieldValidationReset($content);

        if ($content !== $originalContent) {
            File::put($this->componentInfo['path'], $content);
            $this->fixedItems[] = [
                'type' => 'form_validation_reset',
                'file' => $this->componentInfo['relative_path'],
                'description' => '修復表單驗證重置',
            ];
            return true;
        }

        return false;
    }

    /**
     * 修復確認欄位重置
     */
    protected function fixConfirmationFields(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['path']);
        $originalContent = $content;

        // 尋找確認相關的屬性
        $confirmationFields = $this->extractConfirmationFields($content);
        
        if (empty($confirmationFields)) {
            return false;
        }

        // 在重置方法中添加確認欄位重置
        foreach ($confirmationFields as $field) {
            $content = $this->addConfirmationFieldReset($content, $field);
        }

        // 添加確認文字驗證
        $content = $this->addConfirmationTextValidation($content, $confirmationFields);

        if ($content !== $originalContent) {
            File::put($this->componentInfo['path'], $content);
            $this->fixedItems[] = [
                'type' => 'confirmation_fields',
                'file' => $this->componentInfo['relative_path'],
                'description' => '修復確認欄位重置',
            ];
            return true;
        }

        return false;
    }

    /**
     * 添加模態關閉事件處理
     */
    protected function addModalCloseEventHandling(): bool
    {
        if (!isset($this->componentInfo['view_path']) || !File::exists($this->componentInfo['view_path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['view_path']);
        $originalContent = $content;

        // 檢查是否已經有關閉事件處理
        if (strpos($content, '@keydown.escape') !== false) {
            return false;
        }

        // 添加 ESC 鍵關閉模態
        $content = preg_replace(
            '/(<div[^>]*class[^>]*modal[^>]*)(>)/',
            '$1 @keydown.escape="closeModal"$2',
            $content
        );

        // 添加背景點擊關閉
        $content = preg_replace(
            '/(<div[^>]*class[^>]*(?:backdrop|overlay)[^>]*)(>)/',
            '$1 @click="closeModal"$2',
            $content
        );

        // 添加關閉按鈕的 wire:key
        $content = preg_replace(
            '/(<button[^>]*wire:click\s*=\s*["\']closeModal["\'][^>]*)(>)/',
            '$1 wire:key="modal-close-button"$2',
            $content
        );

        if ($content !== $originalContent) {
            File::put($this->componentInfo['view_path'], $content);
            $this->fixedItems[] = [
                'type' => 'modal_close_events',
                'file' => $this->componentInfo['view_relative_path'] ?? '',
                'description' => '添加模態關閉事件處理',
            ];
            return true;
        }

        return false;
    }

    /**
     * 修復模態開啟時的狀態初始化
     */
    protected function fixModalOpenInitialization(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return false;
        }

        $content = File::get($this->componentInfo['path']);
        $originalContent = $content;

        // 檢查是否有 openModal 或 showModal 方法
        if (preg_match('/function\s+(openModal|showModal)/', $content, $matches)) {
            $methodName = $matches[1];
            
            // 確保方法包含狀態初始化
            if (!preg_match("/function\s+{$methodName}[^{]*{[^}]*resetForm/", $content)) {
                $content = $this->addStateInitializationToOpenModal($content, $methodName);
            }
        }

        if ($content !== $originalContent) {
            File::put($this->componentInfo['path'], $content);
            $this->fixedItems[] = [
                'type' => 'modal_open_initialization',
                'file' => $this->componentInfo['relative_path'],
                'description' => '修復模態開啟時的狀態初始化',
            ];
            return true;
        }

        return false;
    }

    /**
     * 提取模態屬性
     */
    protected function extractModalProperties(string $content): array
    {
        $properties = [];
        
        // 尋找模態相關的屬性
        $patterns = [
            '/public\s+bool\s+\$show(?:Modal|Dialog|Form)\s*=\s*false;/',
            '/public\s+bool\s+\$(?:modal|dialog|form)(?:Open|Visible|Show)\s*=\s*false;/',
            '/public\s+bool\s+\$is(?:Open|Visible|Show)\s*=\s*false;/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[0] as $match) {
                    if (preg_match('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', $match, $propMatch)) {
                        $properties[] = $propMatch[1];
                    }
                }
            }
        }

        return array_unique($properties);
    }

    /**
     * 提取確認欄位
     */
    protected function extractConfirmationFields(string $content): array
    {
        $fields = [];
        
        // 尋找確認相關的屬性
        $patterns = [
            '/public\s+string\s+\$confirm(?:Text|ation|Password|Delete)\s*=\s*[\'"][\'"];/',
            '/public\s+string\s+\$(?:delete|remove)Confirmation\s*=\s*[\'"][\'"];/',
            '/public\s+bool\s+\$confirm(?:Delete|Action|Operation)\s*=\s*false;/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[0] as $match) {
                    if (preg_match('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', $match, $propMatch)) {
                        $fields[] = $propMatch[1];
                    }
                }
            }
        }

        return array_unique($fields);
    }

    /**
     * 產生 closeModal 方法
     */
    protected function generateCloseModalMethod(array $modalProperties): string
    {
        $resetStatements = [];
        
        foreach ($modalProperties as $property) {
            $resetStatements[] = "        \$this->{$property} = false;";
        }

        $resetCode = implode("\n", $resetStatements);

        return "
    /**
     * 關閉模態並重置表單
     */
    public function closeModal(): void
    {
        // 關閉模態
{$resetCode}
        
        // 重置表單
        \$this->resetForm();
        
        // 強制重新渲染
        \$this->dispatch('\$refresh');
        
        // 發送模態關閉事件
        \$this->dispatch('modal-closed');
    }";
    }

    /**
     * 產生 openModal 方法
     */
    protected function generateOpenModalMethod(array $modalProperties): string
    {
        $openStatements = [];
        
        foreach ($modalProperties as $property) {
            $openStatements[] = "        \$this->{$property} = true;";
        }

        $openCode = implode("\n", $openStatements);

        return "
    /**
     * 開啟模態並初始化狀態
     */
    public function openModal(): void
    {
        // 先重置表單確保乾淨狀態
        \$this->resetForm();
        
        // 開啟模態
{$openCode}
        
        // 發送模態開啟事件
        \$this->dispatch('modal-opened');
    }";
    }

    /**
     * 改進 closeModal 方法
     */
    protected function improveCloseModalMethod(string $content, array $modalProperties): string
    {
        // 確保 closeModal 方法包含完整的重置邏輯
        $pattern = '/(public\s+function\s+closeModal\s*\([^)]*\)[^{]*{)([^}]*)(})/s';
        
        $resetStatements = [];
        foreach ($modalProperties as $property) {
            $resetStatements[] = "        \$this->{$property} = false;";
        }
        $resetCode = implode("\n", $resetStatements);

        $improvedBody = "
        // 關閉模態
{$resetCode}
        
        // 重置表單和驗證
        \$this->resetForm();
        \$this->resetValidation();
        
        // 強制重新渲染
        \$this->dispatch('\$refresh');
        
        // 發送模態關閉事件
        \$this->dispatch('modal-closed');
";

        return preg_replace($pattern, '$1' . $improvedBody . '$3', $content);
    }

    /**
     * 改進 resetForm 方法以包含模態狀態
     */
    protected function improveResetFormForModal(string $content, array $modalProperties): string
    {
        if (!preg_match('/function\s+resetForm/', $content)) {
            return $content;
        }

        // 檢查 resetForm 是否已經重置模態狀態
        $hasModalReset = false;
        foreach ($modalProperties as $property) {
            if (preg_match("/function\s+resetForm[^{]*{[^}]*\\\$this->{$property}\s*=\s*false/", $content)) {
                $hasModalReset = true;
                break;
            }
        }

        if (!$hasModalReset) {
            $resetStatements = [];
            foreach ($modalProperties as $property) {
                $resetStatements[] = "        \$this->{$property} = false;";
            }
            $resetCode = implode("\n", $resetStatements);

            $pattern = '/(public\s+function\s+resetForm\s*\([^)]*\)[^{]*{)([^}]*)(})/s';
            $replacement = '$1$2' . "\n        // 重置模態狀態\n{$resetCode}\n    $3";
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    /**
     * 為方法添加驗證重置
     */
    protected function addValidationResetToMethod(string $content, string $methodName): string
    {
        if (!preg_match("/function\s+{$methodName}[^{]*{[^}]*resetValidation/", $content)) {
            $pattern = "/(public\s+function\s+{$methodName}\s*\([^)]*\)[^{]*{)([^}]*)(})/s";
            $replacement = '$1$2' . "\n        \$this->resetValidation();\n    $3";
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    /**
     * 添加特定欄位驗證重置
     */
    protected function addSpecificFieldValidationReset(string $content): string
    {
        // 為常見的表單欄位添加特定驗證重置
        $commonFields = ['name', 'email', 'password', 'username', 'title', 'description'];
        
        foreach ($commonFields as $field) {
            if (preg_match("/public\s+string\s+\\\${$field}/", $content)) {
                // 添加 resetValidation 的特定欄位版本
                $resetMethods = ['resetForm', 'closeModal'];
                
                foreach ($resetMethods as $methodName) {
                    if (preg_match("/function\s+{$methodName}/", $content)) {
                        $pattern = "/(public\s+function\s+{$methodName}\s*\([^)]*\)[^{]*{)([^}]*)(})/s";
                        $replacement = '$1$2' . "\n        \$this->resetValidation('{$field}');\n    $3";
                        $content = preg_replace($pattern, $replacement, $content, 1);
                    }
                }
            }
        }

        return $content;
    }

    /**
     * 添加確認欄位重置
     */
    protected function addConfirmationFieldReset(string $content, string $field): string
    {
        $resetMethods = ['resetForm', 'closeModal', 'cancel'];
        
        foreach ($resetMethods as $methodName) {
            if (preg_match("/function\s+{$methodName}/", $content)) {
                if (!preg_match("/function\s+{$methodName}[^{]*{[^}]*\\\$this->{$field}/", $content)) {
                    $defaultValue = str_contains($field, 'confirm') && !str_contains($field, 'Text') ? 'false' : "''";
                    
                    $pattern = "/(public\s+function\s+{$methodName}\s*\([^)]*\)[^{]*{)([^}]*)(})/s";
                    $replacement = '$1$2' . "\n        \$this->{$field} = {$defaultValue};\n    $3";
                    $content = preg_replace($pattern, $replacement, $content, 1);
                }
            }
        }

        return $content;
    }

    /**
     * 添加確認文字驗證
     */
    protected function addConfirmationTextValidation(string $content, array $confirmationFields): string
    {
        foreach ($confirmationFields as $field) {
            if (str_contains($field, 'Text') || str_contains($field, 'ation')) {
                // 添加確認文字驗證方法
                if (!preg_match("/function\s+validate" . ucfirst($field) . "/", $content)) {
                    $validationMethod = $this->generateConfirmationValidationMethod($field);
                    $content = $this->insertMethodIntoClass($content, $validationMethod);
                }
            }
        }

        return $content;
    }

    /**
     * 產生確認驗證方法
     */
    protected function generateConfirmationValidationMethod(string $field): string
    {
        $methodName = 'validate' . ucfirst($field);
        $expectedText = str_contains($field, 'delete') ? 'DELETE' : 'CONFIRM';

        return "
    /**
     * 驗證確認文字
     */
    public function {$methodName}(): bool
    {
        if (strtoupper(\$this->{$field}) !== '{$expectedText}') {
            \$this->addError('{$field}', '請輸入 {$expectedText} 以確認操作');
            return false;
        }
        
        return true;
    }";
    }

    /**
     * 為 openModal 添加狀態初始化
     */
    protected function addStateInitializationToOpenModal(string $content, string $methodName): string
    {
        $pattern = "/(public\s+function\s+{$methodName}\s*\([^)]*\)[^{]*{)([^}]*)(})/s";
        $replacement = '$1' . "\n        // 初始化表單狀態\n        \$this->resetForm();\n$2$3";
        
        return preg_replace($pattern, $replacement, $content);
    }

    /**
     * 驗證模態狀態管理
     */
    protected function validateModalStateManagement(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return true;
        }

        $content = File::get($this->componentInfo['path']);

        // 檢查是否有模態狀態屬性
        if (!preg_match('/public\s+bool\s+\$(?:show|modal|is)/', $content)) {
            $this->progress['errors'][] = '模態元件缺少狀態管理屬性';
            return false;
        }

        // 檢查是否有 closeModal 方法
        if (!preg_match('/function\s+closeModal/', $content)) {
            $this->progress['errors'][] = '模態元件缺少 closeModal 方法';
            return false;
        }

        return true;
    }

    /**
     * 驗證表單重置完整性
     */
    protected function validateFormResetCompleteness(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return true;
        }

        $content = File::get($this->componentInfo['path']);

        // 檢查 resetForm 方法是否存在
        if (!preg_match('/function\s+resetForm/', $content)) {
            $this->progress['errors'][] = '模態表單缺少 resetForm 方法';
            return false;
        }

        // 檢查是否包含驗證重置
        if (!preg_match('/resetValidation\(\)/', $content)) {
            $this->progress['errors'][] = 'resetForm 方法缺少驗證重置';
            return false;
        }

        return true;
    }

    /**
     * 驗證確認欄位處理
     */
    protected function validateConfirmationFieldHandling(): bool
    {
        if (!isset($this->componentInfo['path']) || !File::exists($this->componentInfo['path'])) {
            return true;
        }

        $content = File::get($this->componentInfo['path']);
        $confirmationFields = $this->extractConfirmationFields($content);

        // 如果有確認欄位，檢查是否正確處理
        foreach ($confirmationFields as $field) {
            if (!preg_match("/\\\$this->{$field}\s*=/", $content)) {
                $this->progress['errors'][] = "確認欄位 {$field} 未在重置方法中處理";
                return false;
            }
        }

        return true;
    }

    /**
     * 產生改進的重置方法主體（覆寫父類別方法）
     */
    protected function generateImprovedResetMethodBody(string $methodName, array $properties): string
    {
        $resetStatements = [];

        // 重置表單屬性
        foreach ($properties as $property) {
            $defaultValue = $this->getPropertyDefaultValue($property);
            $resetStatements[] = "        \$this->{$property} = {$defaultValue};";
        }

        // 重置模態狀態
        $resetStatements[] = "        \$this->showModal = false;";
        $resetStatements[] = "        \$this->isOpen = false;";

        // 清除驗證錯誤
        $resetStatements[] = "        \$this->resetValidation();";

        $resetCode = implode("\n", $resetStatements);

        return "
        // 記錄表單重置操作
        \\Log::info('🔄 {$methodName} - 模態表單重置開始', [
            'timestamp' => now()->toISOString(),
            'user' => auth()->user()->username ?? 'unknown',
            'modal_component' => static::class,
        ]);
        
        // 重置所有表單欄位和模態狀態
{$resetCode}
        
        // 強制重新渲染以確保前端同步
        \$this->dispatch('\$refresh');
        
        // 發送表單重置完成事件
        \$this->dispatch('{$methodName}-completed');
        \$this->dispatch('modal-form-reset');
        
        // 記錄重置完成
        \\Log::info('✅ {$methodName} - 模態表單重置完成');
";
    }
}