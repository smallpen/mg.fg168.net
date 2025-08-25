<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LanguageFileCache;
use App\Services\LanguagePerformanceMonitor;
use App\Services\MultilingualLogger;

/**
 * 語言效能監控中介軟體
 * 
 * 監控語言相關操作的效能並記錄指標
 */
class LanguagePerformanceMiddleware
{
    /**
     * 語言檔案快取服務
     */
    private LanguageFileCache $cache;

    /**
     * 語言效能監控服務
     */
    private LanguagePerformanceMonitor $monitor;

    /**
     * 多語系日誌記錄器
     */
    private MultilingualLogger $logger;

    /**
     * 建構函數
     */
    public function __construct(
        LanguageFileCache $cache,
        LanguagePerformanceMonitor $monitor,
        MultilingualLogger $logger
    ) {
        $this->cache = $cache;
        $this->monitor = $monitor;
        $this->logger = $logger;
    }

    /**
     * 處理傳入的請求
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // 記錄請求開始
        $this->recordRequestStart($request);
        
        // 處理請求
        $response = $next($request);
        
        // 記錄請求完成
        $this->recordRequestComplete($request, $response, $startTime, $startMemory);
        
        return $response;
    }

    /**
     * 記錄請求開始
     *
     * @param Request $request
     * @return void
     */
    private function recordRequestStart(Request $request): void
    {
        // 如果是語言切換請求，記錄快取操作
        if ($request->has('locale')) {
            $this->monitor->recordCachePerformance('request', $request->get('locale'));
        }
    }

    /**
     * 記錄請求完成
     *
     * @param Request $request
     * @param Response $response
     * @param float $startTime
     * @param int $startMemory
     * @return void
     */
    private function recordRequestComplete(
        Request $request, 
        Response $response, 
        float $startTime, 
        int $startMemory
    ): void {
        $duration = (microtime(true) - $startTime) * 1000; // 轉換為毫秒
        $memoryUsage = memory_get_usage(true) - $startMemory;
        $currentLocale = app()->getLocale();
        
        // 記錄頁面載入效能（如果包含語言相關操作）
        if ($this->isLanguageRelatedRequest($request)) {
            $this->recordLanguagePagePerformance(
                $request,
                $response,
                $currentLocale,
                $duration,
                $memoryUsage
            );
        }
        
        // 檢查是否有語言相關錯誤
        if ($response->getStatusCode() >= 400) {
            $this->recordLanguageError($request, $response, $currentLocale);
        }
    }

    /**
     * 判斷是否為語言相關請求
     *
     * @param Request $request
     * @return bool
     */
    private function isLanguageRelatedRequest(Request $request): bool
    {
        // 檢查是否有語言參數
        if ($request->has('locale')) {
            return true;
        }
        
        // 檢查路由是否包含語言相關功能
        $route = $request->route();
        if ($route) {
            $routeName = $route->getName();
            $languageRoutes = [
                'admin.', // 管理後台路由
                'language.', // 語言相關路由
                'locale.', // 語言設定路由
            ];
            
            foreach ($languageRoutes as $prefix) {
                if (str_starts_with($routeName ?? '', $prefix)) {
                    return true;
                }
            }
        }
        
        // 檢查 Accept-Language 標頭
        if ($request->header('Accept-Language')) {
            return true;
        }
        
        return false;
    }

    /**
     * 記錄語言頁面效能
     *
     * @param Request $request
     * @param Response $response
     * @param string $locale
     * @param float $duration
     * @param int $memoryUsage
     * @return void
     */
    private function recordLanguagePagePerformance(
        Request $request,
        Response $response,
        string $locale,
        float $duration,
        int $memoryUsage
    ): void {
        $route = $request->route();
        $routeName = $route ? $route->getName() : 'unknown';
        
        // 記錄頁面載入效能
        $this->monitor->recordTranslationPerformance(
            "page:{$routeName}",
            $locale,
            $duration,
            $response->getStatusCode() < 400,
            false
        );
        
        // 如果記憶體使用量過高，記錄警報
        $memoryThreshold = 10 * 1024 * 1024; // 10MB
        if ($memoryUsage > $memoryThreshold) {
            $this->logger->logLanguageFileLoadError(
                'page_load',
                $locale,
                "High memory usage detected: " . round($memoryUsage / 1024 / 1024, 2) . "MB",
                [
                    'route' => $routeName,
                    'duration_ms' => round($duration, 2),
                    'memory_usage_bytes' => $memoryUsage,
                    'url' => $request->fullUrl(),
                ]
            );
        }
    }

    /**
     * 記錄語言相關錯誤
     *
     * @param Request $request
     * @param Response $response
     * @param string $locale
     * @return void
     */
    private function recordLanguageError(Request $request, Response $response, string $locale): void
    {
        $statusCode = $response->getStatusCode();
        $route = $request->route();
        $routeName = $route ? $route->getName() : 'unknown';
        
        // 記錄錯誤到效能監控
        $this->monitor->recordTranslationPerformance(
            "error:{$statusCode}:{$routeName}",
            $locale,
            0, // 錯誤情況下不記錄時間
            false, // 標記為失敗
            false
        );
        
        // 如果是語言切換相關錯誤，特別記錄
        if ($request->has('locale') && $statusCode >= 400) {
            $targetLocale = $request->get('locale');
            $this->logger->logLanguageSwitchFailure(
                $targetLocale,
                "HTTP {$statusCode} error during language switch",
                [
                    'route' => $routeName,
                    'current_locale' => $locale,
                    'target_locale' => $targetLocale,
                    'status_code' => $statusCode,
                    'url' => $request->fullUrl(),
                ]
            );
        }
    }
}