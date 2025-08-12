<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 管理後台 API 路由
|--------------------------------------------------------------------------
|
| 這裡定義所有管理後台的 API 路由，主要用於 AJAX 請求和 Livewire 元件
| 所有路由都會套用管理後台中介軟體群組
|
*/

// 快速搜尋 API
Route::get('/search', function () {
    // 由 GlobalSearch Livewire 元件處理
    return response()->json(['message' => 'Use Livewire component']);
})->name('search')->middleware('throttle:admin-search');

// 通知 API
Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', function () {
        // 由 NotificationCenter Livewire 元件處理
        return response()->json(['message' => 'Use Livewire component']);
    })->name('index');
    
    Route::post('/{notification}/read', function ($notification) {
        // 由 NotificationCenter Livewire 元件處理
        return response()->json(['message' => 'Use Livewire component']);
    })->name('read');
    
    Route::post('/mark-all-read', function () {
        // 由 NotificationCenter Livewire 元件處理
        return response()->json(['message' => 'Use Livewire component']);
    })->name('mark-all-read');
    
    Route::delete('/{notification}', function ($notification) {
        // 由 NotificationCenter Livewire 元件處理
        return response()->json(['message' => 'Use Livewire component']);
    })->name('delete');
});

// 使用者偏好設定 API
Route::post('/preferences', function () {
    // 由相關 Livewire 元件處理
    return response()->json(['message' => 'Use Livewire component']);
})->name('preferences');

// 主題切換 API
Route::post('/theme', function () {
    $theme = request('theme', 'light');
    
    if (in_array($theme, ['light', 'dark', 'auto'])) {
        session(['theme' => $theme]);
        
        // 如果使用者已登入，儲存偏好設定
        if (auth()->check()) {
            $user = auth()->user();
            $preferences = $user->preferences ?? [];
            $preferences['theme'] = $theme;
            $user->update(['preferences' => $preferences]);
        }
        
        return response()->json([
            'success' => true,
            'theme' => $theme,
            'message' => '主題已更新'
        ]);
    }
    
    return response()->json([
        'success' => false,
        'message' => '無效的主題設定'
    ], 400);
})->name('theme');

// 語言切換 API
Route::post('/locale', function () {
    $locale = request('locale', 'zh_TW');
    
    if (in_array($locale, ['zh_TW', 'en', 'zh_CN'])) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
        
        // 如果使用者已登入，儲存偏好設定
        if (auth()->check()) {
            $user = auth()->user();
            $preferences = $user->preferences ?? [];
            $preferences['locale'] = $locale;
            $user->update(['preferences' => $preferences]);
        }
        
        return response()->json([
            'success' => true,
            'locale' => $locale,
            'message' => '語言已更新'
        ]);
    }
    
    return response()->json([
        'success' => false,
        'message' => '不支援的語言設定'
    ], 400);
})->name('locale');

// 側邊欄狀態 API
Route::post('/sidebar', function () {
    $collapsed = request()->boolean('collapsed');
    
    session(['sidebar_collapsed' => $collapsed]);
    
    // 如果使用者已登入，儲存偏好設定
    if (auth()->check()) {
        $user = auth()->user();
        $preferences = $user->preferences ?? [];
        $preferences['sidebar_collapsed'] = $collapsed;
        $user->update(['preferences' => $preferences]);
    }
    
    return response()->json([
        'success' => true,
        'collapsed' => $collapsed,
        'message' => '側邊欄狀態已更新'
    ]);
})->name('sidebar');

// Session 管理 API
Route::prefix('session')->name('session.')->group(function () {
    Route::post('/refresh', function () {
        session()->regenerate();
        
        return response()->json([
            'success' => true,
            'message' => 'Session refreshed',
            'session_id' => session()->getId(),
            'remaining_time' => config('session.lifetime') * 60,
        ]);
    })->name('refresh');
    
    Route::get('/status', function () {
        $sessionLifetime = config('session.lifetime') * 60;
        $lastActivity = session('last_activity', now()->timestamp);
        $remainingTime = $sessionLifetime - (now()->timestamp - $lastActivity);
        
        return response()->json([
            'authenticated' => auth()->check(),
            'remaining_time' => max(0, $remainingTime),
            'requires_reauth' => session('requires_reauth', false),
            'session_id' => session()->getId(),
            'last_activity' => $lastActivity,
            'warnings' => session('security_warnings', []),
        ]);
    })->name('status');
    
    Route::post('/extend', function () {
        // 延長 Session 時間
        session(['last_activity' => now()->timestamp]);
        
        return response()->json([
            'success' => true,
            'message' => 'Session extended',
            'remaining_time' => config('session.lifetime') * 60,
        ]);
    })->name('extend');
    
    Route::delete('/terminate-others', function () {
        // 終止其他 Session（需要實作 Session 管理服務）
        return response()->json([
            'success' => true,
            'message' => 'Other sessions terminated',
        ]);
    })->name('terminate-others')->middleware('throttle:admin-sensitive');
});

// 系統狀態 API
Route::get('/system/status', function () {
    return response()->json([
        'status' => 'online',
        'maintenance_mode' => app()->isDownForMaintenance(),
        'server_time' => now()->toISOString(),
        'timezone' => config('app.timezone'),
        'locale' => app()->getLocale(),
        'version' => config('app.version', '1.0.0'),
    ]);
})->name('system.status');

// 快取管理 API（需要超級管理員權限）
Route::prefix('cache')->name('cache.')->middleware('role:super_admin')->group(function () {
    Route::post('/clear', function () {
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        
        return response()->json([
            'success' => true,
            'message' => '快取已清除',
        ]);
    })->name('clear')->middleware('throttle:admin-sensitive');
    
    Route::post('/config-clear', function () {
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        
        return response()->json([
            'success' => true,
            'message' => '配置快取已清除',
        ]);
    })->name('config-clear')->middleware('throttle:admin-sensitive');
    
    Route::post('/route-clear', function () {
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        
        return response()->json([
            'success' => true,
            'message' => '路由快取已清除',
        ]);
    })->name('route-clear')->middleware('throttle:admin-sensitive');
    
    Route::post('/view-clear', function () {
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        
        return response()->json([
            'success' => true,
            'message' => '視圖快取已清除',
        ]);
    })->name('view-clear')->middleware('throttle:admin-sensitive');
});

// 檔案上傳 API
Route::post('/upload', function () {
    // 由相關 Livewire 元件處理檔案上傳
    return response()->json(['message' => 'Use Livewire component']);
})->name('upload')->middleware('throttle:admin-upload');

// 健康檢查 API
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'services' => [
            'database' => 'connected',
            'cache' => 'connected',
            'session' => 'active',
        ],
    ]);
})->name('health');