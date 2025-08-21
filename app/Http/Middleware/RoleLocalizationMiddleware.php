<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * 角色本地化中介軟體
 * 
 * 處理角色管理相關頁面的語言設定
 */
class RoleLocalizationMiddleware
{
    /**
     * 處理傳入的請求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 檢查是否為角色管理相關路由
        if ($this->isRoleManagementRoute($request)) {
            $this->setLocaleForRoleManagement($request);
        }

        return $next($request);
    }

    /**
     * 檢查是否為角色管理相關路由
     *
     * @param Request $request
     * @return bool
     */
    private function isRoleManagementRoute(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        
        if (!$routeName) {
            return false;
        }

        // 角色管理相關路由模式
        $roleManagementPatterns = [
            'admin.roles.*',
            'livewire.admin.roles.*',
            'api.role-translations.*',
        ];

        foreach ($roleManagementPatterns as $pattern) {
            if (fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        // 檢查 URL 路徑
        $path = $request->path();
        $roleManagementPaths = [
            'admin/roles*',
            'livewire/admin/roles*',
            'api/role-translations*',
        ];

        foreach ($roleManagementPaths as $pathPattern) {
            if (fnmatch($pathPattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 為角色管理設定語言環境
     *
     * @param Request $request
     */
    private function setLocaleForRoleManagement(Request $request): void
    {
        // 優先順序：
        // 1. URL 參數中的 locale
        // 2. Session 中儲存的 locale
        // 3. 使用者偏好設定
        // 4. 瀏覽器語言
        // 5. 應用程式預設語言

        $locale = $this->determineLocale($request);
        
        if ($this->isValidLocale($locale)) {
            App::setLocale($locale);
            Session::put('role_management_locale', $locale);
            
            // 設定 Carbon 語言環境
            if (class_exists('\Carbon\Carbon')) {
                \Carbon\Carbon::setLocale($this->getCarbonLocale($locale));
            }
        }
    }

    /**
     * 決定要使用的語言環境
     *
     * @param Request $request
     * @return string
     */
    private function determineLocale(Request $request): string
    {
        // 1. 檢查 URL 參數
        if ($request->has('locale')) {
            return $request->get('locale');
        }

        // 2. 檢查 Session
        if (Session::has('role_management_locale')) {
            return Session::get('role_management_locale');
        }

        // 3. 檢查使用者偏好設定
        if ($request->user() && method_exists($request->user(), 'getPreferredLocale')) {
            $userLocale = $request->user()->getPreferredLocale();
            if ($userLocale) {
                return $userLocale;
            }
        }

        // 4. 檢查瀏覽器語言
        $browserLocale = $this->getBrowserLocale($request);
        if ($browserLocale) {
            return $browserLocale;
        }

        // 5. 使用應用程式預設語言
        return config('app.locale', 'en');
    }

    /**
     * 從瀏覽器 Accept-Language 標頭取得語言偏好
     *
     * @param Request $request
     * @return string|null
     */
    private function getBrowserLocale(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');
        
        if (!$acceptLanguage) {
            return null;
        }

        // 解析 Accept-Language 標頭
        $languages = [];
        foreach (explode(',', $acceptLanguage) as $lang) {
            $parts = explode(';', trim($lang));
            $locale = trim($parts[0]);
            $quality = 1.0;
            
            if (isset($parts[1]) && strpos($parts[1], 'q=') === 0) {
                $quality = (float) substr($parts[1], 2);
            }
            
            $languages[$locale] = $quality;
        }

        // 按品質排序
        arsort($languages);

        // 尋找支援的語言
        foreach (array_keys($languages) as $locale) {
            // 完全匹配
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
            
            // 語言代碼匹配（例如 zh-TW -> zh_TW）
            $normalizedLocale = str_replace('-', '_', $locale);
            if ($this->isValidLocale($normalizedLocale)) {
                return $normalizedLocale;
            }
            
            // 主要語言匹配（例如 zh-TW -> zh）
            $primaryLang = explode('-', $locale)[0];
            if ($this->isValidLocale($primaryLang)) {
                return $primaryLang;
            }
        }

        return null;
    }

    /**
     * 檢查語言環境是否有效
     *
     * @param string $locale
     * @return bool
     */
    private function isValidLocale(string $locale): bool
    {
        $supportedLocales = config('app.supported_locales', ['en', 'zh_TW']);
        return in_array($locale, $supportedLocales);
    }

    /**
     * 取得 Carbon 對應的語言環境
     *
     * @param string $locale
     * @return string
     */
    private function getCarbonLocale(string $locale): string
    {
        $carbonLocales = [
            'en' => 'en',
            'zh_TW' => 'zh_TW',
            'zh_CN' => 'zh_CN',
            'zh' => 'zh',
        ];

        return $carbonLocales[$locale] ?? 'en';
    }
}