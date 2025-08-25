<?php

/**
 * 主題切換多語系功能測試
 * 
 * 測試主題切換按鈕在不同語言下的顯示是否正確
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\App;

// 建立 Laravel 應用實例
$app = new Application(realpath(__DIR__));

// 設定基本路徑
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

// 啟動應用
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== 主題切換多語系功能測試 ===\n\n";

// 測試正體中文翻譯
echo "1. 測試正體中文翻譯:\n";
App::setLocale('zh_TW');

$translations = [
    'theme.light' => __('theme.light'),
    'theme.dark' => __('theme.dark'),
    'theme.auto' => __('theme.auto'),
    'theme.toggle' => __('theme.toggle'),
    'auth.theme.light' => __('auth.theme.light'),
    'auth.theme.dark' => __('auth.theme.dark'),
    'auth.theme.toggle' => __('auth.theme.toggle'),
];

foreach ($translations as $key => $value) {
    echo "   {$key}: {$value}\n";
}

echo "\n";

// 測試英文翻譯
echo "2. 測試英文翻譯:\n";
App::setLocale('en');

foreach ($translations as $key => $value) {
    $englishValue = __($key);
    echo "   {$key}: {$englishValue}\n";
}

echo "\n";

// 檢查語言檔案是否存在
echo "3. 檢查語言檔案:\n";

$languageFiles = [
    'lang/zh_TW/theme.php',
    'lang/en/theme.php',
    'lang/zh_TW/auth.php',
    'lang/en/auth.php',
];

foreach ($languageFiles as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $status = $exists ? '✓ 存在' : '✗ 不存在';
    echo "   {$file}: {$status}\n";
}

echo "\n";

// 檢查主題相關的翻譯鍵是否完整
echo "4. 檢查翻譯鍵完整性:\n";

$requiredKeys = [
    'theme' => ['light', 'dark', 'auto', 'toggle', 'title'],
    'auth.theme' => ['light', 'dark', 'toggle'],
];

$locales = ['zh_TW', 'en'];

foreach ($locales as $locale) {
    echo "   語言: {$locale}\n";
    App::setLocale($locale);
    
    foreach ($requiredKeys as $prefix => $keys) {
        foreach ($keys as $key) {
            $fullKey = "{$prefix}.{$key}";
            $translation = __($fullKey);
            $missing = $translation === $fullKey;
            $status = $missing ? '✗ 缺少' : '✓ 存在';
            echo "     {$fullKey}: {$status} ({$translation})\n";
        }
    }
    echo "\n";
}

echo "=== 測試完成 ===\n";