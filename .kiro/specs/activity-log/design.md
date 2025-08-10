# 活動記錄功能設計文件

## 概述

活動記錄功能提供完整的系統審計追蹤，採用非同步記錄、分層儲存、即時監控和完整性保護的設計理念。

## 架構設計

### 核心元件架構

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│ ActivityList    │    │ ActivityDetail   │    │ Activity        │
│   Component     │◄──►│   Component      │◄──►│  Repository     │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                        │                        │
         ▼                        ▼                        ▼
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│ ActivityStats   │    │ ActivityMonitor  │    │ ActivityLogger  │
│   Component     │    │   Component      │    │   Service       │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                        │                        │
         ▼                        ▼                        ▼
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│ ActivityExport  │    │ SecurityAlert    │    │ IntegrityCheck  │
│   Component     │    │   Component      │    │   Service       │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

## 元件設計

### 1. ActivityList 元件

**檔案位置**: `app/Livewire/Admin/Activities/ActivityList.php`

```php
class ActivityList extends Component
{
    // 篩選條件
    public string $search = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $userFilter = '';
    public string $typeFilter = '';
    public string $objectFilter = '';
    public string $resultFilter = '';
    public string $ipFilter = '';
    
    // 顯示設定
    public int $perPage = 50;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public bool $realTimeMode = false;
    
    // 批量操作
    public array $selectedActivities = [];
    public string $bulkAction = '';
    
    // 計算屬性
    public function getActivitiesProperty(): LengthAwarePaginator
    public function getFilterOptionsProperty(): array
    public function getStatsProperty(): array
    
    // 操作方法
    public function viewDetail(int $activityId): void
    public function exportActivities(): void
    public function toggleRealTime(): void
    public function clearFilters(): void
    public function executeBulkAction(): void
    
    // 即時更新
    #[On('activity-logged')]
    public function refreshActivities(): void
}
```

### 2. ActivityDetail 元件

**檔案位置**: `app/Livewire/Admin/Activities/ActivityDetail.php`

```php
class ActivityDetail extends Component
{
    public Activity $activity;
    public bool $showRawData = false;
    public bool $showRelatedActivities = true;
    
    // 計算屬性
    public function getRelatedActivitiesProperty(): Collection
    public function getFormattedDataProperty(): array
    public function getSecurityRiskLevelProperty(): string
    
    // 操作方法
    public function toggleRawData(): void
    public function exportDetail(): void
    public function flagAsSuspicious(): void
    public function addNote(string $note): void
}
```

### 3. ActivityStats 元件

**檔案位置**: `app/Livewire/Admin/Activities/ActivityStats.php`

```php
class ActivityStats extends Component
{
    public string $timeRange = '7d'; // 1d, 7d, 30d, 90d
    public string $chartType = 'timeline'; // timeline, distribution, heatmap
    public array $selectedMetrics = ['total', 'users', 'security'];
    
    // 計算屬性
    public function getTimelineDataProperty(): array
    public function getDistributionDataProperty(): array
    public function getTopUsersProperty(): Collection
    public function getSecurityEventsProperty(): Collection
    
    // 操作方法
    public function updateTimeRange(string $range): void
    public function updateChartType(string $type): void
    public function exportStats(): void
    public function refreshStats(): void
}
```

### 4. ActivityMonitor 元件

**檔案位置**: `app/Livewire/Admin/Activities/ActivityMonitor.php`

```php
class ActivityMonitor extends Component
{
    public bool $isMonitoring = false;
    public array $monitorRules = [];
    public array $recentAlerts = [];
    public int $alertCount = 0;
    
    // 計算屬性
    public function getActiveRulesProperty(): Collection
    public function getRecentActivitiesProperty(): Collection
    
    // 操作方法
    public function startMonitoring(): void
    public function stopMonitoring(): void
    public function addRule(array $rule): void
    public function removeRule(int $ruleId): void
    public function acknowledgeAlert(int $alertId): void
    
    // 即時監控
    #[On('security-alert')]
    public function handleSecurityAlert(array $alert): void
    
    #[On('activity-logged')]
    public function checkRules(array $activity): void
}
```

## 資料存取層設計

### ActivityRepository

```php
interface ActivityRepositoryInterface
{
    public function getPaginatedActivities(array $filters, int $perPage): LengthAwarePaginator;
    public function getActivityById(int $id): ?Activity;
    public function getRelatedActivities(Activity $activity): Collection;
    public function getActivityStats(string $timeRange): array;
    public function getSecurityEvents(string $timeRange): Collection;
    public function getTopUsers(string $timeRange, int $limit = 10): Collection;
    public function exportActivities(array $filters, string $format): string;
    public function cleanupOldActivities(int $daysToKeep): int;
    public function verifyIntegrity(): array;
    public function createBackup(string $timeRange): string;
    public function searchActivities(string $query, array $filters = []): Collection;
}
```

### ActivityLogger 服務

```php
class ActivityLogger
{
    public function log(string $type, string $description, array $data = []): void
    public function logUserAction(string $action, ?Model $subject = null, array $data = []): void
    public function logSecurityEvent(string $event, string $description, array $context = []): void
    public function logSystemEvent(string $event, array $data = []): void
    public function logApiAccess(string $endpoint, array $data = []): void
    
    // 批量記錄
    public function logBatch(array $activities): void
    
    // 非同步記錄
    public function logAsync(string $type, string $description, array $data = []): void
    
    // 完整性保護
    protected function generateSignature(array $data): string
    protected function verifySignature(Activity $activity): bool
}
```

### SecurityAnalyzer 服務

```php
class SecurityAnalyzer
{
    public function analyzeActivity(Activity $activity): array
    public function detectAnomalies(Collection $activities): array
    public function calculateRiskScore(Activity $activity): int
    public function identifyPatterns(string $userId, string $timeRange): array
    public function generateSecurityReport(string $timeRange): array
    public function checkSuspiciousIPs(): Collection
    public function monitorFailedLogins(): array
}
```

## 資料模型設計

### Activity 模型

```php
class Activity extends Model
{
    protected $fillable = [
        'type', 'description', 'subject_type', 'subject_id',
        'causer_type', 'causer_id', 'properties', 'ip_address',
        'user_agent', 'result', 'risk_level', 'signature'
    ];
    
    protected $casts = [
        'properties' => 'json',
        'created_at' => 'datetime',
        'risk_level' => 'integer',
    ];
    
    // 關聯關係
    public function subject(): MorphTo
    public function causer(): MorphTo
    public function alerts(): HasMany
    
    // 計算屬性
    public function getFormattedPropertiesAttribute(): array
    public function getRiskLevelTextAttribute(): string
    public function getIsSecurityEventAttribute(): bool
    public function getRelatedActivitiesAttribute(): Collection
    
    // 完整性驗證
    public function verifyIntegrity(): bool
    public function generateSignature(): string
    
    // 搜尋範圍
    public function scopeByUser($query, $userId)
    public function scopeByType($query, $type)
    public function scopeByDateRange($query, $from, $to)
    public function scopeSecurityEvents($query)
    public function scopeHighRisk($query)
}
```

### SecurityAlert 模型

```php
class SecurityAlert extends Model
{
    protected $fillable = [
        'activity_id', 'type', 'severity', 'title', 'description',
        'rule_id', 'acknowledged_at', 'acknowledged_by'
    ];
    
    protected $casts = [
        'acknowledged_at' => 'datetime',
        'created_at' => 'datetime',
    ];
    
    // 關聯關係
    public function activity(): BelongsTo
    public function acknowledgedBy(): BelongsTo
    public function rule(): BelongsTo
    
    // 計算屬性
    public function getIsAcknowledgedAttribute(): bool
    public function getSeverityColorAttribute(): string
    
    // 操作方法
    public function acknowledge(User $user): void
    public function escalate(): void
}
```

### MonitorRule 模型

```php
class MonitorRule extends Model
{
    protected $fillable = [
        'name', 'description', 'conditions', 'actions',
        'is_active', 'created_by', 'priority'
    ];
    
    protected $casts = [
        'conditions' => 'json',
        'actions' => 'json',
        'is_active' => 'boolean',
    ];
    
    // 關聯關係
    public function creator(): BelongsTo
    public function alerts(): HasMany
    
    // 規則檢查
    public function matches(Activity $activity): bool
    public function execute(Activity $activity): void
    
    // 統計
    public function getTriggeredCountAttribute(): int
    public function getLastTriggeredAttribute(): ?Carbon
}
```

## 使用者介面設計

### 活動記錄列表頁面

```
┌─────────────────────────────────────────────────────────────┐
│  活動記錄                    [即時監控] [匯出] [統計分析]     │
├─────────────────────────────────────────────────────────────┤
│  [搜尋框] [日期範圍] [使用者] [類型] [結果] [IP] [清除篩選]   │
├─────────────────────────────────────────────────────────────┤
│  時間        使用者    類型    描述          IP        結果   │
│  10:30:25   admin    登入    管理員登入     192.168.1.1 成功 │
│  10:28:15   john     建立    建立使用者     192.168.1.5 成功 │
│  10:25:30   admin    刪除    刪除角色       192.168.1.1 成功 │
│  10:20:45   unknown  登入    登入失敗       10.0.0.1   失敗 │
│  ⚠️ 10:18:12  admin    權限    提升權限       192.168.1.1 警告 │
├─────────────────────────────────────────────────────────────┤
│                    [← 上一頁] 1 2 3 [下一頁 →]                │
└─────────────────────────────────────────────────────────────┘
```

### 活動詳情對話框

```
┌─────────────────────────────────────────────────────────────┐
│  活動詳情 #12345                        [匯出] [標記可疑]   │
├─────────────────────────────────────────────────────────────┤
│  基本資訊:                                                  │
│  時間: 2024-01-15 10:30:25                                 │
│  使用者: admin (系統管理員)                                  │
│  類型: 使用者管理 > 建立使用者                               │
│  描述: 建立新使用者 "john_doe"                              │
│  結果: 成功                                                 │
│  風險等級: 低 🟢                                            │
│                                                            │
│  技術資訊:                                                  │
│  IP 位址: 192.168.1.100                                    │
│  使用者代理: Mozilla/5.0 (Windows NT 10.0; Win64; x64)     │
│  Session ID: sess_abc123...                                │
│                                                            │
│  操作資料:                                                  │
│  {                                                         │
│    "username": "john_doe",                                 │
│    "email": "john@example.com",                           │
│    "roles": ["user"],                                     │
│    "is_active": true                                      │
│  }                                                         │
│                                                            │
│  相關活動: [顯示 3 筆相關記錄]                               │
│                                                            │
│  [關閉] [上一筆] [下一筆]                                    │
└─────────────────────────────────────────────────────────────┘
```

### 即時監控介面

```
┌─────────────────────────────────────────────────────────────┐
│  即時活動監控                          🟢 監控中 [停止監控]  │
├─────────────────────────────────────────────────────────────┤
│  監控規則: [管理規則]                                        │
│  • 登入失敗超過 5 次 (啟用)                                  │
│  • 異常 IP 存取 (啟用)                                       │
│  • 權限提升操作 (啟用)                                       │
│  • 批量資料操作 (停用)                                       │
│                                                            │
│  最新活動: (自動更新)                                        │
│  ┌────────────────────────────────────────────────────────┐  │
│  │ 🔴 10:35:42 - 安全警報                                │  │
│  │    使用者 "unknown" 從 IP 10.0.0.1 登入失敗 6 次       │  │
│  │    [查看詳情] [封鎖 IP] [忽略]                         │  │
│  └────────────────────────────────────────────────────────┘  │
│  ┌────────────────────────────────────────────────────────┐  │
│  │ 🟡 10:34:15 - 一般活動                                │  │
│  │    管理員 "admin" 建立新角色 "editor"                  │  │
│  │    [查看詳情]                                         │  │
│  └────────────────────────────────────────────────────────┘  │
│                                                            │
│  統計摘要:                                                  │
│  今日活動: 1,234 筆 | 安全事件: 3 筆 | 警報: 1 筆           │
└─────────────────────────────────────────────────────────────┘
```

### 統計分析頁面

```
┌─────────────────────────────────────────────────────────────┐
│  活動統計分析                    [時間範圍: 7天▼] [匯出報告] │
├─────────────────────────────────────────────────────────────┤
│  ┌─ 活動趨勢圖 ─────────────────────────────────────────────┐ │
│  │     1000 ┤                                             │ │
│  │      800 ┤     ●                                       │ │
│  │      600 ┤   ●   ●                                     │ │
│  │      400 ┤ ●       ●   ●                               │ │
│  │      200 ┤           ●   ●   ●                         │ │
│  │        0 └─────────────────────────────────────────────│ │
│  │          一  二  三  四  五  六  日                      │ │
│  └─────────────────────────────────────────────────────────┘ │
│                                                            │
│  ┌─ 活動類型分佈 ─┐  ┌─ 最活躍使用者 ─┐  ┌─ 安全事件 ─────┐ │
│  │ 登入/登出 45%  │  │ 1. admin  234  │  │ 登入失敗  12   │ │
│  │ 資料操作 30%   │  │ 2. john   156  │  │ 權限異常   3   │ │
│  │ 系統設定 15%   │  │ 3. mary   98   │  │ IP 異常    2   │ │
│  │ 其他     10%   │  │ 4. bob    67   │  │ 總計      17   │ │
│  └───────────────┘  └───────────────┘  └───────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## 安全性設計

### 完整性保護

```php
class ActivityIntegrityService
{
    public function generateSignature(array $data): string
    {
        $payload = json_encode($data, JSON_SORT_KEYS);
        return hash_hmac('sha256', $payload, config('app.key'));
    }
    
    public function verifyActivity(Activity $activity): bool
    {
        $data = $activity->only(['type', 'description', 'properties', 'created_at']);
        $expectedSignature = $this->generateSignature($data);
        return hash_equals($expectedSignature, $activity->signature);
    }
    
    public function verifyBatch(Collection $activities): array
    {
        $results = [];
        foreach ($activities as $activity) {
            $results[$activity->id] = $this->verifyActivity($activity);
        }
        return $results;
    }
}
```

### 敏感資料保護

```php
class SensitiveDataFilter
{
    protected array $sensitiveFields = [
        'password', 'token', 'secret', 'key', 'credit_card'
    ];
    
    public function filterProperties(array $properties): array
    {
        return $this->recursiveFilter($properties);
    }
    
    protected function recursiveFilter(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->recursiveFilter($value);
            } elseif ($this->isSensitiveField($key)) {
                $data[$key] = '[FILTERED]';
            }
        }
        return $data;
    }
    
    protected function isSensitiveField(string $field): bool
    {
        foreach ($this->sensitiveFields as $sensitive) {
            if (stripos($field, $sensitive) !== false) {
                return true;
            }
        }
        return false;
    }
}
```

## 效能優化

### 非同步記錄

```php
class AsyncActivityLogger
{
    public function logAsync(string $type, string $description, array $data = []): void
    {
        dispatch(new LogActivityJob($type, $description, $data))
            ->onQueue('activities');
    }
    
    public function logBatch(array $activities): void
    {
        $chunks = array_chunk($activities, 100);
        foreach ($chunks as $chunk) {
            dispatch(new LogActivitiesBatchJob($chunk))
                ->onQueue('activities');
        }
    }
}

class LogActivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function handle(): void
    {
        Activity::create([
            'type' => $this->type,
            'description' => $this->description,
            'properties' => $this->data,
            'signature' => $this->generateSignature(),
            // ... 其他欄位
        ]);
    }
}
```

### 查詢優化

```php
// 索引優化
Schema::table('activities', function (Blueprint $table) {
    $table->index(['created_at', 'type']);
    $table->index(['causer_id', 'causer_type']);
    $table->index(['subject_id', 'subject_type']);
    $table->index('ip_address');
    $table->index('risk_level');
});

// 分區表設計（按月分區）
class ActivityPartitionService
{
    public function createMonthlyPartition(Carbon $date): void
    {
        $tableName = 'activities_' . $date->format('Y_m');
        // 建立分區表邏輯
    }
    
    public function queryAcrossPartitions(array $filters): Collection
    {
        // 跨分區查詢邏輯
    }
}
```