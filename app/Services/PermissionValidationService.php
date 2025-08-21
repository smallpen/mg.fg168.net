<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\PermissionDependency;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

/**
 * 權限驗證服務
 * 
 * 提供權限相關的資料驗證功能
 */
class PermissionValidationService
{
    /**
     * 驗證權限建立資料
     * 
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function validateCreateData(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z_\.]+$/',
                'unique:permissions,name'
            ],
            'display_name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'module' => ['required', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:view,create,edit,delete,manage'],
            'dependencies' => ['array'],
            'dependencies.*' => ['integer', 'exists:permissions,id'],
        ], $this->getValidationMessages());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $this->sanitizePermissionData($validator->validated());
    }

    /**
     * 驗證權限更新資料
     * 
     * @param Permission $permission
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function validateUpdateData(Permission $permission, array $data): array
    {
        $rules = [
            'display_name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:view,create,edit,delete,manage'],
            'dependencies' => ['array'],
            'dependencies.*' => ['integer', 'exists:permissions,id'],
        ];

        // 系統權限的特殊規則
        if (!$permission->is_system_permission) {
            $rules['name'] = [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z_\.]+$/',
                Rule::unique('permissions')->ignore($permission->id)
            ];
            $rules['module'] = ['required', 'string', 'max:50'];
        }

        $validator = Validator::make($data, $rules, $this->getValidationMessages());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $this->sanitizePermissionData($validator->validated());
    }

    /**
     * 驗證權限依賴關係
     * 
     * @param int $permissionId
     * @param array $dependencyIds
     * @return bool
     * @throws ValidationException
     */
    public function validateDependencies(int $permissionId, array $dependencyIds): bool
    {
        // 驗證依賴權限 ID 格式
        $validator = Validator::make(['dependencies' => $dependencyIds], [
            'dependencies' => ['array'],
            'dependencies.*' => ['integer', 'exists:permissions,id'],
        ], [
            'dependencies.array' => '依賴權限格式錯誤',
            'dependencies.*.integer' => '依賴權限 ID 必須為整數',
            'dependencies.*.exists' => '選擇的依賴權限不存在',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 檢查循環依賴
        foreach ($dependencyIds as $dependencyId) {
            if (!PermissionDependency::validateNoCycle($permissionId, $dependencyId)) {
                throw ValidationException::withMessages([
                    'dependencies' => ['不能建立循環依賴關係']
                ]);
            }
        }

        return true;
    }

    /**
     * 驗證權限名稱格式
     * 
     * @param string $name
     * @return bool
     * @throws ValidationException
     */
    public function validatePermissionName(string $name): bool
    {
        $validator = Validator::make(['name' => $name], [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z_\.]+$/',
            ]
        ], [
            'name.required' => '權限名稱為必填項目',
            'name.max' => '權限名稱不能超過 100 個字元',
            'name.regex' => '權限名稱只能包含小寫字母、底線和點號',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return true;
    }

    /**
     * 驗證權限名稱唯一性
     * 
     * @param string $name
     * @param int|null $excludeId
     * @return bool
     * @throws ValidationException
     */
    public function validatePermissionNameUnique(string $name, ?int $excludeId = null): bool
    {
        $query = Permission::where('name', $name);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => ['權限名稱已存在']
            ]);
        }

        return true;
    }

    /**
     * 驗證模組名稱
     * 
     * @param string $module
     * @return bool
     * @throws ValidationException
     */
    public function validateModule(string $module): bool
    {
        $allowedModules = $this->getAllowedModules();
        
        $validator = Validator::make(['module' => $module], [
            'module' => [
                'required',
                'string',
                'max:50',
                'in:' . implode(',', array_keys($allowedModules))
            ]
        ], [
            'module.required' => '模組為必填項目',
            'module.max' => '模組名稱不能超過 50 個字元',
            'module.in' => '無效的模組名稱',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return true;
    }

    /**
     * 驗證權限類型
     * 
     * @param string $type
     * @return bool
     * @throws ValidationException
     */
    public function validatePermissionType(string $type): bool
    {
        $allowedTypes = ['view', 'create', 'edit', 'delete', 'manage'];
        
        $validator = Validator::make(['type' => $type], [
            'type' => [
                'required',
                'string',
                'in:' . implode(',', $allowedTypes)
            ]
        ], [
            'type.required' => '權限類型為必填項目',
            'type.in' => '無效的權限類型',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return true;
    }

    /**
     * 驗證權限是否可以刪除
     * 
     * @param Permission $permission
     * @return bool
     * @throws ValidationException
     */
    public function validateCanDelete(Permission $permission): bool
    {
        $errors = [];

        // 檢查是否為系統權限
        if ($permission->is_system_permission) {
            $errors[] = '不能刪除系統核心權限';
        }

        // 檢查是否被角色使用
        if ($permission->roles()->exists()) {
            $roleNames = $permission->roles()->pluck('name')->toArray();
            $errors[] = '權限仍被以下角色使用：' . implode('、', $roleNames);
        }

        // 檢查是否被其他權限依賴
        if ($permission->dependents()->exists()) {
            $dependentNames = $permission->dependents()->pluck('name')->toArray();
            $errors[] = '以下權限依賴此權限：' . implode('、', $dependentNames);
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages([
                'permission' => $errors
            ]);
        }

        return true;
    }

    /**
     * 驗證批量操作資料
     * 
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function validateBulkOperation(array $data): array
    {
        $validator = Validator::make($data, [
            'action' => 'required|string|in:delete,export',
            'permission_ids' => 'required|array|max:100',
            'permission_ids.*' => 'required|integer|exists:permissions,id',
            'confirm' => 'required_if:action,delete|boolean',
        ], [
            'action.required' => '請選擇操作類型',
            'action.in' => '無效的操作類型',
            'permission_ids.required' => '請選擇要操作的權限',
            'permission_ids.max' => '一次最多只能操作 100 個權限',
            'permission_ids.*.exists' => '選擇的權限不存在',
            'confirm.required_if' => '刪除操作需要確認',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * 驗證匯入資料
     * 
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function validateImportData(array $data): array
    {
        $validatedData = [];
        $errors = [];

        foreach ($data as $index => $permissionData) {
            try {
                $validator = Validator::make($permissionData, [
                    'name' => [
                        'required',
                        'string',
                        'max:100',
                        'regex:/^[a-z_\.]+$/',
                    ],
                    'display_name' => ['required', 'string', 'max:50'],
                    'description' => ['nullable', 'string', 'max:255'],
                    'module' => ['required', 'string', 'max:50'],
                    'type' => ['required', 'string', 'in:view,create,edit,delete,manage'],
                    'dependencies' => ['array'],
                    'dependencies.*' => ['string'], // 匯入時使用權限名稱
                ], $this->getValidationMessages());

                if ($validator->fails()) {
                    $errors["row_{$index}"] = $validator->errors()->toArray();
                } else {
                    $validatedData[] = $this->sanitizePermissionData($validator->validated());
                }
            } catch (\Exception $e) {
                $errors["row_{$index}"] = ['general' => [$e->getMessage()]];
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $validatedData;
    }

    /**
     * 取得驗證訊息
     * 
     * @return array
     */
    private function getValidationMessages(): array
    {
        return [
            'name.required' => '權限名稱為必填項目',
            'name.regex' => '權限名稱只能包含小寫字母、底線和點號',
            'name.unique' => '權限名稱已存在',
            'name.max' => '權限名稱不能超過 100 個字元',
            'display_name.required' => '顯示名稱為必填項目',
            'display_name.max' => '顯示名稱不能超過 50 個字元',
            'description.max' => '描述不能超過 255 個字元',
            'module.required' => '模組為必填項目',
            'module.max' => '模組名稱不能超過 50 個字元',
            'type.required' => '權限類型為必填項目',
            'type.in' => '無效的權限類型',
            'dependencies.array' => '依賴權限格式錯誤',
            'dependencies.*.integer' => '依賴權限 ID 必須為整數',
            'dependencies.*.exists' => '選擇的依賴權限不存在',
        ];
    }

    /**
     * 清理權限資料
     * 
     * @param array $data
     * @return array
     */
    private function sanitizePermissionData(array $data): array
    {
        // 清理字串欄位
        if (isset($data['name'])) {
            $data['name'] = trim(strtolower($data['name']));
        }

        if (isset($data['display_name'])) {
            $data['display_name'] = trim($data['display_name']);
        }

        if (isset($data['description'])) {
            $data['description'] = trim($data['description']) ?: null;
        }

        if (isset($data['module'])) {
            $data['module'] = trim(strtolower($data['module']));
        }

        if (isset($data['type'])) {
            $data['type'] = trim(strtolower($data['type']));
        }

        // 清理依賴陣列
        if (isset($data['dependencies'])) {
            $data['dependencies'] = array_values(array_unique(array_filter($data['dependencies'])));
        }

        return $data;
    }

    /**
     * 取得允許的模組列表
     * 
     * @return array
     */
    private function getAllowedModules(): array
    {
        return [
            'users' => '使用者管理',
            'roles' => '角色管理',
            'permissions' => '權限管理',
            'dashboard' => '儀表板',
            'system' => '系統管理',
            'reports' => '報表管理',
            'settings' => '設定管理',
            'audit' => '審計管理',
            'monitoring' => '監控管理',
            'security' => '安全管理',
        ];
    }
}