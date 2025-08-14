<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Models\Role;
use App\Repositories\UserRepository;
use Livewire\Component;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

/**
 * 使用者表單 Livewire 元件
 * 
 * 處理使用者的建立和編輯功能
 */
class UserForm extends Component
{
    public ?User $user = null;
    public bool $isEditing = false;
    
    // 表單欄位
    public string $username = '';
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $is_active = true;
    public array $selectedRoles = [];
    
    // 其他屬性
    public bool $showPassword = false;
    
    protected UserRepository $userRepository;

    /**
     * 元件初始化
     */
    public function boot(UserRepository $userRepository): void
    {
        $this->userRepository = $userRepository;
    }

    /**
     * 元件掛載
     */
    public function mount(?User $user = null): void
    {
        if ($user) {
            $this->user = $user->load('roles');
            $this->isEditing = true;
            $this->loadUserData();
            
            // 檢查編輯權限
            if (!auth()->user()->hasPermission('users.edit')) {
                abort(403, __('admin.users.no_permission_edit'));
            }
        } else {
            // 檢查建立權限
            if (!auth()->user()->hasPermission('users.create')) {
                abort(403, __('admin.users.no_permission_create'));
            }
        }
    }

    /**
     * 載入使用者資料到表單
     */
    protected function loadUserData(): void
    {
        if ($this->user) {
            $this->username = $this->user->getAttributeValue('username') ?? '';
            $this->name = $this->user->name ?? '';
            $this->email = $this->user->email ?? '';
            $this->is_active = (bool) $this->user->is_active;
            $this->selectedRoles = $this->user->roles->pluck('id')->toArray();
        }
    }

    /**
     * 取得所有可用角色
     */
    public function getAvailableRolesProperty()
    {
        return Role::orderBy('display_name')->get();
    }

    /**
     * 驗證規則
     */
    protected function rules(): array
    {
        $rules = [
            'username' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($this->user?->id),
            ],
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user?->id),
            ],
            'is_active' => 'boolean',
            'selectedRoles' => 'array',
            'selectedRoles.*' => 'exists:roles,id',
        ];

        // 密碼驗證規則
        if (!$this->isEditing) {
            // 建立新使用者時密碼為必填
            $rules['password'] = 'required|string|min:8|confirmed';
            $rules['password_confirmation'] = 'required';
        } else {
            // 編輯使用者時密碼為選填
            $rules['password'] = 'nullable|string|min:8|confirmed';
            $rules['password_confirmation'] = 'nullable';
        }

        return $rules;
    }

    /**
     * 自訂驗證訊息
     */
    protected function messages(): array
    {
        return [
            'username.required' => '使用者名稱為必填欄位',
            'username.unique' => '此使用者名稱已被使用',
            'username.alpha_dash' => '使用者名稱只能包含字母、數字、破折號和底線',
            'name.required' => '姓名為必填欄位',
            'email.email' => '請輸入有效的電子郵件地址',
            'email.unique' => '此電子郵件地址已被使用',
            'password.required' => '密碼為必填欄位',
            'password.min' => '密碼至少需要 8 個字元',
            'password.confirmed' => '密碼確認不符',
            'password_confirmation.required' => '請確認密碼',
            'selectedRoles.*.exists' => '選擇的角色無效',
        ];
    }

    /**
     * 儲存使用者
     */
    public function save()
    {
        $this->validate();

        try {
            $userData = [
                'username' => $this->username,
                'name' => $this->name,
                'email' => $this->email ?: null,
                'is_active' => $this->is_active,
            ];

            // 處理密碼
            if (!empty($this->password)) {
                $userData['password'] = Hash::make($this->password);
            }

            if ($this->isEditing) {
                // 更新使用者
                $this->user->update($userData);
                
                // 同步角色
                $this->user->roles()->sync($this->selectedRoles);
                
                $message = __('admin.messages.success.updated', ['item' => __('admin.users.user')]);
            } else {
                // 建立新使用者
                $user = User::create($userData);
                
                // 分配角色
                if (!empty($this->selectedRoles)) {
                    $user->roles()->sync($this->selectedRoles);
                }
                
                $this->user = $user;
                $message = __('admin.messages.success.created', ['item' => __('admin.users.user')]);
            }

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => $message
            ]);

            // 重定向到使用者列表
            return $this->redirect(route('admin.users.index'));

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => $this->isEditing 
                    ? __('admin.messages.error.update_failed', ['item' => __('admin.users.user')])
                    : __('admin.messages.error.create_failed', ['item' => __('admin.users.user')])
            ]);
        }
    }

    /**
     * 取消操作
     */
    public function cancel()
    {
        return $this->redirect(route('admin.users.index'));
    }

    /**
     * 切換密碼顯示
     */
    public function togglePasswordVisibility(): void
    {
        $this->showPassword = !$this->showPassword;
    }

    /**
     * 檢查是否可以修改使用者狀態
     */
    public function getCanModifyStatusProperty(): bool
    {
        if (!$this->user) {
            return true;
        }

        // 不能停用自己
        if ($this->user->id === auth()->id()) {
            return false;
        }

        // 非超級管理員不能修改超級管理員
        if ($this->user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * 檢查是否可以修改角色
     */
    public function getCanModifyRolesProperty(): bool
    {
        if (!$this->user) {
            return true;
        }

        // 非超級管理員不能修改超級管理員的角色
        if ($this->user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.users.user-form', [
            'availableRoles' => $this->availableRoles,
        ])->layout('admin.layouts.app', [
            'title' => $this->isEditing ? __('admin.users.edit') : __('admin.users.create')
        ]);
    }
}