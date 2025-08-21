<?php

namespace App\Rules;

use App\Models\Permission;
use App\Services\PermissionSecurityService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

/**
 * 權限安全驗證規則
 * 
 * 提供權限操作的安全驗證，包含：
 * - 系統權限保護
 * - 循環依賴檢查
 * - 權限名稱安全性檢查
 * - 操作權限驗證
 */
class PermissionSecurityRule implements ValidationRule
{
    protected string $operation;
    protected ?Permission $permission;
    protected PermissionSecurityService $securityService;

    public function __construct(string $operation, ?Permission $permission = null)
    {
        $this->operation = $operation;
        $this->permission = $permission;
        $this->securityService = app(PermissionSecurityService::class);
    }

    /**
     * 執行驗證規則
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();

        if (!$user) {
            $fail('未授權的操作');
            return;
        }

        try {
            // 執行多層級權限檢查
            if (!$this->securityService->checkMultiLevelPermission($this->operation, $this->permission, $user)) {
                $fail('您沒有執行此操作的權限');
                return;
            }

            // 根據屬性類型執行特定驗證
            switch ($attribute) {
                case 'name':
                    $this->validatePermissionName($value, $fail);
                    break;
                case 'dependencies':
                    $this->validateDependencies($value, $fail);
                    break;
                case 'module':
                    $this->validateModule($value, $fail);
                    break;
                case 'type':
                    $this->validateType($value, $fail);
                    break;
                case 'permission_ids':
                    $this->validatePermissionIds($value, $fail);
                    break;
                default:
                    $this->validateGeneral($attribute, $value, $fail);
            }

        } catch (\Exception $e) {
            $fail('安全驗證失敗: ' . $e->getMessage());
        }
    }

    /**
     * 驗證權限名稱
     */
    protected function validatePermissionName(string $name, Closure $fail): void
    {
        // 檢查是否為系統核心權限
        if ($this->securityService->isSystemCorePermission($name)) {
            if ($this->operation === 'create') {
                $fail('不能建立與系統核心權限同名的權限');
                return;
            }

            if ($this->operation === 'update' && $this->permission && $this->permission->name !== $name) {
                $fail('不能將權限名稱修改為系統核心權限名稱');
                return;
            }
        }

        // 檢查危險模式
        $dangerousPatterns = [
            'system.delete',
            'admin.destroy',
            'auth.bypass',
            'security.disable',
            'root.',
            'sudo.',
            '*.delete.*',
            '*.destroy.*',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (fnmatch($pattern, $name)) {
                $fail('權限名稱包含危險模式');
                return;
            }
        }

        // 檢查權限名稱格式
        if (!preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)*$/', $name)) {
            $fail('權限名稱格式不正確，只能包含小寫字母、數字、底線和點號');
            return;
        }

        // 檢查長度限制
        if (strlen($name) > 100) {
            $fail('權限名稱不能超過 100 個字元');
            return;
        }

        // 檢查是否包含保留字
        $reservedWords = ['admin', 'system', 'root', 'sudo', 'all', 'any', 'none'];
        $parts = explode('.', $name);
        
        foreach ($parts as $part) {
            if (in_array($part, $reservedWords) && !$this->isAllowedReservedWord($part, $name)) {
                $fail("權限名稱不能包含保留字 '{$part}'");
                return;
            }
        }
    }

    /**
     * 驗證依賴關係
     */
    protected function validateDependencies(array $dependencies, Closure $fail): void
    {
        if (empty($dependencies)) {
            return;
        }

        // 檢查依賴權限是否存在
        $existingPermissions = Permission::whereIn('id', $dependencies)->pluck('id')->toArray();
        $missingPermissions = array_diff($dependencies, $existingPermissions);

        if (!empty($missingPermissions)) {
            $fail('以下依賴權限不存在: ' . implode(', ', $missingPermissions));
            return;
        }

        // 檢查循環依賴
        if ($this->permission) {
            foreach ($dependencies as $dependencyId) {
                if ($this->hasCircularDependency($this->permission->id, $dependencyId)) {
                    $dependencyName = Permission::find($dependencyId)?->name ?? $dependencyId;
                    $fail("與權限 '{$dependencyName}' 形成循環依賴");
                    return;
                }
            }
        }

        // 檢查依賴深度
        $maxDepth = config('permissions.max_dependency_depth', 5);
        foreach ($dependencies as $dependencyId) {
            $depth = $this->calculateDependencyDepth($dependencyId);
            if ($depth >= $maxDepth) {
                $dependencyName = Permission::find($dependencyId)?->name ?? $dependencyId;
                $fail("權限 '{$dependencyName}' 的依賴層級過深");
                return;
            }
        }

        // 檢查依賴數量限制
        $maxDependencies = config('permissions.max_dependencies_per_permission', 10);
        if (count($dependencies) > $maxDependencies) {
            $fail("每個權限最多只能有 {$maxDependencies} 個依賴");
            return;
        }
    }

    /**
     * 驗證模組
     */
    protected function validateModule(string $module, Closure $fail): void
    {
        // 檢查模組名稱格式
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $module)) {
            $fail('模組名稱格式不正確，只能包含小寫字母、數字和底線');
            return;
        }

        // 檢查系統模組保護
        $systemModules = ['system', 'auth', 'admin'];
        if (in_array($module, $systemModules)) {
            $user = Auth::user();
            if (!$user || !$user->hasPermission('system.permissions.manage')) {
                $fail('您沒有權限操作系統模組的權限');
                return;
            }
        }

        // 檢查模組是否在允許列表中
        $allowedModules = config('permissions.allowed_modules', []);
        if (!empty($allowedModules) && !in_array($module, $allowedModules)) {
            $fail('不允許的模組名稱');
            return;
        }
    }

    /**
     * 驗證權限類型
     */
    protected function validateType(string $type, Closure $fail): void
    {
        $allowedTypes = ['view', 'create', 'edit', 'delete', 'manage'];
        
        if (!in_array($type, $allowedTypes)) {
            $fail('無效的權限類型');
            return;
        }

        // 檢查高風險類型的權限
        $highRiskTypes = ['delete', 'manage'];
        if (in_array($type, $highRiskTypes)) {
            $user = Auth::user();
            if (!$user || !$user->hasPermission('permissions.high_risk_operations')) {
                $fail('您沒有權限建立高風險類型的權限');
                return;
            }
        }
    }

    /**
     * 驗證權限 ID 列表（用於批量操作）
     */
    protected function validatePermissionIds(array $permissionIds, Closure $fail): void
    {
        if (empty($permissionIds)) {
            $fail('請選擇要操作的權限');
            return;
        }

        // 檢查數量限制
        $maxBulkOperations = config('permissions.max_bulk_operations', 50);
        if (count($permissionIds) > $maxBulkOperations) {
            $fail("一次最多只能操作 {$maxBulkOperations} 個權限");
            return;
        }

        // 檢查權限是否存在
        $existingPermissions = Permission::whereIn('id', $permissionIds)->get();
        $existingIds = $existingPermissions->pluck('id')->toArray();
        $missingIds = array_diff($permissionIds, $existingIds);

        if (!empty($missingIds)) {
            $fail('以下權限不存在: ' . implode(', ', $missingIds));
            return;
        }

        // 檢查是否包含系統核心權限
        $systemPermissions = $existingPermissions->filter(function ($permission) {
            return $this->securityService->isSystemCorePermission($permission->name);
        });

        if ($systemPermissions->isNotEmpty() && $this->operation === 'delete') {
            $systemNames = $systemPermissions->pluck('name')->toArray();
            $fail('不能刪除系統核心權限: ' . implode(', ', $systemNames));
            return;
        }

        // 檢查是否有權限操作所有選中的權限
        $user = Auth::user();
        foreach ($existingPermissions as $permission) {
            try {
                if (!$this->securityService->checkMultiLevelPermission($this->operation, $permission, $user)) {
                    $fail("您沒有權限對 '{$permission->name}' 執行此操作");
                    return;
                }
            } catch (\Exception $e) {
                $fail("權限檢查失敗: {$e->getMessage()}");
                return;
            }
        }
    }

    /**
     * 一般驗證
     */
    protected function validateGeneral(string $attribute, mixed $value, Closure $fail): void
    {
        // 檢查輸入長度
        if (is_string($value) && strlen($value) > 1000) {
            $fail('輸入內容過長');
            return;
        }

        // 檢查是否包含危險字元
        if (is_string($value) && $this->containsDangerousContent($value)) {
            $fail('輸入內容包含危險字元');
            return;
        }
    }

    /**
     * 檢查是否有循環依賴
     */
    protected function hasCircularDependency(int $permissionId, int $dependencyId): bool
    {
        if ($permissionId === $dependencyId) {
            return true;
        }

        return $this->checkDependencyPath($dependencyId, $permissionId);
    }

    /**
     * 檢查依賴路徑
     */
    protected function checkDependencyPath(int $fromId, int $toId, array $visited = []): bool
    {
        if (in_array($fromId, $visited)) {
            return false; // 避免無限迴圈
        }

        $visited[] = $fromId;

        $dependencies = Permission::find($fromId)?->dependencies()->pluck('id')->toArray() ?? [];

        foreach ($dependencies as $dependencyId) {
            if ($dependencyId === $toId) {
                return true;
            }

            if ($this->checkDependencyPath($dependencyId, $toId, $visited)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 計算依賴深度
     */
    protected function calculateDependencyDepth(int $permissionId, array $visited = []): int
    {
        if (in_array($permissionId, $visited)) {
            return 0; // 避免無限迴圈
        }

        $visited[] = $permissionId;
        $dependencies = Permission::find($permissionId)?->dependencies()->pluck('id')->toArray() ?? [];

        if (empty($dependencies)) {
            return 0;
        }

        $maxDepth = 0;
        foreach ($dependencies as $dependencyId) {
            $depth = $this->calculateDependencyDepth($dependencyId, $visited);
            $maxDepth = max($maxDepth, $depth);
        }

        return $maxDepth + 1;
    }

    /**
     * 檢查是否為允許的保留字
     */
    protected function isAllowedReservedWord(string $word, string $fullName): bool
    {
        // 某些情況下允許使用保留字
        $allowedPatterns = [
            'admin.access',
            'admin.dashboard',
            'system.manage',
            'system.view',
        ];

        return in_array($fullName, $allowedPatterns);
    }

    /**
     * 檢查是否包含危險內容
     */
    protected function containsDangerousContent(string $content): bool
    {
        $dangerousPatterns = [
            '<script',
            'javascript:',
            'vbscript:',
            'onload=',
            'onerror=',
            'onclick=',
            'eval(',
            'exec(',
            'system(',
            'shell_exec(',
            '<?php',
            '<%',
        ];

        $content = strtolower($content);
        foreach ($dangerousPatterns as $pattern) {
            if (str_contains($content, $pattern)) {
                return true;
            }
        }

        return false;
    }
}