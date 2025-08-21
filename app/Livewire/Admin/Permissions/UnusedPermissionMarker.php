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
 * 未使用權限標記元件
 * 
 * 提供未使用權限的標記和管理功能
 */
class UnusedPermissionMarker extends Component
{
    use HandlesLivewireErrors;

    // 標記選項
    public int $daysThreshold = 90;
    public bool $excludeSystemPermissions = true;
    public bool $excludeWithDependents = true;
    public bool $autoMarkEnabled = false;
    
    // 顯示狀態
    public bool $showModal = false;
    public bool $showPreview = false;
    
    // 預覽資料
    public array $previewData = [];
    
    // 選中的權限
    public array $selectedPermissions = [];
    public bool $selectAll = false;

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
        if (!auth()->user()->hasPermission('permissions.manage')) {
            abort(403, __('admin.errors.unauthorized'));
        }
    }

    /**
     * 取得未使用權限預覽（計算屬性）
     */
    public function getUnusedPermissionsPreviewProperty(): Collection
    {
        try {
            $unusedPermissions = $this->usageAnalysisService->getUnusedPermissions();
            
            return $unusedPermissions->filter(function ($permission) {
                // 排除系統權限
                if ($this->excludeSystemPermissions && $permission['is_system_permission']) {
                    return false;
                }
                
                // 排除有被依賴的權限
                if ($this->excludeWithDependents && $permission['has_dependents']) {
                    return false;
                }
                
                // 檢查天數閾值
                $createdAt = \Carbon\Carbon::parse($permission['created_at']);
                $daysSinceCreated = $createdAt->diffInDays(now());
                
                return $daysSinceCreated >= $this->daysThreshold;
            });
        } catch (\Exception $e) {
            logger()->error('Error getting unused permissions preview', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * 開啟標記模態框
     */
    #[On('open-unused-permission-marker')]
    public function openModal(): void
    {
        $this->showModal = true;
        $this->generatePreview();
    }

    /**
     * 關閉模態框
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->showPreview = false;
        $this->selectedPermissions = [];
        $this->selectAll = false;
        $this->previewData = [];
    }

    /**
     * 生成預覽資料
     */
    public function generatePreview(): void
    {
        try {
            $options = [
                'days_threshold' => $this->daysThreshold,
                'exclude_system' => $this->excludeSystemPermissions,
            ];
            
            $result = $this->usageAnalysisService->markUnusedPermissions($options);
            
            $this->previewData = $result;
            $this->showPreview = true;
            
            // 記錄預覽操作
            $this->auditService->logDataAccess('permissions', 'unused_permissions_preview_generated', [
                'options' => $options,
                'preview_count' => $result['marked_unused'],
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '生成預覽時發生錯誤'
            ]);
        }
    }

    /**
     * 重新生成預覽
     */
    public function refreshPreview(): void
    {
        $this->generatePreview();
    }

    /**
     * 執行標記操作
     */
    public function executeMarking(): void
    {
        try {
            if (empty($this->selectedPermissions) && !$this->selectAll) {
                $this->dispatch('show-toast', [
                    'type' => 'warning',
                    'message' => '請選擇要標記的權限'
                ]);
                return;
            }

            $permissionsToMark = $this->selectAll ? 
                                collect($this->previewData['marked_permissions'])->pluck('id')->toArray() :
                                $this->selectedPermissions;

            // 執行標記
            $this->markSelectedPermissions($permissionsToMark);
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "已成功標記 " . count($permissionsToMark) . " 個未使用權限"
            ]);
            
            // 記錄標記操作
            $this->auditService->logDataAccess('permissions', 'unused_permissions_marked', [
                'marked_count' => count($permissionsToMark),
                'permission_ids' => $permissionsToMark,
                'options' => [
                    'days_threshold' => $this->daysThreshold,
                    'exclude_system' => $this->excludeSystemPermissions,
                    'exclude_with_dependents' => $this->excludeWithDependents,
                ],
            ]);
            
            // 通知父元件重新載入
            $this->dispatch('unused-permissions-marked');
            
            $this->closeModal();
            
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '標記權限時發生錯誤'
            ]);
        }
    }

    /**
     * 切換全選狀態
     */
    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedPermissions = collect($this->previewData['marked_permissions'])->pluck('id')->toArray();
        } else {
            $this->selectedPermissions = [];
        }
    }

    /**
     * 切換單個權限選擇
     */
    public function togglePermissionSelection(int $permissionId): void
    {
        if (in_array($permissionId, $this->selectedPermissions)) {
            $this->selectedPermissions = array_diff($this->selectedPermissions, [$permissionId]);
        } else {
            $this->selectedPermissions[] = $permissionId;
        }

        // 更新全選狀態
        $totalPermissions = count($this->previewData['marked_permissions'] ?? []);
        $this->selectAll = count($this->selectedPermissions) === $totalPermissions;
    }

    /**
     * 更新天數閾值
     */
    public function updatedDaysThreshold(): void
    {
        // 確保天數在合理範圍內
        $this->daysThreshold = max(1, min(365, $this->daysThreshold));
        
        // 如果已經顯示預覽，重新生成
        if ($this->showPreview) {
            $this->generatePreview();
        }
    }

    /**
     * 更新排除選項
     */
    public function updatedExcludeSystemPermissions(): void
    {
        if ($this->showPreview) {
            $this->generatePreview();
        }
    }

    /**
     * 更新排除有被依賴的權限選項
     */
    public function updatedExcludeWithDependents(): void
    {
        if ($this->showPreview) {
            $this->generatePreview();
        }
    }

    /**
     * 取得權限風險等級
     */
    public function getPermissionRiskLevel(array $permission): array
    {
        $riskLevel = 'low';
        $riskReasons = [];
        
        // 檢查是否為系統權限
        if ($permission['is_system_permission']) {
            $riskLevel = 'high';
            $riskReasons[] = '系統權限';
        }
        
        // 檢查是否有被依賴
        if ($permission['has_dependents']) {
            $riskLevel = $riskLevel === 'high' ? 'high' : 'medium';
            $riskReasons[] = '被其他權限依賴';
        }
        
        // 檢查是否有依賴
        if ($permission['has_dependencies']) {
            $riskReasons[] = '依賴其他權限';
        }
        
        // 檢查創建時間
        $createdAt = \Carbon\Carbon::parse($permission['created_at']);
        $daysSinceCreated = $createdAt->diffInDays(now());
        
        if ($daysSinceCreated < 30) {
            $riskLevel = $riskLevel === 'high' ? 'high' : 'medium';
            $riskReasons[] = '最近創建';
        }
        
        return [
            'level' => $riskLevel,
            'reasons' => $riskReasons,
            'badge_class' => $this->getRiskBadgeClass($riskLevel),
            'badge_text' => $this->getRiskBadgeText($riskLevel),
        ];
    }

    /**
     * 取得風險等級徽章樣式
     */
    private function getRiskBadgeClass(string $level): string
    {
        switch ($level) {
            case 'high':
                return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            case 'medium':
                return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
            default:
                return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        }
    }

    /**
     * 取得風險等級徽章文字
     */
    private function getRiskBadgeText(string $level): string
    {
        switch ($level) {
            case 'high':
                return '高風險';
            case 'medium':
                return '中風險';
            default:
                return '低風險';
        }
    }

    /**
     * 標記選中的權限
     */
    private function markSelectedPermissions(array $permissionIds): void
    {
        // 這裡可以實作實際的標記邏輯
        // 例如：更新資料庫欄位、添加標籤等
        
        // 目前將標記資訊存入快取
        $existingMarked = cache()->get('marked_unused_permissions', []);
        $newMarked = array_unique(array_merge($existingMarked, $permissionIds));
        
        cache()->put('marked_unused_permissions', $newMarked, now()->addDays(30));
        
        // 清除相關快取
        $this->usageAnalysisService->clearUsageCache();
    }

    /**
     * 檢查權限是否已被標記
     */
    public function isPermissionMarked(int $permissionId): bool
    {
        $markedPermissions = cache()->get('marked_unused_permissions', []);
        return in_array($permissionId, $markedPermissions);
    }

    /**
     * 取消標記權限
     */
    public function unmarkPermission(int $permissionId): void
    {
        try {
            $markedPermissions = cache()->get('marked_unused_permissions', []);
            $markedPermissions = array_diff($markedPermissions, [$permissionId]);
            
            cache()->put('marked_unused_permissions', $markedPermissions, now()->addDays(30));
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '已取消標記'
            ]);
            
            // 記錄取消標記操作
            $this->auditService->logDataAccess('permissions', 'unused_permission_unmarked', [
                'permission_id' => $permissionId,
            ]);
            
            // 通知父元件重新載入
            $this->dispatch('unused-permissions-updated');
            
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '取消標記時發生錯誤'
            ]);
        }
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.permissions.unused-permission-marker', [
            'unusedPermissionsPreview' => $this->unusedPermissionsPreview,
        ]);
    }
}