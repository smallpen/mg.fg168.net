<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * 使用者服務類別
 * 
 * 處理使用者相關的業務邏輯，包括使用者建立、更新、刪除等功能
 * 實作使用者角色指派和權限檢查邏輯
 */
class UserService
{
    /**
     * 權限服務實例
     * 
     * @var PermissionService
     */
    protected PermissionService $permissionService;

    /**
     * 儀表板服務實例
     * 
     * @var DashboardService
     */
    protected DashboardService $dashboardService;

    /**
     * 建構函式
     * 
     * @param PermissionService $permissionService
     * @param DashboardService $dashboardService
     */
    public function __construct(
        PermissionService $permissionService,
        DashboardService $dashboardService
    ) {
        $this->permissionService = $permissionService;
        $this->dashboardService = $dashboardService;
    }

    /**
     * 建立新使用者
     * 
     * @param array $data
     * @return User
     * @throws ValidationException
     * @throws \Exception
     */
    public function createUser(array $data): User
    {
        // 驗證輸入資料
        $this->validateUserData($data);

        try {
            DB::beginTransaction();

            // 建立使用者
            $user = User::create([
                'username' => $data['username'],
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['password']),
                'theme_preference' => $data['theme_preference'] ?? 'light',
                'locale' => $data['locale'] ?? 'zh_TW',
                'is_active' => $data['is_active'] ?? true,
            ]);

            // 指派角色（如果有提供）
            if (!empty($data['roles'])) {
                $this->assignRolesToUser($user, $data['roles']);
            }

            DB::commit();

            // 清除相關快取
            $this->clearUserRelatedCache($user);

            Log::info('User created successfully', [
                'user_id' => $user->id,
                'username' => $user->username,
                'created_by' => auth()->id(),
                'roles' => $data['roles'] ?? []
            ]);

            return $user;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create user', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * 更新使用者資訊
     * 
     * @param User $user
     * @param array $data
     * @return User
     * @throws ValidationException
     * @throws \Exception
     */
    public function updateUser(User $user, array $data): User
    {
        // 驗證輸入資料（更新時）
        $this->validateUserData($data, $user->id);

        try {
            DB::beginTransaction();

            // 準備更新資料
            $updateData = [];
            
            if (isset($data['username'])) {
                $updateData['username'] = $data['username'];
            }
            
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            
            if (isset($data['email'])) {
                $updateData['email'] = $data['email'];
            }
            
            if (isset($data['password']) && !empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }
            
            if (isset($data['theme_preference'])) {
                $updateData['theme_preference'] = $data['theme_preference'];
            }
            
            if (isset($data['locale'])) {
                $updateData['locale'] = $data['locale'];
            }
            
            if (isset($data['is_active'])) {
                $updateData['is_active'] = $data['is_active'];
            }

            // 更新使用者資訊
            $user->update($updateData);

            // 更新角色（如果有提供）
            if (isset($data['roles'])) {
                $this->syncUserRoles($user, $data['roles']);
            }

            DB::commit();

            // 清除相關快取
            $this->clearUserRelatedCache($user);

            Log::info('User updated successfully', [
                'user_id' => $user->id,
                'username' => $user->username,
                'updated_by' => auth()->id(),
                'updated_fields' => array_keys($updateData),
                'roles_updated' => isset($data['roles'])
            ]);

            return $user->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update user', [
                'user_id' => $user->id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 刪除使用者
     * 
     * @param User $user
     * @param bool $forceDelete 是否強制刪除（硬刪除）
     * @return bool
     * @throws \Exception
     */
    public function deleteUser(User $user, bool $forceDelete = false): bool
    {
        // 檢查是否為超級管理員（不能刪除）
        if ($user->isSuperAdmin()) {
            throw new \InvalidArgumentException('Cannot delete super admin user');
        }

        // 檢查是否為當前登入使用者（不能刪除自己）
        if (auth()->id() === $user->username) {
            throw new \InvalidArgumentException('Cannot delete current user');
        }

        try {
            DB::beginTransaction();

            $userId = $user->id;
            $username = $user->username;

            if ($forceDelete) {
                // 硬刪除：移除所有關聯並刪除使用者
                $user->roles()->detach();
                $user->forceDelete();
            } else {
                // 軟刪除：停用使用者
                $user->update(['is_active' => false]);
            }

            DB::commit();

            // 清除相關快取
            $this->clearUserRelatedCache($user);

            Log::info('User deleted successfully', [
                'user_id' => $userId,
                'username' => $username,
                'deleted_by' => auth()->id(),
                'force_delete' => $forceDelete
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete user', [
                'user_id' => $user->id,
                'force_delete' => $forceDelete,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 啟用使用者
     * 
     * @param User $user
     * @return bool
     * @throws \Exception
     */
    public function activateUser(User $user): bool
    {
        try {
            $user->update(['is_active' => true]);

            // 清除相關快取
            $this->clearUserRelatedCache($user);

            Log::info('User activated', [
                'user_id' => $user->id,
                'username' => $user->username,
                'activated_by' => auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to activate user', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 停用使用者
     * 
     * @param User $user
     * @return bool
     * @throws \Exception
     */
    public function deactivateUser(User $user): bool
    {
        // 檢查是否為超級管理員（不能停用）
        if ($user->isSuperAdmin()) {
            throw new \InvalidArgumentException('Cannot deactivate super admin user');
        }

        // 檢查是否為當前登入使用者（不能停用自己）
        if (auth()->id() === $user->username) {
            throw new \InvalidArgumentException('Cannot deactivate current user');
        }

        try {
            $user->update(['is_active' => false]);

            // 清除相關快取
            $this->clearUserRelatedCache($user);

            Log::info('User deactivated', [
                'user_id' => $user->id,
                'username' => $user->username,
                'deactivated_by' => auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to deactivate user', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 為使用者指派角色
     * 
     * @param User $user
     * @param array $roleNames
     * @return bool
     * @throws \Exception
     */
    public function assignRolesToUser(User $user, array $roleNames): bool
    {
        try {
            foreach ($roleNames as $roleName) {
                $this->permissionService->assignRole($user, $roleName);
            }

            // 清除相關快取
            $this->clearUserRelatedCache($user);

            Log::info('Roles assigned to user', [
                'user_id' => $user->id,
                'username' => $user->username,
                'roles' => $roleNames,
                'assigned_by' => auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to assign roles to user', [
                'user_id' => $user->id,
                'roles' => $roleNames,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 同步使用者角色
     * 
     * @param User $user
     * @param array $roleNames
     * @return bool
     * @throws \Exception
     */
    public function syncUserRoles(User $user, array $roleNames): bool
    {
        try {
            $this->permissionService->syncRoles($user, $roleNames);

            // 清除相關快取
            $this->clearUserRelatedCache($user);

            Log::info('User roles synchronized', [
                'user_id' => $user->id,
                'username' => $user->username,
                'roles' => $roleNames,
                'synchronized_by' => auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to synchronize user roles', [
                'user_id' => $user->id,
                'roles' => $roleNames,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 移除使用者角色
     * 
     * @param User $user
     * @param string $roleName
     * @return bool
     * @throws \Exception
     */
    public function removeRoleFromUser(User $user, string $roleName): bool
    {
        try {
            $this->permissionService->removeRole($user, $roleName);

            // 清除相關快取
            $this->clearUserRelatedCache($user);

            Log::info('Role removed from user', [
                'user_id' => $user->id,
                'username' => $user->username,
                'role' => $roleName,
                'removed_by' => auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to remove role from user', [
                'user_id' => $user->id,
                'role' => $roleName,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 檢查使用者是否有權限執行特定操作
     * 
     * @param User $user
     * @param string $permission
     * @return bool
     */
    public function userHasPermission(User $user, string $permission): bool
    {
        return $this->permissionService->hasPermission($user, $permission);
    }

    /**
     * 檢查使用者是否可以存取特定模組
     * 
     * @param User $user
     * @param string $module
     * @return bool
     */
    public function userCanAccessModule(User $user, string $module): bool
    {
        return $this->permissionService->canAccessModule($user, $module);
    }

    /**
     * 取得使用者的所有權限
     * 
     * @param User $user
     * @return Collection
     */
    public function getUserPermissions(User $user): Collection
    {
        return $this->permissionService->getUserPermissions($user);
    }

    /**
     * 重設使用者密碼
     * 
     * @param User $user
     * @param string $newPassword
     * @return bool
     * @throws \Exception
     */
    public function resetUserPassword(User $user, string $newPassword): bool
    {
        // 驗證密碼強度
        $this->validatePassword($newPassword);

        try {
            $user->update([
                'password' => Hash::make($newPassword)
            ]);

            Log::info('User password reset', [
                'user_id' => $user->id,
                'username' => $user->username,
                'reset_by' => auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to reset user password', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 更新使用者偏好設定
     * 
     * @param User $user
     * @param array $preferences
     * @return bool
     * @throws \Exception
     */
    public function updateUserPreferences(User $user, array $preferences): bool
    {
        try {
            $updateData = [];

            if (isset($preferences['theme_preference'])) {
                $updateData['theme_preference'] = $preferences['theme_preference'];
            }

            if (isset($preferences['locale'])) {
                $updateData['locale'] = $preferences['locale'];
            }

            if (!empty($updateData)) {
                $user->update($updateData);

                Log::info('User preferences updated', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'preferences' => $updateData
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update user preferences', [
                'user_id' => $user->id,
                'preferences' => $preferences,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 驗證使用者資料
     * 
     * @param array $data
     * @param int|null $userId 更新時的使用者 ID
     * @return void
     * @throws ValidationException
     */
    protected function validateUserData(array $data, ?int $userId = null): void
    {
        $rules = [
            'username' => [
                $userId ? 'sometimes' : 'required',
                'string',
                'min:3',
                'max:20',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:users,username' . ($userId ? ",$userId" : '')
            ],
            'name' => ($userId ? 'sometimes' : 'required') . '|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email' . ($userId ? ",$userId" : '')
            ],
            'password' => $userId ? 'nullable|string|min:8' : 'required|string|min:8',
            'theme_preference' => 'nullable|in:light,dark',
            'locale' => 'nullable|in:zh_TW,en',
            'is_active' => 'nullable|boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name'
        ];

        $messages = [
            'username.required' => '使用者名稱為必填欄位',
            'username.min' => '使用者名稱至少需要 3 個字元',
            'username.max' => '使用者名稱不能超過 20 個字元',
            'username.regex' => '使用者名稱只能包含字母、數字和底線',
            'username.unique' => '此使用者名稱已被使用',
            'name.required' => '姓名為必填欄位',
            'name.max' => '姓名不能超過 255 個字元',
            'email.email' => '請輸入有效的電子郵件地址',
            'email.unique' => '此電子郵件地址已被使用',
            'password.required' => '密碼為必填欄位',
            'password.min' => '密碼至少需要 8 個字元',
            'theme_preference.in' => '主題偏好只能是 light 或 dark',
            'locale.in' => '語言設定只能是 zh_TW 或 en',
            'roles.array' => '角色必須是陣列格式',
            'roles.*.exists' => '指定的角色不存在'
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 額外驗證密碼強度（如果有提供密碼）
        if (!empty($data['password'])) {
            $this->validatePassword($data['password']);
        }
    }

    /**
     * 驗證密碼強度
     * 
     * @param string $password
     * @return void
     * @throws ValidationException
     */
    protected function validatePassword(string $password): void
    {
        $rules = [
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
            ]
        ];

        $messages = [
            'password.required' => '密碼為必填欄位',
            'password.min' => '密碼至少需要 8 個字元',
            'password.regex' => '密碼必須包含至少一個小寫字母、一個大寫字母和一個數字'
        ];

        $validator = Validator::make(['password' => $password], $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * 清除使用者相關快取
     * 
     * @param User $user
     * @return void
     */
    protected function clearUserRelatedCache(User $user): void
    {
        // 清除使用者權限快取
        $this->permissionService->clearUserPermissionCache($user);
        
        // 清除儀表板快取
        $this->dashboardService->clearCache();
        $this->dashboardService->clearUserPermissionsSummaryCache($user);
    }

    /**
     * 取得使用者建立的業務規則驗證結果
     * 
     * @param array $data
     * @return array
     */
    public function validateUserCreationRules(array $data): array
    {
        $errors = [];

        // 檢查使用者名稱是否符合業務規則
        if (isset($data['username'])) {
            if (strlen($data['username']) < 3) {
                $errors['username'][] = '使用者名稱至少需要 3 個字元';
            }
            
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
                $errors['username'][] = '使用者名稱只能包含字母、數字和底線';
            }
            
            if (User::where('username', $data['username'])->exists()) {
                $errors['username'][] = '此使用者名稱已被使用';
            }
        }

        // 檢查角色是否存在
        if (!empty($data['roles'])) {
            $existingRoles = Role::whereIn('name', $data['roles'])->pluck('name')->toArray();
            $nonExistentRoles = array_diff($data['roles'], $existingRoles);
            
            if (!empty($nonExistentRoles)) {
                $errors['roles'][] = '以下角色不存在: ' . implode(', ', $nonExistentRoles);
            }
        }

        return $errors;
    }

    /**
     * 取得使用者刪除前的依賴檢查結果
     * 
     * @param User $user
     * @return array
     */
    public function getUserDeletionDependencies(User $user): array
    {
        $dependencies = [];

        // 檢查是否為超級管理員
        if ($user->isSuperAdmin()) {
            $dependencies['super_admin'] = '無法刪除超級管理員帳號';
        }

        // 檢查是否為當前登入使用者
        if (auth()->id() === $user->username) {
            $dependencies['current_user'] = '無法刪除當前登入的使用者';
        }

        // 檢查是否有相關的活動記錄
        $activityCount = DB::table('activities')->where('user_id', $user->id)->count();
        if ($activityCount > 0) {
            $dependencies['activities'] = "使用者有 {$activityCount} 筆活動記錄";
        }

        return $dependencies;
    }
}