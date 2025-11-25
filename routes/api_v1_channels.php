<?php

use App\Http\Controllers\Api\V1\AgentController;
use App\Http\Controllers\Api\V1\PlayerController;
use App\Http\Controllers\Api\V1\PointController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes - 通路管理
|--------------------------------------------------------------------------
|
| 通路管理 API 路由定義
| 所有路由都需要 API 認證和適當的權限
|
*/

// 代理管理 API 路由
Route::prefix('agents')->name('agents.')->group(function () {
    
    // 基本 CRUD 操作
    Route::get('/', [AgentController::class, 'index'])
        ->name('index')
        ->middleware(['api_rate_limit:100,1']); // 每分鐘 100 次請求
        
    Route::post('/', [AgentController::class, 'store'])
        ->name('store')
        ->middleware(['api_rate_limit:20,1']); // 每分鐘 20 次請求
        
    Route::get('/{agent}', [AgentController::class, 'show'])
        ->name('show')
        ->where('agent', '[0-9]+')
        ->middleware(['api_rate_limit:200,1']); // 每分鐘 200 次請求
        
    Route::put('/{agent}', [AgentController::class, 'update'])
        ->name('update')
        ->where('agent', '[0-9]+')
        ->middleware(['api_rate_limit:50,1']); // 每分鐘 50 次請求
        
    Route::delete('/{agent}', [AgentController::class, 'destroy'])
        ->name('destroy')
        ->where('agent', '[0-9]+')
        ->middleware(['api_rate_limit:10,1']); // 每分鐘 10 次請求
        
    // 組織架構相關
    Route::get('/{agent}/hierarchy', [AgentController::class, 'hierarchy'])
        ->name('hierarchy')
        ->where('agent', '[0-9]+')
        ->middleware(['api_rate_limit:50,1']); // 每分鐘 50 次請求
        
    // 統計資料
    Route::get('/{agent}/stats', [AgentController::class, 'stats'])
        ->name('stats')
        ->where('agent', '[0-9]+')
        ->middleware(['api_rate_limit:30,1']); // 每分鐘 30 次請求
});

// 玩家管理 API 路由
Route::prefix('players')->name('players.')->group(function () {
    
    // 基本 CRUD 操作
    Route::get('/', [PlayerController::class, 'index'])
        ->name('index')
        ->middleware(['api_rate_limit:100,1']); // 每分鐘 100 次請求
        
    Route::post('/', [PlayerController::class, 'store'])
        ->name('store')
        ->middleware(['api_rate_limit:20,1']); // 每分鐘 20 次請求
        
    Route::get('/{player}', [PlayerController::class, 'show'])
        ->name('show')
        ->where('player', '[0-9]+')
        ->middleware(['api_rate_limit:200,1']); // 每分鐘 200 次請求
        
    Route::put('/{player}', [PlayerController::class, 'update'])
        ->name('update')
        ->where('player', '[0-9]+')
        ->middleware(['api_rate_limit:50,1']); // 每分鐘 50 次請求
        
    Route::delete('/{player}', [PlayerController::class, 'destroy'])
        ->name('destroy')
        ->where('player', '[0-9]+')
        ->middleware(['api_rate_limit:10,1']); // 每分鐘 10 次請求
        
    // 統計資料
    Route::get('/{player}/stats', [PlayerController::class, 'stats'])
        ->name('stats')
        ->where('player', '[0-9]+')
        ->middleware(['api_rate_limit:30,1']); // 每分鐘 30 次請求
        
    // 批量操作
    Route::post('/bulk-action', [PlayerController::class, 'bulkAction'])
        ->name('bulk-action')
        ->middleware(['api_rate_limit:5,1']); // 每分鐘 5 次請求
});

// 點數管理 API 路由
Route::prefix('points')->name('points.')->group(function () {
    
    // 點數交易記錄
    Route::get('/', [PointController::class, 'index'])
        ->name('index')
        ->middleware(['api_rate_limit:100,1']); // 每分鐘 100 次請求
        
    Route::get('/{transaction}', [PointController::class, 'show'])
        ->name('show')
        ->where('transaction', '[0-9]+')
        ->middleware(['api_rate_limit:200,1']); // 每分鐘 200 次請求
        
    // 代理點數查詢
    Route::get('/agents/{agent}', [PointController::class, 'agentPoints'])
        ->name('agent-points')
        ->where('agent', '[0-9]+')
        ->middleware(['api_rate_limit:100,1']); // 每分鐘 100 次請求
        
    // 玩家點數查詢
    Route::get('/players/{player}', [PointController::class, 'playerPoints'])
        ->name('player-points')
        ->where('player', '[0-9]+')
        ->middleware(['api_rate_limit:100,1']); // 每分鐘 100 次請求
        
    // 點數操作
    Route::post('/allocate', [PointController::class, 'allocate'])
        ->name('allocate')
        ->middleware(['api_rate_limit:20,1']); // 每分鐘 20 次請求
        
    Route::post('/recover', [PointController::class, 'recover'])
        ->name('recover')
        ->middleware(['api_rate_limit:20,1']); // 每分鐘 20 次請求
        
    // 統計資料
    Route::get('/stats', [PointController::class, 'stats'])
        ->name('stats')
        ->middleware(['api_rate_limit:30,1']); // 每分鐘 30 次請求
});

// API 健康檢查
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'version' => 'v1',
        'module' => 'channels',
        'timestamp' => now()->toISOString(),
        'services' => [
            'database' => 'ok',
            'cache' => 'ok',
            'queue' => 'ok',
        ],
        'endpoints' => [
            'agents' => 'available',
            'players' => 'available',
            'points' => 'available',
        ]
    ]);
})->name('health');

// API 資訊
Route::get('/info', function () {
    return response()->json([
        'name' => 'Channel Management API',
        'version' => 'v1.0.0',
        'description' => '通路管理 API - 代理、玩家和點數管理',
        'documentation' => url('/api/v1/channels/docs'),
        'endpoints' => [
            'agents' => [
                'list' => url('/api/v1/channels/agents'),
                'create' => url('/api/v1/channels/agents'),
                'show' => url('/api/v1/channels/agents/{id}'),
                'update' => url('/api/v1/channels/agents/{id}'),
                'delete' => url('/api/v1/channels/agents/{id}'),
                'hierarchy' => url('/api/v1/channels/agents/{id}/hierarchy'),
                'stats' => url('/api/v1/channels/agents/{id}/stats'),
            ],
            'players' => [
                'list' => url('/api/v1/channels/players'),
                'create' => url('/api/v1/channels/players'),
                'show' => url('/api/v1/channels/players/{id}'),
                'update' => url('/api/v1/channels/players/{id}'),
                'delete' => url('/api/v1/channels/players/{id}'),
                'stats' => url('/api/v1/channels/players/{id}/stats'),
                'bulk_action' => url('/api/v1/channels/players/bulk-action'),
            ],
            'points' => [
                'transactions' => url('/api/v1/channels/points'),
                'transaction_detail' => url('/api/v1/channels/points/{id}'),
                'agent_points' => url('/api/v1/channels/points/agents/{id}'),
                'player_points' => url('/api/v1/channels/points/players/{id}'),
                'allocate' => url('/api/v1/channels/points/allocate'),
                'recover' => url('/api/v1/channels/points/recover'),
                'stats' => url('/api/v1/channels/points/stats'),
            ],
            'health' => url('/api/v1/channels/health'),
        ],
        'rate_limits' => [
            'default' => '100 requests per minute',
            'create_update' => '20-50 requests per minute',
            'delete' => '10 requests per minute',
            'bulk_operations' => '5 requests per minute',
        ],
        'authentication' => [
            'type' => 'Bearer Token (Laravel Sanctum)',
            'header' => 'Authorization: Bearer {token}',
            'abilities_required' => [
                'agents' => ['agents:read', 'agents:write'],
                'players' => ['players:read', 'players:write'],
                'points' => ['points:read', 'points:manage'],
            ],
        ],
        'permissions' => [
            'agents.view' => '檢視代理資料',
            'agents.create' => '建立代理',
            'agents.edit' => '編輯代理',
            'agents.delete' => '刪除代理',
            'players.view' => '檢視玩家資料',
            'players.create' => '建立玩家',
            'players.edit' => '編輯玩家',
            'players.delete' => '刪除玩家',
            'points.view' => '檢視點數資料',
            'points.manage' => '管理點數操作',
        ]
    ]);
})->name('info');

// API 文檔
Route::get('/docs', [\App\Http\Controllers\Api\V1\ChannelDocumentationController::class, 'index'])
    ->name('docs')
    ->withoutMiddleware(['api_auth', 'api_rate_limit']); // 文檔不需要認證