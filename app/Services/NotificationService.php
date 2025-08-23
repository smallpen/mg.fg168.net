<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use App\Models\User;
use App\Repositories\SettingsRepositoryInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * 通知服務
 * 
 * 處理系統通知的發送，包含郵件通知、範本渲染和頻率限制
 */
class NotificationService
{
    /**
     * 發送通知郵件
     */
    public function sendEmail(string $templateKey, $recipient, array $variables = []): bool
    {
        try {
            // 檢查頻率限制
            if (!$this->checkRateLimit()) {
                Log::warning('通知發送被頻率限制阻止', [
                    'template' => $templateKey,
                    'recipient' => $this->getRecipientInfo($recipient),
                ]);
                return false;
            }

            // 取得範本
            $template = $this->getTemplate($templateKey);
            if (!$template) {
                Log::error('找不到通知範本', ['template_key' => $templateKey]);
                return false;
            }

            // 準備收件者資訊
            $recipientInfo = $this->prepareRecipient($recipient);
            if (!$recipientInfo) {
                Log::error('無效的收件者資訊', ['recipient' => $recipient]);
                return false;
            }

            // 合併預設變數
            $variables = array_merge($this->getDefaultVariables(), $variables);

            // 渲染範本
            $rendered = $template->render($variables);

            // 發送郵件
            Mail::raw($rendered['content'], function ($message) use ($recipientInfo, $rendered) {
                $message->to($recipientInfo['email'], $recipientInfo['name'])
                        ->subject($rendered['subject'])
                        ->from(
                            config('mail.from.address', 'noreply@example.com'),
                            config('mail.from.name', 'System')
                        );
            });

            // 記錄成功發送
            Log::info('通知郵件發送成功', [
                'template' => $templateKey,
                'recipient' => $recipientInfo['email'],
                'subject' => $rendered['subject'],
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('通知郵件發送失敗', [
                'template' => $templateKey,
                'recipient' => $this->getRecipientInfo($recipient),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 批量發送通知郵件
     */
    public function sendBulkEmail(string $templateKey, array $recipients, array $variables = []): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($recipients as $recipient) {
            if (!$this->checkRateLimit()) {
                $results['skipped']++;
                continue;
            }

            if ($this->sendEmail($templateKey, $recipient, $variables)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $this->getRecipientInfo($recipient);
            }

            // 短暫延遲以避免過載
            usleep(100000); // 0.1 秒
        }

        return $results;
    }

    /**
     * 發送系統通知
     */
    public function sendSystemNotification(string $templateKey, array $variables = []): bool
    {
        // 取得系統管理員
        $admins = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['super_admin', 'admin']);
        })->where('is_active', true)->get();

        if ($admins->isEmpty()) {
            Log::warning('沒有找到系統管理員，無法發送系統通知', [
                'template' => $templateKey,
            ]);
            return false;
        }

        $results = $this->sendBulkEmail($templateKey, $admins->toArray(), $variables);

        return $results['success'] > 0;
    }

    /**
     * 檢查頻率限制
     */
    protected function checkRateLimit(): bool
    {
        $key = 'notification_rate_limit';
        $maxAttempts = (int) app(SettingsRepositoryInterface::class)
                            ->getSetting('notification.rate_limit_per_minute')
                            ?->value ?? 10;

        return RateLimiter::attempt($key, $maxAttempts, function () {
            // 允許發送
        }, 60);
    }

    /**
     * 取得通知範本
     */
    protected function getTemplate(string $templateKey): ?NotificationTemplate
    {
        return Cache::remember("notification_template_{$templateKey}", 3600, function () use ($templateKey) {
            return NotificationTemplate::where('key', $templateKey)
                                      ->where('is_active', true)
                                      ->first();
        });
    }

    /**
     * 準備收件者資訊
     */
    protected function prepareRecipient($recipient): ?array
    {
        if (is_string($recipient)) {
            // 如果是字串，假設是電子郵件地址
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                return [
                    'email' => $recipient,
                    'name' => $recipient,
                ];
            }
            return null;
        }

        if (is_array($recipient)) {
            // 如果是陣列，檢查必要欄位
            if (isset($recipient['email']) && filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'email' => $recipient['email'],
                    'name' => $recipient['name'] ?? $recipient['email'],
                ];
            }
            return null;
        }

        if ($recipient instanceof User) {
            // 如果是 User 模型
            return [
                'email' => $recipient->email,
                'name' => $recipient->name ?? $recipient->username,
            ];
        }

        return null;
    }

    /**
     * 取得收件者資訊（用於日誌）
     */
    protected function getRecipientInfo($recipient): string
    {
        if (is_string($recipient)) {
            return $recipient;
        }

        if (is_array($recipient)) {
            return $recipient['email'] ?? 'unknown';
        }

        if ($recipient instanceof User) {
            return $recipient->email;
        }

        return 'unknown';
    }

    /**
     * 取得預設變數
     */
    protected function getDefaultVariables(): array
    {
        return [
            'app_name' => config('app.name', 'Laravel Admin System'),
            'app_url' => config('app.url', 'http://localhost'),
            'login_url' => route('admin.login'),
            'current_year' => date('Y'),
            'current_date' => now()->format('Y-m-d'),
            'current_time' => now()->format('H:i:s'),
        ];
    }

    /**
     * 測試通知設定
     */
    public function testConfiguration(string $testEmail): array
    {
        try {
            // 發送測試郵件
            $result = $this->sendEmail('system_test', $testEmail, [
                'test_message' => '這是一封測試郵件，用於驗證通知設定是否正確。',
                'test_time' => now()->format('Y-m-d H:i:s'),
            ]);

            return [
                'success' => $result,
                'message' => $result ? '測試郵件發送成功' : '測試郵件發送失敗',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '測試失敗：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 取得通知統計資訊
     */
    public function getStatistics(): array
    {
        // 這裡可以從日誌或專門的統計表中取得資料
        return [
            'total_sent' => 0, // 實際應用中從資料庫取得
            'success_rate' => 0,
            'failed_count' => 0,
            'rate_limited' => 0,
            'templates_count' => NotificationTemplate::active()->count(),
        ];
    }

    /**
     * 取得使用者未讀通知數量
     */
    public function getUnreadCount(User $user): int
    {
        // 這裡應該從通知表中取得未讀通知數量
        // 目前先返回 0，實際應用中需要實作通知系統
        return 0;
    }

    /**
     * 取得使用者通知列表
     */
    public function getUserNotifications(User $user, array $options = []): array
    {
        // 這裡應該從通知表中取得使用者通知
        // 目前先返回空陣列，實際應用中需要實作通知系統
        return [];
    }

    /**
     * 清除通知快取
     */
    public function clearCache(): void
    {
        Cache::forget('notification_templates');
        
        // 清除所有範本快取
        $templates = NotificationTemplate::pluck('key');
        foreach ($templates as $key) {
            Cache::forget("notification_template_{$key}");
        }
    }
}