<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * 設定語言中介軟體
 * 
 * 此中介軟體負責根據使用者偏好設定或 session 資料設定應用程式語言
 */
class SetLocale
{
    /**
     * 處理傳入的請求
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 支援的語言列表
        $supportedLocales = ['zh_TW', 'en'];
        
        // 預設語言
        $defaultLocale = config('app.locale', 'zh_TW');
        
        // 優先順序：URL 參數 > Session > 使用者偏好 > 預設語言
        $locale = $this->determineLocale($request, $supportedLocales, $defaultLocale);
        
        // 設定應用程式語言
        App::setLocale($locale);
        
        // 儲存語言偏好到 session
        Session::put('locale', $locale);
        
        // 如果使用者已登入，更新使用者的語言偏好
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->locale !== $locale) {
                $user->update(['locale' => $locale]);
            }
        }
        
        return $next($request);
    }
    
    /**
     * 決定要使用的語言
     *
     * @param Request $request
     * @param array $supportedLocales
     * @param string $defaultLocale
     * @return string
     */
    private function determineLocale(Request $request, array $supportedLocales, string $defaultLocale): string
    {
        // 1. 檢查 URL 參數
        if ($request->has('locale')) {
            $urlLocale = $request->get('locale');
            if (in_array($urlLocale, $supportedLocales)) {
                return $urlLocale;
            }
        }
        
        // 2. 檢查 Session
        $sessionLocale = Session::get('locale');
        if ($sessionLocale && in_array($sessionLocale, $supportedLocales)) {
            return $sessionLocale;
        }
        
        // 3. 檢查已登入使用者的偏好設定
        if (auth()->check()) {
            $userLocale = auth()->user()->locale;
            if ($userLocale && in_array($userLocale, $supportedLocales)) {
                return $userLocale;
            }
        }
        
        // 4. 檢查瀏覽器語言偏好
        $browserLocale = $this->getBrowserLocale($request, $supportedLocales);
        if ($browserLocale) {
            return $browserLocale;
        }
        
        // 5. 使用預設語言
        return $defaultLocale;
    }
    
    /**
     * 從瀏覽器 Accept-Language 標頭取得語言偏好
     *
     * @param Request $request
     * @param array $supportedLocales
     * @return string|null
     */
    private function getBrowserLocale(Request $request, array $supportedLocales): ?string
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
            $quality = isset($parts[1]) ? (float) str_replace('q=', '', trim($parts[1])) : 1.0;
            $languages[$locale] = $quality;
        }
        
        // 按品質排序
        arsort($languages);
        
        // 尋找支援的語言
        foreach ($languages as $locale => $quality) {
            // 完全符合
            if (in_array($locale, $supportedLocales)) {
                return $locale;
            }
            
            // 部分符合（例如：zh 符合 zh_TW）
            $shortLocale = substr($locale, 0, 2);
            foreach ($supportedLocales as $supported) {
                if (substr($supported, 0, 2) === $shortLocale) {
                    return $supported;
                }
            }
        }
        
        return null;
    }
}