<?php

namespace App\Services\LivewireFormReset;

use Illuminate\Support\Collection;

/**
 * 問題識別器
 * 
 * 分析 Livewire 元件中的 DOM 同步問題和修復優先級
 */
class IssueIdentifier
{
    /**
     * 問題類型定義
     */
    const ISSUE_TYPES = [
        'missing_wire_key' => [
            'severity' => 'high',
            'description' => '缺少 wire:key 屬性，可能導致 DOM 更新問題',
            'fix_priority' => 9,
        ],
        'incorrect_wire_model' => [
            'severity' => 'high', 
            'description' => '使用 wire:model.lazy 或 wire:model.live 可能導致重置失效',
            'fix_priority' => 10,
        ],
        'missing_refresh_mechanism' => [
            'severity' => 'medium',
            'description' => '缺少強制刷新機制，重置後可能不同步',
            'fix_priority' => 7,
        ],
        'javascript_conflicts' => [
            'severity' => 'medium',
            'description' => 'JavaScript 事件處理可能與 Livewire 衝突',
            'fix_priority' => 6,
        ],
        'missing_reset_method' => [
            'severity' => 'low',
            'description' => '缺少標準化的重置方法',
            'fix_priority' => 4,
        ],
        'complex_form_structure' => [
            'severity' => 'medium',
            'description' => '複雜的表單結構可能需要特殊處理',
            'fix_priority' => 5,
        ],
        'nested_components' => [
            'severity' => 'high',
            'description' => '巢狀元件可能導致狀態同步問題',
            'fix_priority' => 8,
        ],
    ];

    /**
     * 識別元件中的所有問題
     */
    public function identifyAllIssues(array $componentInfo): array
    {
        $issues = [];

        // 檢查 wire:key 問題
        $wireKeyIssues = $this->checkWireKeyUsage($componentInfo);
        if (!empty($wireKeyIssues)) {
            $issues = array_merge($issues, $wireKeyIssues);
        }

        // 檢查 wire:model 問題
        $wireModelIssues = $this->checkWireModelType($componentInfo);
        if (!empty($wireModelIssues)) {
            $issues = array_merge($issues, $wireModelIssues);
        }

        // 檢查刷新機制
        $refreshIssues = $this->checkRefreshMechanism($componentInfo);
        if (!empty($refreshIssues)) {
            $issues = array_merge($issues, $refreshIssues);
        }

        // 檢查 JavaScript 衝突
        $jsIssues = $this->checkJavaScriptConflicts($componentInfo);
        if (!empty($jsIssues)) {
            $issues = array_merge($issues, $jsIssues);
        }

        // 檢查重置方法
        $resetMethodIssues = $this->checkResetMethods($componentInfo);
        if (!empty($resetMethodIssues)) {
            $issues = array_merge($issues, $resetMethodIssues);
        }

        // 檢查表單複雜度
        $complexityIssues = $this->checkFormComplexity($componentInfo);
        if (!empty($complexityIssues)) {
            $issues = array_merge($issues, $complexityIssues);
        }

        // 檢查巢狀元件
        $nestedIssues = $this->checkNestedComponents($componentInfo);
        if (!empty($nestedIssues)) {
            $issues = array_merge($issues, $nestedIssues);
        }

        return $this->prioritizeIssues($issues);
    }

    /**
     * 檢查 wire:key 使用情況
     */
    public function checkWireKeyUsage(array $componentInfo): array
    {
        $issues = [];
        $wireKeyUsage = $componentInfo['view_wire_key_usage'] ?? [];
        $formElements = $componentInfo['view_form_elements'] ?? [];

        // 檢查表單元素是否缺少 wire:key
        foreach ($formElements as $element) {
            if ($element['has_wire_model'] && !$this->hasWireKeyInElement($element['content'])) {
                $issues[] = [
                    'type' => 'missing_wire_key',
                    'severity' => self::ISSUE_TYPES['missing_wire_key']['severity'],
                    'description' => self::ISSUE_TYPES['missing_wire_key']['description'],
                    'fix_priority' => self::ISSUE_TYPES['missing_wire_key']['fix_priority'],
                    'location' => [
                        'file' => $componentInfo['view_relative_path'] ?? $componentInfo['relative_path'],
                        'line' => $element['line_number'],
                        'element_type' => $element['type'],
                    ],
                    'context' => $element['content'],
                    'suggested_fix' => $this->suggestWireKeyFix($element),
                ];
            }
        }

        // 檢查重複的 wire:key 值
        $keyValues = array_column($wireKeyUsage, 'key_value');
        $duplicateKeys = array_filter(array_count_values($keyValues), fn($count) => $count > 1);

        foreach ($duplicateKeys as $keyValue => $count) {
            $issues[] = [
                'type' => 'duplicate_wire_key',
                'severity' => 'medium',
                'description' => "重複的 wire:key 值 '{$keyValue}' 出現 {$count} 次",
                'fix_priority' => 6,
                'location' => [
                    'file' => $componentInfo['view_relative_path'] ?? $componentInfo['relative_path'],
                    'key_value' => $keyValue,
                ],
                'suggested_fix' => "為每個元素使用唯一的 wire:key 值",
            ];
        }

        return $issues;
    }

    /**
     * 檢查 wire:model 類型
     */
    public function checkWireModelType(array $componentInfo): array
    {
        $issues = [];
        $wireModelUsage = array_merge(
            $componentInfo['wire_model_usage'] ?? [],
            $componentInfo['view_wire_model_usage'] ?? []
        );

        foreach ($wireModelUsage as $usage) {
            if (in_array($usage['type'], ['wire:model.lazy', 'wire:model.live'])) {
                $issues[] = [
                    'type' => 'incorrect_wire_model',
                    'severity' => self::ISSUE_TYPES['incorrect_wire_model']['severity'],
                    'description' => self::ISSUE_TYPES['incorrect_wire_model']['description'],
                    'fix_priority' => self::ISSUE_TYPES['incorrect_wire_model']['fix_priority'],
                    'location' => [
                        'file' => isset($usage['line_number']) ? 
                            ($componentInfo['view_relative_path'] ?? $componentInfo['relative_path']) :
                            $componentInfo['relative_path'],
                        'line' => $usage['line_number'] ?? null,
                        'property' => $usage['property'] ?? '',
                    ],
                    'context' => $usage['full_match'] ?? $usage['context'] ?? '',
                    'current_type' => $usage['type'],
                    'suggested_fix' => $this->suggestWireModelFix($usage['type']),
                ];
            }
        }

        return $issues;
    }

    /**
     * 檢查刷新機制
     */
    public function checkRefreshMechanism(array $componentInfo): array
    {
        $issues = [];
        $resetMethods = $componentInfo['reset_methods'] ?? [];
        
        foreach ($resetMethods as $method) {
            $methodBody = $method['method_body'] ?? '';
            
            // 檢查是否有 $this->dispatch('$refresh')
            if (!$this->hasDispatchRefresh($methodBody)) {
                $issues[] = [
                    'type' => 'missing_refresh_mechanism',
                    'severity' => self::ISSUE_TYPES['missing_refresh_mechanism']['severity'],
                    'description' => self::ISSUE_TYPES['missing_refresh_mechanism']['description'],
                    'fix_priority' => self::ISSUE_TYPES['missing_refresh_mechanism']['fix_priority'],
                    'location' => [
                        'file' => $componentInfo['relative_path'],
                        'method' => $method['name'],
                        'line' => $this->getLineNumber($componentInfo, $method['found_at']),
                    ],
                    'context' => $method['full_match'],
                    'suggested_fix' => $this->suggestRefreshMechanismFix($method['name']),
                ];
            }
        }

        return $issues;
    }

    /**
     * 檢查 JavaScript 衝突
     */
    public function checkJavaScriptConflicts(array $componentInfo): array
    {
        $issues = [];
        $jsEvents = $componentInfo['view_javascript_events'] ?? [];
        
        // 檢查可能的事件衝突
        $conflictPatterns = [
            'form_submit' => '/onsubmit\s*=/',
            'input_change' => '/onchange\s*=/',
            'click_handler' => '/onclick\s*=.*(?:reset|clear)/',
        ];

        foreach ($jsEvents as $event) {
            foreach ($conflictPatterns as $conflictType => $pattern) {
                if (preg_match($pattern, $event['content'])) {
                    $issues[] = [
                        'type' => 'javascript_conflicts',
                        'severity' => self::ISSUE_TYPES['javascript_conflicts']['severity'],
                        'description' => self::ISSUE_TYPES['javascript_conflicts']['description'],
                        'fix_priority' => self::ISSUE_TYPES['javascript_conflicts']['fix_priority'],
                        'location' => [
                            'file' => $componentInfo['view_relative_path'] ?? $componentInfo['relative_path'],
                            'line' => $event['line_number'],
                            'event_type' => $event['type'],
                        ],
                        'context' => $event['content'],
                        'conflict_type' => $conflictType,
                        'suggested_fix' => $this->suggestJavaScriptFix($conflictType),
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * 檢查重置方法
     */
    public function checkResetMethods(array $componentInfo): array
    {
        $issues = [];
        $resetMethods = $componentInfo['reset_methods'] ?? [];
        
        if (empty($resetMethods)) {
            // 檢查是否有重置按鈕但沒有對應方法
            $resetButtons = $componentInfo['view_reset_buttons'] ?? [];
            if (!empty($resetButtons)) {
                $issues[] = [
                    'type' => 'missing_reset_method',
                    'severity' => self::ISSUE_TYPES['missing_reset_method']['severity'],
                    'description' => self::ISSUE_TYPES['missing_reset_method']['description'],
                    'fix_priority' => self::ISSUE_TYPES['missing_reset_method']['fix_priority'],
                    'location' => [
                        'file' => $componentInfo['relative_path'],
                    ],
                    'context' => '元件有重置按鈕但缺少重置方法',
                    'suggested_fix' => $this->suggestResetMethodFix($componentInfo),
                ];
            }
        } else {
            // 檢查重置方法的實作品質
            foreach ($resetMethods as $method) {
                $methodBody = $method['method_body'] ?? '';
                $qualityIssues = $this->analyzeResetMethodQuality($method, $methodBody);
                $issues = array_merge($issues, $qualityIssues);
            }
        }

        return $issues;
    }

    /**
     * 檢查表單複雜度
     */
    public function checkFormComplexity(array $componentInfo): array
    {
        $issues = [];
        $complexityScore = $componentInfo['complexity_score'] ?? 0;
        $formElements = $componentInfo['view_form_elements'] ?? [];
        
        // 高複雜度閾值
        if ($complexityScore > 50 || count($formElements) > 10) {
            $issues[] = [
                'type' => 'complex_form_structure',
                'severity' => self::ISSUE_TYPES['complex_form_structure']['severity'],
                'description' => self::ISSUE_TYPES['complex_form_structure']['description'],
                'fix_priority' => self::ISSUE_TYPES['complex_form_structure']['fix_priority'],
                'location' => [
                    'file' => $componentInfo['relative_path'],
                ],
                'context' => "複雜度分數: {$complexityScore}, 表單元素數量: " . count($formElements),
                'suggested_fix' => $this->suggestComplexityFix($complexityScore, count($formElements)),
            ];
        }

        return $issues;
    }

    /**
     * 檢查巢狀元件
     */
    public function checkNestedComponents(array $componentInfo): array
    {
        $issues = [];
        $viewContent = '';
        
        if (isset($componentInfo['view_path']) && file_exists($componentInfo['view_path'])) {
            $viewContent = file_get_contents($componentInfo['view_path']);
        }

        // 檢查 Livewire 元件標籤
        $nestedComponentPattern = '/<livewire:[^>]+>|@livewire\s*\(/';
        if (preg_match_all($nestedComponentPattern, $viewContent, $matches)) {
            $issues[] = [
                'type' => 'nested_components',
                'severity' => self::ISSUE_TYPES['nested_components']['severity'],
                'description' => self::ISSUE_TYPES['nested_components']['description'],
                'fix_priority' => self::ISSUE_TYPES['nested_components']['fix_priority'],
                'location' => [
                    'file' => $componentInfo['view_relative_path'] ?? $componentInfo['relative_path'],
                ],
                'context' => '發現 ' . count($matches[0]) . ' 個巢狀 Livewire 元件',
                'nested_count' => count($matches[0]),
                'suggested_fix' => $this->suggestNestedComponentFix(count($matches[0])),
            ];
        }

        return $issues;
    }

    /**
     * 按優先級排序問題
     */
    public function prioritizeIssues(array $issues): array
    {
        usort($issues, function ($a, $b) {
            // 先按修復優先級排序（高到低）
            $priorityDiff = $b['fix_priority'] - $a['fix_priority'];
            if ($priorityDiff !== 0) {
                return $priorityDiff;
            }

            // 再按嚴重程度排序
            $severityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
            $severityA = $severityOrder[$a['severity']] ?? 0;
            $severityB = $severityOrder[$b['severity']] ?? 0;
            
            return $severityB - $severityA;
        });

        return $issues;
    }

    /**
     * 分析重置方法品質
     */
    protected function analyzeResetMethodQuality(array $method, string $methodBody): array
    {
        $issues = [];

        // 檢查是否使用了不安全的重置方式
        if (strpos($methodBody, 'window.location.reload()') !== false) {
            $issues[] = [
                'type' => 'unsafe_reset_method',
                'severity' => 'medium',
                'description' => '使用 window.location.reload() 可能導致使用者體驗不佳',
                'fix_priority' => 5,
                'location' => [
                    'method' => $method['name'],
                ],
                'context' => $methodBody,
                'suggested_fix' => '使用 $this->dispatch(\'$refresh\') 替代頁面重新載入',
            ];
        }

        // 檢查是否有適當的錯誤處理
        if (!preg_match('/try\s*{|catch\s*\(/', $methodBody)) {
            $issues[] = [
                'type' => 'missing_error_handling',
                'severity' => 'low',
                'description' => '重置方法缺少錯誤處理',
                'fix_priority' => 3,
                'location' => [
                    'method' => $method['name'],
                ],
                'context' => $methodBody,
                'suggested_fix' => '添加 try-catch 錯誤處理機制',
            ];
        }

        return $issues;
    }

    /**
     * 檢查元素是否有 wire:key
     */
    protected function hasWireKeyInElement(string $elementContent): bool
    {
        return strpos($elementContent, 'wire:key') !== false;
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
     * 取得行號
     */
    protected function getLineNumber(array $componentInfo, int $position): int
    {
        if (isset($componentInfo['path']) && file_exists($componentInfo['path'])) {
            $content = file_get_contents($componentInfo['path']);
            return substr_count(substr($content, 0, $position), "\n") + 1;
        }
        return 0;
    }

    /**
     * 建議 wire:key 修復
     */
    protected function suggestWireKeyFix(array $element): string
    {
        $elementType = $element['type'];
        return "為 {$elementType} 元素添加唯一的 wire:key 屬性，例如: wire:key=\"{$elementType}-{{ \$loop->index }}\"";
    }

    /**
     * 建議 wire:model 修復
     */
    protected function suggestWireModelFix(string $currentType): string
    {
        return "將 {$currentType} 改為 wire:model.defer 以避免即時同步問題";
    }

    /**
     * 建議刷新機制修復
     */
    protected function suggestRefreshMechanismFix(string $methodName): string
    {
        return "在 {$methodName} 方法中添加 \$this->dispatch('\$refresh'); 強制重新渲染";
    }

    /**
     * 建議 JavaScript 修復
     */
    protected function suggestJavaScriptFix(string $conflictType): string
    {
        return match ($conflictType) {
            'form_submit' => '移除 onsubmit 處理器，使用 Livewire 的 wire:submit 替代',
            'input_change' => '移除 onchange 處理器，使用 wire:model 自動處理',
            'click_handler' => '移除 onclick 處理器，使用 wire:click 替代',
            default => '移除 JavaScript 事件處理器，使用對應的 Livewire 指令',
        };
    }

    /**
     * 建議重置方法修復
     */
    protected function suggestResetMethodFix(array $componentInfo): string
    {
        $className = $componentInfo['class_name'] ?? 'Component';
        return "在 {$className} 中添加 resetFilters() 或 resetForm() 方法";
    }

    /**
     * 建議複雜度修復
     */
    protected function suggestComplexityFix(int $complexityScore, int $elementCount): string
    {
        if ($complexityScore > 100) {
            return '考慮將元件拆分為多個較小的子元件';
        } elseif ($elementCount > 15) {
            return '考慮使用分頁或分組來減少單頁表單元素數量';
        } else {
            return '優化元件結構，減少不必要的複雜性';
        }
    }

    /**
     * 建議巢狀元件修復
     */
    protected function suggestNestedComponentFix(int $nestedCount): string
    {
        if ($nestedCount > 5) {
            return '過多的巢狀元件可能導致效能問題，考慮重構架構';
        } else {
            return '確保巢狀元件之間的狀態同步正確處理';
        }
    }
}