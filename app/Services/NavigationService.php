<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * 導航服務類別
 * 
 * 負責管理系統導航選單結構、權限過濾、麵包屑生成和快取機制
 */
class NavigationService
{
    /**
     * 選單結構定義
     */
    protected array $menuStructure = [
        [
            'key' => 'dashboard',
            'title' => '儀表板',
            'icon' => 'chart-bar',
            'route' => 'admin.dashboard',
            'permission' => 'admin.dashboard.view',
            'order' => 1,
        ],
        [
            'key' => 'users',
            'title' => '使用者管理',
            'icon' => 'users',
            'permission' => 'admin.users.view',
            'order' => 2,
            'children' => [
                [
                    'key' => 'users.index',
                    'title' => '使用者列表',
                    'route' => 'admin.users.index',
                    'permission' => 'admin.users.view',
                    'order' => 1,
                ],
                [
                    'key' => 'users.create',
                    'title' => '建立使用者',
                    'route' => 'admin.users.create',
                    'permission' => 'admin.users.create',
                    'order' => 2,
                ],
            ],
        ],
        [
            'key' => 'roles',
            'title' => '角色管理',
            'icon' => 'shield-check',
            'permission' => 'admin.roles.view',
            'order' => 3,
            'children' => [
                [
                    'key' => 'roles.index',
                    'title' => '角色列表',
                    'route' => 'admin.roles.index',
                    'permission' => 'admin.roles.view',
                    'order' => 1,
                ],
                [
                    'key' => 'roles.permissions',
                    'title' => '權限設定',
                    'route' => 'admin.roles.permissions',
                    'permission' => 'admin.roles.manage',
                    'order' => 2,
                ],
            ],
        ],
        [
            'key' => 'permissions',
            'title' => '權限管理',
            'icon' => 'key',
            'permission' => 'admin.permissions.view',
            'order' => 4,
            'children' => [
                [
                    'key' => 'permissions.index',
                    'title' => '權限列表',
                    'route' => 'admin.permissions.index',
                    'permission' => 'admin.permissions.view',
                    'order' => 1,
                ],
                [
                    'key' => 'permissions.groups',
                    'title' => '權限分組',
                    'route' => 'admin.permissions.groups',
                    'permission' => 'admin.permissions.manage',
                    'order' => 2,
                ],
                [
                    'key' => 'permissions.dependencies',
                    'title' => '依賴關係',
                    'route' => 'admin.permissions.dependencies',
                    'permission' => 'admin.permissions.manage',
                    'order' => 3,
                ],
            ],
        ],
        [
            'key' => 'settings',
            'title' => '系統設定',
            'icon' => 'cog',
            'permission' => 'admin.settings.view',
            'order' => 5,
            'children' => [
                [
                    'key' => 'settings.general',
                    'title' => '基本設定',
                    'route' => 'admin.settings.general',
                    'permission' => 'admin.settings.general',
                    'order' => 1,
                ],
                [
                    'key' => 'settings.security',
                    'title' => '安全設定',
                    'route' => 'admin.settings.security',
                    'permission' => 'admin.settings.security',
                    'order' => 2,
                ],
                [
                    'key' => 'settings.appearance',
                    'title' => '外觀設定',
                    'route' => 'admin.settings.appearance',
                    'permission' => 'admin.settings.appearance',
                    'order' => 3,
                ],
            ],
        ],
        [
            'key' => 'activities',
            'title' => '活動記錄',
            'icon' => 'clipboard-list',
            'permission' => 'admin.activities.view',
            'order' => 6,
            'children' => [
                [
                    'key' => 'activities.logs',
                    'title' => '操作日誌',
                    'route' => 'admin.activities.logs',
                    'permission' => 'admin.activities.view',
                    'order' => 1,
                ],
                [
                    'key' => 'activities.security',
                    'title' => '安全事件',
                    'route' => 'admin.activities.security',
                    'permission' => 'admin.activities.security',
                    'order' => 2,
                ],
                [
                    'key' => 'activities.statistics',
                    'title' => '統計分析',
                    'route' => 'admin.activities.statistics',
                    'permission' => 'admin.activities.statistics',
                    'order' => 3,
                ],
            ],
        ],
    ];

    /**
     * 取得完整的選單結構
     */
    public function getMenuStructure(): array
    {
        return collect($this->menuStructure)
            ->sortBy('order')
            ->map(function ($item) {
                if (isset($item['children'])) {
                    $item['children'] = collect($item['children'])
                        ->sortBy('order')
                        ->values()
                        ->toArray();
                }
                return $item;
            })
            ->values()
            ->toArray();
    }

    /**
     * 根據使用者權限過濾選單
     */
    public function filterMenuByPermissions(array $menu, User $user): array
    {
        return collect($menu)->map(function ($item) use ($user) {
            // 檢查使用者是否有此選單項目的權限
            if (!$this->hasMenuPermission($item, $user)) {
                return null; // 標記為要移除的項目
            }

            // 如果有子選單，遞迴過濾子選單
            if (isset($item['children'])) {
                $filteredChildren = $this->filterMenuByPermissions($item['children'], $user);
                
                // 如果所有子選單都被過濾掉，則隱藏父選單
                if (empty($filteredChildren)) {
                    return null; // 標記為要移除的項目
                }
                
                $item['children'] = $filteredChildren;
            }

            return $item;
        })->filter()->values()->toArray(); // 移除 null 項目
    }

    /**
     * 取得使用者的選單結構（包含權限過濾和快取）
     */
    public function getUserMenuStructure(User $user): array
    {
        $cacheKey = $this->getUserMenuCacheKey($user);
        
        return Cache::remember($cacheKey, 3600, function () use ($user) {
            $menu = $this->getMenuStructure();
            return $this->filterMenuByPermissions($menu, $user);
        });
    }

    /**
     * 預熱選單快取
     */
    public function warmupMenuCache(User $user): void
    {
        $this->getUserMenuStructure($user);
        $this->getQuickActions($user);
        
        // 預載入麵包屑快取
        $this->cacheCommonBreadcrumbs();
    }

    /**
     * 批量清除多個使用者的選單快取
     */
    public function clearMultipleUserMenuCache(array $userIds): void
    {
        foreach ($userIds as $userId) {
            $pattern = "menu_structure_{$userId}_*";
            $this->clearCacheByPattern($pattern);
        }
    }

    /**
     * 取得選單快取統計資訊
     */
    public function getMenuCacheStats(): array
    {
        $stats = [
            'total_cached_menus' => 0,
            'cache_hit_rate' => 0,
            'average_cache_size' => 0,
            'expired_caches' => 0,
        ];

        // 這裡可以實作更詳細的快取統計邏輯
        return $stats;
    }

    /**
     * 快取常用的麵包屑路徑
     */
    protected function cacheCommonBreadcrumbs(): void
    {
        $commonRoutes = [
            'admin.dashboard',
            'admin.users.index',
            'admin.users.create',
            'admin.roles.index',
            'admin.settings.general',
        ];

        foreach ($commonRoutes as $route) {
            $cacheKey = "breadcrumbs_{$route}";
            Cache::remember($cacheKey, 1800, function () use ($route) {
                return $this->getCurrentBreadcrumbs($route);
            });
        }
    }

    /**
     * 根據模式清除快取
     */
    protected function clearCacheByPattern(string $pattern): void
    {
        // 在實際應用中，這裡需要根據快取驅動實作模式匹配清除
        // 這是一個簡化的實作
        Cache::flush();
    }

    /**
     * 生成當前路由的麵包屑導航
     */
    public function getCurrentBreadcrumbs(string $routeName = null): array
    {
        $routeName = $routeName ?: Route::currentRouteName();
        
        if (!$routeName) {
            return [];
        }

        $breadcrumbs = [];
        $menuItem = $this->findMenuItemByRoute($routeName);
        
        if ($menuItem) {
            $breadcrumbs = $this->buildBreadcrumbsFromMenuItem($menuItem);
        } else {
            // 如果找不到對應的選單項目，嘗試從路由名稱生成麵包屑
            $breadcrumbs = $this->buildBreadcrumbsFromRoute($routeName);
        }

        return $breadcrumbs;
    }

    /**
     * 取得使用者的快速操作選單
     */
    public function getQuickActions(User $user): array
    {
        $quickActions = [
            [
                'title' => '建立使用者',
                'route' => 'admin.users.create',
                'icon' => 'user-plus',
                'permission' => 'admin.users.create',
                'color' => 'primary',
            ],
            [
                'title' => '建立角色',
                'route' => 'admin.roles.create',
                'icon' => 'shield-plus',
                'permission' => 'admin.roles.create',
                'color' => 'success',
            ],
            [
                'title' => '系統設定',
                'route' => 'admin.settings.general',
                'icon' => 'cog',
                'permission' => 'admin.settings.general',
                'color' => 'secondary',
            ],
            [
                'title' => '查看日誌',
                'route' => 'admin.activities.logs',
                'icon' => 'clipboard-list',
                'permission' => 'admin.activities.view',
                'color' => 'info',
            ],
        ];

        return collect($quickActions)->filter(function ($action) use ($user) {
            return $user->hasPermission($action['permission']);
        })->values()->toArray();
    }

    /**
     * 建立選單樹狀結構
     */
    public function buildMenuTree(array $items, ?int $parentId = null): array
    {
        $tree = [];
        
        foreach ($items as $item) {
            if (($item['parent_id'] ?? null) === $parentId) {
                $children = $this->buildMenuTree($items, $item['id'] ?? null);
                if (!empty($children)) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        
        return $tree;
    }

    /**
     * 取得選單權限對應表
     */
    public function getMenuPermissions(): array
    {
        $permissions = [];
        $this->extractPermissionsFromMenu($this->menuStructure, $permissions);
        return array_unique($permissions);
    }

    /**
     * 快取使用者選單結構
     */
    public function cacheMenuStructure(User $user): void
    {
        $cacheKey = $this->getUserMenuCacheKey($user);
        $menu = $this->getMenuStructure();
        $filteredMenu = $this->filterMenuByPermissions($menu, $user);
        
        Cache::put($cacheKey, $filteredMenu, 3600);
    }

    /**
     * 清除選單快取
     */
    public function clearMenuCache(?User $user = null): void
    {
        if ($user) {
            // 清除特定使用者的快取
            $cacheKey = $this->getUserMenuCacheKey($user);
            Cache::forget($cacheKey);
        } else {
            // 清除所有選單相關快取
            Cache::flush(); // 在實際應用中應該更精確地清除相關快取
        }
    }

    /**
     * 檢查使用者是否有選單項目的權限
     */
    protected function hasMenuPermission(array $menuItem, User $user): bool
    {
        if (!isset($menuItem['permission'])) {
            return true; // 沒有設定權限要求的選單項目預設可見
        }

        $hasPermission = $user->hasPermission($menuItem['permission']);
        

        return $hasPermission;
    }

    /**
     * 取得使用者選單快取鍵
     */
    protected function getUserMenuCacheKey(User $user): string
    {
        $roleIds = $user->roles->pluck('id')->sort()->implode('_');
        return "menu_structure_{$user->id}_{$roleIds}";
    }

    /**
     * 根據路由名稱尋找對應的選單項目
     */
    protected function findMenuItemByRoute(string $routeName): ?array
    {
        return $this->searchMenuByRoute($this->menuStructure, $routeName);
    }

    /**
     * 遞迴搜尋選單項目
     */
    protected function searchMenuByRoute(array $menu, string $routeName): ?array
    {
        foreach ($menu as $item) {
            if (isset($item['route']) && $item['route'] === $routeName) {
                return $item;
            }
            
            if (isset($item['children'])) {
                $found = $this->searchMenuByRoute($item['children'], $routeName);
                if ($found) {
                    return $found;
                }
            }
        }
        
        return null;
    }

    /**
     * 從選單項目建立麵包屑
     */
    protected function buildBreadcrumbsFromMenuItem(array $menuItem): array
    {
        $breadcrumbs = [];
        
        // 尋找父選單項目
        $parentItem = $this->findParentMenuItem($menuItem);
        if ($parentItem) {
            $breadcrumbs = array_merge($breadcrumbs, $this->buildBreadcrumbsFromMenuItem($parentItem));
        }
        
        $breadcrumbs[] = [
            'title' => $menuItem['title'],
            'route' => $menuItem['route'] ?? null,
            'active' => true,
        ];
        
        return $breadcrumbs;
    }

    /**
     * 從路由名稱建立麵包屑
     */
    protected function buildBreadcrumbsFromRoute(string $routeName): array
    {
        $segments = explode('.', $routeName);
        $breadcrumbs = [];
        
        // 移除 'admin' 前綴
        if ($segments[0] === 'admin') {
            array_shift($segments);
        }
        
        $currentRoute = 'admin';
        foreach ($segments as $index => $segment) {
            $currentRoute .= '.' . $segment;
            $isLast = $index === count($segments) - 1;
            
            $breadcrumbs[] = [
                'title' => $this->formatSegmentTitle($segment),
                'route' => $isLast ? null : $currentRoute,
                'active' => $isLast,
            ];
        }
        
        return $breadcrumbs;
    }

    /**
     * 尋找選單項目的父項目
     */
    protected function findParentMenuItem(array $targetItem): ?array
    {
        foreach ($this->menuStructure as $item) {
            if (isset($item['children'])) {
                foreach ($item['children'] as $child) {
                    if ($child['key'] === $targetItem['key']) {
                        return $item;
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * 格式化路由片段為標題
     */
    protected function formatSegmentTitle(string $segment): string
    {
        $titles = [
            'dashboard' => '儀表板',
            'users' => '使用者管理',
            'roles' => '角色管理',
            'permissions' => '權限管理',
            'settings' => '系統設定',
            'activities' => '活動記錄',
            'index' => '列表',
            'create' => '建立',
            'edit' => '編輯',
            'show' => '檢視',
        ];
        
        return $titles[$segment] ?? Str::title($segment);
    }

    /**
     * 從選單結構中提取所有權限
     */
    protected function extractPermissionsFromMenu(array $menu, array &$permissions): void
    {
        foreach ($menu as $item) {
            if (isset($item['permission'])) {
                $permissions[] = $item['permission'];
            }
            
            if (isset($item['children'])) {
                $this->extractPermissionsFromMenu($item['children'], $permissions);
            }
        }
    }
}