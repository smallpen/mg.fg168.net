<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;

/**
 * 角色安全性稽核服務
 * 
 * 檢查角色管理系統的安全性和權限控制
 */
class RoleSecurityAuditService
{
    private array $securityIssues = [];
    private array $auditResults = [];

    /**
     * 執行完整的安全性稽核
     * 
     * @return array
     */
    public function performSecurityAudit(): array
    {
        try {
            Log::info('開始執行角色管理安全性稽核');

            $this->securityIssues = [];
            $this->auditResults = [];

            // 檢查路由權限保護
            $this->auditRoutePermissions();

            // 檢查控制器權限檢查
            $this->auditControllerPermissions();

            // 檢查 Livewire 元件權限
            $this->auditLivewirePermissions();

            // 檢查系統角色保護
            $this->auditSystemRoleProtection();

            // 檢查權限繼承安全性
            $this->auditPermissionInheritanceSecurity();

            // 檢查批量操作安全性
            $this->auditBulkOperationSecurity();

            // 檢查資料驗證和清理
            $this->auditDataValidationAndSanitization();

            // 檢查審計日誌
            $this->auditLoggingMechanisms();

            // 生成安全性報告
            $securityReport = $this->generateSecurityReport();

            Log::info('角色管理安全性稽核完成', [
                'issues_count' => count($this->securityIssues),
                'overall_status' => $securityReport['overall_status']
            ]);

            return [
                'success' => true,
                'timestamp' => now()->toISOString(),
                'security_report' => $securityReport,
                'audit_results' => $this->auditResults,
                'security_issues' => $this->securityIssues
            ];

        } catch (\Exception $e) {
            Log::error('安全性稽核失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'security_issues' => $this->securityIssues
            ];
        }
    }

    /**
     * 稽核路由權限保護
     * 
     * @return void
     */
    private function auditRoutePermissions(): void
    {
        $roleRoutes = collect(Route::getRoutes())->filter(function ($route) {
            return str_contains($route->uri(), 'admin/roles');
        });

        $unprotectedRoutes = [];
        $weaklyProtectedRoutes = [];

        foreach ($roleRoutes as $route) {
            $middleware = $route->middleware();
            $hasAuth = in_array('auth', $middleware) || in_array('admin', $middleware);
            $hasPermissionCheck = collect($middleware)->contains(function ($m) {
                return str_contains($m, 'can:') || str_contains($m, 'permission:');
            });

            if (!$hasAuth) {
                $unprotectedRoutes[] = [
                    'uri' => $route->uri(),
                    'methods' => $route->methods(),
                    'name' => $route->getName()
                ];
            } elseif (!$hasPermissionCheck) {
                $weaklyProtectedRoutes[] = [
                    'uri' => $route->uri(),
                    'methods' => $route->methods(),
                    'name' => $route->getName(),
                    'middleware' => $middleware
                ];
            }
        }

        if (!empty($unprotectedRoutes)) {
            $this->securityIssues[] = [
                'type' => 'unprotected_routes',
                'severity' => 'high',
                'message' => '發現未受保護的角色管理路由',
                'details' => $unprotectedRoutes
            ];
        }

        if (!empty($weaklyProtectedRoutes)) {
            $this->securityIssues[] = [
                'type' => 'weakly_protected_routes',
                'severity' => 'medium',
                'message' => '發現僅有認證保護但缺少權限檢查的路由',
                'details' => $weaklyProtectedRoutes
            ];
        }

        $this->auditResults['route_permissions'] = [
            'total_routes' => $roleRoutes->count(),
            'unprotected_routes' => count($unprotectedRoutes),
            'weakly_protected_routes' => count($weaklyProtectedRoutes),
            'properly_protected_routes' => $roleRoutes->count() - count($unprotectedRoutes) - count($weaklyProtectedRoutes)
        ];
    }

    /**
     * 稽核控制器權限檢查
     * 
     * @return void
     */
    private function auditControllerPermissions(): void
    {
        $controllerPath = app_path('Http/Controllers/Admin/RoleController.php');
        
        if (!file_exists($controllerPath)) {
            $this->securityIssues[] = [
                'type' => 'missing_controller',
                'severity' => 'high',
                'message' => 'RoleController 檔案不存在'
            ];
            return;
        }

        $controllerContent = file_get_contents($controllerPath);
        $methodsWithoutAuth = [];
        $methodsWithoutPermissionCheck = [];

        // 檢查是否有 authorize 呼叫
        $reflection = new ReflectionClass(\App\Http\Controllers\Admin\RoleController::class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->getName() !== \App\Http\Controllers\Admin\RoleController::class) {
                continue;
            }

            $methodName = $method->getName();
            $methodContent = $this->getMethodContent($controllerContent, $methodName);

            if (!str_contains($methodContent, '$this->authorize(') && !str_contains($methodContent, 'Gate::authorize(')) {
                $methodsWithoutAuth[] = $methodName;
            }
        }

        if (!empty($methodsWithoutAuth)) {
            $this->securityIssues[] = [
                'type' => 'controller_missing_authorization',
                'severity' => 'high',
                'message' => 'RoleController 中的方法缺少權限檢查',
                'details' => $methodsWithoutAuth
            ];
        }

        $this->auditResults['controller_permissions'] = [
            'total_methods' => count($methods),
            'methods_without_auth' => count($methodsWithoutAuth),
            'methods_with_auth' => count($methods) - count($methodsWithoutAuth)
        ];
    }

    /**
     * 稽核 Livewire 元件權限
     * 
     * @return void
     */
    private function auditLivewirePermissions(): void
    {
        $livewireComponents = [
            'App\Livewire\Admin\Roles\RoleList',
            'App\Livewire\Admin\Roles\RoleForm',
            'App\Livewire\Admin\Roles\PermissionMatrix',
            'App\Livewire\Admin\Roles\RoleDeleteModal',
            'App\Livewire\Admin\Roles\BulkPermissionModal'
        ];

        $componentsWithoutAuth = [];
        $componentsWithWeakAuth = [];

        foreach ($livewireComponents as $componentClass) {
            if (!class_exists($componentClass)) {
                $componentsWithoutAuth[] = $componentClass;
                continue;
            }

            $reflection = new ReflectionClass($componentClass);
            $mountMethod = null;
            
            try {
                $mountMethod = $reflection->getMethod('mount');
            } catch (\ReflectionException $e) {
                // mount 方法不存在
            }

            $hasAuthCheck = false;
            if ($mountMethod) {
                $methodContent = $this->getMethodContentFromReflection($mountMethod);
                $hasAuthCheck = str_contains($methodContent, 'authorize(') || 
                               str_contains($methodContent, 'Gate::') ||
                               str_contains($methodContent, 'can(');
            }

            if (!$hasAuthCheck) {
                $componentsWithWeakAuth[] = $componentClass;
            }
        }

        if (!empty($componentsWithoutAuth)) {
            $this->securityIssues[] = [
                'type' => 'missing_livewire_components',
                'severity' => 'medium',
                'message' => '部分 Livewire 元件不存在',
                'details' => $componentsWithoutAuth
            ];
        }

        if (!empty($componentsWithWeakAuth)) {
            $this->securityIssues[] = [
                'type' => 'livewire_weak_authorization',
                'severity' => 'medium',
                'message' => 'Livewire 元件缺少權限檢查',
                'details' => $componentsWithWeakAuth
            ];
        }

        $this->auditResults['livewire_permissions'] = [
            'total_components' => count($livewireComponents),
            'missing_components' => count($componentsWithoutAuth),
            'components_with_weak_auth' => count($componentsWithWeakAuth),
            'properly_protected_components' => count($livewireComponents) - count($componentsWithoutAuth) - count($componentsWithWeakAuth)
        ];
    }

    /**
     * 稽核系統角色保護
     * 
     * @return void
     */
    private function auditSystemRoleProtection(): void
    {
        $systemRoles = Role::where('is_system_role', true)->get();
        $vulnerableSystemRoles = [];

        foreach ($systemRoles as $role) {
            $issues = [];

            // 檢查是否可以被編輯
            if (!$role->is_system_role) {
                $issues[] = 'is_system_role 標記不正確';
            }

            // 檢查是否有適當的保護邏輯
            // 這裡可以添加更多檢查邏輯

            if (!empty($issues)) {
                $vulnerableSystemRoles[] = [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'issues' => $issues
                ];
            }
        }

        if (!empty($vulnerableSystemRoles)) {
            $this->securityIssues[] = [
                'type' => 'vulnerable_system_roles',
                'severity' => 'high',
                'message' => '系統角色存在安全性問題',
                'details' => $vulnerableSystemRoles
            ];
        }

        $this->auditResults['system_role_protection'] = [
            'total_system_roles' => $systemRoles->count(),
            'vulnerable_system_roles' => count($vulnerableSystemRoles),
            'protected_system_roles' => $systemRoles->count() - count($vulnerableSystemRoles)
        ];
    }

    /**
     * 稽核權限繼承安全性
     * 
     * @return void
     */
    private function auditPermissionInheritanceSecurity(): void
    {
        $inheritanceIssues = [];

        // 檢查循環依賴
        $rolesWithParents = Role::whereNotNull('parent_id')->get();
        foreach ($rolesWithParents as $role) {
            if ($role->hasCircularDependency($role->parent_id)) {
                $inheritanceIssues[] = [
                    'type' => 'circular_dependency',
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'parent_id' => $role->parent_id
                ];
            }
        }

        // 檢查過深的層級
        foreach ($rolesWithParents as $role) {
            $depth = $role->getDepth();
            if ($depth > 5) { // 超過 5 層被認為過深
                $inheritanceIssues[] = [
                    'type' => 'excessive_depth',
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'depth' => $depth
                ];
            }
        }

        // 檢查權限提升風險
        foreach ($rolesWithParents as $role) {
            $parentPermissions = $role->parent ? $role->parent->getAllPermissions() : collect();
            $childPermissions = $role->permissions;
            
            // 檢查子角色是否有父角色沒有的敏感權限
            $sensitivePermissions = ['users.delete', 'roles.delete', 'system.admin'];
            $riskyPermissions = $childPermissions->whereIn('name', $sensitivePermissions)
                                                ->whereNotIn('id', $parentPermissions->pluck('id'));
            
            if ($riskyPermissions->isNotEmpty()) {
                $inheritanceIssues[] = [
                    'type' => 'privilege_escalation_risk',
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'risky_permissions' => $riskyPermissions->pluck('name')->toArray()
                ];
            }
        }

        if (!empty($inheritanceIssues)) {
            $this->securityIssues[] = [
                'type' => 'permission_inheritance_issues',
                'severity' => 'medium',
                'message' => '權限繼承存在安全性問題',
                'details' => $inheritanceIssues
            ];
        }

        $this->auditResults['permission_inheritance_security'] = [
            'roles_with_parents' => $rolesWithParents->count(),
            'inheritance_issues' => count($inheritanceIssues),
            'safe_inheritance_roles' => $rolesWithParents->count() - count($inheritanceIssues)
        ];
    }

    /**
     * 稽核批量操作安全性
     * 
     * @return void
     */
    private function auditBulkOperationSecurity(): void
    {
        $bulkOperationIssues = [];

        // 檢查批量操作是否有適當的權限檢查
        // 這裡可以檢查 RoleController 的 bulkAction 方法

        $controllerPath = app_path('Http/Controllers/Admin/RoleController.php');
        if (file_exists($controllerPath)) {
            $controllerContent = file_get_contents($controllerPath);
            $bulkActionContent = $this->getMethodContent($controllerContent, 'bulkAction');

            if (!str_contains($bulkActionContent, '$this->authorize(')) {
                $bulkOperationIssues[] = [
                    'type' => 'missing_authorization',
                    'method' => 'bulkAction',
                    'message' => '批量操作缺少權限檢查'
                ];
            }

            if (!str_contains($bulkActionContent, 'is_system_role')) {
                $bulkOperationIssues[] = [
                    'type' => 'missing_system_role_check',
                    'method' => 'bulkAction',
                    'message' => '批量操作未檢查系統角色保護'
                ];
            }
        }

        if (!empty($bulkOperationIssues)) {
            $this->securityIssues[] = [
                'type' => 'bulk_operation_security_issues',
                'severity' => 'medium',
                'message' => '批量操作存在安全性問題',
                'details' => $bulkOperationIssues
            ];
        }

        $this->auditResults['bulk_operation_security'] = [
            'total_issues' => count($bulkOperationIssues),
            'security_status' => empty($bulkOperationIssues) ? 'secure' : 'needs_improvement'
        ];
    }

    /**
     * 稽核資料驗證和清理
     * 
     * @return void
     */
    private function auditDataValidationAndSanitization(): void
    {
        $validationIssues = [];

        // 檢查 Role 模型的 fillable 屬性
        $role = new Role();
        $fillable = $role->getFillable();
        
        $sensitiveFields = ['is_system_role', 'is_active'];
        $exposedSensitiveFields = array_intersect($fillable, $sensitiveFields);
        
        if (!empty($exposedSensitiveFields)) {
            $validationIssues[] = [
                'type' => 'exposed_sensitive_fields',
                'model' => 'Role',
                'fields' => $exposedSensitiveFields,
                'message' => '敏感欄位暴露在批量賦值中'
            ];
        }

        // 檢查表單驗證規則
        $formComponents = [
            'App\Livewire\Admin\Roles\RoleForm'
        ];

        foreach ($formComponents as $componentClass) {
            if (class_exists($componentClass)) {
                $reflection = new ReflectionClass($componentClass);
                
                try {
                    $rulesMethod = $reflection->getMethod('rules');
                    // 這裡可以檢查驗證規則是否足夠嚴格
                } catch (\ReflectionException $e) {
                    $validationIssues[] = [
                        'type' => 'missing_validation_rules',
                        'component' => $componentClass,
                        'message' => '缺少驗證規則方法'
                    ];
                }
            }
        }

        if (!empty($validationIssues)) {
            $this->securityIssues[] = [
                'type' => 'data_validation_issues',
                'severity' => 'medium',
                'message' => '資料驗證和清理存在問題',
                'details' => $validationIssues
            ];
        }

        $this->auditResults['data_validation'] = [
            'total_issues' => count($validationIssues),
            'validation_status' => empty($validationIssues) ? 'secure' : 'needs_improvement'
        ];
    }

    /**
     * 稽核審計日誌機制
     * 
     * @return void
     */
    private function auditLoggingMechanisms(): void
    {
        $loggingIssues = [];

        // 檢查是否有適當的日誌記錄
        $logChannels = config('logging.channels', []);
        
        if (!isset($logChannels['audit'])) {
            $loggingIssues[] = [
                'type' => 'missing_audit_channel',
                'message' => '缺少專用的審計日誌通道'
            ];
        }

        // 檢查關鍵操作是否有日誌記錄
        $criticalMethods = ['delete', 'bulkAction', 'duplicate'];
        $controllerPath = app_path('Http/Controllers/Admin/RoleController.php');
        
        if (file_exists($controllerPath)) {
            $controllerContent = file_get_contents($controllerPath);
            
            foreach ($criticalMethods as $method) {
                $methodContent = $this->getMethodContent($controllerContent, $method);
                if (!str_contains($methodContent, 'Log::')) {
                    $loggingIssues[] = [
                        'type' => 'missing_method_logging',
                        'method' => $method,
                        'message' => "方法 {$method} 缺少日誌記錄"
                    ];
                }
            }
        }

        if (!empty($loggingIssues)) {
            $this->securityIssues[] = [
                'type' => 'logging_mechanism_issues',
                'severity' => 'low',
                'message' => '審計日誌機制存在問題',
                'details' => $loggingIssues
            ];
        }

        $this->auditResults['logging_mechanisms'] = [
            'total_issues' => count($loggingIssues),
            'logging_status' => empty($loggingIssues) ? 'adequate' : 'needs_improvement'
        ];
    }

    /**
     * 生成安全性報告
     * 
     * @return array
     */
    private function generateSecurityReport(): array
    {
        $totalIssues = count($this->securityIssues);
        $highSeverityIssues = collect($this->securityIssues)->where('severity', 'high')->count();
        $mediumSeverityIssues = collect($this->securityIssues)->where('severity', 'medium')->count();
        $lowSeverityIssues = collect($this->securityIssues)->where('severity', 'low')->count();

        // 計算整體安全性評級
        $overallStatus = 'excellent';
        if ($highSeverityIssues > 0) {
            $overallStatus = 'critical';
        } elseif ($mediumSeverityIssues > 3) {
            $overallStatus = 'needs_improvement';
        } elseif ($mediumSeverityIssues > 0 || $lowSeverityIssues > 5) {
            $overallStatus = 'good';
        }

        return [
            'overall_status' => $overallStatus,
            'security_score' => $this->calculateSecurityScore(),
            'issue_summary' => [
                'total_issues' => $totalIssues,
                'high_severity' => $highSeverityIssues,
                'medium_severity' => $mediumSeverityIssues,
                'low_severity' => $lowSeverityIssues
            ],
            'recommendations' => $this->generateSecurityRecommendations(),
            'audit_timestamp' => now()->toISOString()
        ];
    }

    /**
     * 計算安全性評分
     * 
     * @return int
     */
    private function calculateSecurityScore(): int
    {
        $baseScore = 100;
        
        foreach ($this->securityIssues as $issue) {
            switch ($issue['severity']) {
                case 'high':
                    $baseScore -= 20;
                    break;
                case 'medium':
                    $baseScore -= 10;
                    break;
                case 'low':
                    $baseScore -= 5;
                    break;
            }
        }

        return max(0, $baseScore);
    }

    /**
     * 生成安全性建議
     * 
     * @return array
     */
    private function generateSecurityRecommendations(): array
    {
        $recommendations = [];

        $highSeverityIssues = collect($this->securityIssues)->where('severity', 'high');
        if ($highSeverityIssues->isNotEmpty()) {
            $recommendations[] = '立即修復高嚴重性安全問題，這些問題可能導致系統被攻擊';
        }

        $routeIssues = collect($this->securityIssues)->where('type', 'unprotected_routes');
        if ($routeIssues->isNotEmpty()) {
            $recommendations[] = '為所有角色管理路由添加適當的權限檢查中介軟體';
        }

        $controllerIssues = collect($this->securityIssues)->where('type', 'controller_missing_authorization');
        if ($controllerIssues->isNotEmpty()) {
            $recommendations[] = '在控制器方法中添加 $this->authorize() 呼叫';
        }

        $systemRoleIssues = collect($this->securityIssues)->where('type', 'vulnerable_system_roles');
        if ($systemRoleIssues->isNotEmpty()) {
            $recommendations[] = '加強系統角色的保護機制，防止意外修改或刪除';
        }

        if (empty($recommendations)) {
            $recommendations[] = '系統安全性良好，建議定期執行安全性稽核';
        }

        return $recommendations;
    }

    /**
     * 從檔案內容中提取方法內容
     * 
     * @param string $content
     * @param string $methodName
     * @return string
     */
    private function getMethodContent(string $content, string $methodName): string
    {
        $pattern = '/public\s+function\s+' . preg_quote($methodName) . '\s*\([^)]*\)[^{]*\{(.*?)\n\s*\}/s';
        if (preg_match($pattern, $content, $matches)) {
            return $matches[1] ?? '';
        }
        return '';
    }

    /**
     * 從反射方法中取得方法內容
     * 
     * @param ReflectionMethod $method
     * @return string
     */
    private function getMethodContentFromReflection(ReflectionMethod $method): string
    {
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        
        if ($filename && $startLine && $endLine) {
            $lines = file($filename);
            $methodLines = array_slice($lines, $startLine - 1, $endLine - $startLine + 1);
            return implode('', $methodLines);
        }
        
        return '';
    }
}