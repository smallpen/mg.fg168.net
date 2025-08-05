<?php

namespace App\Http\Livewire\Admin\Users;

use App\Http\Livewire\Admin\AdminComponent;
use App\Models\User;
use App\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

/**
 * 使用者表單 Livewire 元件
 * 
 * 用於建立和編輯使用者，支援即時驗證和角色指派
 */
class UserForm extends AdminComponent
{
    /**
     * 使用者 ID（編輯模式時使用）
     * 
     * @var int|null
     */
    public $userId;

    /**
     * 使用者名稱
     * 
     * @var string
     */
    public $username = '';

    /**
     * 姓名
     * 
     * @var string
     */
    public $name = '';

    /**
     * 電子郵件
     * 
     * @var string
     */
    public $email = '';

    /**
     * 密碼
     * 
     * @var string
     */
    public $password = '';

    /**
     * 確認密碼
     * 
     * @var string
     */
    public $password_confirmation = '';

    /**
     * 是否啟用
     * 
     * @var bool
     */
    public $is_active = true;

    /**
     * 選中的角色 ID 陣列
     * 
     * @var array
     */
    public $selectedRoles = [];

    /**
     * 是否為編輯模式
     * 
     * @var bool
     */
    public $isEditMode = false;

    /**
     * 即時驗證規則
     * 
     * @var array
     */
    protected $rules = [
        'username' => 'required|string|min:3|max:20|regex:/^[a-zA-Z0-9_]+$/',
        'name' => 'nullable|string|max:255',
        'email' => 'nullable|email|max:255',
        'password' => 'nullable|string|min:8|confirmed',
        'is_active' => 'boolean',
        'selectedRoles' => 'array',
        'selectedRoles.*' => 'exists:roles,id',
    ];

    /**
     * 驗證訊息
     * 
     * @var array
     */
    protected $messages = [
        'username.required' => '使用者名稱為必填欄位',
        'username.min' => '使用者名稱至少需要 3 個字元',
        'username.max' => '使用者名稱不能超過 20 個字元',
        'username.regex' => '使用者名稱只能包含字母、數字和底線',
        'username.unique' => '此使用者名稱已被使用',
        'name.max' => '姓名不能超過 255 個字元',
        'email.email' => '請輸入有效的電子郵件地址',
        'email.max' => '電子郵件不能超過 255 個字元',
        'email.unique' => '此電子郵件已被使用',
        'password.min' => '密碼至少需要 8 個字元',
        'password.confirmed' => '密碼確認不符',
        'selectedRoles.*.exists' => '選擇的角色不存在',
    ];

    /**
     * 元件掛載
     * 
     * @param int|null $userId
     */
    public function mount($userId = null)
    {
        parent::mount();

        // 檢查權限
        if ($userId) {
            if (!$this->hasPermission('users.edit')) {
                abort(403, __('admin.users.no_permission_edit'));
            }
            $this->loadUser($userId);
        } else {
            if (!$this->hasPermission('users.create')) {
                abort(403, __('admin.users.no_permission_create'));
            }
        }
    }

    /**
     * 載入使用者資料
     * 
     * @param int $userId
     */
    private function loadUser($userId)
    {
        $user = User::with('roles')->findOrFail($userId);

        // 防止編輯超級管理員（除非自己也是超級管理員）
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            abort(403, __('admin.users.cannot_modify_super_admin'));
        }

        $this->userId = $user->id;
        $this->username = $user->username;
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->is_active = $user->is_active;
        $this->selectedRoles = $user->roles->pluck('id')->toArray();
        $this->isEditMode = true;
    }

    /**
     * 即時驗證使用者名稱
     */
    public function updatedUsername()
    {
        $this->validateOnly('username', [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:20',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('users', 'username')->ignore($this->userId),
            ],
        ]);
    }

    /**
     * 即時驗證電子郵件
     */
    public function updatedEmail()
    {
        if (!empty($this->email)) {
            $this->validateOnly('email', [
                'email' => [
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($this->userId),
                ],
            ]);
        }
    }

    /**
     * 即時驗證密碼
     */
    public function updatedPassword()
    {
        if (!empty($this->password)) {
            $this->validateOnly('password');
        }
    }

    /**
     * 即時驗證密碼確認
     */
    public function updatedPasswordConfirmation()
    {
        if (!empty($this->password_confirmation)) {
            $this->validateOnly('password');
        }
    }

    /**
     * 取得驗證規則
     * 
     * @return array
     */
    protected function getValidationRules()
    {
        $rules = [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:20',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('users', 'username')->ignore($this->userId),
            ],
            'name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'selectedRoles' => 'array',
            'selectedRoles.*' => 'exists:roles,id',
        ];

        // 電子郵件驗證（如果有填寫）
        if (!empty($this->email)) {
            $rules['email'] = [
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->userId),
            ];
        }

        // 密碼驗證
        if ($this->isEditMode) {
            // 編輯模式：密碼為選填
            if (!empty($this->password)) {
                $rules['password'] = 'string|min:8|confirmed';
            }
        } else {
            // 建立模式：密碼為必填
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        return $rules;
    }

    /**
     * 儲存使用者
     */
    public function save()
    {
        $this->validate($this->getValidationRules(), $this->messages);

        try {
            DB::beginTransaction();

            $userData = [
                'username' => $this->username,
                'name' => $this->name ?: null,
                'email' => $this->email ?: null,
                'is_active' => $this->is_active,
            ];

            // 處理密碼
            if (!empty($this->password)) {
                $userData['password'] = Hash::make($this->password);
            }

            if ($this->isEditMode) {
                // 更新使用者
                $user = User::findOrFail($this->userId);
                
                // 防止修改超級管理員（除非自己也是超級管理員）
                if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
                    throw new \Exception(__('admin.users.cannot_modify_super_admin'));
                }

                // 防止停用自己的帳號
                if ($user->id === auth()->id() && !$this->is_active) {
                    throw new \Exception(__('admin.users.cannot_disable_self'));
                }

                $user->update($userData);
                $message = __('admin.messages.success.updated', ['item' => '使用者']);
            } else {
                // 建立使用者
                $user = User::create($userData);
                $message = __('admin.messages.success.created', ['item' => '使用者']);
            }

            // 同步角色
            $this->syncUserRoles($user);

            DB::commit();

            $this->showSuccess($message);

            // 重新導向到使用者列表
            return redirect()->route('admin.users.index');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->showError($e->getMessage());
        }
    }

    /**
     * 同步使用者角色
     * 
     * @param User $user
     */
    private function syncUserRoles(User $user)
    {
        // 檢查是否有權限管理角色
        if (!$this->hasPermission('users.assign_roles')) {
            return;
        }

        // 防止移除自己的超級管理員角色
        if ($user->id === auth()->id() && $user->isSuperAdmin()) {
            $superAdminRole = Role::where('name', 'super_admin')->first();
            if ($superAdminRole && !in_array($superAdminRole->id, $this->selectedRoles)) {
                $this->selectedRoles[] = $superAdminRole->id;
            }
        }

        // 同步角色
        $user->roles()->sync($this->selectedRoles);
    }

    /**
     * 取得所有可用角色
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableRolesProperty()
    {
        $query = Role::orderBy('display_name');

        // 非超級管理員不能指派超級管理員角色
        if (!auth()->user()->isSuperAdmin()) {
            $query->where('name', '!=', 'super_admin');
        }

        return $query->get();
    }

    /**
     * 檢查角色是否被選中
     * 
     * @param int $roleId
     * @return bool
     */
    public function isRoleSelected($roleId)
    {
        return in_array($roleId, $this->selectedRoles);
    }

    /**
     * 切換角色選擇
     * 
     * @param int $roleId
     */
    public function toggleRole($roleId)
    {
        if (in_array($roleId, $this->selectedRoles)) {
            $this->selectedRoles = array_diff($this->selectedRoles, [$roleId]);
        } else {
            $this->selectedRoles[] = $roleId;
        }
    }

    /**
     * 重設表單
     */
    public function resetForm()
    {
        $this->reset([
            'username',
            'name', 
            'email',
            'password',
            'password_confirmation',
            'selectedRoles'
        ]);
        
        $this->is_active = true;
        $this->resetErrorBag();
    }

    /**
     * 取得密碼強度指示器
     * 
     * @return array
     */
    public function getPasswordStrengthProperty()
    {
        if (empty($this->password)) {
            return ['strength' => 0, 'label' => '', 'color' => ''];
        }

        $score = 0;
        $checks = [
            'length' => strlen($this->password) >= 8,
            'lowercase' => preg_match('/[a-z]/', $this->password),
            'uppercase' => preg_match('/[A-Z]/', $this->password),
            'numbers' => preg_match('/\d/', $this->password),
            'special' => preg_match('/[^a-zA-Z\d]/', $this->password),
        ];

        $score = array_sum($checks);

        $levels = [
            0 => ['label' => '', 'color' => ''],
            1 => ['label' => '非常弱', 'color' => 'red'],
            2 => ['label' => '弱', 'color' => 'orange'],
            3 => ['label' => '普通', 'color' => 'yellow'],
            4 => ['label' => '強', 'color' => 'blue'],
            5 => ['label' => '非常強', 'color' => 'green'],
        ];

        return [
            'strength' => $score,
            'label' => $levels[$score]['label'],
            'color' => $levels[$score]['color'],
            'checks' => $checks,
        ];
    }

    /**
     * 渲染元件
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.admin.users.user-form', [
            'availableRoles' => $this->availableRoles,
            'passwordStrength' => $this->passwordStrength,
        ]);
    }
}