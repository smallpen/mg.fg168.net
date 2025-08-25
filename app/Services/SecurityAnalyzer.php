<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\SecurityAlert;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SecurityAnalyzer
{
    /**
     * 分析單一活動記錄的安全風險
     */
    public function analyzeActivity(Activity $activity): array
    {
        $riskFactors = [];
        $riskScore = 0;

        // 檢查 IP 位址風險
        $ipRisk = $this->analyzeIpAddress($activity->ip_address);
        if ($ipRisk['score'] > 0) {
            $riskFactors[] = $ipRisk;
            $riskScore += $ipRisk['score'];
        }

        // 檢查使用者行為風險
        $userRisk = $this->analyzeUserBehavior($activity);
        if ($userRisk['score'] > 0) {
            $riskFactors[] = $userRisk;
            $riskScore += $userRisk['score'];
        }

        // 檢查操作類型風險
        $actionRisk = $this->analyzeActionType($activity);
        if ($actionRisk['score'] > 0) {
            $riskFactors[] = $actionRisk;
            $riskScore += $actionRisk['score'];
        }

        // 檢查時間模式風險
        $timeRisk = $this->analyzeTimePattern($activity);
        if ($timeRisk['score'] > 0) {
            $riskFactors[] = $timeRisk;
            $riskScore += $timeRisk['score'];
        }

        // 檢查頻率風險
        $frequencyRisk = $this->analyzeFrequency($activity);
        if ($frequencyRisk['score'] > 0) {
            $riskFactors[] = $frequencyRisk;
            $riskScore += $frequencyRisk['score'];
        }

        return [
            'risk_score' => min($riskScore, 10), // 最高風險分數為 10
            'risk_level' => $this->getRiskLevel($riskScore),
            'risk_factors' => $riskFactors,
            'recommendations' => $this->getRecommendations($riskFactors),
        ];
    }

    /**
     * 檢測異常活動模式
     */
    public function detectAnomalies(Collection $activities): array
    {
        $anomalies = [];

        // 檢測登入失敗模式
        $loginFailures = $this->detectLoginFailurePatterns($activities);
        if (!empty($loginFailures)) {
            $anomalies[] = [
                'type' => 'login_failure_pattern',
                'severity' => 'high',
                'description' => '檢測到異常登入失敗模式',
                'details' => $loginFailures,
            ];
        }

        // 檢測批量操作
        $bulkOperations = $this->detectBulkOperations($activities);
        if (!empty($bulkOperations)) {
            $anomalies[] = [
                'type' => 'bulk_operations',
                'severity' => 'medium',
                'description' => '檢測到批量操作',
                'details' => $bulkOperations,
            ];
        }

        // 檢測權限提升
        $privilegeEscalation = $this->detectPrivilegeEscalation($activities);
        if (!empty($privilegeEscalation)) {
            $anomalies[] = [
                'type' => 'privilege_escalation',
                'severity' => 'high',
                'description' => '檢測到權限提升活動',
                'details' => $privilegeEscalation,
            ];
        }

        // 檢測異常時間活動
        $offHoursActivity = $this->detectOffHoursActivity($activities);
        if (!empty($offHoursActivity)) {
            $anomalies[] = [
                'type' => 'off_hours_activity',
                'severity' => 'medium',
                'description' => '檢測到非工作時間活動',
                'details' => $offHoursActivity,
            ];
        }

        return $anomalies;
    }

    /**
     * 計算活動記錄的風險分數
     */
    public function calculateRiskScore(Activity $activity): int
    {
        $analysis = $this->analyzeActivity($activity);
        return $analysis['risk_score'];
    }

    /**
     * 識別使用者活動模式
     */
    public function identifyPatterns(string $userId, string $timeRange): array
    {
        $startDate = $this->parseTimeRange($timeRange);
        
        $activities = Activity::where('causer_id', $userId)
            ->where('causer_type', User::class)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at')
            ->get();

        return [
            'login_patterns' => $this->analyzeLoginPatterns($activities),
            'activity_frequency' => $this->analyzeActivityFrequency($activities),
            'ip_addresses' => $this->analyzeIpPatterns($activities),
            'action_types' => $this->analyzeActionPatterns($activities),
            'time_patterns' => $this->analyzeTimePatterns($activities),
        ];
    }

    /**
     * 生成安全報告
     */
    public function generateSecurityReport(string $timeRange): array
    {
        $startDate = $this->parseTimeRange($timeRange);
        
        return [
            'summary' => $this->getSecuritySummary($startDate),
            'top_risks' => $this->getTopRisks($startDate),
            'alert_statistics' => $this->getAlertStatistics($startDate),
            'user_activity' => $this->getUserActivitySummary($startDate),
            'ip_analysis' => $this->getIpAnalysis($startDate),
            'recommendations' => $this->getSecurityRecommendations($startDate),
        ];
    }

    /**
     * 檢查可疑 IP 位址
     */
    public function checkSuspiciousIPs(): Collection
    {
        $suspiciousIPs = collect();
        
        // 檢查登入失敗次數過多的 IP
        $failedLoginIPs = Activity::where('type', 'login_failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->select('ip_address', DB::raw('COUNT(*) as failure_count'))
            ->groupBy('ip_address')
            ->having('failure_count', '>', 5)
            ->get();

        foreach ($failedLoginIPs as $ip) {
            $suspiciousIPs->push([
                'ip_address' => $ip->ip_address,
                'reason' => 'excessive_login_failures',
                'details' => "24小時內登入失敗 {$ip->failure_count} 次",
                'risk_level' => 'high',
            ]);
        }

        // 檢查來自異常地理位置的 IP
        $foreignIPs = Activity::whereNotNull('ip_address')
            ->where('created_at', '>=', now()->subHours(24))
            ->whereNotIn('ip_address', $this->getTrustedIPs())
            ->select('ip_address', DB::raw('COUNT(*) as activity_count'))
            ->groupBy('ip_address')
            ->having('activity_count', '>', 10)
            ->get();

        foreach ($foreignIPs as $ip) {
            $suspiciousIPs->push([
                'ip_address' => $ip->ip_address,
                'reason' => 'foreign_ip_activity',
                'details' => "來自非信任 IP 的大量活動 ({$ip->activity_count} 次)",
                'risk_level' => 'medium',
            ]);
        }

        return $suspiciousIPs;
    }

    /**
     * 監控登入失敗
     */
    public function monitorFailedLogins(): array
    {
        $timeWindow = now()->subMinutes(15);
        
        $failedLogins = Activity::where('type', 'login_failed')
            ->where('created_at', '>=', $timeWindow)
            ->with('causer')
            ->get()
            ->groupBy('ip_address');

        $alerts = [];
        
        foreach ($failedLogins as $ipAddress => $attempts) {
            if ($attempts->count() >= 3) {
                $alerts[] = [
                    'type' => 'multiple_login_failures',
                    'ip_address' => $ipAddress,
                    'attempt_count' => $attempts->count(),
                    'time_window' => '15分鐘',
                    'usernames' => $attempts->pluck('properties.username')->filter()->unique()->values(),
                    'severity' => $attempts->count() >= 5 ? 'high' : 'medium',
                ];
            }
        }

        return $alerts;
    }

    /**
     * 分析 IP 位址風險
     */
    protected function analyzeIpAddress(?string $ipAddress): array
    {
        if (!$ipAddress) {
            return ['score' => 0, 'reason' => ''];
        }

        // 檢查是否為內網 IP
        if ($this->isInternalIP($ipAddress)) {
            return ['score' => 0, 'reason' => '內網 IP'];
        }

        // 檢查是否為信任 IP
        if (in_array($ipAddress, $this->getTrustedIPs())) {
            return ['score' => 0, 'reason' => '信任 IP'];
        }

        // 檢查 IP 黑名單
        if ($this->isBlacklistedIP($ipAddress)) {
            return ['score' => 8, 'reason' => 'IP 位於黑名單'];
        }

        // 檢查最近的失敗嘗試
        $recentFailures = Activity::where('ip_address', $ipAddress)
            ->where('result', 'failed')
            ->where('created_at', '>=', now()->subHours(1))
            ->count();

        if ($recentFailures >= 5) {
            return ['score' => 6, 'reason' => '最近有多次失敗嘗試'];
        }

        // 外網 IP 有基本風險
        return ['score' => 2, 'reason' => '外網 IP 存取'];
    }

    /**
     * 分析使用者行為風險
     */
    protected function analyzeUserBehavior(Activity $activity): array
    {
        if (!$activity->user_id) {
            return ['score' => 1, 'reason' => '匿名操作'];
        }

        $user = $activity->user;
        if (!$user) {
            return ['score' => 2, 'reason' => '使用者不存在'];
        }

        // 檢查使用者是否被停用
        if (!$user->is_active) {
            return ['score' => 9, 'reason' => '已停用使用者的活動'];
        }

        // 檢查使用者最近的活動模式
        $recentActivities = Activity::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(1))
            ->count();

        if ($recentActivities > 50) {
            return ['score' => 4, 'reason' => '異常高頻活動'];
        }

        return ['score' => 0, 'reason' => '正常使用者行為'];
    }

    /**
     * 分析操作類型風險
     */
    protected function analyzeActionType(Activity $activity): array
    {
        $highRiskActions = [
            'user_deleted' => 7,
            'role_deleted' => 6,
            'permission_modified' => 5,
            'system_settings_changed' => 5,
            'login_failed' => 3,
        ];

        $mediumRiskActions = [
            'user_created' => 2,
            'role_created' => 2,
            'user_role_assigned' => 3,
        ];

        if (isset($highRiskActions[$activity->type])) {
            return [
                'score' => $highRiskActions[$activity->type],
                'reason' => '高風險操作類型'
            ];
        }

        if (isset($mediumRiskActions[$activity->type])) {
            return [
                'score' => $mediumRiskActions[$activity->type],
                'reason' => '中風險操作類型'
            ];
        }

        return ['score' => 0, 'reason' => '一般操作'];
    }

    /**
     * 分析時間模式風險
     */
    protected function analyzeTimePattern(Activity $activity): array
    {
        $hour = $activity->created_at->hour;
        $dayOfWeek = $activity->created_at->dayOfWeek;

        // 非工作時間 (22:00 - 06:00)
        if ($hour >= 22 || $hour <= 6) {
            return ['score' => 3, 'reason' => '非工作時間活動'];
        }

        // 週末活動
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            return ['score' => 2, 'reason' => '週末活動'];
        }

        return ['score' => 0, 'reason' => '正常時間'];
    }

    /**
     * 分析頻率風險
     */
    protected function analyzeFrequency(Activity $activity): array
    {
        if (!$activity->user_id) {
            return ['score' => 0, 'reason' => ''];
        }

        // 檢查同一使用者在短時間內的活動頻率
        $recentCount = Activity::where('user_id', $activity->user_id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($recentCount > 20) {
            return ['score' => 5, 'reason' => '5分鐘內活動過於頻繁'];
        }

        if ($recentCount > 10) {
            return ['score' => 2, 'reason' => '活動頻率較高'];
        }

        return ['score' => 0, 'reason' => '正常頻率'];
    }

    /**
     * 取得風險等級
     */
    protected function getRiskLevel(int $score): string
    {
        return match (true) {
            $score >= 8 => 'critical',
            $score >= 6 => 'high',
            $score >= 3 => 'medium',
            $score >= 1 => 'low',
            default => 'none',
        };
    }

    /**
     * 取得安全建議
     */
    protected function getRecommendations(array $riskFactors): array
    {
        $recommendations = [];

        foreach ($riskFactors as $factor) {
            $recommendations = array_merge($recommendations, match ($factor['reason']) {
                'IP 位於黑名單' => ['立即封鎖此 IP 位址', '檢查相關使用者帳號'],
                '已停用使用者的活動' => ['檢查帳號是否被盜用', '強制登出所有 session'],
                '異常高頻活動' => ['檢查是否為自動化攻擊', '考慮暫時限制帳號'],
                '高風險操作類型' => ['審查操作合理性', '通知相關管理員'],
                '非工作時間活動' => ['確認操作必要性', '加強監控'],
                default => [],
            });
        }

        return array_unique($recommendations);
    }

    /**
     * 檢測登入失敗模式
     */
    protected function detectLoginFailurePatterns(Collection $activities): array
    {
        $loginFailures = $activities->where('type', 'login_failed');
        $patterns = [];

        // 按 IP 分組檢查
        $byIP = $loginFailures->groupBy('ip_address');
        foreach ($byIP as $ip => $failures) {
            if ($failures->count() >= 5) {
                $patterns[] = [
                    'pattern' => 'ip_brute_force',
                    'ip_address' => $ip,
                    'failure_count' => $failures->count(),
                    'time_span' => $failures->last()->created_at->diffInMinutes($failures->first()->created_at),
                ];
            }
        }

        return $patterns;
    }

    /**
     * 檢測批量操作
     */
    protected function detectBulkOperations(Collection $activities): array
    {
        $bulkOperations = [];
        
        $byUser = $activities->groupBy('user_id');
        foreach ($byUser as $userId => $userActivities) {
            $byType = $userActivities->groupBy('type');
            foreach ($byType as $type => $typeActivities) {
                if ($typeActivities->count() >= 10) {
                    $timeSpan = $typeActivities->last()->created_at->diffInMinutes($typeActivities->first()->created_at);
                    if ($timeSpan <= 30) { // 30分鐘內
                        $bulkOperations[] = [
                            'user_id' => $userId,
                            'operation_type' => $type,
                            'count' => $typeActivities->count(),
                            'time_span_minutes' => $timeSpan,
                        ];
                    }
                }
            }
        }

        return $bulkOperations;
    }

    /**
     * 檢測權限提升
     */
    protected function detectPrivilegeEscalation(Collection $activities): array
    {
        $privilegeActivities = $activities->filter(function ($activity) {
            return str_contains(strtolower($activity->description), '權限') ||
                   str_contains(strtolower($activity->description), 'role') ||
                   str_contains(strtolower($activity->description), 'permission');
        });

        return $privilegeActivities->map(function ($activity) {
            return [
                'activity_id' => $activity->id,
                'user_id' => $activity->user_id,
                'description' => $activity->description,
                'timestamp' => $activity->created_at,
            ];
        })->values()->toArray();
    }

    /**
     * 檢測非工作時間活動
     */
    protected function detectOffHoursActivity(Collection $activities): array
    {
        return $activities->filter(function ($activity) {
            $hour = $activity->created_at->hour;
            $dayOfWeek = $activity->created_at->dayOfWeek;
            
            return ($hour >= 22 || $hour <= 6) || ($dayOfWeek == 0 || $dayOfWeek == 6);
        })->map(function ($activity) {
            return [
                'activity_id' => $activity->id,
                'user_id' => $activity->user_id,
                'type' => $activity->type,
                'timestamp' => $activity->created_at,
                'reason' => $this->getOffHoursReason($activity->created_at),
            ];
        })->values()->toArray();
    }

    /**
     * 取得非工作時間原因
     */
    protected function getOffHoursReason(Carbon $timestamp): string
    {
        $hour = $timestamp->hour;
        $dayOfWeek = $timestamp->dayOfWeek;

        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            return '週末活動';
        }

        if ($hour >= 22 || $hour <= 6) {
            return '深夜/清晨活動';
        }

        return '非工作時間';
    }

    /**
     * 解析時間範圍
     */
    protected function parseTimeRange(string $timeRange): Carbon
    {
        return match ($timeRange) {
            '1h' => now()->subHour(),
            '6h' => now()->subHours(6),
            '12h' => now()->subHours(12),
            '1d' => now()->subDay(),
            '3d' => now()->subDays(3),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };
    }

    /**
     * 檢查是否為內網 IP
     */
    protected function isInternalIP(string $ip): bool
    {
        $internalRanges = [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.0/8',
        ];

        foreach ($internalRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查 IP 是否在指定範圍內
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        [$subnet, $mask] = explode('/', $range);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - $mask);
        
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * 取得信任的 IP 位址列表
     */
    protected function getTrustedIPs(): array
    {
        return Cache::remember('trusted_ips', 3600, function () {
            // 這裡可以從配置檔案或資料庫讀取信任的 IP 列表
            return config('security.trusted_ips', []);
        });
    }

    /**
     * 檢查是否為黑名單 IP
     */
    protected function isBlacklistedIP(string $ip): bool
    {
        $blacklist = Cache::remember('blacklisted_ips', 3600, function () {
            // 這裡可以從配置檔案或資料庫讀取黑名單 IP
            return config('security.blacklisted_ips', []);
        });

        return in_array($ip, $blacklist);
    }

    /**
     * 取得安全摘要
     */
    protected function getSecuritySummary(Carbon $startDate): array
    {
        return [
            'total_activities' => Activity::where('created_at', '>=', $startDate)->count(),
            'high_risk_activities' => Activity::where('created_at', '>=', $startDate)
                ->where('risk_level', '>', 6)->count(),
            'security_alerts' => SecurityAlert::where('created_at', '>=', $startDate)->count(),
            'failed_logins' => Activity::where('type', 'login_failed')
                ->where('created_at', '>=', $startDate)->count(),
        ];
    }

    /**
     * 取得最高風險活動
     */
    protected function getTopRisks(Carbon $startDate): Collection
    {
        return Activity::where('created_at', '>=', $startDate)
            ->where('risk_level', '>', 5)
            ->orderBy('risk_level', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * 取得警報統計
     */
    protected function getAlertStatistics(Carbon $startDate): array
    {
        return SecurityAlert::getStatistics($startDate->diffInDays(now()));
    }

    /**
     * 取得使用者活動摘要
     */
    protected function getUserActivitySummary(Carbon $startDate): array
    {
        return [
            'most_active_users' => Activity::where('created_at', '>=', $startDate)
                ->whereNotNull('user_id')
                ->select('user_id', DB::raw('COUNT(*) as activity_count'))
                ->groupBy('user_id')
                ->orderBy('activity_count', 'desc')
                ->limit(10)
                ->with('user')
                ->get(),
            'unique_users' => Activity::where('created_at', '>=', $startDate)
                ->distinct('user_id')
                ->count('user_id'),
        ];
    }

    /**
     * 取得 IP 分析
     */
    protected function getIpAnalysis(Carbon $startDate): array
    {
        return [
            'unique_ips' => Activity::where('created_at', '>=', $startDate)
                ->whereNotNull('ip_address')
                ->distinct('ip_address')
                ->count('ip_address'),
            'suspicious_ips' => $this->checkSuspiciousIPs(),
            'top_ips' => Activity::where('created_at', '>=', $startDate)
                ->whereNotNull('ip_address')
                ->select('ip_address', DB::raw('COUNT(*) as activity_count'))
                ->groupBy('ip_address')
                ->orderBy('activity_count', 'desc')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * 取得安全建議
     */
    protected function getSecurityRecommendations(Carbon $startDate): array
    {
        $recommendations = [];

        // 基於最近的安全事件提供建議
        $highRiskCount = Activity::where('created_at', '>=', $startDate)
            ->where('risk_level', '>', 6)->count();

        if ($highRiskCount > 10) {
            $recommendations[] = '檢測到大量高風險活動，建議加強安全監控';
        }

        $failedLoginCount = Activity::where('type', 'login_failed')
            ->where('created_at', '>=', $startDate)->count();

        if ($failedLoginCount > 50) {
            $recommendations[] = '登入失敗次數過多，建議啟用帳號鎖定機制';
        }

        return $recommendations;
    }

    /**
     * 分析登入模式
     */
    protected function analyzeLoginPatterns(Collection $activities): array
    {
        $logins = $activities->whereIn('type', ['login', 'login_success']);
        
        return [
            'total_logins' => $logins->count(),
            'unique_ips' => $logins->pluck('ip_address')->unique()->count(),
            'login_hours' => $logins->groupBy(function ($activity) {
                return $activity->created_at->hour;
            })->map->count(),
        ];
    }

    /**
     * 分析活動頻率
     */
    protected function analyzeActivityFrequency(Collection $activities): array
    {
        return [
            'total_activities' => $activities->count(),
            'daily_average' => $activities->count() / max($activities->pluck('created_at')->unique('Y-m-d')->count(), 1),
            'peak_hour' => $activities->groupBy(function ($activity) {
                return $activity->created_at->hour;
            })->sortByDesc->count()->keys()->first(),
        ];
    }

    /**
     * 分析 IP 模式
     */
    protected function analyzeIpPatterns(Collection $activities): array
    {
        $ips = $activities->pluck('ip_address')->filter();
        
        return [
            'unique_ips' => $ips->unique()->count(),
            'most_used_ip' => $ips->countBy()->sortDesc()->keys()->first(),
            'internal_ips' => $ips->filter([$this, 'isInternalIP'])->unique()->count(),
        ];
    }

    /**
     * 分析操作模式
     */
    protected function analyzeActionPatterns(Collection $activities): array
    {
        return $activities->groupBy('type')->map->count()->sortDesc()->toArray();
    }

    /**
     * 分析時間模式
     */
    protected function analyzeTimePatterns(Collection $activities): array
    {
        return [
            'by_hour' => $activities->groupBy(function ($activity) {
                return $activity->created_at->hour;
            })->map->count(),
            'by_day_of_week' => $activities->groupBy(function ($activity) {
                return $activity->created_at->dayOfWeek;
            })->map->count(),
            'weekend_activity' => $activities->filter(function ($activity) {
                return in_array($activity->created_at->dayOfWeek, [0, 6]);
            })->count(),
        ];
    }
}