<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PerformanceMonitoringService;
use App\Services\ComponentLazyLoadingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 效能管理控制器
 * 
 * 處理效能監控、優化和報告相關的請求
 */
class PerformanceController extends Controller
{
    protected PerformanceMonitoringService $performanceService;
    protected ComponentLazyLoadingService $lazyLoadingService;

    public function __construct(
        PerformanceMonitoringService $performanceService,
        ComponentLazyLoadingService $lazyLoadingService
    ) {
        $this->performanceService = $performanceService;
        $this->lazyLoadingService = $lazyLoadingService;
    }

    /**
     * 記錄效能指標
     */
    public function recordMetric(Request $request): JsonResponse
    {
        $request->validate([
            'metric' => 'required|string|in:lcp,fid,cls,ttfb,fcp',
            'value' => 'required|numeric|min:0',
            'context' => 'array',
        ]);

        $this->performanceService->recordMetric(
            $request->input('metric'),
            $request->input('value'),
            $request->input('context', [])
        );

        return response()->json(['success' => true]);
    }

    /**
     * 取得效能統計
     */
    public function getStats(Request $request): JsonResponse
    {
        $period = $request->input('period', '24h');
        $stats = $this->performanceService->getPerformanceStats($period);

        return response()->json($stats);
    }

    /**
     * 取得即時指標
     */
    public function getRealTimeMetrics(): JsonResponse
    {
        $metrics = $this->performanceService->getRealTimeMetrics();
        return response()->json($metrics);
    }

    /**
     * 渲染懶載入元件
     */
    public function renderComponent(Request $request): JsonResponse
    {
        $request->validate([
            'component' => 'required|string',
            'attributes' => 'array',
        ]);

        $componentName = $request->input('component');
        $attributes = $request->input('attributes', []);

        // 檢查元件是否允許懶載入
        if (!$this->lazyLoadingService->shouldLazyLoad($componentName)) {
            return response()->json(['error' => '元件不支援懶載入'], 400);
        }

        try {
            // 這裡應該實作實際的元件渲染邏輯
            // 目前返回佔位符 HTML
            $html = $this->lazyLoadingService->generateLazyComponentHtml($componentName, $attributes);
            
            return response()->json([
                'html' => $html,
                'component' => $componentName,
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => '元件渲染失敗: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 取得效能建議
     */
    public function getRecommendations(): JsonResponse
    {
        $recommendations = $this->performanceService->getPerformanceRecommendations();
        return response()->json($recommendations);
    }

    /**
     * 產生效能報告
     */
    public function generateReport(Request $request): JsonResponse
    {
        $period = $request->input('period', '7d');
        $report = $this->performanceService->generatePerformanceReport($period);

        return response()->json($report);
    }

    /**
     * 清除效能資料
     */
    public function clearData(Request $request): JsonResponse
    {
        $period = $request->input('period');
        $this->performanceService->clearPerformanceData($period);

        return response()->json(['success' => true]);
    }

    /**
     * 更新即時指標
     */
    public function updateRealTimeMetrics(Request $request): JsonResponse
    {
        $request->validate([
            'metrics' => 'required|array',
        ]);

        $this->performanceService->updateRealTimeMetrics($request->input('metrics'));

        return response()->json(['success' => true]);
    }

    /**
     * 取得懶載入統計
     */
    public function getLazyLoadingStats(): JsonResponse
    {
        $stats = $this->lazyLoadingService->getLoadingStats();
        return response()->json($stats);
    }

    /**
     * 註冊懶載入元件
     */
    public function registerLazyComponent(Request $request): JsonResponse
    {
        $request->validate([
            'component' => 'required|string',
            'config' => 'required|array',
            'config.priority' => 'required|string|in:high,medium,low',
            'config.defer' => 'required|boolean',
            'config.placeholder' => 'nullable|string',
        ]);

        $this->lazyLoadingService->registerLazyComponent(
            $request->input('component'),
            $request->input('config')
        );

        return response()->json(['success' => true]);
    }
}