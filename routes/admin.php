<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;

/*
|--------------------------------------------------------------------------
| 管理後台路由
|--------------------------------------------------------------------------
|
| 這裡定義所有管理後台的路由，所有路由都會套用管理後台中介軟體群組
| 包含認證、權限檢查、佈局設定、安全檢查等中介軟體
|
*/

// 管理後台路由群組 - 套用完整的中介軟體堆疊
Route::middleware('admin')
     ->prefix('admin')
     ->name('admin.')
     ->group(function () {
    
    // 儀表板路由
    Route::get('/dashboard', [DashboardController::class, 'index'])
         ->name('dashboard')
         ->middleware('can:dashboard.view');
    
    // 使用者管理路由群組
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', function () {
            return view('admin.users.index');
        })->name('index')->middleware('can:users.view');
        
        Route::get('/create', function () {
            return view('admin.users.create');
        })->name('create')->middleware('can:users.create');
        
        Route::get('/{user}', function ($user) {
            return view('admin.users.show', compact('user'));
        })->name('show')->middleware('can:users.view');
        
        Route::get('/{user}/edit', function ($user) {
            return view('admin.users.edit', compact('user'));
        })->name('edit')->middleware('can:users.edit');
    });
    
    // 角色管理路由群組
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', function () {
            return view('admin.roles.index');
        })->name('index')->middleware('can:roles.view');
        
        Route::get('/create', function () {
            return view('admin.roles.create');
        })->name('create')->middleware('can:roles.create');
        
        Route::get('/{role}/edit', function ($role) {
            return view('admin.roles.edit', compact('role'));
        })->name('edit')->middleware('can:roles.edit');
    });
    
    // 權限管理路由群組
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', function () {
            return view('admin.permissions.index');
        })->name('index')->middleware('can:permissions.view');
        
        Route::get('/create', function () {
            return view('admin.permissions.create');
        })->name('create')->middleware('can:permissions.create');
        
        Route::get('/{permission}/edit', function ($permission) {
            return view('admin.permissions.edit', compact('permission'));
        })->name('edit')->middleware('can:permissions.edit');
    });
    
    // 系統設定路由群組
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', function () {
            return view('admin.settings.index');
        })->name('index')->middleware('can:settings.view');
        
        Route::get('/general', function () {
            return view('admin.settings.general');
        })->name('general')->middleware('can:settings.general');
        
        Route::get('/security', function () {
            return view('admin.settings.security');
        })->name('security')->middleware('can:settings.security');
        
        Route::get('/appearance', function () {
            return view('admin.settings.appearance');
        })->name('appearance')->middleware('can:settings.appearance');
        
        Route::get('/notifications', function () {
            return view('admin.settings.notifications');
        })->name('notifications')->middleware('can:settings.notifications');
    });
    
    // 活動記錄路由群組
    Route::prefix('activities')->name('activities.')->group(function () {
        Route::get('/', function () {
            return view('admin.activities.index');
        })->name('index')->middleware('can:activities.view');
        
        Route::get('/security', function () {
            return view('admin.activities.security');
        })->name('security')->middleware('can:activities.security');
        
        Route::get('/statistics', function () {
            return view('admin.activities.statistics');
        })->name('statistics')->middleware('can:activities.statistics');
    });
    
    // 個人資料和帳號管理路由
    Route::get('/profile', function () {
        return view('admin.profile.index');
    })->name('profile');
    
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/settings', function () {
            return view('admin.account.settings');
        })->name('settings');
        
        Route::get('/security', function () {
            return view('admin.account.security');
        })->name('security');
        
        Route::get('/preferences', function () {
            return view('admin.account.preferences');
        })->name('preferences');
        
        Route::get('/sessions', function () {
            return view('admin.account.sessions');
        })->name('sessions');
    });
    
    // 通知中心路由
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', function () {
            return view('admin.notifications.index');
        })->name('index');
        
        Route::get('/settings', function () {
            return view('admin.notifications.settings');
        })->name('settings');
    });
    
    // 說明中心路由
    Route::get('/help', function () {
        return view('admin.help.index');
    })->name('help');
    
    // API 文檔路由（僅開發環境）
    Route::get('/api-docs', function () {
        return view('admin.api-docs.index');
    })->name('api-docs')->middleware('env:local');
    
    // 系統資訊路由（需要超級管理員權限）
    Route::get('/system-info', function () {
        return view('admin.system.info');
    })->name('system.info')->middleware('role:super_admin');
    
    // 維護模式管理路由（需要超級管理員權限）
    Route::prefix('maintenance')->name('maintenance.')->middleware('role:super_admin')->group(function () {
        Route::get('/', function () {
            return view('admin.maintenance.index');
        })->name('index');
        
        Route::get('/logs', function () {
            return view('admin.maintenance.logs');
        })->name('logs');
        
        Route::get('/cache', function () {
            return view('admin.maintenance.cache');
        })->name('cache');
        
        Route::get('/backups', function () {
            return view('admin.maintenance.backups');
        })->name('backups');
    });
    
});

// 開發環境測試路由
if (app()->environment('local')) {
    Route::middleware('admin')
         ->prefix('admin')
         ->name('admin.')
         ->group(function () {
        
        // 動畫展示路由
        Route::get('/animations', function () {
            return view('admin.animations.index');
        })->name('animations');
        
        // 佈局測試路由
        Route::get('/test-layout', function () {
            return view('admin.test-layout');
        })->name('test-layout');
        
        // 主題測試路由
        Route::get('/test-theme', function () {
            return view('admin.test-theme');
        })->name('test-theme');
        
        // 響應式測試路由
        Route::get('/test-responsive', function () {
            return view('admin.test-responsive');
        })->name('test-responsive');
        
        // 元件測試路由
        Route::get('/test-components', function () {
            return view('admin.test-components');
        })->name('test-components');
        
        // 效能測試路由
        Route::get('/test-performance', function () {
            return view('admin.test-performance');
        })->name('test-performance');
        
    });
}

