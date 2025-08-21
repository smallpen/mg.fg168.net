<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

/**
 * 角色資料驗證服務
 * 
 * 提供角色管理相關的資料驗證和清理功能
 */
class RoleDataValidationService
{
    /**
     * 危險字元模式
     */
    const DANGEROUS_PATTERNS = [
        '/(<script[^>]*>.*?<\/script>)/is',  // Script 標籤
        '/(<iframe[^>]*>.*?<\/iframe>)/is',  // Iframe 標籤
        '/(<object[^>]*>.*?<\/object>)/is',  // Object 標籤
        '/(<embed[^>]*>.*?<\/embed>)/is',    // Embed 標籤
        '/(<link[^>]*>)/is',                 // Link 標籤
        '/(<meta[^>]*>)/is',                 // Meta 標籤
        '/(javascript:|vbscript:|data:)/i',  // 危險協議
        '/(on\w+\s*=)/i',                    // 事件處理器
        '/(\beval\s*\()/i',                  // eval 函數
        '/(\bexec\s*\()/i',                  // exec 函數
    ];

    /**
     * SQL 注入模式
     */
    const SQL_INJECTION_PATTERNS = [
        '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION|SCRIPT)\b)/i',
        '/(\'|\"|;|--|\*|\/\*|\*\/)/i',
        '/(\bOR\b.*\b=\b|\bAND\b.*\b=\b)/i',
        '/(\b1\s*=\s*1\b|\b1\s*=\s*0\b)/i',
    ];

    /**
     * 驗證角色建立資料
     */
    public function validateRoleCreationData(array $data): array
    {
        // 清理輸入資料
        $sanitizedData = $this->sanitizeRoleData($data);

        // 建立驗證器
        $validator = Validator::make($sanitizedData, [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-z_][a-z0-9_]*$/',
                'unique:roles,name',
                function ($attribute, $value, $fail) {
                    if ($this->isReservedName($value)) {
                        $fail('此名稱為系統保留名稱');
                    }
                }
            ],
            'display_name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                function ($attribute, $value, $fail) {
                    if ($this->containsDangerousContent($value)) {
                        $fail('顯示名稱包含不安全的內容');
                    }
                }
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($value && $this->containsDangerousContent($value)) {
                        $fail('描述包含不安全的內容');
                    }
                }
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:roles,id',
                function ($attribute, $value, $fail) use ($sanitizedData) {
                    if ($value && $this->wouldCreateCircularDependency(null, $value)) {
                        $fail('設定此父角色會造成循環依賴');
                    }
                }
            ],
            'is_active' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ], [
            'name.required' => '角色名稱為必填項目',
            'name.unique' => '角色名稱已存在',
            'name.regex' => '角色名稱只能包含小寫英文字母、數字和底線，且必須以字母或底線開頭',
            'name.min' => '角色名稱至少需要2個字元',
            'name.max' => '角色名稱不能超過50個字元',
            'display_name.required' => '顯示名稱為必填項目',
            'display_name.min' => '顯示名稱至少需要2個字元',
            'display_name.max' => '顯示名稱不能超過50個字元',
            'description.max' => '描述不能超過255個字元',
            'parent_id.exists' => '指定的父角色不存在',
            'permissions.array' => '權限必須為陣列格式',
            'permissions.*.exists' => '指定的權限不存在',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $sanitizedData;
    }

    /**
     * 驗證角色更新資料
     */
    public function validateRoleUpdateData(array $data, Role $role): array
    {
        // 清理輸入資料
        $sanitizedData = $this->sanitizeRoleData($data);

        // 建立驗證規則
        $rules = [
            'display_name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                function ($attribute, $value, $fail) {
                    if ($this->containsDangerousContent($value)) {
                        $fail('顯示名稱包含不安全的內容');
                    }
                }
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($value && $this->containsDangerousContent($value)) {
                        $fail('描述包含不安全的內容');
                    }
                }
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:roles,id',
                function ($attribute, $value, $fail) use ($role) {
                    if ($value && $this->wouldCreateCircularDependency($role, $value)) {
                        $fail('設定此父角色會造成循環依賴');
                    }
                }
            ],
            'is_active' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ];

        // 如果不是系統角色，允許修改名稱
        if (!$role->is_system_role) {
            $rules['name'] = [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-z_][a-z0-9_]*$/',
                'unique:roles,name,' . $role->id,
                function ($attribute, $value, $fail) {
                    if ($this->isReservedName($value)) {
                        $fail('此名稱為系統保留名稱');
                    }
                }
            ];
        }

        $validator = Validator::make($sanitizedData, $rules, [
            'name.required' => '角色名稱為必填項目',
            'name.unique' => '角色名稱已存在',
            'name.regex' => '角色名稱只能包含小寫英文字母、數字和底線，且必須以字母或底線開頭',
            'name.min' => '角色名稱至少需要2個字元',
            'name.max' => '角色名稱不能超過50個字元',
            'display_name.required' => '顯示名稱為必填項目',
            'display_name.min' => '顯示名稱至少需要2個字元',
            'display_name.max' => '顯示名稱不能超過50個字元',
            'description.max' => '描述不能超過255個字元',
            'parent_id.exists' => '指定的父角色不存在',
            'permissions.array' => '權限必須為陣列格式',
            'permissions.*.exists' => '指定的權限不存在',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $sanitizedData;
    }

    /**
     * 驗證權限指派資料
     */
    public function validatePermissionAssignmentData(array $data, Role $role): array
    {
        $validator = Validator::make($data, [
            'permissions' => 'required|array',
            'permissions.*' => 'integer|exists:permissions,id',
            'operation' => 'required|string|in:add,remove,replace',
        ], [
            'permissions.required' => '權限列表為必填項目',
            'permissions.array' => '權限必須為陣列格式',
            'permissions.*.exists' => '指定的權限不存在',
            'operation.required' => '操作類型為必填項目',
            'operation.in' => '操作類型必須為 add、remove 或 replace',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 額外的業務邏輯驗證
        $this->validatePermissionBusinessLogic($data, $role);

        return $data;
    }

    /**
     * 驗證批量操作資料
     */
    public function validateBulkOperationData(array $data): array
    {
        $validator = Validator::make($data, [
            'role_ids' => 'required|array|min:1|max:100',
            'role_ids.*' => 'integer|exists:roles,id',
            'operation' => 'required|string|in:activate,deactivate,delete,assign_permissions',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ], [
            'role_ids.required' => '角色ID列表為必填項目',
            'role_ids.array' => '角色ID必須為陣列格式',
            'role_ids.min' => '至少需要選擇一個角色',
            'role_ids.max' => '一次最多只能操作100個角色',
            'role_ids.*.exists' => '指定的角色不存在',
            'operation.required' => '操作類型為必填項目',
            'operation.in' => '無效的操作類型',
            'permissions.array' => '權限必須為陣列格式',
            'permissions.*.exists' => '指定的權限不存在',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 驗證批量操作的業務邏輯
        $this->validateBulkOperationBusinessLogic($data);

        return $data;
    }

    /**
     * 清理角色資料
     */
    protected function sanitizeRoleData(array $data): array
    {
        $sanitized = [];

        // 清理角色名稱
        if (isset($data['name'])) {
            $sanitized['name'] = $this->sanitizeRoleName($data['name']);
        }

        // 清理顯示名稱
        if (isset($data['display_name'])) {
            $sanitized['display_name'] = $this->sanitizeDisplayName($data['display_name']);
        }

        // 清理描述
        if (isset($data['description'])) {
            $sanitized['description'] = $this->sanitizeDescription($data['description']);
        }

        // 清理其他欄位
        foreach (['parent_id', 'is_active', 'permissions'] as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = $data[$field];
            }
        }

        return $sanitized;
    }

    /**
     * 清理角色名稱
     */
    protected function sanitizeRoleName(string $name): string
    {
        // 移除所有非字母數字和底線的字元
        $name = preg_replace('/[^a-zA-Z0-9_]/', '', trim($name));
        
        // 轉換為小寫
        $name = strtolower($name);
        
        // 確保以字母或底線開頭
        if (!preg_match('/^[a-z_]/', $name)) {
            $name = 'role_' . $name;
        }
        
        // 限制長度
        return substr($name, 0, 50);
    }

    /**
     * 清理顯示名稱
     */
    protected function sanitizeDisplayName(string $displayName): string
    {
        // 移除HTML標籤
        $displayName = strip_tags(trim($displayName));
        
        // 移除危險內容
        $displayName = $this->removeDangerousContent($displayName);
        
        // 正規化空白字元
        $displayName = preg_replace('/\s+/', ' ', $displayName);
        
        // 限制長度
        return substr($displayName, 0, 50);
    }

    /**
     * 清理描述
     */
    protected function sanitizeDescription(?string $description): ?string
    {
        if (empty($description)) {
            return null;
        }

        // 移除HTML標籤
        $description = strip_tags(trim($description));
        
        // 移除危險內容
        $description = $this->removeDangerousContent($description);
        
        // 正規化空白字元
        $description = preg_replace('/\s+/', ' ', $description);
        
        // 限制長度
        return substr($description, 0, 255);
    }

    /**
     * 檢查是否包含危險內容
     */
    protected function containsDangerousContent(string $content): bool
    {
        // 檢查危險模式
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        // 檢查SQL注入模式
        foreach (self::SQL_INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 移除危險內容
     */
    protected function removeDangerousContent(string $content): string
    {
        // 移除危險模式
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }

        // 移除SQL注入模式
        foreach (self::SQL_INJECTION_PATTERNS as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }

        return $content;
    }

    /**
     * 檢查是否為保留名稱
     */
    protected function isReservedName(string $name): bool
    {
        $reservedNames = [
            'root', 'admin', 'administrator', 'system', 'guest', 'anonymous', 
            'public', 'user', 'users', 'role', 'roles', 'permission', 'permissions',
            'null', 'undefined', 'true', 'false', 'select', 'insert', 'update', 
            'delete', 'drop', 'create', 'alter', 'exec', 'union', 'script'
        ];

        return in_array(strtolower($name), $reservedNames);
    }

    /**
     * 檢查是否會造成循環依賴
     */
    protected function wouldCreateCircularDependency(?Role $role, int $parentId): bool
    {
        if (!$role) {
            return false;
        }

        // 不能設定自己為父角色
        if ($role->id === $parentId) {
            return true;
        }

        // 檢查是否會造成循環
        $parentRole = Role::find($parentId);
        if (!$parentRole) {
            return false;
        }

        // 遞迴檢查父角色的祖先
        $ancestors = $this->getRoleAncestors($parentRole);
        return in_array($role->id, $ancestors);
    }

    /**
     * 取得角色的所有祖先ID
     */
    protected function getRoleAncestors(Role $role): array
    {
        $ancestors = [];
        $current = $role;

        while ($current->parent_id) {
            $ancestors[] = $current->parent_id;
            $current = Role::find($current->parent_id);
            
            if (!$current) {
                break;
            }

            // 防止無限迴圈
            if (in_array($current->id, $ancestors)) {
                break;
            }
        }

        return $ancestors;
    }

    /**
     * 驗證權限業務邏輯
     */
    protected function validatePermissionBusinessLogic(array $data, Role $role): void
    {
        // 如果是系統角色，檢查核心權限
        if ($role->is_system_role) {
            $corePermissions = Permission::whereIn('name', [
                'admin.access', 'roles.view', 'users.view', 'system.manage'
            ])->pluck('id')->toArray();

            if ($data['operation'] === 'remove' || $data['operation'] === 'replace') {
                $removingCorePermissions = array_intersect($data['permissions'], $corePermissions);
                if (!empty($removingCorePermissions)) {
                    throw ValidationException::withMessages([
                        'permissions' => '不能移除系統角色的核心權限'
                    ]);
                }
            }
        }

        // 檢查權限數量限制
        if (count($data['permissions']) > 100) {
            throw ValidationException::withMessages([
                'permissions' => '一次最多只能操作100個權限'
            ]);
        }
    }

    /**
     * 驗證批量操作業務邏輯
     */
    protected function validateBulkOperationBusinessLogic(array $data): void
    {
        $roleIds = $data['role_ids'];
        $operation = $data['operation'];

        // 檢查是否包含系統角色
        $systemRoles = Role::whereIn('id', $roleIds)
            ->where('is_system_role', true)
            ->count();

        if ($systemRoles > 0) {
            $restrictedOperations = ['delete', 'deactivate'];
            if (in_array($operation, $restrictedOperations)) {
                throw ValidationException::withMessages([
                    'role_ids' => '不能對系統角色執行此操作'
                ]);
            }
        }

        // 檢查是否有使用者關聯的角色（針對刪除操作）
        if ($operation === 'delete') {
            $rolesWithUsers = Role::whereIn('id', $roleIds)
                ->whereHas('users')
                ->count();

            if ($rolesWithUsers > 0) {
                throw ValidationException::withMessages([
                    'role_ids' => '選擇的角色中有些仍有使用者關聯，無法刪除'
                ]);
            }
        }
    }

    /**
     * 驗證搜尋和篩選參數
     */
    public function validateSearchAndFilterData(array $data): array
    {
        $validator = Validator::make($data, [
            'search' => 'nullable|string|max:100',
            'permission_count_filter' => 'nullable|string|in:all,none,low,medium,high',
            'user_count_filter' => 'nullable|string|in:all,none,low,medium,high',
            'system_role_filter' => 'nullable|string|in:all,system,custom',
            'status_filter' => 'nullable|string|in:all,active,inactive',
            'sort_field' => 'nullable|string|in:name,display_name,created_at,updated_at,users_count,permissions_count',
            'sort_direction' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 清理搜尋字串
        if (isset($data['search'])) {
            $data['search'] = $this->sanitizeSearchString($data['search']);
        }

        return $data;
    }

    /**
     * 清理搜尋字串
     */
    protected function sanitizeSearchString(string $search): string
    {
        // 移除危險字元
        $search = $this->removeDangerousContent($search);
        
        // 移除多餘的空白
        $search = preg_replace('/\s+/', ' ', trim($search));
        
        // 限制長度
        return substr($search, 0, 100);
    }
}