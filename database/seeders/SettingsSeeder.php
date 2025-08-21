<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settingsConfig = Config::get('system-settings.settings', []);
        $categories = Config::get('system-settings.categories', []);

        foreach ($settingsConfig as $key => $config) {
            $categoryInfo = $categories[$config['category']] ?? [];
            
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $config['default'] ?? null,
                    'category' => $config['category'],
                    'type' => $config['type'] ?? 'text',
                    'options' => [
                        'required' => $config['required'] ?? false,
                        'validation' => $config['validation'] ?? null,
                        'min' => $config['min'] ?? null,
                        'max' => $config['max'] ?? null,
                        'values' => $config['options'] ?? null,
                        'dependencies' => $config['dependencies'] ?? [],
                        'preview' => $config['preview'] ?? false,
                        'config_path' => $config['config_path'] ?? null,
                    ],
                    'description' => $config['description'] ?? '',
                    'default_value' => $config['default'] ?? null,
                    'is_encrypted' => $config['sensitive'] ?? false,
                    'is_system' => true, // 所有預設設定都標記為系統設定
                    'is_public' => !($config['sensitive'] ?? false),
                    'sort_order' => $config['sort_order'] ?? 0,
                ]
            );
        }

        $this->command->info('系統設定已成功建立或更新');
    }
}
