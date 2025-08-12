<?php

namespace App\Examples;

use App\Services\NavigationService;
use App\Models\User;

/**
 * NavigationService 使用範例
 * 
 * 展示如何使用 NavigationService 來管理導航選單
 */
class NavigationServiceExample
{
    protected NavigationService $navigationService;

    public function __construct(NavigationService $navigationService)
    {
        $this->navigationService = $navigationService;
    }

    /**
     * 基本使用範例
     */
    public function basicUsage(): void
    {
        // 取得完整的選單結構
        $menuStructure = $this->navigationService->getMenuStructure();
        
        // 取得選單權限列表
        $permissions = $this->navigationService->getMenuPermissions();
        
        // 建立選單樹狀結構
        $menuTree = $this->navigationService->buildMenuTree($menuStructure);
    }

    /**
     * 使用者相關功能範例
     */
    public function userSpecificFeatures(User $user): void
    {
        // 取得使用者的選單結構（包含權限過濾和快取）
        $userMenu = $this->navigationService->getUserMenuStructure($user);
        
        // 取得使用者的快速操作選單
        $quickActions = $this->navigationService->getQuickActions($user);
        
        // 生成當前路由的麵包屑導航
        $breadcrumbs = $this->navigationService->getCurrentBreadcrumbs('admin.users.index');
    }

    /**
     * 快取管理範例
     */
    public function cacheManagement(User $user): void
    {
        // 手動快取使用者選單結構
        $this->navigationService->cacheMenuStructure($user);
        
        // 清除特定使用者的選單快取
        $this->navigationService->clearMenuCache($user);
        
        // 清除所有選單快取
        $this->navigationService->clearMenuCache();
    }

    /**
     * 權限過濾範例
     */
    public function permissionFiltering(User $user): void
    {
        // 取得完整選單
        $fullMenu = $this->navigationService->getMenuStructure();
        
        // 根據使用者權限過濾選單
        $filteredMenu = $this->navigationService->filterMenuByPermissions($fullMenu, $user);
        
        // 比較過濾前後的差異
        $removedItems = array_diff(
            array_column($fullMenu, 'key'),
            array_column($filteredMenu, 'key')
        );
    }

    /**
     * 麵包屑導航範例
     */
    public function breadcrumbExamples(): void
    {
        // 從特定路由生成麵包屑
        $breadcrumbs1 = $this->navigationService->getCurrentBreadcrumbs('admin.users.create');
        
        // 從當前路由生成麵包屑
        $breadcrumbs2 = $this->navigationService->getCurrentBreadcrumbs();
        
        // 麵包屑結構範例：
        // [
        //     ['title' => '使用者管理', 'route' => 'admin.users.index', 'active' => false],
        //     ['title' => '建立使用者', 'route' => null, 'active' => true]
        // ]
    }

    /**
     * 快速操作選單範例
     */
    public function quickActionsExample(User $user): void
    {
        $quickActions = $this->navigationService->getQuickActions($user);
        
        // 快速操作結構範例：
        // [
        //     [
        //         'title' => '建立使用者',
        //         'route' => 'admin.users.create',
        //         'icon' => 'user-plus',
        //         'permission' => 'admin.users.create',
        //         'color' => 'primary'
        //     ]
        // ]
        
        // 在視圖中使用
        foreach ($quickActions as $action) {
            echo "<a href='" . route($action['route']) . "' class='btn btn-{$action['color']}'>";
            echo "<i class='icon-{$action['icon']}'></i> {$action['title']}";
            echo "</a>";
        }
    }
}