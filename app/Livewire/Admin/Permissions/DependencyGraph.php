<?php

namespace App\Livewire\Admin\Permissions;

use App\Models\Permission;
use App\Models\PermissionDependency;
use App\Repositories\PermissionRepository;
use App\Services\AuditLogService;
use App\Services\PermissionValidationService;
use App\Traits\HandlesLivewireErrors;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * 權限依賴關係圖表 Livewire 元件
 * 
 * 提供權限依賴關係的視覺化顯示和管理功能
 */
class DependencyGraph extends Component
{
    use HandlesLivewireErrors;

    // 選中的權限
    public ?int $selectedPermissionId = null;
    public ?Permission $selectedPermission = null;
    
    // 圖表顯示模式
    public string $viewMode = 'tree'; // tree, network, list
    public string $direction = 'dependencies'; // dependencies, dependents, both
    
    // 篩選選項
    public string $moduleFilter = 'all';
    public string $typeFilter = 'all';
    public int $maxDepth = 3;
    
    // UI 狀態
    public bool $showAddDependency = false;
    public bool $isLoading = false;
    public array $expandedNodes = [];
    
    // 新增依賴相關
    public array $availablePermissions = [];
    public array $selectedDependencies = [];
    
    // 圖表資料
    public array $graphData = [];
    public array $dependencyTree = [];
    public array $dependentTree = [];
    
    // 依賴路徑
    public array $dependencyPaths = [];
    public array $dependentPaths = [];
    
    protected PermissionRepository $permissionRepository;
    protected PermissionValidationService $validationService;
    protected AuditLogService $auditService;

    /**
     * 元件初始化
     */
    public function boot(
        PermissionRepository $permissionRepository,
        PermissionValidationService $validationService,
        AuditLogService $auditService
    ): void {
        $this->permissionRepository = $permissionRepository;
        $this->validationService = $validationService;
        $this->auditService = $auditService;
    }

    /**
     * 元件掛載
     */
    public function mount(?int $permissionId = null): void
    {
        // 檢查權限
        if (!auth()->user()->hasPermission('permissions.view')) {
            abort(403, '您沒有檢視權限的權限');
        }

        if ($permissionId) {
            $this->selectPermission($permissionId);
        }

        $this->loadAvailablePermissions();
    }

    /**
     * 選擇權限
     */
    #[On('select-permission-for-dependencies')]
    public function selectPermission(int $permissionId): void
    {
        try {
            $this->selectedPermissionId = $permissionId;
            $this->selectedPermission = Permission::with(['dependencies', 'dependents'])->find($permissionId);
            
            if (!$this->selectedPermission) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '權限不存在'
                ]);
                return;
            }

            $this->loadDependencyData();
            $this->generateGraphData();
            
            // 記錄查看操作
            $this->auditService->logDataAccess('permissions', 'dependency_view', [
                'permission_id' => $permissionId,
                'permission_name' => $this->selectedPermission->name,
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '載入權限依賴關係失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 載入依賴關係資料
     */
    private function loadDependencyData(): void
    {
        if (!$this->selectedPermission) {
            return;
        }

        // 載入依賴樹
        if ($this->direction === 'dependencies' || $this->direction === 'both') {
            $this->dependencyTree = $this->buildDependencyTree($this->selectedPermission->id, [], 0);
        }

        // 載入被依賴樹
        if ($this->direction === 'dependents' || $this->direction === 'both') {
            $this->dependentTree = $this->buildDependentTree($this->selectedPermission->id, [], 0);
        }
        
        // 載入依賴路徑
        $this->loadDependencyPaths();
    }

    /**
     * 建立依賴樹
     */
    private function buildDependencyTree(int $permissionId, array $visited = [], int $depth = 0): array
    {
        if ($depth >= $this->maxDepth || in_array($permissionId, $visited)) {
            return [];
        }

        $visited[] = $permissionId;
        $permission = Permission::find($permissionId);
        
        if (!$permission) {
            return [];
        }

        // 應用篩選
        if (!$this->matchesFilter($permission)) {
            return [];
        }

        $dependencies = $permission->dependencies()
                                  ->when($this->moduleFilter !== 'all', function ($query) {
                                      $query->where('module', $this->moduleFilter);
                                  })
                                  ->when($this->typeFilter !== 'all', function ($query) {
                                      $query->where('type', $this->typeFilter);
                                  })
                                  ->get();

        $children = [];
        foreach ($dependencies as $dependency) {
            $childTree = $this->buildDependencyTree($dependency->id, $visited, $depth + 1);
            if (!empty($childTree) || $depth < $this->maxDepth - 1) {
                $children[] = [
                    'id' => $dependency->id,
                    'name' => $dependency->name,
                    'display_name' => $dependency->display_name,
                    'module' => $dependency->module,
                    'type' => $dependency->type,
                    'is_system' => $dependency->is_system_permission,
                    'depth' => $depth + 1,
                    'children' => $childTree,
                ];
            }
        }

        return $children;
    }

    /**
     * 建立被依賴樹
     */
    private function buildDependentTree(int $permissionId, array $visited = [], int $depth = 0): array
    {
        if ($depth >= $this->maxDepth || in_array($permissionId, $visited)) {
            return [];
        }

        $visited[] = $permissionId;
        $permission = Permission::find($permissionId);
        
        if (!$permission) {
            return [];
        }

        // 應用篩選
        if (!$this->matchesFilter($permission)) {
            return [];
        }

        $dependents = $permission->dependents()
                                ->when($this->moduleFilter !== 'all', function ($query) {
                                    $query->where('module', $this->moduleFilter);
                                })
                                ->when($this->typeFilter !== 'all', function ($query) {
                                    $query->where('type', $this->typeFilter);
                                })
                                ->get();

        $children = [];
        foreach ($dependents as $dependent) {
            $childTree = $this->buildDependentTree($dependent->id, $visited, $depth + 1);
            if (!empty($childTree) || $depth < $this->maxDepth - 1) {
                $children[] = [
                    'id' => $dependent->id,
                    'name' => $dependent->name,
                    'display_name' => $dependent->display_name,
                    'module' => $dependent->module,
                    'type' => $dependent->type,
                    'is_system' => $dependent->is_system_permission,
                    'depth' => $depth + 1,
                    'children' => $childTree,
                ];
            }
        }

        return $children;
    }

    /**
     * 檢查權限是否符合篩選條件
     */
    private function matchesFilter(Permission $permission): bool
    {
        if ($this->moduleFilter !== 'all' && $permission->module !== $this->moduleFilter) {
            return false;
        }

        if ($this->typeFilter !== 'all' && $permission->type !== $this->typeFilter) {
            return false;
        }

        return true;
    }

    /**
     * 生成圖表資料
     */
    private function generateGraphData(): void
    {
        if (!$this->selectedPermission) {
            $this->graphData = [];
            return;
        }

        $nodes = [];
        $edges = [];
        $visited = [];

        // 添加中心節點
        $nodes[] = [
            'id' => $this->selectedPermission->id,
            'name' => $this->selectedPermission->name,
            'display_name' => $this->selectedPermission->display_name,
            'module' => $this->selectedPermission->module,
            'type' => $this->selectedPermission->type,
            'is_system' => $this->selectedPermission->is_system_permission,
            'is_center' => true,
            'level' => 0,
        ];
        $visited[] = $this->selectedPermission->id;

        // 添加依賴節點和邊
        if ($this->direction === 'dependencies' || $this->direction === 'both') {
            $this->addDependencyNodes($this->selectedPermission->id, $nodes, $edges, $visited, 1, 'dependency');
        }

        // 添加被依賴節點和邊
        if ($this->direction === 'dependents' || $this->direction === 'both') {
            $this->addDependentNodes($this->selectedPermission->id, $nodes, $edges, $visited, 1, 'dependent');
        }

        $this->graphData = [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
        
        // 發送事件給前端更新網路圖
        $this->dispatch('dependency-graph-updated', [
            'graphData' => $this->graphData
        ]);
    }

    /**
     * 添加依賴節點
     */
    private function addDependencyNodes(int $permissionId, array &$nodes, array &$edges, array &$visited, int $level, string $type): void
    {
        if ($level > $this->maxDepth) {
            return;
        }

        $permission = Permission::find($permissionId);
        if (!$permission) {
            return;
        }

        $dependencies = $permission->dependencies()
                                  ->when($this->moduleFilter !== 'all', function ($query) {
                                      $query->where('module', $this->moduleFilter);
                                  })
                                  ->when($this->typeFilter !== 'all', function ($query) {
                                      $query->where('type', $this->typeFilter);
                                  })
                                  ->get();

        foreach ($dependencies as $dependency) {
            if (!in_array($dependency->id, $visited)) {
                $nodes[] = [
                    'id' => $dependency->id,
                    'name' => $dependency->name,
                    'display_name' => $dependency->display_name,
                    'module' => $dependency->module,
                    'type' => $dependency->type,
                    'is_system' => $dependency->is_system_permission,
                    'is_center' => false,
                    'level' => $level,
                    'node_type' => $type,
                ];
                $visited[] = $dependency->id;
            }

            $edges[] = [
                'from' => $permissionId,
                'to' => $dependency->id,
                'type' => 'dependency',
                'label' => '依賴',
            ];

            // 遞迴添加子依賴
            $this->addDependencyNodes($dependency->id, $nodes, $edges, $visited, $level + 1, $type);
        }
    }

    /**
     * 添加被依賴節點
     */
    private function addDependentNodes(int $permissionId, array &$nodes, array &$edges, array &$visited, int $level, string $type): void
    {
        if ($level > $this->maxDepth) {
            return;
        }

        $permission = Permission::find($permissionId);
        if (!$permission) {
            return;
        }

        $dependents = $permission->dependents()
                                ->when($this->moduleFilter !== 'all', function ($query) {
                                    $query->where('module', $this->moduleFilter);
                                })
                                ->when($this->typeFilter !== 'all', function ($query) {
                                    $query->where('type', $this->typeFilter);
                                })
                                ->get();

        foreach ($dependents as $dependent) {
            if (!in_array($dependent->id, $visited)) {
                $nodes[] = [
                    'id' => $dependent->id,
                    'name' => $dependent->name,
                    'display_name' => $dependent->display_name,
                    'module' => $dependent->module,
                    'type' => $dependent->type,
                    'is_system' => $dependent->is_system_permission,
                    'is_center' => false,
                    'level' => $level,
                    'node_type' => $type,
                ];
                $visited[] = $dependent->id;
            }

            $edges[] = [
                'from' => $dependent->id,
                'to' => $permissionId,
                'type' => 'dependent',
                'label' => '被依賴',
            ];

            // 遞迴添加子被依賴
            $this->addDependentNodes($dependent->id, $nodes, $edges, $visited, $level + 1, $type);
        }
    }

    /**
     * 載入可用權限
     */
    private function loadAvailablePermissions(): void
    {
        $query = Permission::select('id', 'name', 'display_name', 'module', 'type')
                          ->orderBy('module')
                          ->orderBy('name');

        // 排除已選中的權限
        if ($this->selectedPermissionId) {
            $query->where('id', '!=', $this->selectedPermissionId);
        }

        $this->availablePermissions = $query->get()->toArray();
    }

    /**
     * 開啟新增依賴對話框
     */
    public function openAddDependency(): void
    {
        if (!auth()->user()->hasPermission('permissions.edit')) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '您沒有編輯權限的權限'
            ]);
            return;
        }

        if (!$this->selectedPermission) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '請先選擇一個權限'
            ]);
            return;
        }

        $this->selectedDependencies = [];
        $this->showAddDependency = true;
        $this->loadAvailablePermissions();
    }

    /**
     * 新增依賴關係
     */
    public function addDependencies(): void
    {
        try {
            if (!auth()->user()->hasPermission('permissions.edit')) {
                throw new \Exception('您沒有編輯權限的權限');
            }

            if (empty($this->selectedDependencies)) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '請選擇要新增的依賴權限'
                ]);
                return;
            }

            // 驗證依賴關係
            $this->validationService->validateDependencies($this->selectedPermissionId, $this->selectedDependencies);

            // 新增依賴關係
            $currentDependencies = $this->selectedPermission->dependencies->pluck('id')->toArray();
            $newDependencies = array_unique(array_merge($currentDependencies, $this->selectedDependencies));
            
            $this->permissionRepository->syncDependencies($this->selectedPermission, $newDependencies);

            // 記錄審計日誌
            $this->auditService->logDataAccess('permissions', 'dependencies_added', [
                'permission_id' => $this->selectedPermissionId,
                'permission_name' => $this->selectedPermission->name,
                'added_dependencies' => $this->selectedDependencies,
            ]);

            // 重新載入資料
            $this->selectedPermission->refresh();
            $this->loadDependencyData();
            $this->generateGraphData();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '依賴關係新增成功'
            ]);

            $this->showAddDependency = false;
            
        } catch (ValidationException $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '新增依賴關係失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 移除依賴關係
     */
    public function removeDependency(int $dependencyId): void
    {
        try {
            if (!auth()->user()->hasPermission('permissions.edit')) {
                throw new \Exception('您沒有編輯權限的權限');
            }

            $dependency = Permission::find($dependencyId);
            if (!$dependency) {
                throw new \Exception('依賴權限不存在');
            }

            // 移除依賴關係
            $this->selectedPermission->dependencies()->detach($dependencyId);

            // 記錄審計日誌
            $this->auditService->logDataAccess('permissions', 'dependency_removed', [
                'permission_id' => $this->selectedPermissionId,
                'permission_name' => $this->selectedPermission->name,
                'removed_dependency_id' => $dependencyId,
                'removed_dependency_name' => $dependency->name,
            ]);

            // 重新載入資料
            $this->selectedPermission->refresh();
            $this->loadDependencyData();
            $this->generateGraphData();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "已移除依賴關係：{$dependency->display_name}"
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '移除依賴關係失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 切換節點展開狀態
     */
    public function toggleNode(string $nodeId): void
    {
        if (in_array($nodeId, $this->expandedNodes)) {
            $this->expandedNodes = array_diff($this->expandedNodes, [$nodeId]);
        } else {
            $this->expandedNodes[] = $nodeId;
        }
    }

    /**
     * 檢查節點是否展開
     */
    public function isNodeExpanded(string $nodeId): bool
    {
        return in_array($nodeId, $this->expandedNodes);
    }

    /**
     * 自動解析依賴關係
     */
    public function autoResolveDependencies(): void
    {
        try {
            if (!auth()->user()->hasPermission('permissions.edit')) {
                throw new \Exception('您沒有編輯權限的權限');
            }

            if (!$this->selectedPermission) {
                throw new \Exception('請先選擇一個權限');
            }

            // 根據權限類型自動推薦依賴關係
            $suggestedDependencies = $this->getSuggestedDependencies($this->selectedPermission);

            if (empty($suggestedDependencies)) {
                $this->dispatch('show-toast', [
                    'type' => 'info',
                    'message' => '沒有找到建議的依賴關係'
                ]);
                return;
            }

            // 驗證並新增建議的依賴關係
            $currentDependencies = $this->selectedPermission->dependencies->pluck('id')->toArray();
            $newDependencies = array_diff($suggestedDependencies, $currentDependencies);

            if (empty($newDependencies)) {
                $this->dispatch('show-toast', [
                    'type' => 'info',
                    'message' => '所有建議的依賴關係已存在'
                ]);
                return;
            }

            $this->validationService->validateDependencies($this->selectedPermissionId, array_merge($currentDependencies, $newDependencies));

            $this->permissionRepository->syncDependencies($this->selectedPermission, array_merge($currentDependencies, $newDependencies));

            // 記錄審計日誌
            $this->auditService->logDataAccess('permissions', 'dependencies_auto_resolved', [
                'permission_id' => $this->selectedPermissionId,
                'permission_name' => $this->selectedPermission->name,
                'added_dependencies' => $newDependencies,
            ]);

            // 重新載入資料
            $this->selectedPermission->refresh();
            $this->loadDependencyData();
            $this->generateGraphData();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '自動解析完成，新增了 ' . count($newDependencies) . ' 個依賴關係'
            ]);
            
        } catch (ValidationException $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '自動解析失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 取得建議的依賴關係
     */
    private function getSuggestedDependencies(Permission $permission): array
    {
        $suggestions = [];

        // 根據權限類型建議依賴關係
        switch ($permission->type) {
            case 'edit':
                // 編輯權限通常依賴檢視權限
                $viewPermission = Permission::where('module', $permission->module)
                                           ->where('type', 'view')
                                           ->first();
                if ($viewPermission) {
                    $suggestions[] = $viewPermission->id;
                }
                break;

            case 'delete':
                // 刪除權限通常依賴檢視和編輯權限
                $viewPermission = Permission::where('module', $permission->module)
                                           ->where('type', 'view')
                                           ->first();
                $editPermission = Permission::where('module', $permission->module)
                                           ->where('type', 'edit')
                                           ->first();
                if ($viewPermission) {
                    $suggestions[] = $viewPermission->id;
                }
                if ($editPermission) {
                    $suggestions[] = $editPermission->id;
                }
                break;

            case 'manage':
                // 管理權限通常依賴所有其他權限
                $otherPermissions = Permission::where('module', $permission->module)
                                             ->whereIn('type', ['view', 'create', 'edit', 'delete'])
                                             ->pluck('id')
                                             ->toArray();
                $suggestions = array_merge($suggestions, $otherPermissions);
                break;
        }

        return array_unique($suggestions);
    }

    /**
     * 載入依賴路徑
     */
    private function loadDependencyPaths(): void
    {
        if (!$this->selectedPermission) {
            return;
        }

        // 載入依賴路徑
        if ($this->direction === 'dependencies' || $this->direction === 'both') {
            $this->dependencyPaths = $this->findAllDependencyPaths($this->selectedPermission->id);
        }

        // 載入被依賴路徑
        if ($this->direction === 'dependents' || $this->direction === 'both') {
            $this->dependentPaths = $this->findAllDependentPaths($this->selectedPermission->id);
        }
    }

    /**
     * 尋找所有依賴路徑
     */
    private function findAllDependencyPaths(int $permissionId, array $currentPath = [], array $visited = []): array
    {
        if (in_array($permissionId, $visited)) {
            return []; // 避免循環依賴
        }

        $permission = Permission::find($permissionId);
        if (!$permission) {
            return [];
        }

        $visited[] = $permissionId;
        $currentPath[] = [
            'id' => $permission->id,
            'name' => $permission->name,
            'display_name' => $permission->display_name,
            'module' => $permission->module,
            'type' => $permission->type,
        ];

        $paths = [];
        $dependencies = $permission->dependencies;

        if ($dependencies->isEmpty()) {
            // 這是一個終端節點，返回當前路徑
            $paths[] = $currentPath;
        } else {
            // 遞迴尋找每個依賴的路徑
            foreach ($dependencies as $dependency) {
                $subPaths = $this->findAllDependencyPaths($dependency->id, $currentPath, $visited);
                $paths = array_merge($paths, $subPaths);
            }
        }

        return $paths;
    }

    /**
     * 尋找所有被依賴路徑
     */
    private function findAllDependentPaths(int $permissionId, array $currentPath = [], array $visited = []): array
    {
        if (in_array($permissionId, $visited)) {
            return []; // 避免循環依賴
        }

        $permission = Permission::find($permissionId);
        if (!$permission) {
            return [];
        }

        $visited[] = $permissionId;
        $currentPath[] = [
            'id' => $permission->id,
            'name' => $permission->name,
            'display_name' => $permission->display_name,
            'module' => $permission->module,
            'type' => $permission->type,
        ];

        $paths = [];
        $dependents = $permission->dependents;

        if ($dependents->isEmpty()) {
            // 這是一個終端節點，返回當前路徑
            $paths[] = $currentPath;
        } else {
            // 遞迴尋找每個被依賴的路徑
            foreach ($dependents as $dependent) {
                $subPaths = $this->findAllDependentPaths($dependent->id, $currentPath, $visited);
                $paths = array_merge($paths, $subPaths);
            }
        }

        return $paths;
    }

    /**
     * 取得特定權限的依賴路徑
     */
    public function getDependencyPath(int $targetPermissionId): array
    {
        if (!$this->selectedPermission) {
            return [];
        }

        return $this->findPathBetweenPermissions($this->selectedPermission->id, $targetPermissionId, 'dependency');
    }

    /**
     * 取得特定權限的被依賴路徑
     */
    public function getDependentPath(int $targetPermissionId): array
    {
        if (!$this->selectedPermission) {
            return [];
        }

        return $this->findPathBetweenPermissions($targetPermissionId, $this->selectedPermission->id, 'dependent');
    }

    /**
     * 尋找兩個權限之間的路徑
     */
    private function findPathBetweenPermissions(int $fromId, int $toId, string $direction): array
    {
        $queue = [[$fromId]];
        $visited = [];

        while (!empty($queue)) {
            $path = array_shift($queue);
            $currentId = end($path);

            if ($currentId === $toId) {
                // 找到路徑，轉換為權限物件
                return array_map(function ($id) {
                    $permission = Permission::find($id);
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => $permission->display_name,
                        'module' => $permission->module,
                        'type' => $permission->type,
                    ];
                }, $path);
            }

            if (in_array($currentId, $visited)) {
                continue;
            }

            $visited[] = $currentId;
            $permission = Permission::find($currentId);

            if (!$permission) {
                continue;
            }

            $relatedPermissions = $direction === 'dependency' 
                ? $permission->dependencies 
                : $permission->dependents;

            foreach ($relatedPermissions as $related) {
                if (!in_array($related->id, $visited)) {
                    $newPath = $path;
                    $newPath[] = $related->id;
                    $queue[] = $newPath;
                }
            }
        }

        return [];
    }

    /**
     * 檢查循環依賴
     */
    public function checkCircularDependencies(): void
    {
        try {
            $result = PermissionDependency::validateIntegrity();
            
            if ($result['is_valid']) {
                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => '沒有發現循環依賴'
                ]);
            } else {
                $circularIssues = array_filter($result['issues'], function ($issue) {
                    return $issue['type'] === 'circular_dependency';
                });

                if (!empty($circularIssues)) {
                    $this->dispatch('show-toast', [
                        'type' => 'warning',
                        'message' => '發現 ' . count($circularIssues) . ' 個循環依賴問題'
                    ]);
                } else {
                    $this->dispatch('show-toast', [
                        'type' => 'info',
                        'message' => '發現 ' . $result['total_issues'] . ' 個依賴關係問題'
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '檢查循環依賴失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 更新檢視模式
     */
    public function updatedViewMode(): void
    {
        $this->generateGraphData();
    }

    /**
     * 更新方向
     */
    public function updatedDirection(): void
    {
        $this->loadDependencyData();
        $this->generateGraphData();
    }

    /**
     * 更新模組篩選
     */
    public function updatedModuleFilter(): void
    {
        $this->loadDependencyData();
        $this->generateGraphData();
    }

    /**
     * 更新類型篩選
     */
    public function updatedTypeFilter(): void
    {
        $this->loadDependencyData();
        $this->generateGraphData();
    }

    /**
     * 更新最大深度
     */
    public function updatedMaxDepth(): void
    {
        $this->loadDependencyData();
        $this->generateGraphData();
    }

    /**
     * 取得可用的模組選項
     */
    public function getModulesProperty(): Collection
    {
        return Permission::distinct()->orderBy('module')->pluck('module');
    }

    /**
     * 取得可用的類型選項
     */
    public function getTypesProperty(): Collection
    {
        return Permission::distinct()->orderBy('type')->pluck('type');
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.permissions.dependency-graph', [
            'modules' => $this->modules,
            'types' => $this->types,
        ]);
    }
}