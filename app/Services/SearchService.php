<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 全域搜尋服務
 * 
 * 提供跨模組的搜尋功能，包括：
 * - 使用者搜尋
 * - 角色搜尋
 * - 權限搜尋
 * - 頁面和功能搜尋
 * - 搜尋結果快取
 * - 搜尋記錄
 */
class SearchService
{
    /**
     * 可搜尋的模型配置
     */
    protected array $searchableModels = [
        'users' => [
            'model' => User::class,
            'fields' => ['name', 'email', 'username'],
            'permission' => 'admin.users.view',
            'icon' => 'user',
            'route' => 'admin.users.show',
        ],
        'roles' => [
            'model' => Role::class,
            'fields' => ['name', 'display_name', 'description'],
            'permission' => 'admin.roles.view',
            'icon' => 'shield-check',
            'route' => 'admin.roles.show',
        ],
        'permissions' => [
            'model' => Permission::class,
            'fields' => ['name', 'display_name', 'description'],
            'permission' => 'admin.permissions.view',
            'icon' => 'key',
            'route' => 'admin.permissions.show',
        ],
    ];

    /**
     * 系統頁面配置
     */
    protected array $systemPages = [
        [
            'name' => '儀表板',
            'route' => 'admin.dashboard',
            'icon' => 'chart-bar',
            'keywords' => ['dashboard', '首頁', '主頁', '儀表板'],
        ],
        [
            'name' => '使用者管理',
            'route' => 'admin.users.index',
            'icon' => 'users',
            'keywords' => ['users', '使用者', '用戶', '會員'],
        ],
        [
            'name' => '建立使用者',
            'route' => 'admin.users.create',
            'icon' => 'user-plus',
            'keywords' => ['create user', '建立使用者', '新增使用者', '添加用戶'],
        ],
        [
            'name' => '角色管理',
            'route' => 'admin.roles.index',
            'icon' => 'shield-check',
            'keywords' => ['roles', '角色', '權限組'],
        ],
        [
            'name' => '建立角色',
            'route' => 'admin.roles.create',
            'icon' => 'shield-plus',
            'keywords' => ['create role', '建立角色', '新增角色'],
        ],
        [
            'name' => '權限管理',
            'route' => 'admin.permissions.index',
            'icon' => 'key',
            'keywords' => ['permissions', '權限', '許可權'],
        ],
        [
            'name' => '系統設定',
            'route' => 'admin.settings.index',
            'icon' => 'cog',
            'keywords' => ['settings', '設定', '配置', '系統'],
        ],
        [
            'name' => '活動記錄',
            'route' => 'admin.activities.index',
            'icon' => 'clipboard-list',
            'keywords' => ['activities', '活動', '記錄', '日誌', 'logs'],
        ],
    ];

    /**
     * 執行全域搜尋
     * 
     * @param string $query 搜尋關鍵字
     * @param User $user 當前使用者
     * @param array $options 搜尋選項
     * @return array 搜尋結果
     */
    public function globalSearch(string $query, User $user, array $options = []): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $cacheKey = "global_search:" . md5($query . $user->id . serialize($options));
        
        return Cache::remember($cacheKey, 300, function () use ($query, $user, $options) {
            $results = [];
            $maxResults = $options['limit'] ?? 20;
            $categories = $options['categories'] ?? ['pages', 'users', 'roles', 'permissions'];

            // 搜尋系統頁面
            if (in_array('pages', $categories)) {
                $pageResults = $this->searchPages($query);
                if (!empty($pageResults)) {
                    $results['pages'] = [
                        'title' => '頁面和功能',
                        'items' => array_slice($pageResults, 0, 5),
                        'total' => count($pageResults),
                    ];
                }
            }

            // 搜尋各個模型
            foreach ($this->searchableModels as $key => $config) {
                if (!in_array($key, $categories)) {
                    continue;
                }

                if (!$user->hasPermission($config['permission'])) {
                    continue;
                }

                $modelResults = $this->searchInModel($config['model'], $query, $config);
                if ($modelResults->isNotEmpty()) {
                    $results[$key] = [
                        'title' => $this->getCategoryTitle($key),
                        'items' => $modelResults->take(5)->toArray(),
                        'total' => $modelResults->count(),
                    ];
                }
            }

            // 記錄搜尋查詢
            $this->logSearchQuery($query, $user, count($results));

            return $results;
        });
    }

    /**
     * 在特定模組中搜尋
     * 
     * @param string $module 模組名稱
     * @param string $query 搜尋關鍵字
     * @param User $user 當前使用者
     * @return Collection 搜尋結果
     */
    public function searchInModule(string $module, string $query, User $user): Collection
    {
        if (!isset($this->searchableModels[$module])) {
            return collect([]);
        }

        $config = $this->searchableModels[$module];
        
        if (!$user->hasPermission($config['permission'])) {
            return collect([]);
        }

        return $this->searchInModel($config['model'], $query, $config);
    }

    /**
     * 取得搜尋建議
     * 
     * @param string $query 搜尋關鍵字
     * @return array 搜尋建議
     */
    public function getSearchSuggestions(string $query): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $suggestions = [];

        // 從系統頁面中取得建議
        foreach ($this->systemPages as $page) {
            foreach ($page['keywords'] as $keyword) {
                if (str_contains(strtolower($keyword), strtolower($query))) {
                    $suggestions[] = [
                        'text' => $page['name'],
                        'type' => 'page',
                        'icon' => $page['icon'],
                    ];
                    break;
                }
            }
        }

        // 從快取中取得熱門搜尋
        $popularSearches = $this->getPopularSearches();
        foreach ($popularSearches as $search) {
            if (str_contains(strtolower($search), strtolower($query))) {
                $suggestions[] = [
                    'text' => $search,
                    'type' => 'popular',
                    'icon' => 'fire',
                ];
            }
        }

        return array_slice($suggestions, 0, 8);
    }

    /**
     * 記錄搜尋查詢
     * 
     * @param string $query 搜尋關鍵字
     * @param User $user 使用者
     * @param int $resultCount 結果數量
     * @return void
     */
    public function logSearchQuery(string $query, User $user, int $resultCount = 0): void
    {
        try {
            // 記錄到日誌
            Log::info('Global search performed', [
                'user_id' => $user->id,
                'query' => $query,
                'result_count' => $resultCount,
                'timestamp' => now(),
            ]);

            // 更新搜尋統計
            $this->updateSearchStatistics($query);
        } catch (\Exception $e) {
            Log::error('Failed to log search query', [
                'error' => $e->getMessage(),
                'query' => $query,
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * 取得熱門搜尋
     * 
     * @return array 熱門搜尋列表
     */
    public function getPopularSearches(): array
    {
        return Cache::get('popular_searches', [
            '使用者管理',
            '角色管理',
            '系統設定',
            '活動記錄',
            '權限管理',
        ]);
    }

    /**
     * 建立搜尋索引
     * 
     * @return void
     */
    public function buildSearchIndex(): void
    {
        Log::info('Building search index...');

        foreach ($this->searchableModels as $key => $config) {
            $model = $config['model'];
            $count = $model::count();
            
            Log::info("Indexed {$count} records for {$key}");
        }

        Cache::put('search_index_built_at', now(), 3600);
        Log::info('Search index build completed');
    }

    /**
     * 更新搜尋索引
     * 
     * @param string $model 模型類別
     * @param int $id 記錄 ID
     * @return void
     */
    public function updateSearchIndex(string $model, int $id): void
    {
        // 清除相關的搜尋快取
        $this->clearSearchCache();
        
        Log::info("Updated search index for {$model}:{$id}");
    }

    /**
     * 清除搜尋快取
     * 
     * @return void
     */
    public function clearSearchCache(): void
    {
        $pattern = 'global_search:*';
        
        // 這裡可以實作更精確的快取清除邏輯
        Cache::flush();
        
        Log::info('Search cache cleared');
    }

    /**
     * 在模型中搜尋
     * 
     * @param string $model 模型類別
     * @param string $query 搜尋關鍵字
     * @param array $config 搜尋配置
     * @return Collection 搜尋結果
     */
    protected function searchInModel(string $model, string $query, array $config): Collection
    {
        $queryBuilder = $model::query();

        // 建立搜尋條件
        $queryBuilder->where(function ($q) use ($query, $config) {
            foreach ($config['fields'] as $index => $field) {
                if ($index === 0) {
                    $q->where($field, 'like', "%{$query}%");
                } else {
                    $q->orWhere($field, 'like', "%{$query}%");
                }
            }
        });

        // 限制結果數量
        $results = $queryBuilder->limit(10)->get();

        // 格式化結果
        return $results->map(function ($item) use ($config) {
            return $this->formatSearchResult($item, $config);
        });
    }

    /**
     * 搜尋系統頁面
     * 
     * @param string $query 搜尋關鍵字
     * @return array 搜尋結果
     */
    protected function searchPages(string $query): array
    {
        $results = [];

        foreach ($this->systemPages as $page) {
            $match = false;

            // 檢查頁面名稱
            if (str_contains(strtolower($page['name']), strtolower($query))) {
                $match = true;
            }

            // 檢查關鍵字
            if (!$match) {
                foreach ($page['keywords'] as $keyword) {
                    if (str_contains(strtolower($keyword), strtolower($query))) {
                        $match = true;
                        break;
                    }
                }
            }

            if ($match) {
                try {
                    $url = route($page['route']);
                } catch (\Exception $e) {
                    $url = '#';
                }
                
                $results[] = [
                    'type' => 'page',
                    'id' => $page['route'],
                    'title' => $page['name'],
                    'subtitle' => '系統頁面',
                    'icon' => $page['icon'],
                    'url' => $url,
                ];
            }
        }

        return $results;
    }

    /**
     * 格式化搜尋結果
     * 
     * @param mixed $item 搜尋項目
     * @param array $config 配置
     * @return array 格式化結果
     */
    protected function formatSearchResult($item, array $config): array
    {
        $result = [
            'type' => $this->getModelType($config['model']),
            'id' => $item->id,
            'icon' => $config['icon'],
        ];

        // 嘗試生成路由 URL，如果失敗則使用預設值
        try {
            $result['url'] = route($config['route'], $item->id);
        } catch (\Exception $e) {
            $result['url'] = '#';
        }

        // 根據模型類型設定標題和副標題
        switch ($config['model']) {
            case User::class:
                $result['title'] = $item->name ?? $item->username;
                $result['subtitle'] = $item->email;
                break;

            case Role::class:
                $result['title'] = $item->display_name ?? $item->name;
                $result['subtitle'] = "角色 • {$item->permissions()->count()} 個權限";
                break;

            case Permission::class:
                $result['title'] = $item->display_name ?? $item->name;
                $result['subtitle'] = "權限 • {$item->module} 模組";
                break;

            default:
                $result['title'] = $item->name ?? $item->title ?? '未知項目';
                $result['subtitle'] = $item->description ?? '';
                break;
        }

        return $result;
    }

    /**
     * 取得模型類型
     * 
     * @param string $model 模型類別
     * @return string 類型名稱
     */
    protected function getModelType(string $model): string
    {
        $modelMap = [
            User::class => 'user',
            Role::class => 'role',
            Permission::class => 'permission',
        ];

        return $modelMap[$model] ?? 'unknown';
    }

    /**
     * 取得分類標題
     * 
     * @param string $category 分類名稱
     * @return string 分類標題
     */
    protected function getCategoryTitle(string $category): string
    {
        $titles = [
            'users' => '使用者',
            'roles' => '角色',
            'permissions' => '權限',
            'pages' => '頁面和功能',
        ];

        return $titles[$category] ?? $category;
    }

    /**
     * 更新搜尋統計
     * 
     * @param string $query 搜尋關鍵字
     * @return void
     */
    protected function updateSearchStatistics(string $query): void
    {
        $key = 'search_stats:' . date('Y-m-d');
        $stats = Cache::get($key, []);
        
        if (!isset($stats[$query])) {
            $stats[$query] = 0;
        }
        
        $stats[$query]++;
        
        Cache::put($key, $stats, 86400); // 24 小時

        // 更新熱門搜尋
        $this->updatePopularSearches($stats);
    }

    /**
     * 更新熱門搜尋
     * 
     * @param array $stats 搜尋統計
     * @return void
     */
    protected function updatePopularSearches(array $stats): void
    {
        arsort($stats);
        $popular = array_slice(array_keys($stats), 0, 10);
        
        Cache::put('popular_searches', $popular, 3600);
    }
}