<?php

namespace App\Http\Middleware;

use App\Services\NavigationService;
use App\Services\NotificationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * 管理後台佈局中介軟體
 * 
 * 負責為管理後台頁面設定佈局相關的資料和狀態
 * 包含導航選單、麵包屑、通知等共用資料
 */
class AdminLayoutMiddleware
{
    /**
     * 導航服務
     */
    protected NavigationService $navigationService;
    
    /**
     * 通知服務
     */
    protected NotificationService $notificationService;
    
    /**
     * 建構函式
     */
    public function __construct(
        NavigationService $navigationService,
        NotificationService $notificationService
    ) {
        $this->navigationService = $navigationService;
        $this->notificationService = $notificationService;
    }
    
    /**
     * 處理傳入的請求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 確保使用者已登入
        if (!Auth::check()) {
            return $next($request);
        }
        
        $user = Auth::user();
        
        // 設定佈局相關的視圖資料
        $this->shareLayoutData($request, $user);
        
        // 設定頁面標題和麵包屑
        $this->setupPageMetadata($request);
        
        // 設定使用者偏好設定
        $this->setupUserPreferences($user);
        
        // 檢查維護模式狀態
        $this->checkMaintenanceMode($request, $user);
        
        return $next($request);
    }
    
    /**
     * 共享佈局資料到視圖
     */
    protected function shareLayoutData(Request $request, $user): void
    {
        // 取得導航選單結構
        $menuStructure = $this->navigationService->getMenuStructure($user);
        
        // 取得當前路由的麵包屑
        $breadcrumbs = $this->navigationService->getCurrentBreadcrumbs($request->route()->getName() ?? '');
        
        // 取得未讀通知數量
        $unreadNotifications = $this->notificationService->getUnreadCount($user);
        
        // 取得最近通知
        $recentNotifications = $this->notificationService->getUserNotifications($user, [
            'limit' => 5,
            'unread_only' => false
        ]);
        
        // 取得快速操作選單
        $quickActions = $this->navigationService->getQuickActions($user);
        
        // 取得當前頁面資訊
        $currentRoute = $request->route()->getName() ?? '';
        $currentUrl = $request->url();
        
        // 共享資料到所有視圖
        View::share([
            'adminLayout' => [
                'menuStructure' => $menuStructure,
                'breadcrumbs' => $breadcrumbs,
                'unreadNotifications' => $unreadNotifications,
                'recentNotifications' => $recentNotifications,
                'quickActions' => $quickActions,
                'currentRoute' => $currentRoute,
                'currentUrl' => $currentUrl,
                'user' => $user,
                'sidebarCollapsed' => session('sidebar_collapsed', false),
                'currentTheme' => session('theme', 'light'),
                'currentLocale' => app()->getLocale(),
            ]
        ]);
    }
    
    /**
     * 設定頁面元資料
     */
    protected function setupPageMetadata(Request $request): void
    {
        $routeName = $request->route()->getName() ?? '';
        
        // 根據路由名稱設定頁面標題
        $pageTitle = $this->getPageTitle($routeName);
        
        // 設定頁面描述
        $pageDescription = $this->getPageDescription($routeName);
        
        // 設定頁面關鍵字
        $pageKeywords = $this->getPageKeywords($routeName);
        
        View::share([
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'pageKeywords' => $pageKeywords,
            'fullPageTitle' => $pageTitle ? "{$pageTitle} - 管理後台" : '管理後台',
        ]);
    }
    
    /**
     * 設定使用者偏好設定
     */
    protected function setupUserPreferences($user): void
    {
        // 取得使用者主題偏好
        $theme = $user->preferences['theme'] ?? session('theme', 'light');
        
        // 取得使用者語言偏好
        $locale = $user->preferences['locale'] ?? app()->getLocale();
        
        // 取得側邊欄狀態偏好
        $sidebarCollapsed = $user->preferences['sidebar_collapsed'] ?? session('sidebar_collapsed', false);
        
        // 取得每頁顯示數量偏好
        $perPage = $user->preferences['per_page'] ?? 15;
        
        // 取得時區偏好
        $timezone = $user->preferences['timezone'] ?? config('app.timezone');
        
        // 設定到 session 中
        session([
            'theme' => $theme,
            'sidebar_collapsed' => $sidebarCollapsed,
            'per_page' => $perPage,
            'timezone' => $timezone,
        ]);
        
        // 設定應用程式語言
        if ($locale !== app()->getLocale()) {
            app()->setLocale($locale);
        }
        
        // 共享使用者偏好到視圖
        View::share([
            'userPreferences' => [
                'theme' => $theme,
                'locale' => $locale,
                'sidebarCollapsed' => $sidebarCollapsed,
                'perPage' => $perPage,
                'timezone' => $timezone,
            ]
        ]);
    }
    
    /**
     * 檢查維護模式狀態
     */
    protected function checkMaintenanceMode(Request $request, $user): void
    {
        // 檢查是否處於維護模式
        $isMaintenanceMode = app()->isDownForMaintenance();
        
        // 檢查使用者是否有維護模式存取權限
        $hasMaintenanceAccess = $user->hasRole('super_admin') || 
                               $user->hasPermission('maintenance.access');
        
        // 如果處於維護模式且使用者沒有存取權限
        if ($isMaintenanceMode && !$hasMaintenanceAccess) {
            // 檢查 IP 是否在允許清單中
            $maintenanceData = app()->maintenanceMode()->data();
            $allowedIps = $maintenanceData['allowed'] ?? [];
            
            if (!in_array($request->ip(), $allowedIps)) {
                // 記錄維護模式存取嘗試
                activity()
                    ->causedBy($user)
                    ->withProperties([
                        'ip' => $request->ip(),
                        'url' => $request->url(),
                        'maintenance_mode' => true,
                    ])
                    ->log('維護模式期間嘗試存取');
            }
        }
        
        // 共享維護模式狀態到視圖
        View::share([
            'maintenanceMode' => [
                'isActive' => $isMaintenanceMode,
                'hasAccess' => $hasMaintenanceAccess,
                'message' => $maintenanceData['message'] ?? '系統維護中，請稍後再試',
                'retryAfter' => $maintenanceData['retry'] ?? null,
            ]
        ]);
    }
    
    /**
     * 根據路由名稱取得頁面標題
     */
    protected function getPageTitle(string $routeName): string
    {
        $titles = [
            'admin.dashboard' => '儀表板',
            'admin.users.index' => '使用者管理',
            'admin.users.create' => '建立使用者',
            'admin.users.show' => '使用者詳情',
            'admin.users.edit' => '編輯使用者',
            'admin.roles.index' => '角色管理',
            'admin.roles.create' => '建立角色',
            'admin.roles.edit' => '編輯角色',
            'admin.permissions.index' => '權限管理',
            'admin.settings.index' => '系統設定',
            'admin.profile' => '個人資料',
            'admin.account.settings' => '帳號設定',
            'admin.help' => '說明中心',
            'admin.animations' => '動畫展示',
        ];
        
        return $titles[$routeName] ?? '';
    }
    
    /**
     * 根據路由名稱取得頁面描述
     */
    protected function getPageDescription(string $routeName): string
    {
        $descriptions = [
            'admin.dashboard' => '管理後台首頁，查看系統統計和快速操作',
            'admin.users.index' => '管理系統使用者，包含新增、編輯、刪除等功能',
            'admin.users.create' => '建立新的系統使用者帳號',
            'admin.roles.index' => '管理使用者角色和權限設定',
            'admin.permissions.index' => '管理系統權限和存取控制',
            'admin.settings.index' => '系統基本設定和配置管理',
        ];
        
        return $descriptions[$routeName] ?? '';
    }
    
    /**
     * 根據路由名稱取得頁面關鍵字
     */
    protected function getPageKeywords(string $routeName): string
    {
        $keywords = [
            'admin.dashboard' => '管理後台,儀表板,統計,系統管理',
            'admin.users.index' => '使用者管理,帳號管理,使用者列表',
            'admin.roles.index' => '角色管理,權限管理,存取控制',
            'admin.permissions.index' => '權限管理,系統權限,存取控制',
            'admin.settings.index' => '系統設定,配置管理,系統配置',
        ];
        
        return $keywords[$routeName] ?? '管理後台,系統管理';
    }
    
    /**
     * 記錄佈局相關的活動
     */
    protected function logLayoutActivity(string $action, array $properties = []): void
    {
        if (Auth::check()) {
            activity()
                ->causedBy(Auth::user())
                ->withProperties(array_merge($properties, [
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'timestamp' => now()->toISOString(),
                ]))
                ->log("佈局操作: {$action}");
        }
    }
}