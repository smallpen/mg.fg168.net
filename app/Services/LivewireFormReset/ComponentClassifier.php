<?php

namespace App\Services\LivewireFormReset;

use Illuminate\Support\Collection;

/**
 * 元件分類器
 * 
 * 根據元件特徵和使用模式對 Livewire 元件進行分類，
 * 並確定修復優先級和策略
 */
class ComponentClassifier
{
    /**
     * 元件類型定義
     */
    const COMPONENT_TYPES = [
        'LIST_FILTER' => [
            'name' => '列表篩選器',
            'description' => '用於資料列表的搜尋和篩選功能',
            'priority_weight' => 9,
            'common_patterns' => ['List', 'Filter', 'Search'],
            'typical_methods' => ['resetFilters', 'clearFilters', 'updatedSearch'],
            'fix_strategy' => 'ListFilterResetFix',
        ],
        'FORM_MODAL' => [
            'name' => '表單模態',
            'description' => '模態對話框中的表單元件',
            'priority_weight' => 8,
            'common_patterns' => ['Modal', 'Form', 'Dialog'],
            'typical_methods' => ['resetForm', 'closeModal', 'showModal'],
            'fix_strategy' => 'ModalFormResetFix',
        ],
        'SETTINGS_FORM' => [
            'name' => '設定表單',
            'description' => '系統設定和配置表單',
            'priority_weight' => 7,
            'common_patterns' => ['Settings', 'Config', 'Preference'],
            'typical_methods' => ['resetForm', 'saveSettings', 'resetToDefault'],
            'fix_strategy' => 'StandardFormResetFix',
        ],
        'MONITORING_CONTROL' => [
            'name' => '監控控制',
            'description' => '系統監控和效能控制元件',
            'priority_weight' => 6,
            'common_patterns' => ['Monitor', 'Performance', 'Stats', 'Chart'],
            'typical_methods' => ['resetFilters', 'refreshData', 'updateInterval'],
            'fix_strategy' => 'MonitoringControlFix',
        ],
        'USER_MANAGEMENT' => [
            'name' => '使用者管理',
            'description' => '使用者相關的管理功能',
            'priority_weight' => 10,
            'common_patterns' => ['User', 'Profile', 'Account'],
            'typical_methods' => ['resetForm', 'resetFilters', 'updateProfile'],
            'fix_strategy' => 'StandardFormResetFix',
        ],
        'PERMISSION_CONTROL' => [
            'name' => '權限控制',
            'description' => '權限和角色管理元件',
            'priority_weight' => 9,
            'common_patterns' => ['Permission', 'Role', 'Access'],
            'typical_methods' => ['resetForm', 'resetFilters', 'assignRole'],
            'fix_strategy' => 'StandardFormResetFix',
        ],
        'ACTIVITY_LOG' => [
            'name' => '活動日誌',
            'description' => '系統活動和審計日誌',
            'priority_weight' => 5,
            'common_patterns' => ['Activity', 'Log', 'Audit', 'History'],
            'typical_methods' => ['resetFilters', 'clearFilters', 'exportLogs'],
            'fix_strategy' => 'ListFilterResetFix',
        ],
        'AUTHENTICATION' => [
            'name' => '身份驗證',
            'description' => '登入、登出和身份驗證相關',
            'priority_weight' => 10,
            'common_patterns' => ['Login', 'Auth', 'Logout'],
            'typical_methods' => ['resetForm', 'login', 'logout'],
            'fix_strategy' => 'StandardFormResetFix',
        ],
        'DASHBOARD' => [
            'name' => '儀表板',
            'description' => '儀表板和統計顯示元件',
            'priority_weight' => 4,
            'common_patterns' => ['Dashboard', 'Stats', 'Overview'],
            'typical_methods' => ['refreshData', 'resetFilters'],
            'fix_strategy' => 'StandardFormResetFix',
        ],
        'GENERIC' => [
            'name' => '通用元件',
            'description' => '無法明確分類的通用元件',
            'priority_weight' => 3,
            'common_patterns' => [],
            'typical_methods' => [],
            'fix_strategy' => 'StandardFormResetFix',
        ],
    ];

    /**
     * 優先級權重定義
     */
    const PRIORITY_WEIGHTS = [
        'usage_frequency' => 0.4,    // 使用頻率權重
        'component_type' => 0.3,     // 元件類型權重
        'complexity' => 0.2,         // 複雜度權重
        'issue_severity' => 0.1,     // 問題嚴重程度權重
    ];

    /**
     * 分類單個元件
     */
    public function classifyComponent(array $componentInfo): array
    {
        $classification = [
            'component_type' => $this->determineComponentType($componentInfo),
            'priority_score' => 0,
            'usage_frequency' => $this->estimateUsageFrequency($componentInfo),
            'complexity_level' => $this->determineComplexityLevel($componentInfo),
            'fix_strategy' => '',
            'estimated_fix_time' => 0,
            'dependencies' => $this->identifyDependencies($componentInfo),
        ];

        // 計算優先級分數
        $classification['priority_score'] = $this->calculatePriorityScore($componentInfo, $classification);
        
        // 確定修復策略
        $classification['fix_strategy'] = $this->getFixStrategy($classification['component_type']);
        
        // 估算修復時間
        $classification['estimated_fix_time'] = $this->estimateFixTime($componentInfo, $classification);

        return $classification;
    }

    /**
     * 批量分類元件
     */
    public function classifyComponents(Collection $components): Collection
    {
        return $components->map(function ($componentInfo) {
            $classification = $this->classifyComponent($componentInfo);
            return array_merge($componentInfo, ['classification' => $classification]);
        })->sortByDesc('classification.priority_score');
    }

    /**
     * 確定元件類型
     */
    public function determineComponentType(array $componentInfo): string
    {
        $className = $componentInfo['class_name'] ?? '';
        $namespace = $componentInfo['namespace'] ?? '';
        $path = $componentInfo['relative_path'] ?? '';
        $methods = array_column($componentInfo['methods'] ?? [], 'name');

        // 基於類別名稱和路徑的模式匹配
        foreach (self::COMPONENT_TYPES as $type => $config) {
            $patterns = $config['common_patterns'];
            $typicalMethods = $config['typical_methods'];

            // 檢查類別名稱模式
            foreach ($patterns as $pattern) {
                if (stripos($className, $pattern) !== false || 
                    stripos($path, $pattern) !== false) {
                    
                    // 進一步驗證：檢查是否有典型方法
                    $methodMatches = array_intersect($methods, $typicalMethods);
                    if (!empty($methodMatches) || empty($typicalMethods)) {
                        return $type;
                    }
                }
            }
        }

        // 基於命名空間的特殊判斷
        if (stripos($namespace, 'Auth') !== false) {
            return 'AUTHENTICATION';
        }
        
        if (stripos($namespace, 'Dashboard') !== false) {
            return 'DASHBOARD';
        }

        // 基於方法名稱的推斷
        if (in_array('resetFilters', $methods) || in_array('clearFilters', $methods)) {
            if (stripos($className, 'List') !== false) {
                return 'LIST_FILTER';
            }
        }

        if (in_array('resetForm', $methods)) {
            if (stripos($className, 'Modal') !== false) {
                return 'FORM_MODAL';
            }
            if (stripos($className, 'Settings') !== false) {
                return 'SETTINGS_FORM';
            }
        }

        return 'GENERIC';
    }

    /**
     * 估算使用頻率
     */
    public function estimateUsageFrequency(array $componentInfo): int
    {
        $score = 0;
        $componentType = $this->determineComponentType($componentInfo);
        $path = $componentInfo['relative_path'] ?? '';

        // 基於元件類型的基礎分數
        $baseScores = [
            'AUTHENTICATION' => 10,
            'USER_MANAGEMENT' => 9,
            'PERMISSION_CONTROL' => 8,
            'LIST_FILTER' => 8,
            'DASHBOARD' => 7,
            'FORM_MODAL' => 6,
            'SETTINGS_FORM' => 5,
            'ACTIVITY_LOG' => 4,
            'MONITORING_CONTROL' => 4,
            'GENERIC' => 3,
        ];

        $score += $baseScores[$componentType] ?? 3;

        // 基於路徑的調整
        if (stripos($path, 'Admin') !== false) {
            $score += 2; // 管理功能通常使用頻率較高
        }

        if (stripos($path, 'Auth') !== false) {
            $score += 3; // 認證功能使用頻率最高
        }

        // 基於複雜度的調整（複雜元件通常使用頻率較高）
        $complexityScore = $componentInfo['complexity_score'] ?? 0;
        if ($complexityScore > 50) {
            $score += 2;
        } elseif ($complexityScore > 100) {
            $score += 3;
        }

        return min($score, 10); // 限制在 1-10 範圍內
    }

    /**
     * 確定複雜度等級
     */
    public function determineComplexityLevel(array $componentInfo): string
    {
        $complexityScore = $componentInfo['complexity_score'] ?? 0;
        $methodCount = count($componentInfo['methods'] ?? []);
        $propertyCount = count($componentInfo['properties'] ?? []);
        $formElementCount = count($componentInfo['view_form_elements'] ?? []);

        $totalComplexity = $complexityScore + ($methodCount * 2) + $propertyCount + ($formElementCount * 3);

        if ($totalComplexity >= 100) {
            return 'very_high';
        } elseif ($totalComplexity >= 70) {
            return 'high';
        } elseif ($totalComplexity >= 40) {
            return 'medium';
        } elseif ($totalComplexity >= 20) {
            return 'low';
        } else {
            return 'very_low';
        }
    }

    /**
     * 計算優先級分數
     */
    public function calculatePriorityScore(array $componentInfo, array $classification): float
    {
        $score = 0;

        // 使用頻率分數 (0-10)
        $usageScore = $classification['usage_frequency'];
        $score += $usageScore * self::PRIORITY_WEIGHTS['usage_frequency'];

        // 元件類型分數 (0-10)
        $componentType = $classification['component_type'];
        $typeScore = self::COMPONENT_TYPES[$componentType]['priority_weight'] ?? 3;
        $score += $typeScore * self::PRIORITY_WEIGHTS['component_type'];

        // 複雜度分數 (0-10)
        $complexityLevel = $classification['complexity_level'];
        $complexityScore = match ($complexityLevel) {
            'very_high' => 10,
            'high' => 8,
            'medium' => 6,
            'low' => 4,
            'very_low' => 2,
            default => 3,
        };
        $score += $complexityScore * self::PRIORITY_WEIGHTS['complexity'];

        // 問題嚴重程度分數 (基於是否有重置功能)
        $hasResetFunctionality = $componentInfo['has_reset_functionality'] ?? false;
        $issueScore = $hasResetFunctionality ? 5 : 10; // 沒有重置功能的優先級更高
        $score += $issueScore * self::PRIORITY_WEIGHTS['issue_severity'];

        return round($score, 2);
    }

    /**
     * 取得修復策略
     */
    public function getFixStrategy(string $componentType): string
    {
        return self::COMPONENT_TYPES[$componentType]['fix_strategy'] ?? 'StandardFormResetFix';
    }

    /**
     * 估算修復時間（分鐘）
     */
    public function estimateFixTime(array $componentInfo, array $classification): int
    {
        $baseTime = 30; // 基礎修復時間 30 分鐘

        // 基於複雜度調整
        $complexityMultiplier = match ($classification['complexity_level']) {
            'very_high' => 3.0,
            'high' => 2.5,
            'medium' => 2.0,
            'low' => 1.5,
            'very_low' => 1.0,
            default => 1.5,
        };

        $baseTime *= $complexityMultiplier;

        // 基於元件類型調整
        $typeMultiplier = match ($classification['component_type']) {
            'FORM_MODAL' => 1.5,
            'MONITORING_CONTROL' => 1.8,
            'PERMISSION_CONTROL' => 1.3,
            'LIST_FILTER' => 1.2,
            default => 1.0,
        };

        $baseTime *= $typeMultiplier;

        // 基於現有重置功能調整
        $hasResetFunctionality = $componentInfo['has_reset_functionality'] ?? false;
        if (!$hasResetFunctionality) {
            $baseTime *= 1.5; // 需要從頭實作
        }

        // 基於表單元素數量調整
        $formElementCount = count($componentInfo['view_form_elements'] ?? []);
        if ($formElementCount > 10) {
            $baseTime *= 1.3;
        } elseif ($formElementCount > 20) {
            $baseTime *= 1.6;
        }

        return (int) round($baseTime);
    }

    /**
     * 識別依賴關係
     */
    public function identifyDependencies(array $componentInfo): array
    {
        $dependencies = [];

        // 檢查視圖中的巢狀元件
        if (isset($componentInfo['view_path']) && file_exists($componentInfo['view_path'])) {
            $viewContent = file_get_contents($componentInfo['view_path']);
            
            // 尋找 Livewire 元件引用
            if (preg_match_all('/<livewire:([^>\s]+)/', $viewContent, $matches)) {
                foreach ($matches[1] as $componentName) {
                    $dependencies[] = [
                        'type' => 'livewire_component',
                        'name' => $componentName,
                        'relationship' => 'child',
                    ];
                }
            }

            // 尋找 @livewire 指令
            if (preg_match_all('/@livewire\s*\(\s*[\'"]([^\'"]+)[\'"]/', $viewContent, $matches)) {
                foreach ($matches[1] as $componentName) {
                    $dependencies[] = [
                        'type' => 'livewire_component',
                        'name' => $componentName,
                        'relationship' => 'child',
                    ];
                }
            }
        }

        // 檢查 PHP 檔案中的服務依賴
        if (isset($componentInfo['path']) && file_exists($componentInfo['path'])) {
            $phpContent = file_get_contents($componentInfo['path']);
            
            // 尋找注入的服務
            if (preg_match_all('/protected\s+([A-Z][a-zA-Z0-9_\\\\]+)\s+\$([a-zA-Z0-9_]+);/', $phpContent, $matches)) {
                foreach ($matches[1] as $index => $serviceClass) {
                    $dependencies[] = [
                        'type' => 'service',
                        'class' => $serviceClass,
                        'property' => $matches[2][$index],
                        'relationship' => 'dependency',
                    ];
                }
            }
        }

        return $dependencies;
    }

    /**
     * 取得元件類型資訊
     */
    public function getComponentTypeInfo(string $componentType): array
    {
        return self::COMPONENT_TYPES[$componentType] ?? self::COMPONENT_TYPES['GENERIC'];
    }

    /**
     * 取得所有支援的元件類型
     */
    public function getSupportedComponentTypes(): array
    {
        return array_keys(self::COMPONENT_TYPES);
    }

    /**
     * 產生分類報告
     */
    public function generateClassificationReport(Collection $classifiedComponents): array
    {
        $report = [
            'total_components' => $classifiedComponents->count(),
            'type_distribution' => [],
            'priority_distribution' => [],
            'complexity_distribution' => [],
            'estimated_total_time' => 0,
            'high_priority_components' => [],
        ];

        // 統計類型分佈
        $typeGroups = $classifiedComponents->groupBy('classification.component_type');
        foreach ($typeGroups as $type => $components) {
            $report['type_distribution'][$type] = [
                'count' => $components->count(),
                'percentage' => round(($components->count() / $report['total_components']) * 100, 1),
                'avg_priority' => round($components->avg('classification.priority_score'), 2),
            ];
        }

        // 統計優先級分佈
        $priorityRanges = [
            'very_high' => [8, 10],
            'high' => [6, 8],
            'medium' => [4, 6],
            'low' => [2, 4],
            'very_low' => [0, 2],
        ];

        foreach ($priorityRanges as $range => $bounds) {
            $count = $classifiedComponents->filter(function ($component) use ($bounds) {
                $score = $component['classification']['priority_score'];
                return $score >= $bounds[0] && $score < $bounds[1];
            })->count();

            $report['priority_distribution'][$range] = [
                'count' => $count,
                'percentage' => round(($count / $report['total_components']) * 100, 1),
            ];
        }

        // 統計複雜度分佈
        $complexityGroups = $classifiedComponents->groupBy('classification.complexity_level');
        foreach ($complexityGroups as $level => $components) {
            $report['complexity_distribution'][$level] = [
                'count' => $components->count(),
                'percentage' => round(($components->count() / $report['total_components']) * 100, 1),
            ];
        }

        // 計算總估算時間
        $report['estimated_total_time'] = $classifiedComponents->sum('classification.estimated_fix_time');

        // 高優先級元件（分數 >= 7）
        $report['high_priority_components'] = $classifiedComponents
            ->filter(fn($component) => $component['classification']['priority_score'] >= 7)
            ->map(fn($component) => [
                'name' => $component['class_name'],
                'type' => $component['classification']['component_type'],
                'priority_score' => $component['classification']['priority_score'],
                'estimated_time' => $component['classification']['estimated_fix_time'],
            ])
            ->values()
            ->toArray();

        return $report;
    }
}