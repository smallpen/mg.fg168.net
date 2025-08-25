<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Notification;
use App\Models\NotificationRule;
use App\Models\User;
use App\Models\SecurityAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * 活動記錄通知服務
 * 
 * 處理活動記錄相關的通知功能，包含規則管理、通知合併和重試機制
 */
class ActivityNotificationService
{
    protected NotificationService $notificationService;
    protected SecurityAnalyzer $securityAnalyzer;

    public function __construct(
        NotificationService $notificationService,
        SecurityAnalyzer $securityAnalyzer
    ) {
        $this->notificationService = $notificationService;
        $this->securityAnalyzer = $securityAnalyzer;
    }

    /**
     * 處理活動記錄通知
     */
    public function handleActivityNotification(Activity $activity): void
    {
        try {
            Log::info('開始處理活動記錄通知', ['activity_id' => $activity->id]);
            
            // 檢查是否有符合的通知規則
            $matchingRules = $this->getMatchingRules($activity);
            
            Log::info('找到符合的規則數量', ['count' => $matchingRules->count()]);
            
            if ($matchingRules->isEmpty()) {
                Log::info('沒有符合的通知規則');
                return;
            }

            // 為每個符合的規則處理通知
            foreach ($matchingRules as $rule) {
                Log::info('處理通知規則', ['rule_id' => $rule->id, 'rule_name' => $rule->name]);
                $this->processNotificationRule($activity, $rule);
            }

        } catch (\Exception $e) {
            Log::error('處理活動記錄通知時發生錯誤', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 取得符合條件的通知規則
     */
    protected function getMatchingRules(Activity $activity): Collection
    {
        return NotificationRule::active()
            ->get()
            ->filter(function ($rule) use ($activity) {
                return $this->ruleMatches($rule, $activity);
            });
    }

    /**
     * 檢查規則是否符合活動
     */
    protected function ruleMatches(NotificationRule $rule, Activity $activity): bool
    {
        $conditions = $rule->conditions;

        // 檢查活動類型
        if (isset($conditions['activity_types']) && !empty($conditions['activity_types'])) {
            if (!in_array($activity->type, $conditions['activity_types'])) {
                return false;
            }
        }

        // 檢查風險等級
        if (isset($conditions['min_risk_level'])) {
            if ($activity->risk_level < $conditions['min_risk_level']) {
                return false;
            }
        }

        // 檢查使用者
        if (isset($conditions['user_ids']) && !empty($conditions['user_ids'])) {
            if (!in_array($activity->causer_id, $conditions['user_ids'])) {
                return false;
            }
        }

        // 檢查 IP 位址模式
        if (isset($conditions['ip_patterns']) && !empty($conditions['ip_patterns'])) {
            $matched = false;
            foreach ($conditions['ip_patterns'] as $pattern) {
                if ($this->matchesIpPattern($activity->ip_address, $pattern)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return false;
            }
        }

        // 檢查時間範圍
        if (isset($conditions['time_range'])) {
            if (!$this->isWithinTimeRange($activity->created_at, $conditions['time_range'])) {
                return false;
            }
        }

        // 檢查頻率限制
        if (isset($conditions['frequency_limit'])) {
            if (!$this->checkFrequencyLimit($rule, $activity)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 處理通知規則
     */
    protected function processNotificationRule(Activity $activity, NotificationRule $rule): void
    {
        $actions = $rule->actions;

        Log::info('開始處理通知規則', [
            'rule_id' => $rule->id,
            'activity_id' => $activity->id
        ]);

        // 檢查是否需要合併通知
        if ($this->shouldMergeNotification($rule, $activity)) {
            Log::info('合併通知');
            $this->mergeNotification($rule, $activity);
            return;
        }

        // 建立新通知
        Log::info('建立新通知');
        $notification = $this->createNotification($activity, $rule);

        if (!$notification) {
            Log::warning('通知建立失敗');
            return;
        }

        Log::info('通知建立成功', ['notification_id' => $notification->id]);

        // 執行通知動作
        if (isset($actions['actions'])) {
            foreach ($actions['actions'] as $action) {
                Log::info('執行通知動作', ['action_type' => $action['type']]);
                $this->executeNotificationAction($notification, $action, $activity);
            }
        }

        // 更新規則統計
        $this->updateRuleStatistics($rule);
    }

    /**
     * 檢查是否應該合併通知
     */
    protected function shouldMergeNotification(NotificationRule $rule, Activity $activity): bool
    {
        if (!isset($rule->actions['merge_similar']) || !$rule->actions['merge_similar']) {
            return false;
        }

        $mergeWindow = $rule->actions['merge_window'] ?? 300; // 預設 5 分鐘

        // 查找相似的未讀通知
        $existingNotification = Notification::where('type', 'activity_log')
            ->where('data->rule_id', $rule->id)
            ->where('data->activity_type', $activity->type)
            ->whereNull('read_at')
            ->where('created_at', '>=', Carbon::now()->subSeconds($mergeWindow))
            ->first();

        return $existingNotification !== null;
    }

    /**
     * 合併通知
     */
    protected function mergeNotification(NotificationRule $rule, Activity $activity): void
    {
        $mergeWindow = $rule->actions['merge_window'] ?? 300;

        $existingNotification = Notification::where('type', 'activity_log')
            ->where('data->rule_id', $rule->id)
            ->where('data->activity_type', $activity->type)
            ->whereNull('read_at')
            ->where('created_at', '>=', Carbon::now()->subSeconds($mergeWindow))
            ->first();

        if ($existingNotification) {
            $data = $existingNotification->data;
            $data['count'] = ($data['count'] ?? 1) + 1;
            $data['latest_activity_id'] = $activity->id;
            $data['latest_activity_time'] = $activity->created_at->toISOString();

            $existingNotification->update([
                'title' => $this->generateMergedTitle($rule, $data['count']),
                'message' => $this->generateMergedMessage($rule, $activity, $data['count']),
                'data' => $data,
                'updated_at' => Carbon::now()
            ]);

            Log::info('通知已合併', [
                'notification_id' => $existingNotification->id,
                'rule_id' => $rule->id,
                'activity_id' => $activity->id,
                'count' => $data['count']
            ]);
        }
    }

    /**
     * 建立通知
     */
    protected function createNotification(Activity $activity, NotificationRule $rule): ?Notification
    {
        $recipients = $this->getNotificationRecipients($rule);
        
        Log::info('取得通知接收者', ['count' => $recipients->count()]);
        
        if ($recipients->isEmpty()) {
            Log::warning('沒有找到通知接收者');
            return null;
        }

        $notifications = [];

        foreach ($recipients as $recipient) {
            Log::info('為使用者建立通知', [
                'user_id' => $recipient->id,
                'user_name' => $recipient->name
            ]);

            $notification = Notification::create([
                'user_id' => $recipient->id,
                'type' => 'activity_log',
                'title' => $this->generateNotificationTitle($rule, $activity),
                'message' => $this->generateNotificationMessage($rule, $activity),
                'data' => [
                    'rule_id' => $rule->id,
                    'activity_id' => $activity->id,
                    'activity_type' => $activity->type,
                    'risk_level' => $activity->risk_level,
                    'count' => 1
                ],
                'priority' => $this->determineNotificationPriority($activity, $rule),
                'is_browser_notification' => $rule->actions['browser_notification'] ?? false,
                'icon' => $this->getNotificationIcon($activity),
                'color' => $this->getNotificationColor($activity),
                'action_url' => route('admin.activities.show', $activity->id)
            ]);

            Log::info('通知建立成功', [
                'notification_id' => $notification->id,
                'user_id' => $recipient->id
            ]);

            $notifications[] = $notification;
        }

        return $notifications[0] ?? null; // 返回第一個通知用於後續處理
    }

    /**
     * 執行通知動作
     */
    protected function executeNotificationAction(Notification $notification, array $action, Activity $activity): void
    {
        try {
            switch ($action['type']) {
                case 'email':
                    $this->sendEmailNotification($notification, $action);
                    break;

                case 'browser':
                    $this->sendBrowserNotification($notification, $action);
                    break;

                case 'webhook':
                    $this->sendWebhookNotification($notification, $action, $activity);
                    break;

                case 'security_alert':
                    $this->createSecurityAlert($notification, $action, $activity);
                    break;

                default:
                    Log::warning('未知的通知動作類型', [
                        'action_type' => $action['type'],
                        'notification_id' => $notification->id
                    ]);
            }

        } catch (\Exception $e) {
            Log::error('執行通知動作失敗', [
                'action_type' => $action['type'],
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);

            // 記錄失敗並安排重試
            $this->scheduleRetry($notification, $action, $activity);
        }
    }

    /**
     * 發送郵件通知
     */
    protected function sendEmailNotification(Notification $notification, array $action): void
    {
        $user = $notification->user;
        
        $success = $this->notificationService->sendEmail(
            $action['template'] ?? 'activity_notification',
            $user,
            [
                'user_name' => $user->name,
                'notification_title' => $notification->title,
                'notification_message' => $notification->message,
                'activity_url' => $notification->action_url,
                'priority' => $notification->priority_label
            ]
        );

        if (!$success) {
            throw new \Exception('郵件發送失敗');
        }
    }

    /**
     * 發送瀏覽器通知
     */
    protected function sendBrowserNotification(Notification $notification, array $action): void
    {
        // 這裡可以整合 WebSocket 或 Server-Sent Events
        // 目前先記錄到快取中，由前端輪詢取得
        $cacheKey = "browser_notifications:{$notification->user_id}";
        $notifications = Cache::get($cacheKey, []);
        
        $notifications[] = [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'icon' => $notification->icon,
            'url' => $notification->action_url,
            'timestamp' => $notification->created_at->toISOString()
        ];

        Cache::put($cacheKey, $notifications, 3600); // 保存 1 小時
    }

    /**
     * 發送 Webhook 通知
     */
    protected function sendWebhookNotification(Notification $notification, array $action, Activity $activity): void
    {
        $url = $action['url'];
        $payload = [
            'notification_id' => $notification->id,
            'activity_id' => $activity->id,
            'type' => $activity->type,
            'description' => $activity->description,
            'risk_level' => $activity->risk_level,
            'user' => $activity->causer ? [
                'id' => $activity->causer->id,
                'name' => $activity->causer->name,
                'email' => $activity->causer->email
            ] : null,
            'timestamp' => $activity->created_at->toISOString()
        ];

        $response = Http::timeout(30)->post($url, $payload);

        if (!$response->successful()) {
            throw new \Exception("Webhook 請求失敗: {$response->status()}");
        }
    }

    /**
     * 建立安全警報
     */
    protected function createSecurityAlert(Notification $notification, array $action, Activity $activity): void
    {
        SecurityAlert::create([
            'activity_id' => $activity->id,
            'type' => $action['alert_type'] ?? 'activity_notification',
            'severity' => $this->mapPriorityToSeverity($notification->priority),
            'title' => $notification->title,
            'description' => $notification->message,
            'rule_id' => $notification->data['rule_id'] ?? null
        ]);
    }

    /**
     * 安排重試
     */
    protected function scheduleRetry(Notification $notification, array $action, Activity $activity): void
    {
        $retryData = [
            'notification_id' => $notification->id,
            'action' => $action,
            'activity_id' => $activity->id,
            'attempt' => 1,
            'max_attempts' => 3,
            'next_retry' => Carbon::now()->addMinutes(5)
        ];

        Cache::put(
            "notification_retry:{$notification->id}:" . md5(json_encode($action)),
            $retryData,
            3600
        );
    }

    /**
     * 處理通知重試
     */
    public function processRetries(): void
    {
        $retryKeys = Cache::get('notification_retry_keys', []);

        foreach ($retryKeys as $key) {
            $retryData = Cache::get($key);
            
            if (!$retryData || Carbon::now()->lt($retryData['next_retry'])) {
                continue;
            }

            try {
                $notification = Notification::find($retryData['notification_id']);
                $activity = Activity::find($retryData['activity_id']);

                if ($notification && $activity) {
                    $this->executeNotificationAction($notification, $retryData['action'], $activity);
                    
                    // 重試成功，移除重試記錄
                    Cache::forget($key);
                    $this->removeRetryKey($key);
                }

            } catch (\Exception $e) {
                // 重試失敗，增加嘗試次數
                $retryData['attempt']++;
                
                if ($retryData['attempt'] >= $retryData['max_attempts']) {
                    // 達到最大重試次數，放棄重試
                    Cache::forget($key);
                    $this->removeRetryKey($key);
                    
                    Log::error('通知重試達到最大次數，放棄重試', [
                        'notification_id' => $retryData['notification_id'],
                        'action' => $retryData['action'],
                        'error' => $e->getMessage()
                    ]);
                } else {
                    // 安排下次重試
                    $retryData['next_retry'] = Carbon::now()->addMinutes(5 * $retryData['attempt']);
                    Cache::put($key, $retryData, 3600);
                }
            }
        }
    }

    /**
     * 取得通知接收者
     */
    protected function getNotificationRecipients(NotificationRule $rule): Collection
    {
        $recipients = collect();

        if (isset($rule->actions['recipients'])) {
            foreach ($rule->actions['recipients'] as $recipient) {
                switch ($recipient['type']) {
                    case 'user':
                        if (isset($recipient['id'])) {
                            $user = User::find($recipient['id']);
                            if ($user && $user->is_active) {
                                $recipients->push($user);
                            }
                        }
                        break;

                    case 'role':
                        if (isset($recipient['id'])) {
                            $users = User::whereHas('roles', function ($query) use ($recipient) {
                                $query->where('id', $recipient['id']);
                            })->where('is_active', true)->get();
                            $recipients = $recipients->merge($users);
                        }
                        break;

                    case 'all_admins':
                        $admins = User::whereHas('roles', function ($query) {
                            $query->whereIn('name', ['super_admin', 'admin']);
                        })->where('is_active', true)->get();
                        $recipients = $recipients->merge($admins);
                        break;
                }
            }
        }

        return $recipients->unique('id');
    }

    /**
     * 生成通知標題
     */
    protected function generateNotificationTitle(NotificationRule $rule, Activity $activity): string
    {
        $template = $rule->actions['title_template'] ?? '活動記錄警報：{activity_type}';
        
        return str_replace([
            '{activity_type}',
            '{user_name}',
            '{risk_level}'
        ], [
            $activity->type,
            $activity->causer->name ?? '未知使用者',
            $activity->risk_level_text
        ], $template);
    }

    /**
     * 生成合併通知標題
     */
    protected function generateMergedTitle(NotificationRule $rule, int $count): string
    {
        $template = $rule->actions['merged_title_template'] ?? '活動記錄警報：{count} 個相似事件';
        
        return str_replace('{count}', $count, $template);
    }

    /**
     * 生成通知訊息
     */
    protected function generateNotificationMessage(NotificationRule $rule, Activity $activity): string
    {
        $template = $rule->actions['message_template'] ?? 
            '使用者 {user_name} 在 {time} 執行了 {activity_type} 操作：{description}';
        
        return str_replace([
            '{user_name}',
            '{time}',
            '{activity_type}',
            '{description}',
            '{ip_address}'
        ], [
            $activity->causer->name ?? '未知使用者',
            $activity->created_at->format('Y-m-d H:i:s'),
            $activity->type,
            $activity->description,
            $activity->ip_address
        ], $template);
    }

    /**
     * 生成合併通知訊息
     */
    protected function generateMergedMessage(NotificationRule $rule, Activity $activity, int $count): string
    {
        $template = $rule->actions['merged_message_template'] ?? 
            '在過去幾分鐘內發生了 {count} 個相似的 {activity_type} 事件，最新事件時間：{latest_time}';
        
        return str_replace([
            '{count}',
            '{activity_type}',
            '{latest_time}'
        ], [
            $count,
            $activity->type,
            $activity->created_at->format('Y-m-d H:i:s')
        ], $template);
    }

    /**
     * 確定通知優先級
     */
    protected function determineNotificationPriority(Activity $activity, NotificationRule $rule): string
    {
        if (isset($rule->actions['priority'])) {
            return $rule->actions['priority'];
        }

        // 根據風險等級自動確定優先級
        if ($activity->risk_level >= 8) {
            return 'urgent';
        } elseif ($activity->risk_level >= 6) {
            return 'high';
        } elseif ($activity->risk_level >= 3) {
            return 'normal';
        } else {
            return 'low';
        }
    }

    /**
     * 取得通知圖示
     */
    protected function getNotificationIcon(Activity $activity): string
    {
        $iconMap = [
            'login' => 'login',
            'logout' => 'logout',
            'create' => 'plus-circle',
            'update' => 'pencil',
            'delete' => 'trash',
            'security' => 'shield-exclamation',
            'system' => 'cog'
        ];

        return $iconMap[$activity->type] ?? 'bell';
    }

    /**
     * 取得通知顏色
     */
    protected function getNotificationColor(Activity $activity): string
    {
        if ($activity->risk_level >= 8) {
            return 'red';
        } elseif ($activity->risk_level >= 6) {
            return 'yellow';
        } elseif ($activity->risk_level >= 3) {
            return 'blue';
        } else {
            return 'gray';
        }
    }

    /**
     * 檢查 IP 模式匹配
     */
    protected function matchesIpPattern(string $ip, string $pattern): bool
    {
        // 支援 CIDR 表示法和萬用字元
        if (strpos($pattern, '/') !== false) {
            // CIDR 表示法
            return $this->ipInCidr($ip, $pattern);
        } elseif (strpos($pattern, '*') !== false) {
            // 萬用字元模式
            $pattern = str_replace('*', '.*', $pattern);
            return preg_match("/^{$pattern}$/", $ip);
        } else {
            // 精確匹配
            return $ip === $pattern;
        }
    }

    /**
     * 檢查 IP 是否在 CIDR 範圍內
     */
    protected function ipInCidr(string $ip, string $cidr): bool
    {
        list($subnet, $mask) = explode('/', $cidr);
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
        }
        
        return false;
    }

    /**
     * 檢查是否在時間範圍內
     */
    protected function isWithinTimeRange(Carbon $time, array $timeRange): bool
    {
        $hour = $time->hour;
        $dayOfWeek = $time->dayOfWeek; // 0 = Sunday, 6 = Saturday

        if (isset($timeRange['hours'])) {
            if (!in_array($hour, $timeRange['hours'])) {
                return false;
            }
        }

        if (isset($timeRange['days'])) {
            if (!in_array($dayOfWeek, $timeRange['days'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 檢查頻率限制
     */
    protected function checkFrequencyLimit(NotificationRule $rule, Activity $activity): bool
    {
        $limit = $rule->conditions['frequency_limit'];
        $window = $limit['window'] ?? 3600; // 預設 1 小時
        $maxCount = $limit['max_count'] ?? 10;

        $cacheKey = "notification_frequency:{$rule->id}:{$activity->type}";
        $count = Cache::get($cacheKey, 0);

        if ($count >= $maxCount) {
            return false;
        }

        Cache::put($cacheKey, $count + 1, $window);
        return true;
    }

    /**
     * 更新規則統計
     */
    protected function updateRuleStatistics(NotificationRule $rule): void
    {
        $rule->increment('triggered_count');
        $rule->update(['last_triggered_at' => Carbon::now()]);
    }

    /**
     * 將優先級映射到嚴重程度
     */
    protected function mapPriorityToSeverity(string $priority): string
    {
        $mapping = [
            'low' => 'info',
            'normal' => 'warning',
            'high' => 'error',
            'urgent' => 'critical'
        ];

        return $mapping[$priority] ?? 'info';
    }

    /**
     * 移除重試鍵
     */
    protected function removeRetryKey(string $key): void
    {
        $retryKeys = Cache::get('notification_retry_keys', []);
        $retryKeys = array_filter($retryKeys, function ($k) use ($key) {
            return $k !== $key;
        });
        Cache::put('notification_retry_keys', $retryKeys, 3600);
    }

    /**
     * 清理過期的通知
     */
    public function cleanupExpiredNotifications(): int
    {
        $retentionDays = config('activity-log.notification_retention_days', 30);
        
        return Notification::where('type', 'activity_log')
            ->where('created_at', '<', Carbon::now()->subDays($retentionDays))
            ->delete();
    }

    /**
     * 取得通知統計
     */
    public function getNotificationStatistics(): array
    {
        return [
            'total_notifications' => Notification::where('type', 'activity_log')->count(),
            'unread_notifications' => Notification::where('type', 'activity_log')->whereNull('read_at')->count(),
            'notifications_today' => Notification::where('type', 'activity_log')
                ->whereDate('created_at', Carbon::today())->count(),
            'active_rules' => NotificationRule::active()->count(),
            'pending_retries' => count(Cache::get('notification_retry_keys', []))
        ];
    }
}