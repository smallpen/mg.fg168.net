<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Models\User;

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
        Route::get('/', [App\Http\Controllers\Admin\UserController::class, 'index'])
             ->name('index')
             ->middleware('can:users.view');
        
        Route::get('/create', [App\Http\Controllers\Admin\UserController::class, 'create'])
             ->name('create')
             ->middleware('can:users.create');
        
        Route::get('/{user}', [App\Http\Controllers\Admin\UserController::class, 'show'])
             ->name('show')
             ->middleware('can:users.view');
        
        Route::get('/{user}/edit', [App\Http\Controllers\Admin\UserController::class, 'edit'])
             ->name('edit')
             ->middleware('can:users.edit');
    });
    
    // 角色管理路由群組
    Route::prefix('roles')->name('roles.')->middleware('role.security')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\RoleController::class, 'index'])
             ->name('index')
             ->middleware('can:roles.view');
        
        Route::get('/create', [App\Http\Controllers\Admin\RoleController::class, 'create'])
             ->name('create')
             ->middleware('can:roles.create');
        
        Route::get('/{role}', [App\Http\Controllers\Admin\RoleController::class, 'show'])
             ->name('show')
             ->middleware('can:roles.view');
        
        Route::get('/{role}/edit', [App\Http\Controllers\Admin\RoleController::class, 'edit'])
             ->name('edit')
             ->middleware('can:roles.edit');
        
        // 角色統計路由
        Route::get('/statistics', [App\Http\Controllers\Admin\RoleController::class, 'statistics'])
             ->name('statistics')
             ->middleware('can:roles.view');
        
        Route::get('/{role}/statistics', [App\Http\Controllers\Admin\RoleController::class, 'roleStatistics'])
             ->name('role-statistics')
             ->middleware('can:roles.view');
        
        // 權限矩陣路由
        Route::get('/permissions/matrix', [App\Http\Controllers\Admin\RoleController::class, 'permissionMatrix'])
             ->name('permission-matrix')
             ->middleware('can:roles.edit');
        
        Route::get('/{role}/permissions', [App\Http\Controllers\Admin\RoleController::class, 'permissionMatrix'])
             ->name('permissions')
             ->middleware('can:roles.edit');
        
        // 角色操作路由
        Route::post('/{role}/duplicate', [App\Http\Controllers\Admin\RoleController::class, 'duplicate'])
             ->name('duplicate')
             ->middleware('can:roles.create');
        
        Route::get('/{role}/export', [App\Http\Controllers\Admin\RoleController::class, 'export'])
             ->name('export')
             ->middleware('can:roles.view');
        
        Route::post('/bulk-action', [App\Http\Controllers\Admin\RoleController::class, 'bulkAction'])
             ->name('bulk-action')
             ->middleware('can:roles.edit');
    });
    
    // 權限管理路由群組
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\PermissionController::class, 'index'])
             ->name('index')
             ->middleware('can:permissions.view');
        
        Route::get('/matrix', [App\Http\Controllers\Admin\PermissionController::class, 'matrix'])
             ->name('matrix')
             ->middleware('can:roles.edit');
        
        Route::get('/create', [App\Http\Controllers\Admin\PermissionController::class, 'create'])
             ->name('create');
        
        Route::get('/{permission}', [App\Http\Controllers\Admin\PermissionController::class, 'show'])
             ->name('show')
             ->middleware('can:permissions.view');
        
        Route::get('/{permission}/edit', [App\Http\Controllers\Admin\PermissionController::class, 'edit'])
             ->name('edit')
             ->middleware(['can:permissions.edit', 'permission.security:update']);
        
        // 權限匯入匯出路由
        Route::prefix('import-export')->name('import-export.')->group(function () {
            Route::post('/export', [App\Http\Controllers\Admin\PermissionImportExportController::class, 'export'])
                 ->name('export')
                 ->middleware(['can:permissions.export', 'permission.security:export']);
            
            Route::post('/import', [App\Http\Controllers\Admin\PermissionImportExportController::class, 'import'])
                 ->name('import')
                 ->middleware(['can:permissions.import', 'permission.security:import']);
            
            Route::post('/preview', [App\Http\Controllers\Admin\PermissionImportExportController::class, 'preview'])
                 ->name('preview')
                 ->middleware(['can:permissions.import', 'permission.security:import']);
            
            Route::get('/stats', [App\Http\Controllers\Admin\PermissionImportExportController::class, 'stats'])
                 ->name('stats')
                 ->middleware('can:permissions.view');
        });

        // 權限模板路由
        Route::get('/templates', function () {
            return view('admin.permissions.templates');
        })->name('templates')->middleware(['can:permissions.manage', 'permission.security:view']);

        // 權限測試路由
        Route::get('/test', function () {
            return view('admin.permissions.test');
        })->name('test')->middleware(['can:permissions.test', 'permission.security:test']);

        // 權限依賴關係圖表路由
        Route::get('/dependencies', [App\Http\Controllers\Admin\PermissionController::class, 'dependencies'])
             ->name('dependencies')
             ->middleware('can:permissions.view');
    });
    
    // 系統設定路由群組
    Route::prefix('settings')->name('settings.')->middleware(['settings.access', 'settings.performance'])->group(function () {
        // 主要設定頁面路由
        Route::get('/', [App\Http\Controllers\Admin\SettingsController::class, 'index'])
             ->name('index')
             ->middleware('can:system.settings');
        
        // 系統設定管理路由
        Route::get('/system', [App\Http\Controllers\Admin\SettingsController::class, 'system'])
             ->name('system')
             ->middleware('can:system.settings');
        
        // 基本設定管理路由
        Route::get('/basic', [App\Http\Controllers\Admin\SettingsController::class, 'basic'])
             ->name('basic')
             ->middleware('can:system.settings');
        
        // 安全設定路由
        Route::get('/security', [App\Http\Controllers\Admin\SettingsController::class, 'security'])
             ->name('security')
             ->middleware('can:settings.security');
        
        // 外觀設定路由
        Route::get('/appearance', [App\Http\Controllers\Admin\SettingsController::class, 'appearance'])
             ->name('appearance')
             ->middleware('can:system.settings');
        
        // 通知設定路由
        Route::get('/notifications', [App\Http\Controllers\Admin\SettingsController::class, 'notifications'])
             ->name('notifications')
             ->middleware('can:system.settings');

        // 整合設定路由
        Route::get('/integration', [App\Http\Controllers\Admin\SettingsController::class, 'integration'])
             ->name('integration')
             ->middleware('can:system.settings');

        // 維護設定路由
        Route::get('/maintenance', [App\Http\Controllers\Admin\SettingsController::class, 'maintenance'])
             ->name('maintenance')
             ->middleware('can:system.settings');
        
        // 設定備份管理路由
        Route::get('/backups', [App\Http\Controllers\Admin\SettingsController::class, 'backups'])
             ->name('backups')
             ->middleware('can:system.settings');
        
        // 設定變更歷史路由
        Route::get('/history', [App\Http\Controllers\Admin\SettingsController::class, 'history'])
             ->name('history')
             ->middleware('can:system.settings');

        // API 路由群組
        Route::prefix('api')->name('api.')->group(function () {
            // 設定查詢 API
            Route::get('/all', [App\Http\Controllers\Admin\SettingsController::class, 'getAllSettings'])
                 ->name('all')
                 ->middleware('can:system.settings');
            
            Route::get('/{key}', [App\Http\Controllers\Admin\SettingsController::class, 'getSetting'])
                 ->name('get')
                 ->middleware('can:system.settings')
                 ->where('key', '.*');
            
            // 設定更新 API
            Route::put('/{key}', [App\Http\Controllers\Admin\SettingsController::class, 'updateSetting'])
                 ->name('update')
                 ->middleware('can:system.settings')
                 ->where('key', '.*');
            
            Route::post('/batch-update', [App\Http\Controllers\Admin\SettingsController::class, 'batchUpdate'])
                 ->name('batch-update')
                 ->middleware('can:system.settings');
            
            // 設定重設 API
            Route::post('/{key}/reset', [App\Http\Controllers\Admin\SettingsController::class, 'resetSetting'])
                 ->name('reset')
                 ->middleware('can:system.settings')
                 ->where('key', '.*');
            
            // 連線測試 API
            Route::post('/test-connection', [App\Http\Controllers\Admin\SettingsController::class, 'testConnection'])
                 ->name('test-connection')
                 ->middleware('can:system.settings');
            
            // 匯入匯出 API
            Route::post('/export', [App\Http\Controllers\Admin\SettingsController::class, 'exportSettings'])
                 ->name('export')
                 ->middleware('can:system.settings');
            
            Route::post('/import', [App\Http\Controllers\Admin\SettingsController::class, 'importSettings'])
                 ->name('import')
                 ->middleware('can:system.settings');
            
            // 快取管理 API
            Route::post('/clear-cache', [App\Http\Controllers\Admin\SettingsController::class, 'clearCache'])
                 ->name('clear-cache')
                 ->middleware('can:system.settings');
        });
    });
    
    // 活動記錄路由群組
    Route::prefix('activities')->name('activities.')->middleware(['activity.access'])->group(function () {
        // 主要頁面路由
        Route::get('/', [App\Http\Controllers\Admin\ActivityController::class, 'index'])
             ->name('index')
             ->middleware('can:activity_logs.view');
        
        Route::get('/{id}', [App\Http\Controllers\Admin\ActivityController::class, 'show'])
             ->name('show')
             ->where('id', '[0-9]+')
             ->middleware('can:activity_logs.view');
        
        // 功能頁面路由
        Route::get('/security', [App\Http\Controllers\Admin\ActivityController::class, 'security'])
             ->name('security')
             ->middleware('can:system.logs');
        
        Route::get('/stats', [App\Http\Controllers\Admin\ActivityController::class, 'stats'])
             ->name('stats')
             ->middleware('can:system.logs');
        
        Route::get('/monitor', [App\Http\Controllers\Admin\ActivityController::class, 'monitor'])
             ->name('monitor')
             ->middleware('can:system.logs');
        
        Route::get('/export', [App\Http\Controllers\Admin\ActivityController::class, 'export'])
             ->name('export')
             ->middleware('can:activity_logs.export');
        
        Route::get('/custom-report', [App\Http\Controllers\Admin\ActivityController::class, 'customReport'])
             ->name('custom-report')
             ->middleware('can:activity_logs.export');
        
        // 檔案下載路由
        Route::get('/download-export/{filename}', [App\Http\Controllers\Admin\ActivityController::class, 'downloadExport'])
             ->name('download-export')
             ->where('filename', '[a-zA-Z0-9_\-\.]+')
             ->middleware('can:activity_logs.export');
        
        // AJAX API 路由
        Route::post('/search', [App\Http\Controllers\Admin\ActivityController::class, 'search'])
             ->name('search')
             ->middleware('can:activity_logs.view');
        
        Route::post('/bulk-action', [App\Http\Controllers\Admin\ActivityController::class, 'bulkAction'])
             ->name('bulk-action')
             ->middleware('can:activity_logs.delete');
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

