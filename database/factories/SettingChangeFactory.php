<?php

namespace Database\Factories;

use App\Models\Setting;
use App\Models\SettingChange;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 設定變更記錄工廠
 */
class SettingChangeFactory extends Factory
{
    /**
     * 對應的模型名稱
     *
     * @var string
     */
    protected $model = SettingChange::class;

    /**
     * 定義模型的預設狀態
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $oldValue = $this->faker->randomElement([
            $this->faker->word,
            $this->faker->numberBetween(1, 100),
            $this->faker->boolean,
            ['key' => $this->faker->word],
            null,
        ]);

        $newValue = $this->faker->randomElement([
            $this->faker->word,
            $this->faker->numberBetween(1, 100),
            $this->faker->boolean,
            ['key' => $this->faker->word],
            null,
        ]);

        // 使用現有的設定鍵值或建立一個測試用的設定
        $settingKey = $this->faker->randomElement([
            'app.name',
            'app.description', 
            'app.timezone',
            'security.password_min_length',
            'security.login_attempts',
            'theme.primary_color',
            'mail.smtp_host',
            'mail.smtp_port',
        ]);

        return [
            'setting_key' => $settingKey,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'changed_by' => User::factory(),
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'reason' => $this->faker->optional(0.7)->sentence,
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * 建立新增類型的變更記錄
     */
    public function createType(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'old_value' => null,
                'new_value' => $this->faker->word,
                'reason' => '新增設定',
            ];
        });
    }

    /**
     * 建立刪除類型的變更記錄
     */
    public function delete(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'old_value' => $this->faker->word,
                'new_value' => null,
                'reason' => '刪除設定',
            ];
        });
    }

    /**
     * 建立回復類型的變更記錄
     */
    public function restore(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'reason' => '回復到 ' . $this->faker->dateTime->format('Y-m-d H:i:s') . ' 的版本',
            ];
        });
    }

    /**
     * 建立重要變更記錄（系統設定）
     */
    public function important(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'setting_key' => $this->faker->randomElement([
                    'security.password_min_length',
                    'security.login_attempts',
                    'security.session_timeout',
                    'system.maintenance_mode',
                    'system.debug_mode',
                ]),
                'reason' => '重要系統設定變更',
            ];
        });
    }

    /**
     * 建立最近的變更記錄
     */
    public function recent(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            ];
        });
    }

    /**
     * 建立特定分類的變更記錄
     */
    public function category(string $category): static
    {
        $settingsByCategory = [
            'basic' => [
                'app.name',
                'app.description',
                'app.timezone',
                'app.locale',
            ],
            'security' => [
                'security.password_min_length',
                'security.login_attempts',
                'security.session_timeout',
                'security.two_factor_enabled',
            ],
            'mail' => [
                'mail.smtp_host',
                'mail.smtp_port',
                'mail.smtp_username',
                'mail.from_address',
            ],
            'theme' => [
                'theme.primary_color',
                'theme.dark_mode',
                'theme.logo_url',
                'theme.favicon_url',
            ],
        ];

        return $this->state(function (array $attributes) use ($category, $settingsByCategory) {
            return [
                'setting_key' => $this->faker->randomElement($settingsByCategory[$category] ?? ['app.name']),
            ];
        });
    }

    /**
     * 建立特定使用者的變更記錄
     */
    public function byUser(User $user): static
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'changed_by' => $user->id,
            ];
        });
    }

    /**
     * 建立特定設定的變更記錄
     */
    public function forSetting(string $settingKey): static
    {
        return $this->state(function (array $attributes) use ($settingKey) {
            return [
                'setting_key' => $settingKey,
            ];
        });
    }

    /**
     * 建立有原因的變更記錄
     */
    public function withReason(string $reason): static
    {
        return $this->state(function (array $attributes) use ($reason) {
            return [
                'reason' => $reason,
            ];
        });
    }

    /**
     * 建立特定 IP 的變更記錄
     */
    public function fromIp(string $ip): static
    {
        return $this->state(function (array $attributes) use ($ip) {
            return [
                'ip_address' => $ip,
            ];
        });
    }

    /**
     * 建立批量變更記錄（模擬匯入操作）
     */
    public function bulk(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'reason' => '批量匯入設定',
                'created_at' => now(),
            ];
        });
    }

    /**
     * 建立測試用的變更記錄
     */
    public function test(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'setting_key' => 'test.setting',
                'old_value' => 'old_test_value',
                'new_value' => 'new_test_value',
                'reason' => '測試變更',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test User Agent',
            ];
        });
    }
}