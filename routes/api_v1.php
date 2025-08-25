<?php

use App\Http\Controllers\Api\V1\ActivityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes - 活動記錄
|--------------------------------------------------------------------------
|
| 活動記錄 API 路由定義
| 所有路由都需要 API 認證和適當的權限
|
*/

// 活動記錄 API 路由
Route::prefix('activities')->name('activities.')->group(function () {
    
    // 基本 CRUD 操作
    Route::get('/', [ActivityController::class, 'index'])
        ->name('index')
        ->middleware(['api_rate_limit:100,1']); // 每分鐘 100 次請求
        
    Route::get('/{id}', [ActivityController::class, 'show'])
        ->name('show')
        ->where('id', '[0-9]+')
        ->middleware(['api_rate_limit:200,1']); // 每分鐘 200 次請求
        
    // 搜尋功能
    Route::get('/search', [ActivityController::class, 'search'])
        ->name('search')
        ->middleware(['api_rate_limit:50,1']); // 每分鐘 50 次請求
        
    // 統計資料
    Route::get('/stats', [ActivityController::class, 'stats'])
        ->name('stats')
        ->middleware(['api_rate_limit:30,1']); // 每分鐘 30 次請求
        
    // 相關活動
    Route::get('/{id}/related', [ActivityController::class, 'related'])
        ->name('related')
        ->where('id', '[0-9]+')
        ->middleware(['api_rate_limit:100,1']);
        
    // 匯出功能（需要特殊權限）
    Route::post('/export', [ActivityController::class, 'export'])
        ->name('export')
        ->middleware(['api_rate_limit:5,1']); // 每分鐘 5 次請求
        
    Route::get('/download/{filename}', [ActivityController::class, 'download'])
        ->name('download')
        ->where('filename', '[a-zA-Z0-9_\-\.]+')
        ->middleware(['api_rate_limit:10,1']); // 每分鐘 10 次請求
        
    // 批量操作（管理員專用）
    Route::post('/bulk-action', [ActivityController::class, 'bulkAction'])
        ->name('bulk-action')
        ->middleware(['api_rate_limit:10,1']); // 每分鐘 10 次請求
});

// API 健康檢查
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'version' => 'v1',
        'timestamp' => now()->toISOString(),
        'services' => [
            'database' => 'ok',
            'cache' => 'ok',
            'queue' => 'ok',
        ]
    ]);
})->name('health');

// API 資訊
Route::get('/info', function () {
    return response()->json([
        'name' => 'Activity Log API',
        'version' => 'v1.0.0',
        'description' => '活動記錄管理 API',
        'documentation' => url('/api/v1/docs'),
        'endpoints' => [
            'activities' => url('/api/v1/activities'),
            'health' => url('/api/v1/health'),
        ],
        'rate_limits' => [
            'default' => '60 requests per minute',
            'search' => '50 requests per minute',
            'export' => '5 requests per minute',
        ],
        'authentication' => [
            'type' => 'Bearer Token',
            'header' => 'Authorization: Bearer {token}',
        ]
    ]);
})->name('info');

// API 文檔
Route::get('/docs', [\App\Http\Controllers\Api\V1\ApiDocumentationController::class, 'index'])
    ->name('docs')
    ->withoutMiddleware(['api_auth', 'api_rate_limit']); // 文檔不需要認證