<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\Role;
use App\Models\Activity;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Dashboard extends Component
{
    use WithPagination;
    
    // 統計資料屬性
    public array $stats = [];
    public array $chartData = [];
    public array $quickActions = [];
    public array $recentActivities = [];
    
    // 載入狀態
    public bool $isLoading = true;
    
    // 分頁設定
    public int $activitiesPerPage = 10;
    public bool $showAllActivities = false;
    
    public function mount()
    {
        $this->loadDashboardData();
    }
    
    /**
     * 載入儀表板資料
     */
    public function loadDashboardData(): void
    {
        $this->isLoading = true;
        
        // 載入統計資料
        $this->loadStats();
        
        // 載入圖表資料
        $this->loadChartData();
        
        // 載入快速操作
        $this->loadQuickActions();
        
        // 載入最近活動
        $this->loadRecentActivities();
        
        $this->isLoading = false;
    }
    
    /**
     * 載入統計卡片資料
     */
    protected function loadStats(): void
    {
        $this->stats = Cache::remember('dashboard_stats', 300, function () {
            $totalUsers = User::count();
            $activeUsers = User::where('last_login_at', '>=', Carbon::now()->subDays(30))->count();
            $totalRoles = Role::count();
            $todayActivities = Activity::whereDate('created_at', Carbon::today())->count();
            $securityEvents = Activity::whereDate('created_at', Carbon::today())
                ->where('event', 'like', '%security%')
                ->count();
            
            // 計算本月新增使用者
            $thisMonthUsers = User::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();
            
            // 計算活躍度百分比
            $activePercentage = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0;
            
            return [
                'total_users' => [
                    'count' => $totalUsers,
                    'new_this_month' => $thisMonthUsers,
                    'icon' => 'users',
                    'color' => 'blue'
                ],
                'active_users' => [
                    'count' => $activeUsers,
                    'percentage' => $activePercentage,
                    'icon' => 'user-check',
                    'color' => 'green'
                ],
                'total_roles' => [
                    'count' => $totalRoles,
                    'permissions_avg' => $totalRoles > 0 ? Role::with('permissions')->get()->avg(function($role) {
                        return $role->permissions->count();
                    }) : 0,
                    'icon' => 'shield',
                    'color' => 'purple'
                ],
                'today_activities' => [
                    'count' => $todayActivities,
                    'security_events' => $securityEvents,
                    'icon' => 'activity',
                    'color' => 'orange'
                ]
            ];
        });
    }
    
    /**
     * 載入圖表資料
     */
    protected function loadChartData(): void
    {
        $this->chartData = Cache::remember('dashboard_charts', 600, function () {
            // 使用者活動趨勢圖（過去 7 天）
            $activityTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $count = Activity::whereDate('created_at', $date)->count();
                $activityTrend[] = [
                    'date' => $date->format('m/d'),
                    'count' => $count
                ];
            }
            
            // 登入時間分佈圖
            $loginDistribution = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $count = User::whereNotNull('last_login_at')
                    ->whereRaw('HOUR(last_login_at) = ?', [$hour])
                    ->count();
                $loginDistribution[] = [
                    'hour' => sprintf('%02d:00', $hour),
                    'count' => $count
                ];
            }
            
            // 功能使用統計圓餅圖
            $featureUsage = Activity::selectRaw('event, COUNT(*) as count')
                ->whereDate('created_at', '>=', Carbon::now()->subDays(7))
                ->groupBy('event')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $this->translateEventName($item->event),
                        'value' => $item->count
                    ];
                })
                ->toArray();
            
            // 系統效能監控圖表（模擬資料）
            $performanceData = [];
            for ($i = 23; $i >= 0; $i--) {
                $time = Carbon::now()->subHours($i);
                $performanceData[] = [
                    'time' => $time->format('H:i'),
                    'cpu' => rand(20, 80),
                    'memory' => rand(30, 90),
                    'response_time' => rand(100, 500)
                ];
            }
            
            return [
                'activity_trend' => $activityTrend,
                'login_distribution' => $loginDistribution,
                'feature_usage' => $featureUsage,
                'performance' => $performanceData
            ];
        });
    }
    
    /**
     * 載入快速操作按鈕
     */
    protected function loadQuickActions(): void
    {
        $user = Auth::user();
        $this->quickActions = [];
        
        // 根據使用者權限顯示快速操作
        if ($user->can('create', User::class)) {
            $this->quickActions[] = [
                'title' => '建立使用者',
                'description' => '新增系統使用者',
                'icon' => 'user-plus',
                'route' => 'admin.users.create',
                'color' => 'blue'
            ];
        }
        
        if ($user->can('create', Role::class)) {
            $this->quickActions[] = [
                'title' => '建立角色',
                'description' => '新增使用者角色',
                'icon' => 'shield-plus',
                'route' => 'admin.roles.create',
                'color' => 'green'
            ];
        }
        
        // 暫時移除活動記錄連結，因為路由尚未建立
        // if ($user->can('viewAny', Activity::class)) {
        //     $this->quickActions[] = [
        //         'title' => '查看活動記錄',
        //         'description' => '檢視系統活動',
        //         'icon' => 'list',
        //         'route' => 'admin.activities.index',
        //         'color' => 'purple'
        //     ];
        // }
        
        if ($user->can('viewAny', Activity::class)) {
            $this->quickActions[] = [
                'title' => '查看活動記錄',
                'description' => '檢視系統活動',
                'icon' => 'list',
                'route' => 'admin.dashboard', // 暫時導向儀表板
                'color' => 'purple'
            ];
        }
        
        $this->quickActions[] = [
            'title' => '系統設定',
            'description' => '管理系統配置',
            'icon' => 'settings',
            'route' => 'admin.settings.index',
            'color' => 'gray'
        ];
    }
    
    /**
     * 載入最近活動列表
     */
    protected function loadRecentActivities(): void
    {
        $limit = $this->showAllActivities ? $this->activitiesPerPage : 10;
        
        $this->recentActivities = Activity::with('causer')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'event' => $this->translateEventName($activity->event),
                    'causer_name' => $activity->causer?->name ?? '系統',
                    'created_at' => $activity->created_at,
                    'created_at_human' => $activity->created_at->diffForHumans(),
                    'icon' => $this->getActivityIcon($activity->event),
                    'color' => $this->getActivityColor($activity->event)
                ];
            })
            ->toArray();
    }
    
    /**
     * 取得分頁的活動列表
     */
    public function getActivitiesProperty()
    {
        if (!$this->showAllActivities) {
            return collect($this->recentActivities);
        }
        
        return Activity::with('causer')
            ->latest()
            ->paginate($this->activitiesPerPage)
            ->through(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'event' => $this->translateEventName($activity->event),
                    'causer_name' => $activity->causer?->name ?? '系統',
                    'created_at' => $activity->created_at,
                    'created_at_human' => $activity->created_at->diffForHumans(),
                    'icon' => $this->getActivityIcon($activity->event),
                    'color' => $this->getActivityColor($activity->event)
                ];
            });
    }
    
    /**
     * 切換顯示所有活動
     */
    public function toggleShowAllActivities(): void
    {
        $this->showAllActivities = !$this->showAllActivities;
        
        if (!$this->showAllActivities) {
            $this->loadRecentActivities();
        }
        
        $this->resetPage();
    }
    
    /**
     * 翻譯事件名稱
     */
    protected function translateEventName(?string $event): string
    {
        if (!$event) {
            return '未知事件';
        }
        
        $translations = [
            'created' => '建立',
            'updated' => '更新',
            'deleted' => '刪除',
            'login' => '登入',
            'logout' => '登出',
            'password_changed' => '密碼變更',
            'role_assigned' => '角色指派',
            'permission_granted' => '權限授予',
            'security_alert' => '安全警報'
        ];
        
        return $translations[$event] ?? $event;
    }
    
    /**
     * 取得活動圖示
     */
    protected function getActivityIcon(?string $event): string
    {
        if (!$event) {
            return 'activity';
        }
        
        $icons = [
            'created' => 'plus-circle',
            'updated' => 'edit',
            'deleted' => 'trash',
            'login' => 'log-in',
            'logout' => 'log-out',
            'password_changed' => 'key',
            'role_assigned' => 'shield',
            'permission_granted' => 'unlock',
            'security_alert' => 'alert-triangle'
        ];
        
        return $icons[$event] ?? 'activity';
    }
    
    /**
     * 取得活動顏色
     */
    protected function getActivityColor(?string $event): string
    {
        if (!$event) {
            return 'gray';
        }
        
        $colors = [
            'created' => 'green',
            'updated' => 'blue',
            'deleted' => 'red',
            'login' => 'green',
            'logout' => 'gray',
            'password_changed' => 'yellow',
            'role_assigned' => 'purple',
            'permission_granted' => 'blue',
            'security_alert' => 'red'
        ];
        
        return $colors[$event] ?? 'gray';
    }
    
    /**
     * 重新整理儀表板資料
     */
    public function refresh(): void
    {
        // 清除快取
        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_charts');
        
        // 重新載入資料
        $this->loadDashboardData();
        
        // 發送成功訊息
        $this->dispatch('toast', [
            'type' => 'success',
            'message' => '儀表板資料已更新'
        ]);
    }
    
    /**
     * 導航到指定路由
     */
    public function navigateTo(string $route)
    {
        return redirect()->route($route);
    }
    
    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}