<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 活動記錄工廠類別
 */
class ActivityFactory extends Factory
{
    /**
     * 對應的模型名稱
     *
     * @var string
     */
    protected $model = Activity::class;

    /**
     * 定義模型的預設狀態
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            'user_login', 'user_logout', 'user_created', 'user_updated', 'user_deleted',
            'role_created', 'role_updated', 'role_deleted', 'permission_assigned',
            'profile_updated', 'password_changed', 'settings_updated'
        ];

        $modules = [
            'auth', 'users', 'roles', 'permissions', 'profile', 'settings', 'system'
        ];

        $results = ['success', 'failed', 'warning', 'error'];

        return [
            'type' => $this->faker->randomElement($types),
            'event' => $this->faker->randomElement(['created', 'updated', 'deleted', 'viewed']),
            'description' => $this->faker->sentence(),
            'module' => $this->faker->randomElement($modules),
            'user_id' => User::factory(),
            'subject_id' => null,
            'subject_type' => null,
            'properties' => [
                'action' => $this->faker->word(),
                'details' => $this->faker->sentence(),
                'metadata' => [
                    'browser' => $this->faker->userAgent(),
                    'platform' => $this->faker->randomElement(['Windows', 'macOS', 'Linux']),
                ]
            ],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'result' => $this->faker->randomElement($results),
            'risk_level' => $this->faker->numberBetween(1, 10),
            'signature' => 'v1:' . hash('sha256', $this->faker->uuid()),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * 建立高風險活動記錄
     *
     * @return static
     */
    public function highRisk(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => $this->faker->randomElement([
                    'permission_escalation',
                    'sensitive_data_access',
                    'unauthorized_access',
                    'system_config_change'
                ]),
                'risk_level' => $this->faker->numberBetween(7, 10),
                'result' => $this->faker->randomElement(['warning', 'error', 'failed']),
            ];
        });
    }

    /**
     * 建立低風險活動記錄
     *
     * @return static
     */
    public function lowRisk(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => $this->faker->randomElement([
                    'user_login',
                    'profile_updated',
                    'dashboard_viewed'
                ]),
                'risk_level' => $this->faker->numberBetween(1, 3),
                'result' => 'success',
            ];
        });
    }

    /**
     * 建立安全事件活動記錄
     *
     * @return static
     */
    public function securityEvent(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => $this->faker->randomElement([
                    'login_failed',
                    'permission_escalation',
                    'suspicious_ip_access',
                    'data_breach_attempt'
                ]),
                'module' => 'security',
                'risk_level' => $this->faker->numberBetween(6, 10),
                'result' => $this->faker->randomElement(['failed', 'warning', 'error']),
                'properties' => [
                    'security_event' => true,
                    'threat_level' => $this->faker->randomElement(['medium', 'high', 'critical']),
                    'source_ip' => $this->faker->ipv4(),
                    'detection_method' => $this->faker->randomElement(['automated', 'manual', 'alert']),
                ],
            ];
        });
    }

    /**
     * 建立登入失敗活動記錄
     *
     * @return static
     */
    public function loginFailed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'login_failed',
                'event' => 'failed',
                'description' => 'User login attempt failed',
                'module' => 'auth',
                'result' => 'failed',
                'risk_level' => $this->faker->numberBetween(5, 8),
                'properties' => [
                    'username' => $this->faker->userName(),
                    'reason' => $this->faker->randomElement([
                        'invalid_credentials',
                        'account_locked',
                        'account_disabled'
                    ]),
                    'attempt_count' => $this->faker->numberBetween(1, 10),
                ],
            ];
        });
    }

    /**
     * 建立權限提升活動記錄
     *
     * @return static
     */
    public function permissionEscalation(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'permission_escalation',
                'event' => 'updated',
                'description' => 'User permissions were escalated',
                'module' => 'permissions',
                'result' => 'success',
                'risk_level' => 9,
                'properties' => [
                    'previous_role' => 'user',
                    'new_role' => 'admin',
                    'escalated_by' => User::factory()->create()->id,
                    'justification' => $this->faker->sentence(),
                ],
            ];
        });
    }

    /**
     * 建立系統設定變更活動記錄
     *
     * @return static
     */
    public function systemConfigChange(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'system_config_change',
                'event' => 'updated',
                'description' => 'System configuration was modified',
                'module' => 'system',
                'result' => 'success',
                'risk_level' => $this->faker->numberBetween(6, 8),
                'properties' => [
                    'config_key' => $this->faker->randomElement([
                        'security.max_login_attempts',
                        'system.maintenance_mode',
                        'app.debug_mode'
                    ]),
                    'old_value' => $this->faker->word(),
                    'new_value' => $this->faker->word(),
                    'change_reason' => $this->faker->sentence(),
                ],
            ];
        });
    }

    /**
     * 建立無簽章的活動記錄
     *
     * @return static
     */
    public function withoutSignature(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'signature' => null,
            ];
        });
    }

    /**
     * 建立已損壞簽章的活動記錄
     *
     * @return static
     */
    public function corruptedSignature(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'signature' => 'v1:corrupted_signature_hash',
            ];
        });
    }

    /**
     * 建立特定使用者的活動記錄
     *
     * @param User $user
     * @return static
     */
    public function forUser(User $user): static
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->id,
            ];
        });
    }

    /**
     * 建立特定日期範圍的活動記錄
     *
     * @param string $startDate
     * @param string $endDate
     * @return static
     */
    public function inDateRange(string $startDate, string $endDate): static
    {
        return $this->state(function (array $attributes) use ($startDate, $endDate) {
            return [
                'created_at' => $this->faker->dateTimeBetween($startDate, $endDate),
            ];
        });
    }

    /**
     * 建立包含敏感資料的活動記錄
     *
     * @return static
     */
    public function withSensitiveData(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'properties' => [
                    'username' => $this->faker->userName(),
                    'password' => 'secret_password_123',
                    'email' => $this->faker->email(),
                    'api_key' => 'sk_' . $this->faker->uuid(),
                    'credit_card' => '4111-1111-1111-1111',
                    'ssn' => '123-45-6789',
                    'phone' => $this->faker->phoneNumber(),
                    'address' => $this->faker->address(),
                ],
            ];
        });
    }
}