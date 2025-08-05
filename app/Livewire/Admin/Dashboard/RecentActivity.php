<?php

namespace App\Livewire\Admin\Dashboard;

use App\Http\Livewire\Admin\AdminComponent;
use App\Services\ActivityService;
use App\Services\PermissionService;
use Illuminate\Support\Collection;

/**
 * 最近活動元件
 * 
 * 顯示系統最近活動，根據使用者權限動態調整顯示內容
 */
class RecentActivity extends AdminComponent
{
    /**
     * 顯示的活動數量
     * 
     * @var int
     */
    public $limit = 10;

    /**
     * 篩選類型
     * 
     * @var string|null
     */
    public $filterType = null;

    /**
     * 篩選模組
     * 
     * @var string|null
     */
    public $filterModule = null;

    /**
     * 是否只顯示自己的活動
     * 
     * @var bool
     */
    public $onlyMyActivities = false;

    /**
     * 活動服務
     * 
     * @var ActivityService
     */
    protected $activityService;

    /**
     * 權限服務
     * 
     * @var PermissionService
     */
    protected $permissionService;

    /**
     * 元件掛載時執行
     * 
     * @return void
     */
    public function mount()
    {
        $this->activityService = app(ActivityService::class);
        $this->permissionService = app(PermissionService::class);
    }

    /**
     * 取得最近活動
     * 
     * @return Collection
     */
    public function getActivitiesProperty(): Collection
    {
        $user = auth()->user();
        
        // 檢查是否有檢視活動記錄的權限
        if (!$this->permissionService->hasPermission($user, 'system.logs')) {
            // 如果沒有權限，只顯示自己的活動
            return $this->activityService->getUserActivities($user, $this->limit);
        }

        // 有權限的話，可以檢視所有活動
        if ($this->onlyMyActivities) {
            return $this->activityService->getUserActivities($user, $this->limit);
        }

        $activities = $this->activityService->getRecentActivities($this->limit);

        // 應用篩選條件
        if ($this->filterType) {
            $activities = $activities->where('type', $this->filterType);
        }

        if ($this->filterModule) {
            $activities = $activities->where('module', $this->filterModule);
        }

        return $activities;
    }

    /**
     * 取得可用的活動類型
     * 
     * @return array
     */
    public function getActivityTypesProperty(): array
    {
        return [
            'login' => '登入',
            'logout' => '登出',
            'create_user' => '建立使用者',
            'update_user' => '更新使用者',
            'delete_user' => '刪除使用者',
            'create_role' => '建立角色',
            'update_role' => '更新角色',
            'delete_role' => '刪除角色',
            'assign_role' => '指派角色',
            'remove_role' => '移除角色',
            'update_permissions' => '更新權限',
            'view_dashboard' => '檢視儀表板',
            'export_data' => '匯出資料',
        ];
    }

    /**
     * 取得可用的模組
     * 
     * @return array
     */
    public function getModulesProperty(): array
    {
        return [
            'auth' => '認證',
            'users' => '使用者',
            'roles' => '角色',
            'permissions' => '權限',
            'dashboard' => '儀表板',
            'system' => '系統',
        ];
    }

    /**
     * 重新整理活動記錄
     * 
     * @return void
     */
    public function refreshActivities()
    {
        $this->activityService->clearRecentActivitiesCache();
        
        $this->dispatchBrowserEvent('activity-refreshed', [
            'message' => '活動記錄已更新'
        ]);
    }

    /**
     * 設定篩選類型
     * 
     * @param string|null $type
     * @return void
     */
    public function setFilterType(?string $type)
    {
        $this->filterType = $type;
    }

    /**
     * 設定篩選模組
     * 
     * @param string|null $module
     * @return void
     */
    public function setFilterModule(?string $module)
    {
        $this->filterModule = $module;
    }

    /**
     * 切換只顯示自己的活動
     * 
     * @return void
     */
    public function toggleMyActivities()
    {
        $this->onlyMyActivities = !$this->onlyMyActivities;
    }

    /**
     * 清除所有篩選條件
     * 
     * @return void
     */
    public function clearFilters()
    {
        $this->filterType = null;
        $this->filterModule = null;
        $this->onlyMyActivities = false;
    }

    /**
     * 增加顯示數量
     * 
     * @return void
     */
    public function loadMore()
    {
        $this->limit += 10;
    }

    /**
     * 檢查使用者是否可以檢視所有活動
     * 
     * @return bool
     */
    public function getCanViewAllActivitiesProperty(): bool
    {
        return $this->permissionService->hasPermission(auth()->user(), 'system.logs');
    }

    /**
     * 取得活動圖示
     * 
     * @param string $type
     * @return string
     */
    public function getActivityIcon(string $type): string
    {
        return match ($type) {
            'login' => 'login',
            'logout' => 'logout',
            'create_user', 'create_role' => 'plus-circle',
            'update_user', 'update_role', 'update_permissions' => 'pencil',
            'delete_user', 'delete_role' => 'trash',
            'assign_role', 'remove_role' => 'user-group',
            'view_dashboard' => 'chart-bar',
            'export_data' => 'download',
            default => 'information-circle',
        };
    }

    /**
     * 取得活動顏色
     * 
     * @param string $type
     * @return string
     */
    public function getActivityColor(string $type): string
    {
        return match ($type) {
            'login', 'create_user', 'create_role' => 'success',
            'logout' => 'info',
            'update_user', 'update_role', 'update_permissions' => 'warning',
            'delete_user', 'delete_role' => 'danger',
            'assign_role', 'remove_role' => 'primary',
            'view_dashboard', 'export_data' => 'info',
            default => 'gray',
        };
    }

    /**
     * 渲染元件
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.admin.dashboard.recent-activity');
    }
}
