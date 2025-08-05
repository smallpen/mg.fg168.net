<?php

namespace App\Http\Livewire\Admin\Roles;

use App\Http\Livewire\Admin\AdminComponent;
use App\Models\Role;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;

/**
 * 角色列表 Livewire 元件
 * 
 * 顯示角色列表，支援搜尋、篩選和分頁功能
 */
class RoleList extends AdminComponent
{
    use WithPagination;

    /**
     * 搜尋關鍵字
     * 
     * @var string
     */
    public $search = '';

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
        'roleDeleted' => '$refresh',
        'roleUpdated' => '$refresh',
    ];

    /**
     * 元件掛載
     */
    public function mount()
    {
        parent::mount();
        
        // 檢查角色檢視權限
        if (!$this->hasPermission('roles.view')) {
            abort(403, __('admin.roles.no_permission_view'));
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
        $this->statusFilter = '';
        $this->resetPage();
    }

    /**
     * 切換角色狀態（啟用/停用）
     * 
     * @param int $roleId
     */
    public function toggleRoleStatus($roleId)
    {
        if (!$this->hasPermission('roles.edit')) {
            $this->showError(__('admin.roles.no_permission_edit'));
            return;
        }

        $role = Role::findOrFail($roleId);
        
        // 防止停用超級管理員角色
        if ($role->name === 'super_admin') {
            $this->showError(__('admin.roles.cannot_disable_super_admin'));
            return;
        }

        // 檢查是否有 is_active 欄位，如果沒有則新增
        if (!$role->getConnection()->getSchemaBuilder()->hasColumn('roles', 'is_active')) {
            // 如果資料表沒有 is_active 欄位，我們暫時跳過這個功能
            $this->showError(__('admin.roles.status_not_supported'));
            return;
        }

        $role->update(['is_active' => !$role->is_active]);
        
        $statusMessage = $role->is_active 
            ? __('admin.roles.role_activated') 
            : __('admin.roles.role_deactivated');
        
        $this->showSuccess($statusMessage . ': ' . $role->display_name);
    }

    /**
     * 取得角色列表
     * 
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getRolesProperty()
    {
        return Role::query()
            ->withCount(['users', 'permissions'])
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                             ->orWhere('display_name', 'like', '%' . $this->search . '%')
                             ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== '', function (Builder $query) {
                // 如果資料表有 is_active 欄位才進行篩選
                if ($query->getModel()->getConnection()->getSchemaBuilder()->hasColumn('roles', 'is_active')) {
                    $query->where('is_active', $this->statusFilter === '1');
                }
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    /**
     * 取得狀態選項
     * 
     * @return array
     */
    public function getStatusOptionsProperty()
    {
        return [
            '' => __('admin.roles.all_status'),
            '1' => __('admin.roles.active'),
            '0' => __('admin.roles.inactive'),
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
     * 檢查是否支援狀態管理
     * 
     * @return bool
     */
    public function getSupportsStatusProperty()
    {
        return Role::first()?->getConnection()->getSchemaBuilder()->hasColumn('roles', 'is_active') ?? false;
    }

    /**
     * 渲染元件
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.admin.roles.role-list', [
            'roles' => $this->roles,
            'statusOptions' => $this->statusOptions,
            'supportsStatus' => $this->supportsStatus,
        ]);
    }
}