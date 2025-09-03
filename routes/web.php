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

// 測試 PDF 匯出功能（開發用）
if (app()->environment(['local', 'development'])) {
    require __DIR__.'/test-pdf.php';
}
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

// 測試路由
Route::get('/test-styles', function () {
    return view('test-styles');
});

// 語言選擇器測試路由
Route::get('/test-language-selector', function () {
    return view('test-language-selector');
})->name('test.language-selector');

// 簡單語言測試路由
Route::get('/test-simple-language', function () {
    return view('test-simple-language');
})->name('test.simple-language');

// 語言切換測試路由（使用 URL 參數）
Route::get('/test-lang-switch', function () {
    $locale = request('locale', app()->getLocale());
    if (in_array($locale, ['zh_TW', 'en'])) {
        app()->setLocale($locale);
        session(['locale' => $locale]);
    }
    
    return response()->json([
        'current_locale' => app()->getLocale(),
        'session_locale' => session('locale'),
        'supported_locales' => ['zh_TW' => '正體中文', 'en' => 'English'],
        'message' => 'Language switched successfully'
    ]);
})->name('test.lang-switch');

// 焦點管理器測試路由
Route::get('/test-focus', function () {
    return view('test-focus');
})->name('test.focus');

// 焦點管理器範例路由
Route::get('/focus-example', function () {
    return view('admin.components.focus-example');
})->name('focus.example');

// 多語言支援展示頁面
Route::get('/demo/multi-language', function () {
    return view('demo.multi-language');
})->name('demo.multi-language')->middleware(['web', App\Http\Middleware\SetLocale::class]);

// 管理後台認證路由（未登入時可存取）
Route::prefix('admin')->name('admin.')->middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('admin.auth.login');
    })->name('login');
    
    Route::post('/login', function () {
        // 這個路由實際上不會被使用，因為 Livewire 會處理登入邏輯
        // 但保留它以防需要回退到傳統表單提交
        return redirect()->route('admin.login');
    });
});

// 管理後台登出路由（支援 GET 和 POST 請求）
Route::match(['get', 'post'], '/admin/logout', function () {
    // 記錄登出日誌
    if (auth()->check()) {
        logger()->info('管理員登出', [
            'user_id' => auth()->id(),
            'username' => auth()->user()->username,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
    
    // 執行登出
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    
    return redirect()->route('admin.login')->with('success', '您已成功登出');
})->name('admin.logout');

// 載入管理後台路由檔案
require __DIR__.'/admin.php';