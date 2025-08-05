<?php

namespace App\Http\Livewire\Admin\Dashboard;

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
     * 是否顯示詳細資訊
     * 
     * @var bool
     */
    public $showDetails = false;

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
     * 取得最近活動記錄
     * 
     * @return Collection
     */
    public function getRecentActivitiesProperty(): Collection
    {
        $user = auth()->user();
        
        // 檢查是否有檢視活動記錄的權限
        if (!$this->permissionService->hasPermission($user, 'system.logs')) {
            // 如果沒有權限，只顯示自己的活動
            return $this->activityService->getUserActivities($user, $this->limit);
        }

        // 有權限的話顯示所有活動
        $activities = $this->activityService->getRecentActivities($this->limit * 2); // 取更多資料以便篩選

        // 應用篩選條件
        if ($this->filterType) {
            $activities = $activities->where('type', $this->filterType);
        }

        if ($this->filterModule) {
            $activities = $activities->where('module', $this->filterModule);
        }

        return $activities->take($this->limit);
    }

    /**
     * 取得活動統計資料
     * 
     * @return array
     */
    public function getActivityStatsProperty(): array
    {
        $user = auth()->user();
        
        // 檢查權限
        if (!$this->permissionService->hasPermission($user, 'system.logs')) {
            return [];
        }

        return $this->activityService->getActivityStats();
    }

    /**
     * 取得可用的篩選選項
     * 
     * @return array
     */
    public function getFilterOptionsProperty(): array
    {
        $activities = $this->recentActivities;
        
        return [
            'types' => $activities->pluck('type')->unique()->sort()->values()->toArray(),
            'modules' => $activities->whereNotNull('module')->pluck('module')->unique()->sort()->values()->toArray(),
        ];
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
     * 清除篩選條件
     * 
     * @return void
     */
    public function clearFilters()
    {
        $this->filterType = null;
        $this->filterModule = null;
    }

    /**
     * 切換詳細資訊顯示
     * 
     * @return void
     */
    public function toggleDetails()
    {
        $this->showDetails = !$this->showDetails;
    }

    /**
     * 重新整理活動記錄
     * 
     * @return void
     */
    public function refresh()
    {
        // 清除活動快取
        $this->activityService->clearRecentActivitiesCache();
        
        $this->dispatchBrowserEvent('activities-refreshed', [
            'message' => '活動記錄已更新'
        ]);
    }

    /**
     * 載入更多活動
     * 
     * @return void
     */
    public function loadMore()
    {
        $this->limit += 10;
    }

    /**
     * 取得活動類型的顯示名稱
     * 
     * @param string $type
     * @return string
     */
    public function getActivityTypeName(string $type): string
    {
        return match ($type) {
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
            'quick_action' => '快速操作',
            default => $type,
        };
    }

    /**
     * 取得模組的顯示名稱
     * 
     * @param string|null $module
     * @return string
     */
    public function getModuleName(?string $module): string
    {
        if (!$module) {
            return '系統';
        }

        return match ($module) {
            'auth' => '認證',
            'users' => '使用者',
            'roles' => '角色',
            'permissions' => '權限',
            'dashboard' => '儀表板',
            'system' => '系統',
            default => $module,
        };
    }

    /**
     * 檢查是否可以檢視活動詳情
     * 
     * @return bool
     */
    public function canViewActivityDetails(): bool
    {
        return $this->permissionService->hasPermission(auth()->user(), 'system.logs');
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