<?php

namespace Database\Factories;

use App\Models\SettingBackup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 設定備份工廠
 */
class SettingBackupFactory extends Factory
{
    /**
     * 對應的模型名稱
     *
     * @var string
     */
    protected $model = SettingBackup::class;

    /**
     * 定義模型的預設狀態
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'settings_data' => $this->generateSettingsData(),
            'created_by' => User::factory(),
            'backup_type' => $this->faker->randomElement(['manual', 'auto', 'scheduled']),
        ];
    }

    /**
     * 生成測試用設定資料
     *
     * @return array
     */
    protected function generateSettingsData(): array
    {
        return [
            [
                'key' => 'app.name',
                'value' => $this->faker->company(),
                'category' => 'basic',
                'type' => 'text',
                'description' => '應用程式名稱',
                'default_value' => 'Laravel Admin',
                'is_encrypted' => false,
                'is_system' => false,
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'app.timezone',
                'value' => $this->faker->timezone(),
                'category' => 'basic',
                'type' => 'select',
                'description' => '系統時區',
                'default_value' => 'UTC',
                'is_encrypted' => false,
                'is_system' => false,
                'is_public' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'security.password_min_length',
                'value' => $this->faker->numberBetween(6, 12),
                'category' => 'security',
                'type' => 'number',
                'description' => '密碼最小長度',
                'default_value' => 8,
                'is_encrypted' => false,
                'is_system' => false,
                'is_public' => false,
                'sort_order' => 1,
            ],
        ];
    }

    /**
     * 手動備份狀態
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'backup_type' => 'manual',
        ]);
    }

    /**
     * 自動備份狀態
     */
    public function auto(): static
    {
        return $this->state(fn (array $attributes) => [
            'backup_type' => 'auto',
        ]);
    }

    /**
     * 排程備份狀態
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'backup_type' => 'scheduled',
        ]);
    }

    /**
     * 大型備份狀態（包含更多設定）
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'settings_data' => $this->generateLargeSettingsData(),
        ]);
    }

    /**
     * 空備份狀態
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'settings_data' => [],
        ]);
    }

    /**
     * 生成大型設定資料
     *
     * @return array
     */
    protected function generateLargeSettingsData(): array
    {
        $settings = [];
        $categories = ['basic', 'security', 'appearance', 'notifications', 'integrations', 'maintenance'];
        $types = ['text', 'number', 'boolean', 'select', 'email', 'url'];

        for ($i = 1; $i <= 20; $i++) {
            $settings[] = [
                'key' => $this->faker->unique()->slug(2, '.'),
                'value' => $this->generateValueByType($this->faker->randomElement($types)),
                'category' => $this->faker->randomElement($categories),
                'type' => $this->faker->randomElement($types),
                'description' => $this->faker->sentence(),
                'default_value' => $this->generateValueByType($this->faker->randomElement($types)),
                'is_encrypted' => $this->faker->boolean(20), // 20% 機率加密
                'is_system' => $this->faker->boolean(10), // 10% 機率為系統設定
                'is_public' => $this->faker->boolean(70), // 70% 機率為公開設定
                'sort_order' => $i,
            ];
        }

        return $settings;
    }

    /**
     * 根據類型生成對應的值
     *
     * @param string $type
     * @return mixed
     */
    protected function generateValueByType(string $type)
    {
        return match($type) {
            'text' => $this->faker->sentence(),
            'number' => $this->faker->numberBetween(1, 100),
            'boolean' => $this->faker->boolean(),
            'select' => $this->faker->randomElement(['option1', 'option2', 'option3']),
            'email' => $this->faker->email(),
            'url' => $this->faker->url(),
            default => $this->faker->word(),
        };
    }
}