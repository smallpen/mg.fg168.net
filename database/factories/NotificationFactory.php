<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Notification;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            Notification::TYPE_SECURITY,
            Notification::TYPE_SYSTEM,
            Notification::TYPE_USER_ACTION,
            Notification::TYPE_REPORT,
        ];

        $priorities = [
            Notification::PRIORITY_LOW,
            Notification::PRIORITY_NORMAL,
            Notification::PRIORITY_HIGH,
            Notification::PRIORITY_URGENT,
        ];

        $type = $this->faker->randomElement($types);
        $typeConfig = Notification::getTypeConfig()[$type];

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'title' => $this->generateTitleByType($type),
            'message' => $this->generateMessageByType($type),
            'data' => $this->generateDataByType($type),
            'priority' => $this->faker->randomElement($priorities),
            'read_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 week', 'now'),
            'is_browser_notification' => $this->faker->boolean(20),
            'icon' => $typeConfig['icon'],
            'color' => $typeConfig['color'],
            'action_url' => $this->faker->optional(0.5)->randomElement([
                '/admin/users',
                '/admin/roles',
                '/admin/settings',
                '/admin/reports',
                null
            ]),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * 根據類型生成標題
     */
    private function generateTitleByType(string $type): string
    {
        return match ($type) {
            Notification::TYPE_SECURITY => $this->faker->randomElement([
                '安全警報：異常登入嘗試',
                '帳號安全提醒',
                '密碼變更通知',
                '多重登入檢測',
                '可疑活動警報',
            ]),
            Notification::TYPE_SYSTEM => $this->faker->randomElement([
                '系統維護通知',
                '系統更新完成',
                '服務狀態變更',
                '備份完成通知',
                '系統效能警報',
            ]),
            Notification::TYPE_USER_ACTION => $this->faker->randomElement([
                '新使用者註冊',
                '使用者角色變更',
                '權限更新通知',
                '使用者資料修改',
                '帳號狀態變更',
            ]),
            Notification::TYPE_REPORT => $this->faker->randomElement([
                '每日統計報告',
                '週報已生成',
                '月度分析完成',
                '使用者活動報告',
                '系統使用統計',
            ]),
            default => '系統通知',
        };
    }

    /**
     * 根據類型生成訊息
     */
    private function generateMessageByType(string $type): string
    {
        return match ($type) {
            Notification::TYPE_SECURITY => $this->faker->randomElement([
                '檢測到來自 IP ' . $this->faker->ipv4() . ' 的異常登入嘗試，請檢查您的帳號安全。',
                '您的帳號在 ' . $this->faker->dateTimeBetween('-1 week', 'now')->format('Y-m-d H:i') . ' 有登入活動。',
                '密碼已成功變更，如非本人操作請立即聯繫管理員。',
                '檢測到您的帳號在多個裝置同時登入。',
                '系統檢測到可疑活動，建議立即檢查帳號安全設定。',
            ]),
            Notification::TYPE_SYSTEM => $this->faker->randomElement([
                '系統將於今晚 23:00-01:00 進行維護，期間服務可能暫時中斷。',
                '系統已成功更新到版本 ' . $this->faker->semver() . '，新增多項功能改進。',
                '資料庫服務狀態已恢復正常，感謝您的耐心等候。',
                '系統備份已於 ' . $this->faker->dateTimeBetween('-1 week', 'now')->format('Y-m-d H:i') . ' 完成。',
                '系統負載過高，正在進行效能優化。',
            ]),
            Notification::TYPE_USER_ACTION => $this->faker->randomElement([
                '使用者 ' . $this->faker->name() . ' 已成功註冊並等待審核。',
                '管理員已將使用者 ' . $this->faker->name() . ' 的角色變更為編輯者。',
                '使用者權限已更新，新權限將在下次登入時生效。',
                '使用者 ' . $this->faker->name() . ' 已修改個人資料。',
                '使用者帳號狀態已變更為啟用。',
            ]),
            Notification::TYPE_REPORT => $this->faker->randomElement([
                '今日系統共有 ' . $this->faker->numberBetween(10, 100) . ' 位使用者活躍，較昨日增長 ' . $this->faker->numberBetween(1, 20) . '%。',
                '本週統計報告已生成，請至報告頁面查看詳細資料。',
                '月度使用者行為分析已完成，發現 ' . $this->faker->numberBetween(3, 10) . ' 項重要趨勢。',
                '使用者活動報告顯示登入高峰時間為 ' . $this->faker->time('H:i') . '。',
                '系統使用統計：本月總請求數 ' . $this->faker->numberBetween(1000, 10000) . ' 次。',
            ]),
            default => $this->faker->sentence(),
        };
    }

    /**
     * 根據類型生成額外資料
     */
    private function generateDataByType(string $type): ?array
    {
        return match ($type) {
            Notification::TYPE_SECURITY => [
                'ip_address' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent(),
                'location' => $this->faker->city(),
            ],
            Notification::TYPE_SYSTEM => [
                'version' => $this->faker->semver(),
                'maintenance_duration' => $this->faker->numberBetween(30, 120) . ' 分鐘',
            ],
            Notification::TYPE_USER_ACTION => [
                'target_user_id' => $this->faker->numberBetween(1, 100),
                'action_type' => $this->faker->randomElement(['create', 'update', 'delete']),
            ],
            Notification::TYPE_REPORT => [
                'report_type' => $this->faker->randomElement(['daily', 'weekly', 'monthly']),
                'data_points' => $this->faker->numberBetween(10, 100),
            ],
            default => null,
        };
    }

    /**
     * 建立未讀通知
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * 建立已讀通知
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * 建立高優先級通知
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $this->faker->randomElement([
                Notification::PRIORITY_HIGH,
                Notification::PRIORITY_URGENT,
            ]),
        ]);
    }

    /**
     * 建立安全類型通知
     */
    public function security(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Notification::TYPE_SECURITY,
            'priority' => Notification::PRIORITY_HIGH,
            'icon' => 'shield-exclamation',
            'color' => 'red',
        ]);
    }

    /**
     * 建立系統類型通知
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Notification::TYPE_SYSTEM,
            'icon' => 'cog',
            'color' => 'blue',
        ]);
    }
}
