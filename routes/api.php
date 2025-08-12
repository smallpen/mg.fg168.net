<?php

use App\Http\Controllers\HealthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| 健康檢查路由
|--------------------------------------------------------------------------
|
| 這些路由提供系統健康狀態和監控資訊，通常不需要認證
| 可供負載平衡器、監控系統和運維工具使用
|
*/

// 網路連線檢測端點 - 用於前端網路狀態檢測
Route::match(['GET', 'HEAD'], '/ping', function () {
    return response('', 200);
})->name('api.ping');

// 基本健康檢查 - 最輕量級的檢查
Route::get('/health', [HealthController::class, 'basic'])->name('api.health.basic');

// 詳細健康檢查 - 包含所有組件狀態
Route::get('/health/detailed', [HealthController::class, 'detailed'])->name('api.health.detailed');

// 效能指標
Route::get('/health/metrics', [HealthController::class, 'metrics'])->name('api.health.metrics');

// 資料庫健康檢查
Route::get('/health/database', [HealthController::class, 'database'])->name('api.health.database');

// Redis 健康檢查
Route::get('/health/redis', [HealthController::class, 'redis'])->name('api.health.redis');

// 備份狀態
Route::get('/health/backups', [HealthController::class, 'backups'])->name('api.health.backups');

// 系統資訊
Route::get('/health/info', [HealthController::class, 'info'])->name('api.health.info');

// 完整系統檢查
Route::get('/health/full', [HealthController::class, 'fullCheck'])->name('api.health.full');