<?php

namespace App\Http\Livewire\Admin\Users;

use App\Http\Livewire\Admin\AdminComponent;
use App\Models\User;
use App\Models\Role;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;

/**
 * 使用者列表 Livewire 元件
 * 
 * 顯示使用者列表，支援搜尋、篩選和分頁功能
 */
class UserList extends AdminComponent
{
    use WithPagination;

    /**
     * 搜尋關鍵字
     * 
     * @var string
     */
    public $search = '';

    /**
     * 角色篩選
     * 
     * @var string
     */
    public $roleFilter = '';

    /**
     * 狀態篩選
     * 
     * @var string
     */
    public $statusFilter = '';

    /**
     * 排序欄位
     * 
     * @var string
     */
    public $sortBy = 'created_at';

    /**
     * 排序方向
     * 
     * @var string
     */
    public $sortDirection = 'desc';

    /**
     * 每頁顯示數量
     * 
     * @var int
     */
    public $perPage = 10;

    /**
     * 監聽的事件
     * 
     * @var array
     */
    protected $listeners = [
        'userDeleted' => '$refresh',
    ];

    /**
     * 元件掛載
     */
    public function mount()
    {
        parent::mount();
        
        // 檢查使用者管理權限
        if (!$this->hasPermission('users.view')) {
            abort(403, __('admin.users.no_permission_view'));
        }
    }

    /**
     * 重設分頁
     */
    public function updatingSearch()
    {
        $this->resetPage();
    }

    /**
     * 重設分頁
     */
    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    /**
     * 重設分頁
     */
    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    /**
     * 排序功能
     * 
     * @param string $field
     */
    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * 清除篩選條件
     */
    public function clearFilters()
    {
        $this->search = '';
        $this->roleFilter = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    /**
     * 切換使用者狀態
     * 
     * @param int $userId
     */
    public function toggleUserStatus($userId)
    {
        if (!$this->hasPermission('users.edit')) {
            $this->showError(__('admin.users.no_permission_edit'));
            return;
        }

        $user = User::findOrFail($userId);
        
        // 防止停用自己的帳號
        if ($user->id === auth()->id()) {
            $this->showError(__('admin.users.cannot_disable_self'));
            return;
        }

        // 防止停用超級管理員（除非自己也是超級管理員）
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            $this->showError(__('admin.users.cannot_modify_super_admin'));
            return;
        }

        $user->update(['is_active' => !$user->is_active]);
        
        $statusMessage = $user->is_active 
            ? __('admin.users.user_activated') 
            : __('admin.users.user_deactivated');
        
        $this->showSuccess($statusMessage . ': ' . $user->display_name);
    }

    /**
     * 取得使用者列表
     * 
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUsersProperty()
    {
        return User::query()
            ->with(['roles'])
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                             ->orWhere('username', 'like', '%' . $this->search . '%')
                             ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->roleFilter, function (Builder $query) {
                $query->whereHas('roles', function (Builder $roleQuery) {
                    $roleQuery->where('name', $this->roleFilter);
                });
            })
            ->when($this->statusFilter !== '', function (Builder $query) {
                $query->where('is_active', $this->statusFilter === '1');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    /**
     * 取得所有角色選項
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRolesProperty()
    {
        return Role::orderBy('display_name')->get();
    }

    /**
     * 取得狀態選項
     * 
     * @return array
     */
    public function getStatusOptionsProperty()
    {
        return [
            '' => '全部狀態',
            '1' => '啟用',
            '0' => '停用',
        ];
    }

    /**
     * 取得排序圖示
     * 
     * @param string $field
     * @return string
     */
    public function getSortIcon($field)
    {
        if ($this->sortBy !== $field) {
            return 'heroicon-o-arrows-up-down';
        }

        return $this->sortDirection === 'asc' 
            ? 'heroicon-o-arrow-up' 
            : 'heroicon-o-arrow-down';
    }

    /**
     * 渲染元件
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.admin.users.user-list', [
            'users' => $this->users,
            'roles' => $this->roles,
            'statusOptions' => $this->statusOptions,
        ]);
    }
}