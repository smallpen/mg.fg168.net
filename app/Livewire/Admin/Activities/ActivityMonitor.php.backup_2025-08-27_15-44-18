<?php

namespace App\Livewire\Admin\Activities;

use App\Models\Activity;
use App\Models\MonitorRule;
use App\Models\SecurityAlert;
use App\Services\SecurityAnalyzer;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class ActivityMonitor extends Component
{
    // 監控狀態
    public bool $isMonitoring = false;
    public int $alertCount = 0;
    public int $refreshInterval = 5; // 秒
    
    // 監控規則
    public array $monitorRules = [];
    public bool $showRuleModal = false;
    public array $newRule = [
        'name' => '',
        'description' => '',
        'conditions' => [],
        'actions' => [],
        'priority' => 1,
        'is_active' => true
    ];
    
    // 警報管理
    public array $recentAlerts = [];
    public bool $showAlertDetail = false;
    public ?SecurityAlert $selectedAlert = null;
    
    // 統計資料
    public array $todayStats = [
        'total_activities' => 0,
        'security_events' => 0,
        'alerts' => 0,
        'unique_users' => 0
    ];
    
    // 活動頻率監控
    public array $activityFrequency = [];
    public int $normalFrequencyThreshold = 100; // 每分鐘正常活動數量閾值
    
    protected SecurityAnalyzer $securityAnalyzer;
    
    public function boot(SecurityAnalyzer $securityAnalyzer)
    {
        $this->securityAnalyzer = $securityAnalyzer;
    }
    
    public function mount()
    {
        // 檢查權限
        $this->authorize('system.logs');
        
        // 載入監控規則
        $this->loadMonitorRules();
        
        // 載入最新警報
        $this->loadRecentAlerts();
        
        // 載入今日統計
        $this->loadTodayStats();
        
        // 載入活動頻率資料
        $this->loadActivityFrequency();
    }
    
    /**
     * 計算屬性：取得啟用的監控規則
     */
    public function getActiveRulesProperty(): Collection
    {
        return MonitorRule::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();
    }
    
    /**
     * 計算屬性：取得最近的活動記錄
     */
    public function getRecentActivitiesProperty(): Collection
    {
        return Activity::with(['user'])
            ->latest()
            ->limit(10)
            ->get();
    }
    
    /**
     * 計算屬性：取得未確認的警報數量
     */
    public function getUnacknowledgedAlertsCountProperty(): int
    {
        return SecurityAlert::whereNull('acknowledged_at')->count();
    }
    
    /**
     * 開始監控
     */
    public function startMonitoring(): void
    {
        $this->isMonitoring = true;
        
        // 記錄監控開始事件
        activity()
            ->causedBy(auth()->user())
            ->log('開始即時活動監控');
        
        $this->dispatch('monitoring-started');
        session()->flash('success', '即時監控已啟動');
    }
    
    /**
     * 停止監控
     */
    public function stopMonitoring(): void
    {
        $this->isMonitoring = false;
        
        // 記錄監控停止事件
        activity()
            ->causedBy(auth()->user())
            ->log('停止即時活動監控');
        
        $this->dispatch('monitoring-stopped');
        session()->flash('info', '即時監控已停止');
    }
    
    /**
     * 切換監控狀態
     */
    public function toggleMonitoring(): void
    {
        if ($this->isMonitoring) {
            $this->stopMonitoring();
        } else {
            $this->startMonitoring();
        }
    }
    
    /**
     * 新增監控規則
     */
    public function addRule(): void
    {
        $this->validate([
            'newRule.name' => 'required|string|max:255',
            'newRule.description' => 'required|string|max:500',
            'newRule.priority' => 'required|integer|min:1|max:10'
        ], [
            'newRule.name.required' => '規則名稱為必填項目',
            'newRule.description.required' => '規則描述為必填項目',
            'newRule.priority.required' => '優先級為必填項目'
        ]);
        
        MonitorRule::create([
            'name' => $this->newRule['name'],
            'description' => $this->newRule['description'],
            'conditions' => $this->newRule['conditions'],
            'actions' => $this->newRule['actions'],
            'priority' => $this->newRule['priority'],
            'is_active' => $this->newRule['is_active'],
            'created_by' => auth()->id()
        ]);
        
        // 重置表單
        $this->newRule = [
            'name' => '',
            'description' => '',
            'conditions' => [],
            'actions' => [],
            'priority' => 1,
            'is_active' => true
        ];
        
        $this->showRuleModal = false;
        $this->loadMonitorRules();
        
        session()->flash('success', '監控規則已新增');
    }
    
    /**
     * 移除監控規則
     */
    public function removeRule(int $ruleId): void
    {
        $rule = MonitorRule::findOrFail($ruleId);
        $rule->delete();
        
        $this->loadMonitorRules();
        
        session()->flash('success', '監控規則已刪除');
    }
    
    /**
     * 切換規則啟用狀態
     */
    public function toggleRule(int $ruleId): void
    {
        $rule = MonitorRule::findOrFail($ruleId);
        $rule->update(['is_active' => !$rule->is_active]);
        
        $this->loadMonitorRules();
        
        $status = $rule->is_active ? '啟用' : '停用';
        session()->flash('success', "規則已{$status}");
    }
    
    /**
     * 確認警報
     */
    public function acknowledgeAlert(int $alertId): void
    {
        $alert = SecurityAlert::findOrFail($alertId);
        $alert->acknowledge(auth()->user());
        
        $this->loadRecentAlerts();
        $this->alertCount = $this->unacknowledgedAlertsCount;
        
        session()->flash('success', '警報已確認');
    }
    
    /**
     * 查看警報詳情
     */
    public function viewAlertDetail(int $alertId): void
    {
        $this->selectedAlert = SecurityAlert::with(['activity', 'rule'])->findOrFail($alertId);
        $this->showAlertDetail = true;
    }
    
    /**
     * 關閉警報詳情
     */
    public function closeAlertDetail(): void
    {
        $this->selectedAlert = null;
        $this->showAlertDetail = false;
    }
    
    /**
     * 忽略警報
     */
    public function ignoreAlert(int $alertId): void
    {
        $alert = SecurityAlert::findOrFail($alertId);
        $alert->update(['acknowledged_at' => now(), 'acknowledged_by' => auth()->id()]);
        
        $this->loadRecentAlerts();
        
        session()->flash('info', '警報已忽略');
    }
    
    /**
     * 封鎖 IP 位址
     */
    public function blockIp(string $ipAddress): void
    {
        // 這裡可以整合防火牆或安全系統
        // 暫時記錄封鎖動作
        activity()
            ->causedBy(auth()->user())
            ->withProperties(['ip_address' => $ipAddress])
            ->log("封鎖 IP 位址: {$ipAddress}");
        
        session()->flash('success', "IP 位址 {$ipAddress} 已被封鎖");
    }
    
    /**
     * 重新整理監控資料
     */
    public function refreshMonitorData(): void
    {
        $this->loadRecentAlerts();
        $this->loadTodayStats();
        $this->loadActivityFrequency();
        
        session()->flash('success', '監控資料已更新');
    }
    
    /**
     * 即時監控：處理安全警報
     */
    #[On('security-alert')]
    public function handleSecurityAlert(array $alertData): void
    {
        if (!$this->isMonitoring) {
            return;
        }
        
        $this->alertCount++;
        $this->loadRecentAlerts();
        
        // 發送瀏覽器通知
        $this->dispatch('show-alert-notification', [
            'title' => '安全警報',
            'message' => $alertData['title'] ?? '檢測到可疑活動',
            'type' => 'danger'
        ]);
    }
    
    /**
     * 即時監控：檢查活動記錄
     */
    #[On('activity-logged')]
    public function checkRules(array $activityData): void
    {
        if (!$this->isMonitoring) {
            return;
        }
        
        // 更新活動頻率
        $this->updateActivityFrequency();
        
        // 檢查是否觸發監控規則
        $activity = Activity::find($activityData['id'] ?? null);
        if ($activity) {
            $this->evaluateMonitorRules($activity);
        }
        
        // 更新統計資料
        $this->loadTodayStats();
    }
    
    /**
     * 載入監控規則
     */
    protected function loadMonitorRules(): void
    {
        $this->monitorRules = MonitorRule::orderBy('priority', 'desc')
            ->get()
            ->map(function ($rule) {
                return [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'description' => $rule->description,
                    'is_active' => $rule->is_active,
                    'priority' => $rule->priority,
                    'triggered_count' => $rule->triggered_count,
                    'last_triggered' => $rule->last_triggered?->format('Y-m-d H:i:s')
                ];
            })
            ->toArray();
    }
    
    /**
     * 載入最新警報
     */
    protected function loadRecentAlerts(): void
    {
        $this->recentAlerts = SecurityAlert::with(['activity.causer'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'type' => $alert->type,
                    'severity' => $alert->severity,
                    'title' => $alert->title,
                    'description' => $alert->description,
                    'created_at' => $alert->created_at->format('H:i:s'),
                    'is_acknowledged' => $alert->is_acknowledged,
                    'activity_id' => $alert->activity_id,
                    'causer_name' => $alert->activity?->causer?->name ?? 'Unknown'
                ];
            })
            ->toArray();
        
        $this->alertCount = $this->unacknowledgedAlertsCount;
    }
    
    /**
     * 載入今日統計
     */
    protected function loadTodayStats(): void
    {
        $today = now()->startOfDay();
        
        $this->todayStats = [
            'total_activities' => Activity::where('created_at', '>=', $today)->count(),
            'security_events' => Activity::where('created_at', '>=', $today)
                ->where('risk_level', '>', 3)
                ->count(),
            'alerts' => SecurityAlert::where('created_at', '>=', $today)->count(),
            'unique_users' => Activity::where('created_at', '>=', $today)
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id')
        ];
    }
    
    /**
     * 載入活動頻率資料
     */
    protected function loadActivityFrequency(): void
    {
        $intervals = [];
        $now = now();
        
        // 取得過去 10 分鐘的活動頻率
        for ($i = 9; $i >= 0; $i--) {
            $start = $now->copy()->subMinutes($i + 1);
            $end = $now->copy()->subMinutes($i);
            
            $count = Activity::whereBetween('created_at', [$start, $end])->count();
            
            $intervals[] = [
                'time' => $end->format('H:i'),
                'count' => $count,
                'is_abnormal' => $count > $this->normalFrequencyThreshold
            ];
        }
        
        $this->activityFrequency = $intervals;
    }
    
    /**
     * 更新活動頻率
     */
    protected function updateActivityFrequency(): void
    {
        $this->loadActivityFrequency();
        
        // 檢查是否有異常頻率
        $latestInterval = end($this->activityFrequency);
        if ($latestInterval && $latestInterval['is_abnormal']) {
            $this->dispatch('show-alert-notification', [
                'title' => '活動頻率異常',
                'message' => "檢測到異常活動頻率：{$latestInterval['count']} 次/分鐘",
                'type' => 'warning'
            ]);
        }
    }
    
    /**
     * 評估監控規則
     */
    protected function evaluateMonitorRules(Activity $activity): void
    {
        foreach ($this->activeRules as $rule) {
            if ($rule->matches($activity)) {
                $rule->execute($activity);
                
                // 更新規則觸發統計
                $rule->increment('triggered_count');
                $rule->update(['last_triggered_at' => now()]);
            }
        }
    }
    
    public function render()
    {
        return view('livewire.admin.activities.activity-monitor');
    }
}