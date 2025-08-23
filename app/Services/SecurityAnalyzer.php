<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\SecurityAlert;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 安全分析器服務
 * 
 * 負責分析活動記錄中的安全事件，檢測異常模式，
 * 計算風險評分並生成安全警報
 */
class SecurityAnalyzer
{
    /**
     * 安全事件類型定義
     */
    const SECURITY_EVENT_TYPES = [
        'login_failure' => '登入失敗',
        'privilege_escalation' => '權限提升',
        'sensitive_data_access' => '敏感資料存取',
        'system_config_change' => '系統設定變更',
        'suspicious_ip' => '異常 IP 存取',
        'bulk_operation' => '批量操作',
        'unusual_activity_pattern' => '異常活動模式',
        'multiple_failed_attempts' => '多次失敗嘗試',
        'off_hours_access' => '非工作時間存取',
        'geo_anomaly' => '地理位置異常'
    ];

    /**
     * 風險等級定義
     */
    const RISK_LEVELS = [
        'low' => 1,
        'medium' => 2,
        'high' => 3,
        'critical' => 4
    ];

    /**
     * 分析單個活動的安全風險
     *
     * @param Activity $activity 要分析的活動
     * @return array 分析結果
     */
    public function analyzeActivity(Activity $activity): array
    {
        $analysis = [
            'activity_id' => $activity->id,
            'risk_score' => 0,
            'risk_level' => 'low',
            'security_events' => [],
            'anomalies' => [],
            'recommendations' => []
        ];

        // 檢測各種安全事件
        $analysis['security_events'] = $this->detectSecurityEvents($activity);
        
        // 計算風險評分
        $analysis['risk_score'] = $this->calculateRiskScore($activity);
        
        // 確定風險等級
        $analysis['risk_level'] = $this->determineRiskLevel($analysis['risk_score']);
        
        // 檢測異常
        $analysis['anomalies'] = $this->detectActivityAnomalies($activity);
        
        // 生成建議
        $analysis['recommendations'] = $this->generateRecommendations($analysis);

        return $analysis;
    }

    /**
     * 檢測活動中的安全事件
     *
     * @param Activity $activity
     * @return array
     */
    protected function detectSecurityEvents(Activity $activity): array
    {
        $events = [];

        // 檢測登入失敗
        if ($this->isLoginFailure($activity)) {
            $events[] = [
                'type' => 'login_failure',
                'description' => '登入失敗嘗試',
                'severity' => $this->getLoginFailureSeverity($activity)
            ];
        }

        // 檢測權限提升
        if ($this->isPrivilegeEscalation($activity)) {
            $events[] = [
                'type' => 'privilege_escalation',
                'description' => '權限提升操作',
                'severity' => 'high'
            ];
        }

        // 檢測敏感資料存取
        if ($this->isSensitiveDataAccess($activity)) {
            $events[] = [
                'type' => 'sensitive_data_access',
                'description' => '敏感資料存取',
                'severity' => 'medium'
            ];
        }

        // 檢測系統設定變更
        if ($this->isSystemConfigChange($activity)) {
            $events[] = [
                'type' => 'system_config_change',
                'description' => '系統設定變更',
                'severity' => 'high'
            ];
        }

        // 檢測異常 IP
        if ($this->isSuspiciousIP($activity)) {
            $events[] = [
                'type' => 'suspicious_ip',
                'description' => '來自異常 IP 的存取',
                'severity' => 'medium'
            ];
        }

        // 檢測批量操作
        if ($this->isBulkOperation($activity)) {
            $events[] = [
                'type' => 'bulk_operation',
                'description' => '批量操作執行',
                'severity' => 'medium'
            ];
        }

        return $events;
    }

    /**
     * 計算活動的風險評分
     *
     * @param Activity $activity
     * @return int 風險評分 (0-100)
     */
    public function calculateRiskScore(Activity $activity): int
    {
        $score = 0;

        // 基礎風險評分
        $score += $this->getBaseRiskScore($activity);
        
        // 使用者風險評分
        $score += $this->getUserRiskScore($activity);
        
        // IP 風險評分
        $score += $this->getIPRiskScore($activity);
        
        // 時間風險評分
        $score += $this->getTimeRiskScore($activity);
        
        // 操作類型風險評分
        $score += $this->getOperationRiskScore($activity);
        
        // 頻率風險評分
        $score += $this->getFrequencyRiskScore($activity);

        return min($score, 100); // 最高 100 分
    }

    /**
     * 檢測活動中的異常模式
     *
     * @param Collection $activities 活動集合
     * @return array 異常列表
     */
    public function detectAnomalies(Collection $activities): array
    {
        $anomalies = [];

        // 檢測頻率異常
        $frequencyAnomalies = $this->detectFrequencyAnomalies($activities);
        $anomalies = array_merge($anomalies, $frequencyAnomalies);

        // 檢測時間異常
        $timeAnomalies = $this->detectTimeAnomalies($activities);
        $anomalies = array_merge($anomalies, $timeAnomalies);

        // 檢測地理位置異常
        $geoAnomalies = $this->detectGeoAnomalies($activities);
        $anomalies = array_merge($anomalies, $geoAnomalies);

        // 檢測行為模式異常
        $behaviorAnomalies = $this->detectBehaviorAnomalies($activities);
        $anomalies = array_merge($anomalies, $behaviorAnomalies);

        return $anomalies;
    }

    /**
     * 識別使用者的活動模式
     *
     * @param string $userId 使用者 ID
     * @param string $timeRange 時間範圍
     * @return array 模式分析結果
     */
    public function identifyPatterns(string $userId, string $timeRange): array
    {
        $cacheKey = "user_patterns_{$userId}_{$timeRange}";
        
        return Cache::remember($cacheKey, 3600, function () use ($userId, $timeRange) {
            $activities = $this->getUserActivities($userId, $timeRange);
            
            return [
                'user_id' => $userId,
                'time_range' => $timeRange,
                'total_activities' => $activities->count(),
                'activity_types' => $this->analyzeActivityTypes($activities),
                'time_patterns' => $this->analyzeTimePatterns($activities),
                'ip_patterns' => $this->analyzeIPPatterns($activities),
                'risk_trends' => $this->analyzeRiskTrends($activities),
                'anomaly_score' => $this->calculateAnomalyScore($activities)
            ];
        });
    }

    /**
     * 生成安全報告
     *
     * @param string $timeRange 時間範圍
     * @return array 安全報告
     */
    public function generateSecurityReport(string $timeRange): array
    {
        $startDate = $this->parseTimeRange($timeRange);
        
        $activities = Activity::where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();

        $securityEvents = $activities->filter(function ($activity) {
            return $activity->risk_level >= self::RISK_LEVELS['medium'];
        });

        return [
            'period' => $timeRange,
            'start_date' => $startDate->toDateString(),
            'end_date' => now()->toDateString(),
            'summary' => [
                'total_activities' => $activities->count(),
                'security_events' => $securityEvents->count(),
                'high_risk_events' => $securityEvents->where('risk_level', '>=', self::RISK_LEVELS['high'])->count(),
                'critical_events' => $securityEvents->where('risk_level', self::RISK_LEVELS['critical'])->count()
            ],
            'top_risks' => $this->getTopRisks($securityEvents),
            'threat_trends' => $this->analyzeThreatTrends($activities),
            'user_risk_ranking' => $this->getUserRiskRanking($activities),
            'ip_risk_analysis' => $this->getIPRiskAnalysis($activities),
            'recommendations' => $this->generateSecurityRecommendations($activities)
        ];
    }

    /**
     * 檢查可疑 IP 地址
     *
     * @return Collection 可疑 IP 列表
     */
    public function checkSuspiciousIPs(): Collection
    {
        $suspiciousIPs = collect();
        
        // 獲取最近的活動記錄
        $recentActivities = Activity::where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('ip_address')
            ->get()
            ->groupBy('ip_address');

        foreach ($recentActivities as $ip => $activities) {
            $riskScore = $this->calculateIPRiskScore($ip, $activities);
            
            if ($riskScore >= 70) { // 高風險閾值
                $suspiciousIPs->push([
                    'ip_address' => $ip,
                    'risk_score' => $riskScore,
                    'activity_count' => $activities->count(),
                    'failed_logins' => $activities->where('result', 'failed')->count(),
                    'unique_users' => $activities->pluck('causer_id')->unique()->count(),
                    'first_seen' => $activities->min('created_at'),
                    'last_seen' => $activities->max('created_at'),
                    'threat_indicators' => $this->getIPThreatIndicators($ip, $activities)
                ]);
            }
        }

        return $suspiciousIPs->sortByDesc('risk_score');
    }

    /**
     * 監控登入失敗
     *
     * @return array 登入失敗分析
     */
    public function monitorFailedLogins(): array
    {
        $timeWindow = now()->subHours(24);
        
        $failedLogins = Activity::where('type', 'login')
            ->where('result', 'failed')
            ->where('created_at', '>=', $timeWindow)
            ->get();

        $analysis = [
            'total_failures' => $failedLogins->count(),
            'unique_ips' => $failedLogins->pluck('ip_address')->unique()->count(),
            'unique_usernames' => $failedLogins->pluck('properties->username')->unique()->count(),
            'peak_hours' => $this->getFailuresPeakHours($failedLogins),
            'top_failing_ips' => $this->getTopFailingIPs($failedLogins),
            'brute_force_attempts' => $this->detectBruteForceAttempts($failedLogins),
            'credential_stuffing' => $this->detectCredentialStuffing($failedLogins)
        ];

        return $analysis;
    }

    /**
     * 生成安全警報
     *
     * @param Activity $activity 觸發警報的活動
     * @param array $securityEvents 檢測到的安全事件
     * @return SecurityAlert|null 生成的警報
     */
    public function generateSecurityAlert(Activity $activity, array $securityEvents): ?SecurityAlert
    {
        if (empty($securityEvents)) {
            return null;
        }

        $highestSeverity = $this->getHighestSeverity($securityEvents);
        
        // 只為中等以上風險生成警報
        if (!in_array($highestSeverity, ['medium', 'high', 'critical'])) {
            return null;
        }

        $alert = SecurityAlert::create([
            'activity_id' => $activity->id,
            'type' => $securityEvents[0]['type'],
            'severity' => $highestSeverity,
            'title' => $this->generateAlertTitle($securityEvents),
            'description' => $this->generateAlertDescription($activity, $securityEvents),
            'rule_id' => null, // 如果有對應的監控規則可以設定
        ]);

        // 記錄警報生成
        Log::warning('Security alert generated', [
            'alert_id' => $alert->id,
            'activity_id' => $activity->id,
            'severity' => $highestSeverity,
            'events' => $securityEvents
        ]);

        return $alert;
    }

    // ========== 私有輔助方法 ==========

    /**
     * 檢測是否為登入失敗
     */
    protected function isLoginFailure(Activity $activity): bool
    {
        return $activity->type === 'login' && $activity->result === 'failed';
    }

    /**
     * 檢測是否為權限提升
     */
    protected function isPrivilegeEscalation(Activity $activity): bool
    {
        $privilegeOperations = ['roles.assign', 'permissions.grant', 'user.promote'];
        return in_array($activity->type, $privilegeOperations);
    }

    /**
     * 檢測是否為敏感資料存取
     */
    protected function isSensitiveDataAccess(Activity $activity): bool
    {
        $sensitiveTypes = ['users.view', 'system.settings', 'security.audit'];
        return in_array($activity->type, $sensitiveTypes);
    }

    /**
     * 檢測是否為系統設定變更
     */
    protected function isSystemConfigChange(Activity $activity): bool
    {
        return str_starts_with($activity->type, 'system.') || 
               str_starts_with($activity->type, 'config.');
    }

    /**
     * 檢測是否為可疑 IP
     */
    protected function isSuspiciousIP(Activity $activity): bool
    {
        if (!$activity->ip_address) {
            return false;
        }

        // 檢查是否為已知的可疑 IP
        $suspiciousIPs = Cache::get('suspicious_ips', []);
        return in_array($activity->ip_address, $suspiciousIPs);
    }

    /**
     * 檢測是否為批量操作
     */
    protected function isBulkOperation(Activity $activity): bool
    {
        return str_contains($activity->description, '批量') || 
               str_contains($activity->description, 'bulk') ||
               ($activity->properties['batch_size'] ?? 0) > 10;
    }

    /**
     * 獲取登入失敗的嚴重程度
     */
    protected function getLoginFailureSeverity(Activity $activity): string
    {
        $recentFailures = Activity::where('type', 'login')
            ->where('result', 'failed')
            ->where('ip_address', $activity->ip_address)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentFailures >= 10) return 'critical';
        if ($recentFailures >= 5) return 'high';
        if ($recentFailures >= 3) return 'medium';
        return 'low';
    }

    /**
     * 獲取基礎風險評分
     */
    protected function getBaseRiskScore(Activity $activity): int
    {
        $riskMap = [
            'login' => 5,
            'logout' => 1,
            'create' => 10,
            'update' => 8,
            'delete' => 15,
            'system' => 20,
            'security' => 25
        ];

        foreach ($riskMap as $type => $score) {
            if (str_contains($activity->type, $type)) {
                return $score;
            }
        }

        return 5; // 預設風險分數
    }

    /**
     * 獲取使用者風險評分
     */
    protected function getUserRiskScore(Activity $activity): int
    {
        if (!$activity->user_id) {
            return 20; // 匿名操作風險較高
        }

        $user = User::find($activity->user_id);
        if (!$user) {
            return 25; // 使用者不存在風險很高
        }

        // 根據使用者角色調整風險
        if ($user->hasRole('super_admin')) return 5;
        if ($user->hasRole('admin')) return 8;
        return 10;
    }

    /**
     * 獲取 IP 風險評分
     */
    protected function getIPRiskScore(Activity $activity): int
    {
        if (!$activity->ip_address) {
            return 15;
        }

        // 檢查內網 IP
        if ($this->isInternalIP($activity->ip_address)) {
            return 5;
        }

        // 檢查已知惡意 IP
        if ($this->isMaliciousIP($activity->ip_address)) {
            return 30;
        }

        return 10;
    }

    /**
     * 獲取時間風險評分
     */
    protected function getTimeRiskScore(Activity $activity): int
    {
        $hour = $activity->created_at->hour;
        
        // 工作時間 (9-18) 風險較低
        if ($hour >= 9 && $hour <= 18) {
            return 0;
        }
        
        // 深夜時間 (22-6) 風險較高
        if ($hour >= 22 || $hour <= 6) {
            return 15;
        }
        
        return 5; // 其他時間
    }

    /**
     * 獲取操作類型風險評分
     */
    protected function getOperationRiskScore(Activity $activity): int
    {
        if ($activity->result === 'failed') {
            return 10;
        }

        $highRiskOperations = ['delete', 'system', 'security', 'admin'];
        foreach ($highRiskOperations as $operation) {
            if (str_contains($activity->type, $operation)) {
                return 15;
            }
        }

        return 0;
    }

    /**
     * 獲取頻率風險評分
     */
    protected function getFrequencyRiskScore(Activity $activity): int
    {
        $recentCount = Activity::where('user_id', $activity->user_id)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recentCount > 50) return 25;
        if ($recentCount > 20) return 15;
        if ($recentCount > 10) return 10;
        return 0;
    }

    /**
     * 確定風險等級
     */
    protected function determineRiskLevel(int $score): string
    {
        if ($score >= 80) return 'critical';
        if ($score >= 60) return 'high';
        if ($score >= 30) return 'medium';
        return 'low';
    }

    /**
     * 檢測單個活動的異常
     */
    protected function detectActivityAnomalies(Activity $activity): array
    {
        $anomalies = [];

        // 檢測時間異常
        if ($this->isOffHoursActivity($activity)) {
            $anomalies[] = [
                'type' => 'off_hours_access',
                'description' => '非工作時間存取',
                'severity' => 'medium'
            ];
        }

        // 檢測地理位置異常
        if ($this->isGeoAnomaly($activity)) {
            $anomalies[] = [
                'type' => 'geo_anomaly',
                'description' => '地理位置異常',
                'severity' => 'high'
            ];
        }

        return $anomalies;
    }

    /**
     * 生成建議
     */
    protected function generateRecommendations(array $analysis): array
    {
        $recommendations = [];

        if ($analysis['risk_score'] >= 60) {
            $recommendations[] = '建議立即檢查此活動的詳細資訊';
        }

        if (!empty($analysis['security_events'])) {
            $recommendations[] = '建議加強對此使用者的監控';
        }

        if (!empty($analysis['anomalies'])) {
            $recommendations[] = '建議驗證此操作的合法性';
        }

        return $recommendations;
    }

    /**
     * 解析時間範圍
     */
    protected function parseTimeRange(string $timeRange): Carbon
    {
        return match($timeRange) {
            '1d' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => now()->subDays(7)
        };
    }

    /**
     * 檢查是否為內網 IP
     */
    protected function isInternalIP(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * 檢查是否為惡意 IP
     */
    protected function isMaliciousIP(string $ip): bool
    {
        // 這裡可以整合外部威脅情報 API
        $maliciousIPs = Cache::get('malicious_ips', []);
        return in_array($ip, $maliciousIPs);
    }

    /**
     * 檢測是否為非工作時間活動
     */
    protected function isOffHoursActivity(Activity $activity): bool
    {
        $hour = $activity->created_at->hour;
        return $hour < 8 || $hour > 20;
    }

    /**
     * 檢測地理位置異常
     */
    protected function isGeoAnomaly(Activity $activity): bool
    {
        // 簡化實作，實際可以整合 GeoIP 服務
        return false;
    }

    /**
     * 獲取最高嚴重程度
     */
    protected function getHighestSeverity(array $events): string
    {
        $severityOrder = ['low', 'medium', 'high', 'critical'];
        $maxSeverity = 'low';

        foreach ($events as $event) {
            $currentIndex = array_search($event['severity'], $severityOrder);
            $maxIndex = array_search($maxSeverity, $severityOrder);
            
            if ($currentIndex > $maxIndex) {
                $maxSeverity = $event['severity'];
            }
        }

        return $maxSeverity;
    }

    /**
     * 生成警報標題
     */
    protected function generateAlertTitle(array $events): string
    {
        $eventTypes = array_column($events, 'type');
        $mainType = $eventTypes[0];
        
        return self::SECURITY_EVENT_TYPES[$mainType] ?? '安全事件';
    }

    /**
     * 生成警報描述
     */
    protected function generateAlertDescription(Activity $activity, array $events): string
    {
        $description = "檢測到安全事件：\n";
        
        foreach ($events as $event) {
            $description .= "- {$event['description']} (嚴重程度: {$event['severity']})\n";
        }
        
        $description .= "\n活動詳情：\n";
        $description .= "時間: {$activity->created_at}\n";
        $description .= "使用者: " . ($activity->user->name ?? '未知') . "\n";
        $description .= "IP 位址: {$activity->ip_address}\n";
        $description .= "操作: {$activity->description}";
        
        return $description;
    }   
 /**
     * 檢測頻率異常
     */
    protected function detectFrequencyAnomalies(Collection $activities): array
    {
        $anomalies = [];
        $userActivities = $activities->groupBy('user_id');

        foreach ($userActivities as $userId => $userActivities) {
            $activityCount = $userActivities->count();
            $timeSpan = $userActivities->max('created_at')->diffInMinutes($userActivities->min('created_at'));
            
            if ($timeSpan > 0) {
                $frequency = $activityCount / ($timeSpan / 60); // 每小時活動數
                
                if ($frequency > 100) { // 每小時超過 100 次活動
                    $anomalies[] = [
                        'type' => 'high_frequency',
                        'user_id' => $userId,
                        'description' => "使用者活動頻率異常高 ({$frequency} 次/小時)",
                        'severity' => 'high',
                        'details' => [
                            'activity_count' => $activityCount,
                            'time_span_minutes' => $timeSpan,
                            'frequency_per_hour' => round($frequency, 2)
                        ]
                    ];
                }
            }
        }

        return $anomalies;
    }

    /**
     * 檢測時間異常
     */
    protected function detectTimeAnomalies(Collection $activities): array
    {
        $anomalies = [];
        $offHoursActivities = $activities->filter(function ($activity) {
            return $this->isOffHoursActivity($activity);
        });

        if ($offHoursActivities->count() > $activities->count() * 0.3) { // 超過 30% 的活動在非工作時間
            $anomalies[] = [
                'type' => 'off_hours_pattern',
                'description' => '大量非工作時間活動',
                'severity' => 'medium',
                'details' => [
                    'off_hours_count' => $offHoursActivities->count(),
                    'total_count' => $activities->count(),
                    'percentage' => round(($offHoursActivities->count() / $activities->count()) * 100, 2)
                ]
            ];
        }

        return $anomalies;
    }

    /**
     * 檢測地理位置異常
     */
    protected function detectGeoAnomalies(Collection $activities): array
    {
        $anomalies = [];
        $ipGroups = $activities->groupBy('ip_address');

        // 檢測來自多個不同地理位置的快速切換
        foreach ($ipGroups as $ip => $ipActivities) {
            if ($this->isGeoLocationJump($ip, $ipActivities)) {
                $anomalies[] = [
                    'type' => 'geo_jump',
                    'description' => '地理位置快速切換',
                    'severity' => 'high',
                    'details' => [
                        'ip_address' => $ip,
                        'activity_count' => $ipActivities->count()
                    ]
                ];
            }
        }

        return $anomalies;
    }

    /**
     * 檢測行為模式異常
     */
    protected function detectBehaviorAnomalies(Collection $activities): array
    {
        $anomalies = [];
        
        // 檢測異常的操作序列
        $operationSequences = $this->analyzeOperationSequences($activities);
        
        foreach ($operationSequences as $sequence) {
            if ($sequence['anomaly_score'] > 0.8) {
                $anomalies[] = [
                    'type' => 'unusual_sequence',
                    'description' => '異常操作序列',
                    'severity' => 'medium',
                    'details' => $sequence
                ];
            }
        }

        return $anomalies;
    }

    /**
     * 獲取使用者活動
     */
    protected function getUserActivities(string $userId, string $timeRange): Collection
    {
        $startDate = $this->parseTimeRange($timeRange);
        
        return Activity::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 分析活動類型
     */
    protected function analyzeActivityTypes(Collection $activities): array
    {
        $types = $activities->groupBy('type')->map(function ($group) {
            return $group->count();
        })->sortDesc();

        return [
            'distribution' => $types->toArray(),
            'most_common' => $types->keys()->first(),
            'diversity_score' => $types->count() / max($activities->count(), 1)
        ];
    }

    /**
     * 分析時間模式
     */
    protected function analyzeTimePatterns(Collection $activities): array
    {
        $hourlyDistribution = $activities->groupBy(function ($activity) {
            return $activity->created_at->hour;
        })->map->count();

        $dailyDistribution = $activities->groupBy(function ($activity) {
            return $activity->created_at->dayOfWeek;
        })->map->count();

        return [
            'hourly_distribution' => $hourlyDistribution->toArray(),
            'daily_distribution' => $dailyDistribution->toArray(),
            'peak_hour' => $hourlyDistribution->keys()->first(),
            'peak_day' => $dailyDistribution->keys()->first()
        ];
    }

    /**
     * 分析 IP 模式
     */
    protected function analyzeIPPatterns(Collection $activities): array
    {
        $ipDistribution = $activities->groupBy('ip_address')->map->count()->sortDesc();
        
        return [
            'unique_ips' => $ipDistribution->count(),
            'most_used_ip' => $ipDistribution->keys()->first(),
            'ip_diversity' => $ipDistribution->count() / max($activities->count(), 1),
            'suspicious_ips' => $this->identifySuspiciousIPs($ipDistribution)
        ];
    }

    /**
     * 分析風險趨勢
     */
    protected function analyzeRiskTrends(Collection $activities): array
    {
        $riskByDay = $activities->groupBy(function ($activity) {
            return $activity->created_at->toDateString();
        })->map(function ($dayActivities) {
            return $dayActivities->avg('risk_level') ?? 0;
        });

        return [
            'daily_risk' => $riskByDay->toArray(),
            'trend_direction' => $this->calculateTrendDirection($riskByDay),
            'average_risk' => $activities->avg('risk_level') ?? 0
        ];
    }

    /**
     * 計算異常分數
     */
    protected function calculateAnomalyScore(Collection $activities): float
    {
        $score = 0;
        
        // 基於活動頻率的異常分數
        $avgFrequency = $activities->count() / max($activities->groupBy(function ($activity) {
            return $activity->created_at->toDateString();
        })->count(), 1);
        
        if ($avgFrequency > 50) $score += 0.3;
        
        // 基於風險等級的異常分數
        $highRiskCount = $activities->where('risk_level', '>=', self::RISK_LEVELS['high'])->count();
        $riskRatio = $highRiskCount / max($activities->count(), 1);
        
        $score += $riskRatio * 0.5;
        
        // 基於時間模式的異常分數
        $offHoursRatio = $activities->filter(function ($activity) {
            return $this->isOffHoursActivity($activity);
        })->count() / max($activities->count(), 1);
        
        $score += $offHoursRatio * 0.2;
        
        return min($score, 1.0);
    }

    /**
     * 獲取頂級風險
     */
    protected function getTopRisks(Collection $securityEvents): array
    {
        return $securityEvents->sortByDesc('risk_level')
            ->take(10)
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'type' => $activity->type,
                    'description' => $activity->description,
                    'risk_level' => $activity->risk_level,
                    'created_at' => $activity->created_at,
                    'user' => $activity->causer->name ?? '未知',
                    'ip_address' => $activity->ip_address
                ];
            })->values()->toArray();
    }

    /**
     * 分析威脅趨勢
     */
    protected function analyzeThreatTrends(Collection $activities): array
    {
        $dailyThreats = $activities->where('risk_level', '>=', self::RISK_LEVELS['medium'])
            ->groupBy(function ($activity) {
                return $activity->created_at->toDateString();
            })
            ->map->count();

        return [
            'daily_counts' => $dailyThreats->toArray(),
            'trend' => $this->calculateTrendDirection($dailyThreats),
            'peak_day' => $dailyThreats->keys()->first()
        ];
    }

    /**
     * 獲取使用者風險排名
     */
    protected function getUserRiskRanking(Collection $activities): array
    {
        return $activities->groupBy('user_id')
            ->map(function ($userActivities) {
                $user = $userActivities->first()->user;
                return [
                    'user_id' => $user->id ?? null,
                    'username' => $user->username ?? '未知',
                    'name' => $user->name ?? '未知',
                    'activity_count' => $userActivities->count(),
                    'avg_risk_score' => $userActivities->avg('risk_level') ?? 0,
                    'high_risk_activities' => $userActivities->where('risk_level', '>=', self::RISK_LEVELS['high'])->count()
                ];
            })
            ->sortByDesc('avg_risk_score')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * 獲取 IP 風險分析
     */
    protected function getIPRiskAnalysis(Collection $activities): array
    {
        return $activities->groupBy('ip_address')
            ->map(function ($ipActivities, $ip) {
                return [
                    'ip_address' => $ip,
                    'activity_count' => $ipActivities->count(),
                    'unique_users' => $ipActivities->pluck('causer_id')->unique()->count(),
                    'failed_attempts' => $ipActivities->where('result', 'failed')->count(),
                    'avg_risk_score' => $ipActivities->avg('risk_level') ?? 0,
                    'is_internal' => $this->isInternalIP($ip),
                    'threat_score' => $this->calculateIPThreatScore($ip, $ipActivities)
                ];
            })
            ->sortByDesc('threat_score')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * 生成安全建議
     */
    protected function generateSecurityRecommendations(Collection $activities): array
    {
        $recommendations = [];
        
        $highRiskCount = $activities->where('risk_level', '>=', self::RISK_LEVELS['high'])->count();
        if ($highRiskCount > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'incident_response',
                'title' => '處理高風險事件',
                'description' => "發現 {$highRiskCount} 個高風險事件，建議立即調查"
            ];
        }
        
        $failedLogins = $activities->where('type', 'login')->where('result', 'failed')->count();
        if ($failedLogins > 10) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'authentication',
                'title' => '加強登入安全',
                'description' => "檢測到 {$failedLogins} 次登入失敗，建議啟用帳號鎖定機制"
            ];
        }
        
        $offHoursActivities = $activities->filter(function ($activity) {
            return $this->isOffHoursActivity($activity);
        })->count();
        
        if ($offHoursActivities > $activities->count() * 0.2) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'access_control',
                'title' => '監控非工作時間存取',
                'description' => '非工作時間活動較多，建議加強時間存取控制'
            ];
        }
        
        return $recommendations;
    }

    /**
     * 計算 IP 風險分數
     */
    protected function calculateIPRiskScore(string $ip, Collection $activities): int
    {
        $score = 0;
        
        // 失敗登入次數
        $failedLogins = $activities->where('result', 'failed')->count();
        $score += min($failedLogins * 5, 30);
        
        // 不同使用者數量
        $uniqueUsers = $activities->pluck('causer_id')->unique()->count();
        if ($uniqueUsers > 5) $score += 20;
        
        // 活動頻率
        $timeSpan = $activities->max('created_at')->diffInHours($activities->min('created_at'));
        if ($timeSpan > 0) {
            $frequency = $activities->count() / $timeSpan;
            if ($frequency > 10) $score += 15;
        }
        
        // 外部 IP 額外風險
        if (!$this->isInternalIP($ip)) {
            $score += 10;
        }
        
        return min($score, 100);
    }

    /**
     * 獲取 IP 威脅指標
     */
    protected function getIPThreatIndicators(string $ip, Collection $activities): array
    {
        $indicators = [];
        
        $failedLogins = $activities->where('result', 'failed')->count();
        if ($failedLogins >= 5) {
            $indicators[] = "多次登入失敗 ({$failedLogins} 次)";
        }
        
        $uniqueUsers = $activities->pluck('causer_id')->unique()->count();
        if ($uniqueUsers >= 3) {
            $indicators[] = "嘗試多個使用者帳號 ({$uniqueUsers} 個)";
        }
        
        if (!$this->isInternalIP($ip)) {
            $indicators[] = "外部 IP 位址";
        }
        
        $offHoursCount = $activities->filter(function ($activity) {
            return $this->isOffHoursActivity($activity);
        })->count();
        
        if ($offHoursCount > 0) {
            $indicators[] = "非工作時間存取 ({$offHoursCount} 次)";
        }
        
        return $indicators;
    }

    /**
     * 獲取失敗登入的高峰時段
     */
    protected function getFailuresPeakHours(Collection $failedLogins): array
    {
        return $failedLogins->groupBy(function ($activity) {
            return $activity->created_at->hour;
        })->map->count()->sortDesc()->take(3)->toArray();
    }

    /**
     * 獲取失敗次數最多的 IP
     */
    protected function getTopFailingIPs(Collection $failedLogins): array
    {
        return $failedLogins->groupBy('ip_address')
            ->map->count()
            ->sortDesc()
            ->take(5)
            ->toArray();
    }

    /**
     * 檢測暴力破解嘗試
     */
    protected function detectBruteForceAttempts(Collection $failedLogins): array
    {
        $bruteForceAttempts = [];
        
        $ipGroups = $failedLogins->groupBy('ip_address');
        
        foreach ($ipGroups as $ip => $attempts) {
            if ($attempts->count() >= 5) {
                $timeSpan = $attempts->max('created_at')->diffInMinutes($attempts->min('created_at'));
                
                if ($timeSpan <= 60) { // 1 小時內
                    $bruteForceAttempts[] = [
                        'ip_address' => $ip,
                        'attempt_count' => $attempts->count(),
                        'time_span_minutes' => $timeSpan,
                        'unique_usernames' => $attempts->pluck('properties.username')->unique()->count()
                    ];
                }
            }
        }
        
        return $bruteForceAttempts;
    }

    /**
     * 檢測憑證填充攻擊
     */
    protected function detectCredentialStuffing(Collection $failedLogins): array
    {
        $credentialStuffing = [];
        
        $usernameGroups = $failedLogins->groupBy('properties.username');
        
        foreach ($usernameGroups as $username => $attempts) {
            $uniqueIPs = $attempts->pluck('ip_address')->unique();
            
            if ($uniqueIPs->count() >= 3 && $attempts->count() >= 5) {
                $credentialStuffing[] = [
                    'username' => $username,
                    'attempt_count' => $attempts->count(),
                    'unique_ips' => $uniqueIPs->count(),
                    'ip_addresses' => $uniqueIPs->toArray()
                ];
            }
        }
        
        return $credentialStuffing;
    }

    /**
     * 檢測地理位置跳躍
     */
    protected function isGeoLocationJump(string $ip, Collection $activities): bool
    {
        // 簡化實作，實際應該整合 GeoIP 服務
        // 這裡假設如果同一個使用者在短時間內從不同 IP 存取就是異常
        $userGroups = $activities->groupBy('user_id');
        
        foreach ($userGroups as $userId => $userActivities) {
            $timeSpan = $userActivities->max('created_at')->diffInMinutes($userActivities->min('created_at'));
            $uniqueIPs = $userActivities->pluck('ip_address')->unique();
            
            if ($uniqueIPs->count() > 1 && $timeSpan < 60) { // 1小時內多個IP
                return true;
            }
        }
        
        return false;
    }

    /**
     * 分析操作序列
     */
    protected function analyzeOperationSequences(Collection $activities): array
    {
        $sequences = [];
        
        $userGroups = $activities->groupBy('user_id');
        
        foreach ($userGroups as $userId => $userActivities) {
            $sortedActivities = $userActivities->sortBy('created_at');
            $operationTypes = $sortedActivities->pluck('type')->toArray();
            
            // 檢測異常序列模式
            $anomalyScore = $this->calculateSequenceAnomalyScore($operationTypes);
            
            if ($anomalyScore > 0.5) {
                $sequences[] = [
                    'user_id' => $userId,
                    'sequence' => $operationTypes,
                    'anomaly_score' => $anomalyScore,
                    'activity_count' => count($operationTypes)
                ];
            }
        }
        
        return $sequences;
    }

    /**
     * 計算序列異常分數
     */
    protected function calculateSequenceAnomalyScore(array $operationTypes): float
    {
        $score = 0;
        
        // 檢測重複操作
        $typeCount = array_count_values($operationTypes);
        foreach ($typeCount as $type => $count) {
            if ($count > 10) { // 同一操作重複超過10次
                $score += 0.3;
            }
        }
        
        // 檢測危險操作序列
        $dangerousSequences = [
            ['login', 'users.view', 'users.delete'],
            ['login', 'roles.create', 'permissions.assign']
        ];
        
        foreach ($dangerousSequences as $dangerousSeq) {
            if ($this->containsSequence($operationTypes, $dangerousSeq)) {
                $score += 0.5;
            }
        }
        
        return min($score, 1.0);
    }

    /**
     * 檢查是否包含特定序列
     */
    protected function containsSequence(array $haystack, array $needle): bool
    {
        $needleLength = count($needle);
        $haystackLength = count($haystack);
        
        for ($i = 0; $i <= $haystackLength - $needleLength; $i++) {
            $match = true;
            for ($j = 0; $j < $needleLength; $j++) {
                if ($haystack[$i + $j] !== $needle[$j]) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 識別可疑 IP
     */
    protected function identifySuspiciousIPs(Collection $ipDistribution): array
    {
        return $ipDistribution->filter(function ($count, $ip) {
            return $count > 100 || !$this->isInternalIP($ip);
        })->keys()->toArray();
    }

    /**
     * 計算趨勢方向
     */
    protected function calculateTrendDirection(Collection $data): string
    {
        $values = $data->values();
        if ($values->count() < 2) {
            return 'stable';
        }
        
        $first = $values->first();
        $last = $values->last();
        
        if ($last > $first * 1.1) {
            return 'increasing';
        } elseif ($last < $first * 0.9) {
            return 'decreasing';
        }
        
        return 'stable';
    }

    /**
     * 計算 IP 威脅分數
     */
    protected function calculateIPThreatScore(string $ip, Collection $activities): int
    {
        return $this->calculateIPRiskScore($ip, $activities);
    }
}