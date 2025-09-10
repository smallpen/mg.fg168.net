<?php

use App\Http\Controllers\Agent\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 代理自主管理路由
|--------------------------------------------------------------------------
|
| 這裡定義代理專屬的管理介面路由
| 所有路由都需要代理身份驗證
|
*/

Route::middleware(['auth', 'agent.auth'])->prefix('agent')->name('agent.')->group(function () {
    
    // 代理儀表板
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        
        // 儀表板首頁
        Route::get('/', [DashboardController::class, 'index'])
             ->name('index');
        
        // 組織架構
        Route::get('/organization', [DashboardController::class, 'organization'])
             ->name('organization');
        
        // 下層代理管理
        Route::get('/agents', [DashboardController::class, 'agents'])
             ->name('agents');
        
        // 建立下層代理
        Route::get('/agents/create', [DashboardController::class, 'createAgent'])
             ->name('create-agent');
        
        // 玩家管理
        Route::get('/players', [DashboardController::class, 'players'])
             ->name('players');
        
        // 建立玩家
        Route::get('/players/create', [DashboardController::class, 'createPlayer'])
             ->name('create-player');
        
        // 點數管理
        Route::get('/points', [DashboardController::class, 'points'])
             ->name('points');
        
        // 統計報表
        Route::get('/statistics', [DashboardController::class, 'statistics'])
             ->name('statistics');
    });
});