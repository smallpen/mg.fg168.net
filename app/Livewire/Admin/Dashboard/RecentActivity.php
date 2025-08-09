<?php

namespace App\Livewire\Admin\Dashboard;

use Livewire\Component;
use Illuminate\Support\Collection;

/**
 * 最近活動元件
 * 
 * 顯示系統最近活動
 */
class RecentActivity extends Component
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
     * 取得可用的篩選選項
     * 
     * @return array
     */
    public function getFilterOptionsProperty(): array
    {
        $activities = $this->recentActivities;
        
        return [
            'types' => collect($activities)->pluck('type')->unique()->sort()->values()->toArray(),
            'modules' => collect($activities)->whereNotNull('module')->pluck('module')->unique()->sort()->values()->toArray(),
        ];
    }

    /**
     * 取得最近活動記錄
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getRecentActivitiesProperty()
    {
        // 簡化版本，回傳一些示例資料
        $rawActivities = [
            [
                'id' => 1,
                'type' => 'login',
                'user_name' => auth()->user()->name ?? 'Admin',
                'description' => '使用者登入系統',
                'created_at' => now()->subMinutes(5),
                'module' => 'auth'
            ],
            [
                'id' => 2,
                'type' => 'view_dashboard',
                'user_name' => auth()->user()->name ?? 'Admin',
                'description' => '檢視儀表板',
                'created_at' => now()->subMinutes(10),
                'module' => 'dashboard'
            ],
            [
                'id' => 3,
                'type' => 'create_user',
                'user_name' => auth()->user()->name ?? 'Admin',
                'description' => '建立新使用者',
                'created_at' => now()->subMinutes(15),
                'module' => 'users'
            ]
        ];

        // 轉換為物件並添加所需屬性
        $activities = collect($rawActivities)->map(function ($activity) {
            $activity['color'] = $this->getActivityColor($activity['type']);
            $activity['icon'] = $this->getActivityIcon($activity['type']);
            
            // 建立使用者物件
            $activity['user'] = (object) [
                'name' => $activity['user_name']
            ];
            
            // 格式化時間
            $activity['formatted_time'] = $activity['created_at']->diffForHumans();
            
            return (object) $activity;
        })->toArray();

        // 應用篩選條件
        $filtered = collect($activities);

        if ($this->filterType) {
            $filtered = $filtered->where('type', $this->filterType);
        }

        if ($this->filterModule) {
            $filtered = $filtered->where('module', $this->filterModule);
        }

        return $filtered->take($this->limit);
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
        $this->dispatch('activities-refreshed', [
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
            'view_dashboard' => '檢視儀表板',
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
     * 取得活動類型對應的顏色
     * 
     * @param string $type
     * @return string
     */
    public function getActivityColor(string $type): string
    {
        return match ($type) {
            'login' => 'green',
            'logout' => 'gray',
            'create_user' => 'purple',
            'update_user' => 'blue',
            'delete_user' => 'red',
            'create_role' => 'indigo',
            'update_role' => 'blue',
            'delete_role' => 'red',
            'view_dashboard' => 'blue',
            'quick_action' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * 取得活動類型對應的圖示
     * 
     * @param string $type
     * @return string
     */
    public function getActivityIcon(string $type): string
    {
        return match ($type) {
            'login' => 'login',
            'logout' => 'logout',
            'create_user' => 'user-plus',
            'update_user' => 'user-edit',
            'delete_user' => 'user-minus',
            'create_role' => 'shield-plus',
            'update_role' => 'shield-edit',
            'delete_role' => 'shield-minus',
            'view_dashboard' => 'dashboard',
            'quick_action' => 'lightning',
            default => 'activity',
        };
    }

    /**
     * 檢查是否可以檢視活動詳情
     * 
     * @return bool
     */
    public function canViewActivityDetails(): bool
    {
        return auth()->user()->hasPermission('system.logs') ?? false;
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