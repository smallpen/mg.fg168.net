<?php

namespace App\Livewire\Admin\Roles;

use App\Livewire\Admin\AdminComponent;
use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Services\RoleHierarchyService;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

/**
 * 角色層級管理元件
 * 
 * 提供角色層級的樹狀顯示和管理功能
 */
class RoleHierarchy extends AdminComponent
{
    // 顯示控制屬性
    public bool $showInactive = false;
    public bool $showSystemRoles = true;
    public string $expandedNodes = ''; // JSON string of expanded node IDs
    
    // 拖拽操作屬性
    public ?int $draggedRoleId = null;
    public ?int $targetParentId = null;
    
    // 搜尋屬性
    public string $search = '';
    
    // 選中的角色
    public ?int $selectedRoleId = null;

    protected RoleRepositoryInterface $roleRepository;
    protected RoleHierarchyService $hierarchyService;

    /**
     * 元件初始化
     */
    public function boot(
        RoleRepositoryInterface $roleRepository,
        RoleHierarchyService $hierarchyService
    ): void {
        $this->roleRepository = $roleRepository;
        $this->hierarchyService = $hierarchyService;
    }

    /**
     * 元件掛載
     */
    public function mount(): void
    {
        $this->checkPermission('roles.view');
        $this->expandedNodes = json_encode([]);
    }

    /**
     * 取得角色層級樹狀結構（計算屬性）
     */
    public function getHierarchyTreeProperty(): Collection
    {
        return $this->hierarchyService->getHierarchyTree([
            'include_inactive' => $this->showInactive,
            'include_system_roles' => $this->showSystemRoles,
            'search' => $this->search
        ]);
    }

    /**
     * 取得展開的節點 ID 陣列
     */
    public function getExpandedNodesArrayProperty(): array
    {
        return json_decode($this->expandedNodes, true) ?: [];
    }

    /**
     * 取得角色層級統計資訊
     */
    public function getHierarchyStatsProperty(): array
    {
        return $this->hierarchyService->getHierarchyStats();
    }



    /**
     * 切換節點展開/收合狀態
     */
    public function toggleNode(int $roleId): void
    {
        $expandedNodes = $this->expandedNodesArray;
        
        if (in_array($roleId, $expandedNodes)) {
            // 收合節點
            $expandedNodes = array_diff($expandedNodes, [$roleId]);
        } else {
            // 展開節點
            $expandedNodes[] = $roleId;
        }
        
        $this->expandedNodes = json_encode(array_values($expandedNodes));
    }

    /**
     * 展開所有節點
     */
    public function expandAll(): void
    {
        $allRoleIds = Role::pluck('id')->toArray();
        $this->expandedNodes = json_encode($allRoleIds);
    }

    /**
     * 收合所有節點
     */
    public function collapseAll(): void
    {
        $this->expandedNodes = json_encode([]);
    }

    /**
     * 選擇角色
     */
    public function selectRole(int $roleId): void
    {
        $this->selectedRoleId = $roleId;
        $this->dispatch('role-selected', roleId: $roleId);
    }

    /**
     * 切換顯示非啟用角色
     */
    public function toggleShowInactive(): void
    {
        $this->showInactive = !$this->showInactive;
    }

    /**
     * 切換顯示系統角色
     */
    public function toggleShowSystemRoles(): void
    {
        $this->showSystemRoles = !$this->showSystemRoles;
    }

    /**
     * 搜尋更新時的處理
     */
    public function updatedSearch(): void
    {
        // 搜尋時自動展開所有節點以顯示結果
        if (!empty($this->search)) {
            $this->expandAll();
        }
    }

    /**
     * 移動角色到新的父角色下
     */
    public function moveRole(int $roleId, ?int $newParentId): void
    {
        $this->checkPermission('roles.edit');
        
        try {
            $role = $this->roleRepository->findOrFail($roleId);
            
            // 使用服務處理移動邏輯
            $this->hierarchyService->updateRoleParent($role, $newParentId);
            
            $parentName = $newParentId ? 
                $this->roleRepository->findOrFail($newParentId)->display_name : 
                '根層級';
                
            $this->dispatch('role-moved', [
                'message' => "角色「{$role->display_name}」已移動到「{$parentName}」下",
                'roleId' => $roleId,
                'newParentId' => $newParentId
            ]);
            
        } catch (\Exception $e) {
            $this->addError('move', '移動失敗：' . $e->getMessage());
        }
    }

    /**
     * 驗證層級結構完整性
     */
    public function validateHierarchy(): void
    {
        $this->checkPermission('roles.edit');
        
        try {
            $validation = $this->hierarchyService->validateHierarchyIntegrity();
            
            if ($validation['is_valid']) {
                $this->dispatch('hierarchy-validation-success', [
                    'message' => '角色層級結構完整性驗證通過'
                ]);
            } else {
                $this->dispatch('hierarchy-validation-issues', [
                    'issues' => $validation['issues'],
                    'total_issues' => $validation['total_issues']
                ]);
            }
            
        } catch (\Exception $e) {
            $this->addError('validation', '驗證失敗：' . $e->getMessage());
        }
    }

    /**
     * 修復孤立角色
     */
    public function fixOrphanedRoles(): void
    {
        $this->checkPermission('roles.edit');
        
        try {
            $fixedCount = $this->hierarchyService->fixOrphanedRoles();
            
            if ($fixedCount > 0) {
                $this->dispatch('orphaned-roles-fixed', [
                    'message' => "已修復 {$fixedCount} 個孤立角色",
                    'count' => $fixedCount
                ]);
            } else {
                $this->dispatch('no-orphaned-roles', [
                    'message' => '沒有發現孤立角色'
                ]);
            }
            
        } catch (\Exception $e) {
            $this->addError('fix_orphaned', '修復失敗：' . $e->getMessage());
        }
    }

    /**
     * 批量移動角色
     */
    public function bulkMoveRoles(array $roleIds, ?int $newParentId): void
    {
        $this->checkPermission('roles.edit');
        
        try {
            $results = $this->hierarchyService->bulkMoveRoles($roleIds, $newParentId);
            
            if ($results['success_count'] > 0) {
                $parentName = $newParentId ? 
                    $this->roleRepository->findOrFail($newParentId)->display_name : 
                    '根層級';
                    
                $this->dispatch('roles-bulk-moved', [
                    'message' => "已成功移動 {$results['success_count']} 個角色到「{$parentName}」下",
                    'count' => $results['success_count']
                ]);
            }
            
            if ($results['error_count'] > 0) {
                foreach ($results['errors'] as $error) {
                    $this->addError('bulk_move', $error['error']);
                }
            }
            
        } catch (\Exception $e) {
            $this->addError('bulk_move', '批量移動失敗：' . $e->getMessage());
        }
    }

    /**
     * 建立子角色
     */
    public function createChildRole(int $parentId): void
    {
        $this->checkPermission('roles.create');
        
        $parent = $this->roleRepository->findOrFail($parentId);
        
        $this->dispatch('create-child-role', [
            'parentId' => $parentId,
            'parentName' => $parent->display_name
        ]);
    }

    /**
     * 編輯角色
     */
    public function editRole(int $roleId): void
    {
        $this->checkPermission('roles.edit');
        $this->dispatch('edit-role-from-hierarchy', roleId: $roleId);
    }

    /**
     * 刪除角色
     */
    public function deleteRole(int $roleId): void
    {
        $this->checkPermission('roles.delete');
        
        $role = $this->roleRepository->findOrFail($roleId);
        
        if (!$this->roleRepository->canDeleteRole($role)) {
            $this->addError('delete', "角色「{$role->display_name}」無法刪除");
            return;
        }
        
        $this->dispatch('confirm-delete-from-hierarchy', roleId: $roleId);
    }

    /**
     * 確認刪除角色
     */
    #[On('confirmed-delete-from-hierarchy')]
    public function confirmedDelete(int $roleId): void
    {
        try {
            $role = $this->roleRepository->findOrFail($roleId);
            $roleName = $role->display_name;
            
            $this->roleRepository->delete($role);
            
            $this->dispatch('role-deleted-from-hierarchy', [
                'message' => "角色「{$roleName}」已成功刪除",
                'roleId' => $roleId
            ]);
            
        } catch (\Exception $e) {
            $this->addError('delete', '刪除失敗：' . $e->getMessage());
        }
    }

    /**
     * 複製角色
     */
    public function duplicateRole(int $roleId): void
    {
        $this->checkPermission('roles.create');
        
        try {
            $role = $this->roleRepository->findOrFail($roleId);
            $newName = $role->name . '_copy_' . time();
            
            $duplicatedRole = $this->roleRepository->duplicateRole($role, $newName);
            
            $this->dispatch('role-duplicated-from-hierarchy', [
                'message' => "角色「{$role->display_name}」已成功複製",
                'originalRoleId' => $roleId,
                'newRoleId' => $duplicatedRole->id
            ]);
            
        } catch (\Exception $e) {
            $this->addError('duplicate', '複製失敗：' . $e->getMessage());
        }
    }

    /**
     * 檢查角色是否展開
     */
    public function isNodeExpanded(int $roleId): bool
    {
        return in_array($roleId, $this->expandedNodesArray);
    }

    /**
     * 取得角色的完整路徑
     */
    public function getRolePath(Role $role): string
    {
        return $this->roleRepository->getRolePath($role);
    }

    /**
     * 取得角色的層級深度
     */
    public function getRoleDepth(Role $role): int
    {
        return $role->getDepth();
    }

    /**
     * 檢查角色是否有子角色
     */
    public function hasChildren(Role $role): bool
    {
        return $role->children->isNotEmpty();
    }

    /**
     * 取得角色的子角色數量
     */
    public function getChildrenCount(Role $role): int
    {
        return $role->children->count();
    }

    /**
     * 取得角色的權限繼承資訊
     */
    public function getPermissionInheritanceInfo(Role $role): array
    {
        $directPermissions = $role->getDirectPermissions();
        $inheritedPermissions = $role->getInheritedPermissions();
        $allPermissions = $role->getAllPermissions();
        
        return [
            'direct_count' => $directPermissions->count(),
            'inherited_count' => $inheritedPermissions->count(),
            'total_count' => $allPermissions->count(),
            'inheritance_ratio' => $allPermissions->count() > 0 ? 
                round(($inheritedPermissions->count() / $allPermissions->count()) * 100, 1) : 0
        ];
    }

    /**
     * 監聽角色更新事件
     */
    #[On('role-updated')]
    public function handleRoleUpdated(): void
    {
        // 重新載入資料
        $this->dispatch('$refresh');
    }

    /**
     * 監聽角色建立事件
     */
    #[On('role-created')]
    public function handleRoleCreated(): void
    {
        // 重新載入資料
        $this->dispatch('$refresh');
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.roles.role-hierarchy');
    }
}