<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 活動記錄模型工廠
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
            'login', 'logout', 'create_user', 'update_user', 'delete_user',
            'create_role', 'update_role', 'delete_role', 'assign_role',
            'remove_role', 'update_permissions', 'view_dashboard',
            'export_data', 'quick_action', 'login_failed',
            'permission_escalation', 'sensitive_data_access',
            'system_config_change', 'suspicious_ip_access', 'bulk_operation'
        ];

        $modules = ['auth', 'users', 'roles', 'permissions', 'dashboard', 'system', 'security', 'api'];
        $results = ['success', 'failed', 'warning', 'error'];

        $type = $this->faker->randomElement($types);
        $module = $this->faker->randomElement($modules);
        $result = $this->faker->randomElement($results);

        $data = [
            'type' => $type,
            'description' => $this->generateDescription($type),
            'module' => $module,
            'user_id' => $this->faker->boolean(80) ? User::factory() : null,
            'subject_id' => $this->faker->boolean(30) ? $this->faker->numberBetween(1, 100) : null,
            'subject_type' => $this->faker->boolean(30) ? $this->faker->randomElement([
                'App\\Models\\User',
                'App\\Models\\Role',
                'App\\Models\\Permission'
            ]) : null,
            'properties' => $this->faker->boolean(60) ? [
                'key1' => $this->faker->word,
                'key2' => $this->faker->numberBetween(1, 100),
                'key3' => $this->faker->boolean,
            ] : null,
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'result' => $result,
            'risk_level' => $this->faker->numberBetween(1, 10),
        ];

        // 生成數位簽章
        $data['signature'] = hash('sha256', $type . $data['description'] . ($data['user_id'] ?? '') . config('app.key'));

        return $data;
    }

    /**
     * 生成活動描述
     *
     * @param string $type
     * @return string
     */
    protected function generateDescription(string $type): string
    {
        return match ($type) {
            'login' => '使用者登入系統',
            'logout' => '使用者登出系統',
            'create_user' => '建立新使用者：' . $this->faker->name,
            'update_user' => '更新使用者：' . $this->faker->name,
            'delete_user' => '刪除使用者：' . $this->faker->name,
            'create_role' => '建立新角色：' . $this->faker->word,
            'update_role' => '更新角色：' . $this->faker->word,
            'delete_role' => '刪除角色：' . $this->faker->word,
            'assign_role' => '指派角色給使用者',
            'remove_role' => '移除使用者角色',
            'update_permissions' => '更新權限設定',
            'view_dashboard' => '檢視儀表板',
            'export_data' => '匯出資料',
            'quick_action' => '執行快速操作',
            'login_failed' => '登入失敗',
            'permission_escalation' => '權限提升嘗試',
            'sensitive_data_access' => '存取敏感資料',
            'system_config_change' => '系統設定變更',
            'suspicious_ip_access' => '可疑 IP 存取',
            'bulk_operation' => '批量操作執行',
            default => '執行操作：' . $type,
        };
    }

    /**
     * 建立登入活動
     *
     * @return static
     */
    public function login(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'login',
            'description' => '使用者登入系統',
            'module' => 'auth',
            'result' => 'success',
            'risk_level' => 1,
        ]);
    }

    /**
     * 建立登出活動
     *
     * @return static
     */
    public function logout(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'logout',
            'description' => '使用者登出系統',
            'module' => 'auth',
            'result' => 'success',
            'risk_level' => 1,
        ]);
    }

    /**
     * 建立安全事件
     *
     * @return static
     */
    public function securityEvent(): static
    {
        $securityTypes = [
            'login_failed', 'permission_escalation', 'sensitive_data_access',
            'system_config_change', 'suspicious_ip_access', 'bulk_operation'
        ];

        return $this->state(fn (array $attributes) => [
            'type' => $this->faker->randomElement($securityTypes),
            'module' => 'security',
            'result' => $this->faker->randomElement(['failed', 'warning']),
            'risk_level' => $this->faker->numberBetween(5, 10),
        ]);
    }

    /**
     * 建立高風險活動
     *
     * @return static
     */
    public function highRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_level' => $this->faker->numberBetween(7, 10),
            'result' => $this->faker->randomElement(['warning', 'failed']),
        ]);
    }

    /**
     * 建立成功的活動
     *
     * @return static
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'result' => 'success',
            'risk_level' => $this->faker->numberBetween(1, 3),
        ]);
    }

    /**
     * 建立失敗的活動
     *
     * @return static
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'result' => 'failed',
            'risk_level' => $this->faker->numberBetween(3, 8),
        ]);
    }

    /**
     * 建立系統活動
     *
     * @return static
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'module' => 'system',
            'description' => '系統事件：' . $this->faker->sentence,
        ]);
    }
}