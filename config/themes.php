<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 預設主題設定
    |--------------------------------------------------------------------------
    |
    | 這裡定義系統的預設主題設定，包括亮色、暗色和自動模式的基本配置
    |
    */
    'default' => 'light',

    /*
    |--------------------------------------------------------------------------
    | 可用主題列表
    |--------------------------------------------------------------------------
    |
    | 定義系統中可用的所有主題，包括內建主題和自訂主題
    |
    */
    'available' => [
        'light' => [
            'name' => '亮色主題',
            'icon' => 'sun',
            'description' => '適合白天使用的明亮主題',
        ],
        'dark' => [
            'name' => '暗色主題',
            'icon' => 'moon',
            'description' => '適合夜間使用的暗色主題',
        ],
        'auto' => [
            'name' => '自動模式',
            'icon' => 'computer',
            'description' => '根據系統設定自動切換主題',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 自訂主題設定
    |--------------------------------------------------------------------------
    |
    | 這裡可以定義自訂主題的顏色配置和樣式設定
    |
    */
    'custom' => [
        'blue' => [
            'name' => '藍色主題',
            'icon' => 'palette',
            'description' => '以藍色為主調的自訂主題',
            'colors' => [
                'primary' => '#2563EB',
                'primary-dark' => '#1D4ED8',
                'primary-light' => '#3B82F6',
                'secondary' => '#64748B',
                'success' => '#059669',
                'warning' => '#D97706',
                'danger' => '#DC2626',
                'info' => '#0891B2',
            ],
            'backgrounds' => [
                'primary' => '#FFFFFF',
                'secondary' => '#F8FAFC',
                'tertiary' => '#F1F5F9',
                'quaternary' => '#E2E8F0',
            ],
            'texts' => [
                'primary' => '#0F172A',
                'secondary' => '#475569',
                'tertiary' => '#64748B',
                'quaternary' => '#94A3B8',
                'inverse' => '#FFFFFF',
            ],
            'borders' => [
                'primary' => '#E2E8F0',
                'secondary' => '#CBD5E1',
                'tertiary' => '#94A3B8',
            ],
        ],
        'green' => [
            'name' => '綠色主題',
            'icon' => 'palette',
            'description' => '以綠色為主調的自訂主題',
            'colors' => [
                'primary' => '#059669',
                'primary-dark' => '#047857',
                'primary-light' => '#10B981',
                'secondary' => '#6B7280',
                'success' => '#10B981',
                'warning' => '#F59E0B',
                'danger' => '#EF4444',
                'info' => '#06B6D4',
            ],
            'backgrounds' => [
                'primary' => '#FFFFFF',
                'secondary' => '#F9FAFB',
                'tertiary' => '#F3F4F6',
                'quaternary' => '#E5E7EB',
            ],
            'texts' => [
                'primary' => '#111827',
                'secondary' => '#6B7280',
                'tertiary' => '#9CA3AF',
                'quaternary' => '#D1D5DB',
                'inverse' => '#FFFFFF',
            ],
            'borders' => [
                'primary' => '#E5E7EB',
                'secondary' => '#D1D5DB',
                'tertiary' => '#9CA3AF',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 主題切換設定
    |--------------------------------------------------------------------------
    |
    | 主題切換相關的配置選項
    |
    */
    'transition' => [
        'duration' => 300, // 毫秒
        'easing' => 'cubic-bezier(0.4, 0, 0.2, 1)',
        'properties' => [
            'background-color',
            'color',
            'border-color',
            'box-shadow',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 自動主題設定
    |--------------------------------------------------------------------------
    |
    | 自動主題模式的配置選項
    |
    */
    'auto' => [
        'detect_system' => true,
        'time_based' => false,
        'light_hours' => [6, 18], // 6:00 - 18:00 使用亮色主題
        'dark_hours' => [18, 6],  // 18:00 - 6:00 使用暗色主題
    ],

    /*
    |--------------------------------------------------------------------------
    | 主題快取設定
    |--------------------------------------------------------------------------
    |
    | 主題相關的快取配置
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 小時
        'key_prefix' => 'theme_',
    ],

    /*
    |--------------------------------------------------------------------------
    | 主題驗證規則
    |--------------------------------------------------------------------------
    |
    | 自訂主題的驗證規則
    |
    */
    'validation' => [
        'name' => 'required|string|max:50',
        'colors.primary' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        'colors.secondary' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        'colors.success' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        'colors.warning' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        'colors.danger' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
    ],

    /*
    |--------------------------------------------------------------------------
    | 無障礙設定
    |--------------------------------------------------------------------------
    |
    | 主題系統的無障礙功能配置
    |
    */
    'accessibility' => [
        'high_contrast' => true,
        'reduced_motion' => true,
        'focus_indicators' => true,
        'keyboard_navigation' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | 效能設定
    |--------------------------------------------------------------------------
    |
    | 主題系統的效能優化配置
    |
    */
    'performance' => [
        'preload_themes' => ['light', 'dark'],
        'lazy_load_custom' => true,
        'css_minification' => true,
        'critical_css' => true,
    ],
];