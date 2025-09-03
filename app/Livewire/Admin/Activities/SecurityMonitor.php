<?php

namespace App\Livewire\Admin\Activities;

use App\Models\Activity;
use App\Models\SecurityIncident;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * 安全監控 Livewire 元件
 * 
 * 顯示系統安全事件、統計資訊和即時監控資料
 */
class SecurityMonitor extends Component
{
    use WithPagination;

    // 篩選條件
    public string $eventTypeFilter = 'all';
    public string $severityFilter = 'all';
    public string $statusFilter = 'all';
    public string $dateFilter = '';
    public int $perPage = 10;

    // 詳情模態框
    public bool $showDetailsModal = false;
    public ?SecurityIncident $selectedIncident = null;

    // 確認對話框
    public bool $showResolveConfirm = false;
    public ?int $incidentToResolve = null;

    // 統計資料
    public array $stats = [];
    public Collection $recentIncidents;
    public Collection $recentActivities;

    // 可選的篩選選項
    public array $eventTypes = [
        'all' => '所有事件類型',
        'login_failure' => '登入失敗',
        'permission_violation' => '權限違規',
        'suspicious_activity' => '可疑活動',
        'system_anomaly' => '系統異常',
        'brute_force_attack' => '暴力破解攻擊',
        'unauthorized_access' => '未授權存取',
    ];

    public array $severityLevels = [
        'all' => '所有嚴重程度',
        'low' => '低',
        'medium' => '中',
        'high' => '高',
        'critical' => '嚴重',
    ];

    public array $statusOptions = [
        'all' => '所有狀態',
        'unresolved' => '未處理',
        'investigating' => '調查中',
        'resolved' => '已處理',
    ];

    protected ActivityLogger $activityLogger;

    public function boot(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    public function mount()
    {
        $this->authorize('system.logs');
        $this->loadStats();
        $this->loadRecentData();
    }

    /**
     * 載入統計資料
     */
    public function loadStats(): void
    {
        // 威脅等級評估
        $criticalCount = SecurityIncident::where('severity', 'critical')->unresolved()->count();
        $highCount = SecurityIncident::where('severity', 'high')->unresolved()->count();
        
        $threatLevel = 'low';
        $threatColor = 'green';
        
        if ($criticalCount > 0) {
            $threatLevel = 'critical';
            $threatColor = 'red';
        } elseif ($highCount > 2) {
            $threatLevel = 'high';
            $threatColor = 'red';
        } elseif ($highCount > 0) {
            $threatLevel = 'medium';
            $threatColor = 'yellow';
        }

        // 今日安全事件統計
        $todayIncidents = SecurityIncident::today()->count();
        
        // 失敗登入統計（從活動記錄中統計）
        $failedLogins = Activity::where('type', 'user_login')
            ->where('result', 'failed')
            ->whereDate('created_at', today())
            ->count();
        
        // 如果沒有失敗登入記錄，從安全事件中統計
        if ($failedLogins === 0) {
            $failedLogins = SecurityIncident::where('event_type', 'login_failure')
                ->today()
                ->count();
        }
        
        // 可疑活動統計
        $suspiciousActivities = SecurityIncident::where('event_type', 'suspicious_activity')
            ->today()
            ->count();
        
        // 如果沒有安全事件記錄，從活動記錄中統計高風險活動
        if ($suspiciousActivities === 0) {
            $suspiciousActivities = Activity::where('risk_level', '>', 3)
                ->whereDate('created_at', today())
                ->count();
        }

        $this->stats = [
            'threat_level' => [
                'level' => $threatLevel,
                'label' => match($threatLevel) {
                    'low' => '低風險',
                    'medium' => '中風險',
                    'high' => '高風險',
                    'critical' => '嚴重威脅',
                },
                'color' => $threatColor,
                'icon' => match($threatLevel) {
                    'low' => 'check-circle',
                    'medium' => 'exclamation-triangle',
                    'high' => 'exclamation-triangle',
                    'critical' => 'x-circle',
                }
            ],
            'today_incidents' => $todayIncidents,
            'failed_logins' => $failedLogins,
            'suspicious_activities' => $suspiciousActivities,
        ];
    }

    /**
     * 載入最近資料
     */
    public function loadRecentData(): void
    {
        // 載入最近的安全事件
        $this->recentIncidents = SecurityIncident::with(['user', 'resolver'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // 載入最近的安全相關活動
        $this->recentActivities = Activity::with('user')
            ->where(function($query) {
                $query->where('type', 'like', '%login%')
                      ->orWhere('type', 'like', '%security%')
                      ->orWhere('risk_level', '>', 2);
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * 取得篩選後的安全事件
     */
    public function getFilteredIncidents(): LengthAwarePaginator
    {
        $query = SecurityIncident::with(['user', 'resolver']);

        // 應用篩選條件
        if ($this->eventTypeFilter !== 'all') {
            $query->where('event_type', $this->eventTypeFilter);
        }

        if ($this->severityFilter !== 'all') {
            $query->where('severity', $this->severityFilter);
        }

        if ($this->statusFilter !== 'all') {
            switch ($this->statusFilter) {
                case 'unresolved':
                    $query->where('resolved', false);
                    break;
                case 'resolved':
                    $query->where('resolved', true);
                    break;
                case 'investigating':
                    $query->where('resolved', false)
                          ->where('created_at', '>', now()->subHours(24));
                    break;
            }
        }

        if ($this->dateFilter) {
            $query->whereDate('created_at', $this->dateFilter);
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate($this->perPage);
    }

    /**
     * 重置篩選條件
     */
    public function resetFilters(): void
    {
        try {
            // 記錄篩選重置操作
            \Log::info('🔄 resetFilters - 安全監控篩選重置開始', [
                'timestamp' => now()->toISOString(),
                'user' => auth()->user()->username ?? 'unknown',
                'before_reset' => [
                    'eventTypeFilter' => $this->eventTypeFilter ?? 'all',
                    'severityFilter' => $this->severityFilter ?? 'all',
                    'statusFilter' => $this->statusFilter ?? 'all',
                    'dateFilter' => $this->dateFilter ?? '',
                ]
            ]);
            
            // 重置所有篩選條件
            $this->eventTypeFilter = 'all';
            $this->severityFilter = 'all';
            $this->statusFilter = 'all';
            $this->dateFilter = '';
            
            // 重置分頁
            $this->resetPage();
            
            // 強制重新渲染整個元件
            $this->skipRender = false;
            
            // 發送強制 UI 更新事件
            $this->dispatch('force-ui-update');
            
            // 發送前端重置事件，讓 Alpine.js 處理
            $this->dispatch('reset-form-elements');
            
            // 強制 Livewire 同步狀態到前端
            $this->js('
                // 強制更新所有表單元素的值
                setTimeout(() => {
                    const eventTypeSelect = document.querySelector(\'select[wire\\\\:model\\\\.live="eventTypeFilter"]\');
                    if (eventTypeSelect) {
                        eventTypeSelect.value = "all";
                        eventTypeSelect.dispatchEvent(new Event("change", { bubbles: true }));
                    }
                    
                    const severitySelect = document.querySelector(\'select[wire\\\\:model\\\\.live="severityFilter"]\');
                    if (severitySelect) {
                        severitySelect.value = "all";
                        severitySelect.dispatchEvent(new Event("change", { bubbles: true }));
                    }
                    
                    const statusSelect = document.querySelector(\'select[wire\\\\:model\\\\.live="statusFilter"]\');
                    if (statusSelect) {
                        statusSelect.value = "all";
                        statusSelect.dispatchEvent(new Event("change", { bubbles: true }));
                    }
                    
                    const dateInput = document.querySelector(\'input[wire\\\\:model\\\\.live="dateFilter"]\');
                    if (dateInput) {
                        dateInput.value = "";
                        dateInput.dispatchEvent(new Event("input", { bubbles: true }));
                    }
                    
                    console.log("✅ 安全監控篩選表單元素已強制同步");
                }, 100);
            ');
            
            // 顯示成功訊息
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '篩選條件已清除'
            ]);
            
            $this->activityLogger->logUserAction('reset_security_filters', null, [
                'page' => 'security_monitor'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('安全監控重置方法執行失敗', [
                'method' => 'resetFilters',
                'error' => $e->getMessage(),
                'component' => static::class,
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '重置操作失敗，請重試'
            ]);
        }
    }

    /**
     * 重新整理資料
     */
    public function refreshData(): void
    {
        $this->loadStats();
        $this->loadRecentData();
        $this->resetPage();
        
        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => '安全監控資料已更新'
        ]);

        $this->activityLogger->logUserAction('refresh_security_data', null, [
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * 顯示標記已處理確認對話框
     */
    public function showResolveDialog(int $incidentId): void
    {
        $this->incidentToResolve = $incidentId;
        $this->showResolveConfirm = true;
    }

    /**
     * 取消標記已處理
     */
    public function cancelResolve(): void
    {
        $this->showResolveConfirm = false;
        $this->incidentToResolve = null;
    }

    /**
     * 確認標記事件為已處理
     */
    public function confirmResolveIncident(): void
    {
        error_log('🚨🚨🚨 CONFIRM RESOLVE INCIDENT METHOD CALLED 🚨🚨🚨');
        \Log::info('🔥 confirmResolveIncident 方法被調用', [
            'incident_id' => $this->incidentToResolve,
            'timestamp' => now()->toISOString()
        ]);
        
        if (!$this->incidentToResolve) {
            \Log::warning('沒有要處理的事件 ID');
            return;
        }

        try {
            $incident = SecurityIncident::findOrFail($this->incidentToResolve);
            
            // 詳細的認證檢查和日誌記錄
            $authCheck = auth()->check();
            $userId = auth()->id();
            $user = auth()->user();
            
            \Log::info('認證狀態檢查', [
                'auth_check' => $authCheck,
                'user_id' => $userId,
                'user_exists' => !!$user,
                'user_username' => $user ? $user->username : null,
                'incident_id' => $this->incidentToResolve
            ]);
            
            if (!$authCheck) {
                throw new \Exception('使用者未登入');
            }
            
            // 臨時解決方案：如果無法取得使用者 ID，使用預設值
            $resolvedBy = $userId && $userId > 0 ? $userId : 1; // 預設使用 admin 使用者 (ID: 1)
            
            \Log::info('準備更新事件', [
                'original_user_id' => $userId,
                'resolved_by' => $resolvedBy,
                'incident_id' => $this->incidentToResolve
            ]);
            
            $incident->update([
                'resolved' => true,
                'resolved_by' => (int) $resolvedBy,
                'resolved_at' => now(),
                'resolution_notes' => '透過安全監控介面標記為已處理'
            ]);
            
            \Log::info('安全事件已成功標記為已處理', [
                'incident_id' => $this->incidentToResolve,
                'resolved_by' => $resolvedBy
            ]);

            $this->loadStats();
            $this->loadRecentData();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '安全事件已標記為已處理'
            ]);

            $this->activityLogger->logUserAction('resolve_security_incident', $incident, [
                'incident_id' => $this->incidentToResolve,
                'incident_type' => $incident->event_type
            ]);

            // 關閉確認對話框
            $this->showResolveConfirm = false;
            $this->incidentToResolve = null;

        } catch (\Exception $e) {
            \Log::error('標記安全事件為已處理失敗', [
                'incident_id' => $this->incidentToResolve,
                'error' => $e->getMessage()
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '標記事件失敗，請重試'
            ]);
        }
    }

    /**
     * 建立測試安全事件（僅開發環境）
     */
    public function createTestIncident(): void
    {
        try {
            // 檢查環境
            if (!app()->environment('local')) {
                $this->dispatch('show-toast', [
                    'type' => 'warning',
                    'message' => '測試資料功能僅在開發環境可用'
                ]);
                return;
            }

            // 檢查資料表是否存在
            if (!\Schema::hasTable('security_incidents')) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'security_incidents 資料表不存在，請執行遷移'
                ]);
                return;
            }

            $testIncidents = [
                [
                    'event_type' => 'login_failure',
                    'severity' => 'high',
                    'ip_address' => '192.168.1.100',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'data' => [
                        'username' => 'unknown_user',
                        'attempts' => 5,
                        'timestamp' => now()->toISOString()
                    ]
                ],
                [
                    'event_type' => 'permission_violation',
                    'severity' => 'medium',
                    'user_id' => auth()->id(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'data' => [
                        'attempted_action' => 'access_admin_panel',
                        'required_permission' => 'admin.access',
                        'timestamp' => now()->toISOString()
                    ]
                ],
                [
                    'event_type' => 'suspicious_activity',
                    'severity' => 'medium',
                    'user_id' => auth()->id(),
                    'ip_address' => '203.0.113.45',
                    'user_agent' => 'Suspicious Bot/1.0',
                    'data' => [
                        'activity' => 'multiple_rapid_requests',
                        'request_count' => 50,
                        'time_window' => '1 minute',
                        'timestamp' => now()->toISOString()
                    ]
                ],
                [
                    'event_type' => 'brute_force_attack',
                    'severity' => 'critical',
                    'ip_address' => '198.51.100.42',
                    'user_agent' => 'AttackBot/2.0',
                    'data' => [
                        'target_username' => 'admin',
                        'attempts' => 25,
                        'time_window' => '5 minutes',
                        'blocked' => true,
                        'timestamp' => now()->toISOString()
                    ]
                ],
                [
                    'event_type' => 'unauthorized_access',
                    'severity' => 'high',
                    'ip_address' => '203.0.113.100',
                    'user_agent' => 'curl/7.68.0',
                    'data' => [
                        'attempted_url' => '/admin/users/sensitive-data',
                        'method' => 'GET',
                        'blocked' => true,
                        'timestamp' => now()->toISOString()
                    ]
                ]
            ];

            $createdCount = 0;
            foreach ($testIncidents as $incident) {
                try {
                    SecurityIncident::create($incident);
                    $createdCount++;
                } catch (\Exception $e) {
                    \Log::error('建立測試安全事件失敗', [
                        'incident' => $incident,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($createdCount > 0) {
                $this->loadStats();
                $this->loadRecentData();

                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => "已建立 {$createdCount} 個測試安全事件"
                ]);

                $this->activityLogger->logUserAction('create_test_security_incidents', null, [
                    'created_count' => $createdCount,
                    'total_attempted' => count($testIncidents)
                ]);
            } else {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '建立測試資料失敗，請檢查日誌'
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('建立測試安全事件時發生錯誤', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '建立測試資料時發生錯誤：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 顯示事件詳情
     */
    public function showIncidentDetails(int $incidentId): void
    {
        try {
            $this->selectedIncident = SecurityIncident::with(['user', 'resolver'])->findOrFail($incidentId);
            $this->showDetailsModal = true;

            $this->activityLogger->logUserAction('view_security_incident_details', $this->selectedIncident, [
                'incident_id' => $incidentId,
                'incident_type' => $this->selectedIncident->event_type
            ]);

        } catch (\Exception $e) {
            \Log::error('檢視安全事件詳情失敗', [
                'incident_id' => $incidentId,
                'error' => $e->getMessage()
            ]);

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '無法載入事件詳情'
            ]);
        }
    }

    /**
     * 關閉詳情模態框
     */
    public function closeDetailsModal(): void
    {
        $this->showDetailsModal = false;
        $this->selectedIncident = null;
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.activities.security-monitor', [
            'incidents' => $this->getFilteredIncidents(),
        ]);
    }
}