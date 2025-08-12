<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 管理後台基本設定
    |--------------------------------------------------------------------------
    |
    | 這裡定義管理後台的基本配置選項
    |
    */

    'name' => env('ADMIN_NAME', '管理後台'),
    'version' => env('ADMIN_VERSION', '1.0.0'),
    'logo' => env('ADMIN_LOGO', '/images/admin-logo.png'),
    'favicon' => env('ADMIN_FAVICON', '/favicon.ico'),

    /*
    |--------------------------------------------------------------------------
    | 路由設定
    |--------------------------------------------------------------------------
    |
    | 管理後台路由相關設定
    |
    */

    'route' => [
        'prefix' => 'admin',
        'middleware' => ['admin'],
        'home' => '/admin/dashboard',
        'login' => '/admin/login',
        'logout' => '/admin/logout',
    ],

    /*
    |--------------------------------------------------------------------------
    | 佈局設定
    |--------------------------------------------------------------------------
    |
    | 管理後台佈局相關設定
    |
    */

    'layout' => [
        'sidebar' => [
            'width' => 280,
            'collapsed_width' => 64,
            'collapsible' => true,
            'default_collapsed' => false,
        ],
        'topbar' => [
            'height' => 64,
            'fixed' => true,
        ],
        'footer' => [
            'show' => true,
            'fixed' => false,
        ],
        'responsive' => [
            'mobile_breakpoint' => 768,
            'tablet_breakpoint' => 1024,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 主題設定
    |--------------------------------------------------------------------------
    |
    | 管理後台主題相關設定
    |
    */

    'theme' => [
        'default' => 'light',
        'available' => ['light', 'dark', 'auto'],
        'allow_user_selection' => true,
        'auto_detect_system' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | 導航選單設定
    |--------------------------------------------------------------------------
    |
    | 管理後台導航選單結構定義
    |
    */

    'navigation' => [
        'cache_enabled' => true,
        'cache_duration' => 3600, // 1 小時
        'menu_structure' => [
            [
                'key' => 'dashboard',
                'title' => '儀表板',
                'icon' => 'chart-bar',
                'route' => 'admin.dashboard',
                'permission' => 'dashboard.view',
                'order' => 1,
            ],
            [
                'key' => 'users',
                'title' => '使用者管理',
                'icon' => 'users',
                'permission' => 'users.view',
                'order' => 2,
                'children' => [
                    [
                        'key' => 'users.index',
                        'title' => '使用者列表',
                        'route' => 'admin.users.index',
                        'permission' => 'users.view',
                    ],
                    [
                        'key' => 'users.create',
                        'title' => '建立使用者',
                        'route' => 'admin.users.create',
                        'permission' => 'users.create',
                    ],
                ],
            ],
            [
                'key' => 'roles',
                'title' => '角色管理',
                'icon' => 'shield-check',
                'permission' => 'roles.view',
                'order' => 3,
                'children' => [
                    [
                        'key' => 'roles.index',
                        'title' => '角色列表',
                        'route' => 'admin.roles.index',
                        'permission' => 'roles.view',
                    ],
                    [
                        'key' => 'roles.create',
                        'title' => '建立角色',
                        'route' => 'admin.roles.create',
                        'permission' => 'roles.create',
                    ],
                ],
            ],
            [
                'key' => 'permissions',
                'title' => '權限管理',
                'icon' => 'key',
                'route' => 'admin.permissions.index',
                'permission' => 'permissions.view',
                'order' => 4,
            ],
            [
                'key' => 'settings',
                'title' => '系統設定',
                'icon' => 'cog',
                'permission' => 'settings.view',
                'order' => 5,
                'children' => [
                    [
                        'key' => 'settings.general',
                        'title' => '基本設定',
                        'route' => 'admin.settings.general',
                        'permission' => 'settings.general',
                    ],
                    [
                        'key' => 'settings.security',
                        'title' => '安全設定',
                        'route' => 'admin.settings.security',
                        'permission' => 'settings.security',
                    ],
                    [
                        'key' => 'settings.appearance',
                        'title' => '外觀設定',
                        'route' => 'admin.settings.appearance',
                        'permission' => 'settings.appearance',
                    ],
                ],
            ],
            [
                'key' => 'activities',
                'title' => '活動記錄',
                'icon' => 'clipboard-list',
                'permission' => 'activities.view',
                'order' => 6,
                'children' => [
                    [
                        'key' => 'activities.index',
                        'title' => '操作日誌',
                        'route' => 'admin.activities.index',
                        'permission' => 'activities.view',
                    ],
                    [
                        'key' => 'activities.security',
                        'title' => '安全事件',
                        'route' => 'admin.activities.security',
                        'permission' => 'activities.security',
                    ],
                    [
                        'key' => 'activities.statistics',
                        'title' => '統計分析',
                        'route' => 'admin.activities.statistics',
                        'permission' => 'activities.statistics',
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知設定
    |--------------------------------------------------------------------------
    |
    | 管理後台通知系統設定
    |
    */

    'notifications' => [
        'enabled' => true,
        'max_display' => 10,
        'auto_refresh' => true,
        'refresh_interval' => 30000, // 30 秒
        'sound_enabled' => true,
        'browser_notifications' => true,
        'types' => [
            'info' => [
                'icon' => 'information-circle',
                'color' => 'blue',
            ],
            'success' => [
                'icon' => 'check-circle',
                'color' => 'green',
            ],
            'warning' => [
                'icon' => 'exclamation-triangle',
                'color' => 'yellow',
            ],
            'error' => [
                'icon' => 'x-circle',
                'color' => 'red',
            ],
            'security' => [
                'icon' => 'shield-exclamation',
                'color' => 'red',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 搜尋設定
    |--------------------------------------------------------------------------
    |
    | 全域搜尋功能設定
    |
    */

    'search' => [
        'enabled' => true,
        'min_query_length' => 2,
        'max_results' => 20,
        'debounce_delay' => 300, // 毫秒
        'searchable_models' => [
            'users' => [
                'model' => 'App\Models\User',
                'fields' => ['name', 'email'],
                'permission' => 'users.view',
            ],
            'roles' => [
                'model' => 'App\Models\Role',
                'fields' => ['name', 'description'],
                'permission' => 'roles.view',
            ],
            'permissions' => [
                'model' => 'App\Models\Permission',
                'fields' => ['name', 'description'],
                'permission' => 'permissions.view',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 安全設定
    |--------------------------------------------------------------------------
    |
    | 管理後台安全相關設定
    |
    */

    'security' => [
        'session' => [
            'timeout_warning' => 300, // 5 分鐘前警告
            'max_concurrent' => 5,
            'track_ip_changes' => true,
            'track_user_agent_changes' => true,
        ],
        'login' => [
            'max_attempts' => 5,
            'lockout_duration' => 900, // 15 分鐘
            'require_2fa' => false,
        ],
        'password' => [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 效能設定
    |--------------------------------------------------------------------------
    |
    | 管理後台效能優化設定
    |
    */

    'performance' => [
        'cache' => [
            'menu_structure' => true,
            'user_permissions' => true,
            'system_settings' => true,
        ],
        'lazy_loading' => [
            'enabled' => true,
            'components' => ['charts', 'tables', 'images'],
        ],
        'pagination' => [
            'default_per_page' => 15,
            'max_per_page' => 100,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 多語言設定
    |--------------------------------------------------------------------------
    |
    | 管理後台多語言支援設定
    |
    */

    'localization' => [
        'default' => 'zh_TW',
        'available' => [
            'zh_TW' => '正體中文',
            'zh_CN' => '简体中文',
            'en' => 'English',
        ],
        'fallback' => 'zh_TW',
        'auto_detect' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | 開發設定
    |--------------------------------------------------------------------------
    |
    | 開發環境專用設定
    |
    */

    'development' => [
        'debug_toolbar' => env('ADMIN_DEBUG_TOOLBAR', false),
        'test_routes' => env('ADMIN_TEST_ROUTES', true),
        'demo_data' => env('ADMIN_DEMO_DATA', false),
    ],

];