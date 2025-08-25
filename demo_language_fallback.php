<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\App;
use App\Services\LanguageFallbackHandler;

// 建立 Laravel 應用程式實例
$app = new Application(__DIR__);

// 設定基本配置
$app->singleton('config', function () {
    return new \Illuminate\Config\Repository([
        'app' => [
            'locale' => 'zh_TW',
            'fallback_locale' => 'en',
        ]
    ]);
});

// 註冊服務
$app->singleton(LanguageFallbackHandler::class);

echo "=== 語言回退機制示範 ===\n\n";

// 建立回退處理器實例
$fallbackHandler = new LanguageFallbackHandler();

echo "1. 基本翻譯功能測試\n";
echo "-------------------\n";

// 測試存在的翻譯鍵
$result1 = $fallbackHandler->translate('auth.login.title');
echo "翻譯 'auth.login.title': {$result1}\n";

// 測試不存在的翻譯鍵（會回退）
$result2 = $fallbackHandler->translate('nonexistent.key');
echo "翻譯 'nonexistent.key': {$result2}\n";

echo "\n2. 參數替換功能測試\n";
echo "-------------------\n";

// 測試參數替換
$result3 = $fallbackHandler->translate('Hello :name, welcome to :site!', [
    'name' => '張三',
    'site' => '我的網站'
]);
echo "參數替換結果: {$result3}\n";

echo "\n3. 回退鏈配置測試\n";
echo "-----------------\n";

// 顯示當前回退鏈
$chain = $fallbackHandler->getFallbackChain();
echo "當前回退鏈: " . implode(' -> ', $chain) . "\n";

// 設定自定義回退鏈
$fallbackHandler->setFallbackChain(['en', 'zh_TW']);
$newChain = $fallbackHandler->getFallbackChain();
echo "新的回退鏈: " . implode(' -> ', $newChain) . "\n";

echo "\n4. 翻譯狀態檢查\n";
echo "---------------\n";

// 檢查翻譯是否存在
$exists = $fallbackHandler->hasTranslation('auth.login.title');
echo "翻譯 'auth.login.title' 是否存在: " . ($exists ? '是' : '否') . "\n";

$notExists = $fallbackHandler->hasTranslation('definitely.not.exist');
echo "翻譯 'definitely.not.exist' 是否存在: " . ($notExists ? '是' : '否') . "\n";

// 取得翻譯狀態
$status = $fallbackHandler->getTranslationStatus('auth.login.title');
echo "翻譯狀態:\n";
foreach ($status as $locale => $exists) {
    echo "  {$locale}: " . ($exists ? '存在' : '不存在') . "\n";
}

echo "\n5. 統計資訊\n";
echo "----------\n";

$stats = $fallbackHandler->getFallbackStatistics();
echo "回退鏈: " . implode(' -> ', $stats['fallback_chain']) . "\n";
echo "預設語言: {$stats['default_locale']}\n";
echo "快取時間: {$stats['cache_time']} 秒\n";
echo "日誌記錄: " . ($stats['logging_enabled'] ? '啟用' : '停用') . "\n";

echo "\n6. 全域輔助函數測試\n";
echo "------------------\n";

// 注意：在這個示範中，全域函數可能無法正常工作，因為沒有完整的 Laravel 環境
echo "注意：全域輔助函數需要完整的 Laravel 環境才能正常運作\n";
echo "在實際應用中，您可以使用以下函數：\n";
echo "- trans_fallback('key', ['param' => 'value'])\n";
echo "- __f('key', ['param' => 'value'])\n";
echo "- has_trans_fallback('key')\n";
echo "- trans_status('key')\n";

echo "\n=== 示範完成 ===\n";