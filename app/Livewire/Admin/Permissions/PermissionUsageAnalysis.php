<?php

namespace App\Livewire\Admin\Permissions;

use App\Models\Permission;
use App\Services\PermissionUsageAnalysisService;
use App\Services\AuditLogService;
use App\Traits\HandlesLivewireErrors;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Collection;

/**
 * 權限使用情況分析 Livewire 元件
 * 
 * 提供權限使用統計、分析和視覺化功能
 */
class PermissionUsageAnalysis extends Component
{
    use HandlesLivewireErrors;

    // 分析模式
    public string $analysisMode = 'overview'; // overview, detailed, trends, heatmap
    
    // 選中的權限
    public ?int $selectedPermissionId = null;
    
    // 篩選選項
    public string $moduleFilter = 'all';
    public string $usageFilter = 'all'; // all, used, unused, low_usage
    public int $daysRange = 30;
    
    // 顯示選項
    public bool $showUnusedOnly = false;
    public bool $excludeSystemPermissions = true;
    public bool $showTrends = true;
    
    // 標記選項
    public array $markingOptions = [
        'days_threshold' => 90,
        'exclude_system' => true,
    ];

    protected PermissionUsageAnalysisService $usageAnalysisService;
    protected AuditLogService $auditService;

    /**
     * 元件初始化
     */
    public function boot(
        PermissionUsageAnalysisService $usageAnalysisService,
        AuditLogService $auditService
    ): void {
        $this->usageAnalysisService = $usageAnalysisService;
        $this->auditService = $auditService;
    }

    /**
     * 元件掛載
     */
    public function mount(): void
    {
        // 檢查權限
        if (!auth()->user()->hasPermission('permissions.view')) {
            abort(403, __('admin.errors.unauthorized'));
        }

        // 記錄存取日誌
        $this->auditService->logDataAccess('permissions', 'usage_analysis_view');
    }

    /**
     * 取得整體使用統計（計算屬性）
     */
    public function getOverallStatsProperty(): array
    {
        try {
            return $this->usageAnalysisService->getUsageStats();
        } catch (\Exception $e) {
            logger()->error('Error getting overall stats', ['error' => $e->getMessage()]);
            return [
                'total_permissions' => 0,
                'used_permissions' => 0,
                'unused_permissions' => 0,
                'usage_percentage' => 0,
            ];
        }
    }

    /**
     * 取得模組使用統計（計算屬性）
     */
    public function getModuleStatsProperty(): array
    {
        try {
            $stats = $this->usageAnalysisService->getModuleUsageStats();
            
            // 根據模組篩選
            if ($this->moduleFilter !== 'all') {
                $stats = array_filter($stats, function ($stat) {
                    return $stat['module'] === $this->moduleFilter;
                });
            }
            
            return array_values($stats);
        } catch (\Exception $e) {
            logger()->error('Error getting module stats', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * 取得未使用權限列表（計算屬性）
     */
    public function getUnusedPermissionsProperty(): Collection
    {
        try {
            $unusedPermissions = $this->usageAnalysisService->getUnusedPermissions();
            
            // 根據篩選條件過濾
            return $unusedPermissions->filter(function ($permission) {
                // 模組篩選
                if ($this->moduleFilter !== 'all' && $permission['module'] !== $this->moduleFilter) {
                    return false;
                }
                
                // 排除系統權限
                if ($this->excludeSystemPermissions && $permission['is_system_permission']) {
                    return false;
                }
                
                return true;
            });
        } catch (\Exception $e) {
            logger()->error('Error getting unused permissions', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * 取得使用熱力圖資料（計算屬性）
     */
    public function getHeatmapDataProperty(): array
    {
        try {
            $data = $this->usageAnalysisService->getUsageHeatmapData();
            
            // 根據篩選條件過濾
            if ($this->moduleFilter !== 'all') {
                $data = array_filter($data, function ($item) {
                    return $item['module'] === $this->moduleFilter;
                });
            }
            
            if ($this->usageFilter !== 'all') {
                $data = array_filter($data, function ($item) {
                    switch ($this->usageFilter) {
                        case 'used':
                            return $item['user_count'] > 0;
                        case 'unused':
                            return $item['user_count'] === 0;
                        case 'low_usage':
                            return $item['user_count'] > 0 && $item['user_count'] <= 5;
                        default:
                            return true;
                    }
                });
            }
            
            return array_values($data);
        } catch (\Exception $e) {
            logger()->error('Error getting heatmap data', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * 取得選中權限的詳細統計（計算屬性）
     */
    public function getSelectedPermissionStatsProperty(): ?array
    {
        if (!$this->selectedPermissionId) {
            return null;
        }

        try {
            return $this->usageAnalysisService->getUsageStats($this->selectedPermissionId);
        } catch (\Exception $e) {
            logger()->error('Error getting selected permission stats', [
                'permission_id' => $this->selectedPermissionId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 取得選中權限的使用趨勢（計算屬性）
     */
    public function getSelectedPermissionTrendProperty(): ?array
    {
        if (!$this->selectedPermissionId || !$this->showTrends) {
            return null;
        }

        try {
            return $this->usageAnalysisService->getUsageTrend($this->selectedPermissionId, $this->daysRange);
        } catch (\Exception $e) {
            logger()->error('Error getting permission trend', [
                'permission_id' => $this->selectedPermissionId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 取得可用模組列表（計算屬性）
     */
    public function getAvailableModulesProperty(): Collection
    {
        try {
            return Permission::distinct()->orderBy('module')->pluck('module');
        } catch (\Exception $e) {
            logger()->error('Error getting available modules', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * 切換分析模式
     */
    public function setAnalysisMode(string $mode): void
    {
        $validModes = ['overview', 'detailed', 'trends', 'heatmap'];
        
        if (in_array($mode, $validModes)) {
            $this->analysisMode = $mode;
            
            // 記錄模式切換
            $this->auditService->logDataAccess('permissions', 'analysis_mode_changed', [
                'mode' => $mode,
            ]);
        }
    }

    /**
     * 選擇權限進行詳細分析
     */
    public function selectPermission(int $permissionId): void
    {
        try {
            $permission = Permission::find($permissionId);
            if (!$permission) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '權限不存在'
                ]);
                return;
            }

            $this->selectedPermissionId = $permissionId;
            $this->analysisMode = 'detailed';
            
            // 記錄權限選擇
            $this->auditService->logDataAccess('permissions', 'detailed_analysis', [
                'permission_id' => $permissionId,
                'permission_name' => $permission->name,
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '選擇權限時發生錯誤'
            ]);
        }
    }

    /**
     * 清除權限選擇
     */
    public function clearSelection(): void
    {
        $this->selectedPermissionId = null;
        $this->analysisMode = 'overview';
    }

    /**
     * 標記未使用權限
     */
    public function markUnusedPermissions(): void
    {
        try {
            // 檢查權限
            if (!auth()->user()->hasPermission('permissions.manage')) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '您沒有權限執行此操作'
                ]);
                return;
            }

            $result = $this->usageAnalysisService->markUnusedPermissions($this->markingOptions);
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "已標記 {$result['marked_unused']} 個未使用權限"
            ]);
            
            // 記錄標記操作
            $this->auditService->logUserAction('permissions_marked_unused', [
                'total_unused' => $result['total_unused'],
                'marked_unused' => $result['marked_unused'],
                'options' => $this->markingOptions,
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '標記未使用權限時發生錯誤'
            ]);
        }
    }

    /**
     * 重新整理分析資料
     */
    public function refreshAnalysis(): void
    {
        try {
            // 清除快取
            $this->usageAnalysisService->clearUsageCache();
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '分析資料已重新整理'
            ]);
            
            // 記錄重新整理操作
            $this->auditService->logDataAccess('permissions', 'analysis_refreshed');
            
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '重新整理分析資料時發生錯誤'
            ]);
        }
    }

    /**
     * 匯出分析報告
     */
    public function exportAnalysisReport(): void
    {
        try {
            // 檢查權限
            if (!auth()->user()->hasPermission('permissions.export')) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '您沒有匯出權限'
                ]);
                return;
            }

            // 記錄匯出操作
            $this->auditService->logDataAccess('permissions', 'analysis_export', [
                'analysis_mode' => $this->analysisMode,
                'filters' => [
                    'module' => $this->moduleFilter,
                    'usage' => $this->usageFilter,
                    'days_range' => $this->daysRange,
                ],
            ]);
            
            $this->dispatch('export-analysis-report', [
                'mode' => $this->analysisMode,
                'filters' => [
                    'module' => $this->moduleFilter,
                    'usage' => $this->usageFilter,
                    'days_range' => $this->daysRange,
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '匯出分析報告時發生錯誤'
            ]);
        }
    }

    /**
     * 更新篩選條件時重新載入資料
     */
    public function updatedModuleFilter(): void
    {
        // 篩選條件變更時可以添加額外邏輯
    }

    /**
     * 更新使用狀態篩選
     */
    public function updatedUsageFilter(): void
    {
        // 篩選條件變更時可以添加額外邏輯
    }

    /**
     * 更新天數範圍
     */
    public function updatedDaysRange(): void
    {
        // 確保天數範圍在合理範圍內
        $this->daysRange = max(1, min(365, $this->daysRange));
    }

    /**
     * 取得使用狀態的本地化顯示
     */
    public function getUsageStatusBadge(array $stats): array
    {
        if (!$stats['is_used']) {
            return [
                'text' => '未使用',
                'class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
            ];
        }

        $frequency = $stats['usage_frequency'] ?? 0;
        
        if ($frequency >= 20) {
            return [
                'text' => '高頻使用',
                'class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
            ];
        } elseif ($frequency >= 10) {
            return [
                'text' => '中頻使用',
                'class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
            ];
        } else {
            return [
                'text' => '低頻使用',
                'class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'
            ];
        }
    }

    /**
     * 取得趨勢方向的圖示
     */
    public function getTrendIcon(string $direction): string
    {
        switch ($direction) {
            case 'increasing':
                return '↗️';
            case 'decreasing':
                return '↘️';
            default:
                return '➡️';
        }
    }

    /**
     * 取得頻率等級的本地化顯示
     */
    public function getFrequencyLevelText(string $level): string
    {
        $levels = [
            'very_high' => '非常高',
            'high' => '高',
            'medium' => '中等',
            'low' => '低',
            'very_low' => '非常低',
        ];

        return $levels[$level] ?? $level;
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        $data = [
            'overallStats' => $this->overallStats,
            'moduleStats' => $this->moduleStats,
            'availableModules' => $this->availableModules,
        ];

        // 根據分析模式載入不同資料
        switch ($this->analysisMode) {
            case 'detailed':
                $data['selectedPermissionStats'] = $this->selectedPermissionStats;
                $data['selectedPermissionTrend'] = $this->selectedPermissionTrend;
                break;
            case 'heatmap':
                $data['heatmapData'] = $this->heatmapData;
                break;
            default:
                $data['unusedPermissions'] = $this->unusedPermissions;
                break;
        }

        return view('livewire.admin.permissions.permission-usage-analysis', $data);
    }
}