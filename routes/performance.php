<?php

use App\Http\Controllers\Admin\PerformanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 效能優化路由
|--------------------------------------------------------------------------
|
| 這裡定義所有與效能監控、優化和管理相關的路由
|
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // 效能監控 API 路由
    Route::prefix('api/performance')->name('api.performance.')->group(function () {
        
        // 記錄效能指標
        Route::post('metrics', [PerformanceController::class, 'recordMetric'])
            ->name('record-metric');
        
        // 取得效能統計
        Route::get('stats', [PerformanceController::class, 'getStats'])
            ->name('stats');
        
        // 取得即時指標
        Route::get('realtime', [PerformanceController::class, 'getRealTimeMetrics'])
            ->name('realtime');
        
        // 更新即時指標
        Route::post('realtime', [PerformanceController::class, 'updateRealTimeMetrics'])
            ->name('update-realtime');
        
        // 取得效能建議
        Route::get('recommendations', [PerformanceController::class, 'getRecommendations'])
            ->name('recommendations');
        
        // 產生效能報告
        Route::get('report', [PerformanceController::class, 'generateReport'])
            ->name('report');
        
        // 清除效能資料
        Route::delete('data', [PerformanceController::class, 'clearData'])
            ->name('clear-data');
    });
    
    // 懶載入相關路由
    Route::prefix('api/lazy-loading')->name('api.lazy-loading.')->group(function () {
        
        // 渲染懶載入元件
        Route::post('render', [PerformanceController::class, 'renderComponent'])
            ->name('render-component');
        
        // 取得懶載入統計
        Route::get('stats', [PerformanceController::class, 'getLazyLoadingStats'])
            ->name('stats');
        
        // 註冊懶載入元件
        Route::post('register', [PerformanceController::class, 'registerLazyComponent'])
            ->name('register-component');
    });
    
});

// 元件渲染路由（用於懶載入）
Route::middleware(['auth'])->group(function () {
    Route::post('admin/components/render', [PerformanceController::class, 'renderComponent'])
        ->name('admin.components.render');
});