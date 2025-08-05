<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\HealthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| 這裡是應用程式的網頁路由註冊位置。這些路由會被載入到
| RouteServiceProvider 中，並且都會被指派到 "web" 中介軟體群組。
| 開始建立一些精彩的功能吧！
|
*/

// 健康檢查路由（不需要認證）
Route::get('/health', [HealthController::class, 'basic'])->name('health.basic');
Route::get('/health/detailed', [HealthController::class, 'detailed'])->name('health.detailed');
Route::get('/health/metrics', [HealthController::class, 'metrics'])->name('health.metrics');
Route::get('/health/database', [HealthController::class, 'database'])->name('health.database');
Route::get('/health/redis', [HealthController::class, 'redis'])->name('health.redis');
Route::get('/health/backups', [HealthController::class, 'backups'])->name('health.backups');
Route::get('/health/info', [HealthController::class, 'info'])->name('health.info');
Route::get('/health/full', [HealthController::class, 'fullCheck'])->name('health.full');
Route::get('/health/alive', [HealthController::class, 'basic'])->name('health.alive');
Route::get('/health/ready', [HealthController::class, 'detailed'])->name('health.ready');

// 首頁重新導向到管理後台
Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

// 管理後台路由群組
Route::prefix('admin')->name('admin.')->group(function () {
    
    // 認證相關路由（未登入時可存取）
    Route::middleware('guest')->group(function () {
        Route::get('/login', function () {
            return view('admin.auth.login');
        })->name('login');
        
        Route::post('/login', function () {
            // 這個路由實際上不會被使用，因為 Livewire 會處理登入邏輯
            // 但保留它以防需要回退到傳統表單提交
            return redirect()->route('admin.login');
        });
    });
    
    // 需要認證的管理後台路由
    Route::middleware(['auth', 'admin'])->group(function () {
        
        // 儀表板
        Route::get('/dashboard', [DashboardController::class, 'index'])
             ->name('dashboard');
        
        // 使用者管理路由
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', function () {
                return view('admin.users.index');
            })->name('index');
            
            Route::get('/create', function () {
                return view('admin.users.create');
            })->name('create');
            
            Route::get('/{user}', function ($user) {
                return view('admin.users.show', compact('user'));
            })->name('show');
            
            Route::get('/{user}/edit', function ($user) {
                return view('admin.users.edit', compact('user'));
            })->name('edit');
        });
        
        // 角色管理路由
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', function () {
                return view('admin.roles.index');
            })->name('index');
            
            Route::get('/create', function () {
                return view('admin.roles.create');
            })->name('create');
            
            Route::get('/{role}/edit', function ($role) {
                return view('admin.roles.edit', compact('role'));
            })->name('edit');
        });
        
        // 權限管理路由
        Route::prefix('permissions')->name('permissions.')->group(function () {
            Route::get('/', function () {
                return view('admin.permissions.index');
            })->name('index');
        });
        
        // 系統設定路由
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', function () {
                return view('admin.settings.index');
            })->name('index');
        });
        
        // 測試路由（開發環境使用）
        if (app()->environment('local')) {
            Route::get('/test-layout', function () {
                return view('admin.test-layout');
            })->name('test-layout');
            
            Route::get('/test-theme', function () {
                return view('admin.test-theme');
            })->name('test-theme');
        }
        
    });
    
    // 登出路由（保留作為備用，主要由 Livewire 元件處理）
    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('admin.login')->with('success', '您已成功登出');
    })->name('logout');
    
});