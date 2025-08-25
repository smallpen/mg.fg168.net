# 多語系系統故障排除指南

## 概述

本指南提供多語系系統常見問題的診斷方法和解決方案，幫助開發者和管理員快速定位和解決多語系相關問題。

## 常見問題分類

### 1. 語言切換問題

#### 問題 1.1：語言切換後頁面未更新
**症狀：**
- 點擊語言選擇器後頁面沒有變化
- URL 參數改變但內容未更新
- 部分內容更新但部分內容保持原語言

**可能原因：**
- JavaScript 錯誤阻止頁面重新載入
- 快取問題導致舊內容顯示
- 中介軟體未正確處理語言設定

**診斷步驟：**
```bash
# 1. 檢查瀏覽器控制台錯誤
# 開啟瀏覽器開發者工具，查看 Console 標籤

# 2. 檢查網路請求
# 查看 Network 標籤，確認語言切換請求是否成功

# 3. 檢查 Session 資料
docker-compose exec app php artisan tinker
>>> session()->all()
>>> app()->getLocale()
```

**解決方案：**
```bash
# 1. 清除所有快取
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear

# 2. 檢查 SetLocale 中介軟體
# 確認 app/Http/Kernel.php 中已註冊中介軟體

# 3. 重新啟動服務
docker-compose restart app
```

#### 問題 1.2：語言選擇器無法點擊
**症狀：**
- 語言選擇器按鈕無反應
- 下拉選單無法開啟
- 鍵盤導航無效

**可能原因：**
- CSS 樣式衝突
- JavaScript 事件綁定失敗
- 元素被其他元素遮蓋

**診斷步驟：**
```javascript
// 在瀏覽器控制台執行
console.log(document.querySelector('.language-selector'));
console.log(getComputedStyle(document.querySelector('.language-selector')));
```

**解決方案：**
```css
/* 檢查 CSS 樣式 */
.language-selector {
    position: relative;
    z-index: 1000;
    pointer-events: auto;
}

.language-selector:hover,
.language-selector:focus {
    outline: 2px solid #007bff;
}
```

### 2. 翻譯顯示問題

#### 問題 2.1：顯示翻譯鍵而非翻譯內容
**症狀：**
- 頁面顯示 "users.title" 而非 "使用者管理"
- 部分翻譯正常，部分顯示鍵值
- 新增的翻譯鍵無法正確顯示

**可能原因：**
- 翻譯鍵在語言檔案中不存在
- 語言檔案語法錯誤
- 快取中的語言檔案過期

**診斷步驟：**
```bash
# 1. 檢查翻譯鍵是否存在
docker-compose exec app php artisan tinker
>>> __('users.title')
>>> trans('users.title')

# 2. 檢查語言檔案語法
docker-compose exec app php -l lang/zh_TW/users.php
docker-compose exec app php -l lang/en/users.php

# 3. 檢查語言檔案內容
docker-compose exec app cat lang/zh_TW/users.php | grep "title"
```

**解決方案：**
```bash
# 1. 新增缺少的翻譯鍵
# 編輯 lang/zh_TW/users.php 和 lang/en/users.php

# 2. 修復語法錯誤
# 檢查陣列語法、引號配對、逗號等

# 3. 清除語言快取
docker-compose exec app php artisan cache:clear

# 4. 驗證修復結果
docker-compose exec app php artisan lang:check
```

#### 問題 2.2：翻譯參數未正確替換
**症狀：**
- 顯示 "歡迎 :name" 而非 "歡迎 張三"
- 參數佔位符未被實際值替換
- 複數形式翻譯錯誤

**可能原因：**
- 參數名稱不匹配
- 翻譯函數使用錯誤
- 參數值為空或未定義

**診斷步驟：**
```php
// 在 Tinker 中測試
>>> __('messages.welcome', ['name' => '測試'])
>>> trans_choice('messages.items', 5, ['count' => 5])
```

**解決方案：**
```php
// 正確的參數使用方式
// 在語言檔案中
'welcome' => '歡迎 :name',
'items' => '{0} 沒有項目|{1} 一個項目|[2,*] :count 個項目',

// 在程式碼中
__('messages.welcome', ['name' => $user->name])
trans_choice('messages.items', $count, ['count' => $count])
```

### 3. 語言偏好儲存問題

#### 問題 3.1：語言偏好未持久化
**症狀：**
- 重新整理頁面後語言恢復預設
- 重新登入後語言偏好丟失
- 不同瀏覽器標籤頁語言不一致

**可能原因：**
- Session 配置問題
- 資料庫儲存失敗
- Cookie 設定錯誤

**診斷步驟：**
```bash
# 1. 檢查 Session 配置
docker-compose exec app cat config/session.php | grep driver

# 2. 檢查使用者語言偏好
docker-compose exec app php artisan tinker
>>> User::find(1)->locale
>>> session('locale')

# 3. 檢查資料庫連線
docker-compose exec app php artisan migrate:status
```

**解決方案：**
```bash
# 1. 檢查 Session 驅動設定
# 確認 .env 中 SESSION_DRIVER 設定正確

# 2. 檢查資料庫 Schema
# 確認 users 表有 locale 欄位

# 3. 重新執行遷移
docker-compose exec app php artisan migrate:fresh --seed
```

#### 問題 3.2：瀏覽器語言偵測錯誤
**症狀：**
- 首次訪問語言選擇錯誤
- 不支援的語言被選中
- 語言偵測邏輯失效

**可能原因：**
- Accept-Language 標頭解析錯誤
- 支援語言清單配置問題
- 預設語言設定錯誤

**診斷步驟：**
```bash
# 檢查瀏覽器語言標頭
curl -H "Accept-Language: zh-TW,zh;q=0.9,en;q=0.8" http://localhost/admin/login -v

# 檢查語言偵測邏輯
docker-compose exec app php artisan tinker
>>> request()->getPreferredLanguage(['zh_TW', 'en'])
```

**解決方案：**
```php
// 在 SetLocale 中介軟體中改進語言偵測
public function handle($request, Closure $next)
{
    $supportedLocales = ['zh_TW', 'en'];
    $defaultLocale = 'zh_TW';
    
    // 優先順序：URL參數 > 使用者偏好 > Session > 瀏覽器偏好 > 預設
    $locale = $request->get('lang') 
           ?? auth()->user()?->locale 
           ?? session('locale')
           ?? $request->getPreferredLanguage($supportedLocales)
           ?? $defaultLocale;
           
    if (in_array($locale, $supportedLocales)) {
        app()->setLocale($locale);
        session(['locale' => $locale]);
    }
    
    return $next($request);
}
```

### 4. 效能問題

#### 問題 4.1：語言切換響應緩慢
**症狀：**
- 語言切換需要數秒才生效
- 頁面載入時間明顯增加
- 大量語言檔案載入請求

**可能原因：**
- 語言檔案過大
- 快取機制未啟用
- 資料庫查詢過多

**診斷步驟：**
```bash
# 1. 檢查語言檔案大小
du -sh lang/zh_TW/*
du -sh lang/en/*

# 2. 檢查快取狀態
docker-compose exec app php artisan cache:table
docker-compose exec app php artisan config:cache --help

# 3. 分析查詢效能
# 啟用查詢日誌並檢查慢查詢
```

**解決方案：**
```bash
# 1. 啟用語言檔案快取
docker-compose exec app php artisan config:cache

# 2. 優化語言檔案結構
# 將大型語言檔案拆分為較小的模組

# 3. 使用 Redis 快取
# 在 .env 中設定 CACHE_DRIVER=redis
```

#### 問題 4.2：記憶體使用過高
**症狀：**
- 語言切換後記憶體使用激增
- 伺服器記憶體不足錯誤
- 頁面載入失敗

**可能原因：**
- 語言檔案未正確釋放
- 快取策略不當
- 記憶體洩漏

**診斷步驟：**
```bash
# 檢查記憶體使用
docker-compose exec app php -r "echo memory_get_usage(true) / 1024 / 1024 . ' MB';"

# 檢查語言檔案載入
docker-compose exec app php artisan tinker
>>> memory_get_usage(true)
>>> app('translator')->getLoader()->load('zh_TW', 'users')
>>> memory_get_usage(true)
```

**解決方案：**
```php
// 優化語言檔案載入
// 在 AppServiceProvider 中
public function boot()
{
    // 只載入當前語言的檔案
    $this->app->singleton('translator', function ($app) {
        $loader = $app['translation.loader'];
        $locale = $app['config']['app.locale'];
        
        $trans = new Translator($loader, $locale);
        $trans->setFallback($app['config']['app.fallback_locale']);
        
        return $trans;
    });
}
```

### 5. 測試相關問題

#### 問題 5.1：多語系測試失敗
**症狀：**
- 語言切換測試無法通過
- 翻譯內容斷言失敗
- 測試環境語言設定錯誤

**可能原因：**
- 測試資料庫語言檔案缺失
- 測試環境配置錯誤
- 測試案例編寫問題

**診斷步驟：**
```bash
# 1. 檢查測試環境配置
docker-compose exec app cat .env.testing | grep LOCALE

# 2. 執行特定測試
docker-compose exec app php artisan test tests/Feature/MultilingualTest.php --verbose

# 3. 檢查測試資料庫
docker-compose exec app php artisan tinker --env=testing
>>> app()->getLocale()
>>> __('users.title')
```

**解決方案：**
```php
// 在測試基類中設定語言環境
// tests/TestCase.php
protected function setUp(): void
{
    parent::setUp();
    
    // 設定測試語言環境
    app()->setLocale('zh_TW');
    
    // 清除語言快取
    app('translator')->setLoaded([]);
}

// 測試語言切換
public function test_language_switching()
{
    $response = $this->get('/admin/users?lang=en');
    $response->assertSee('User Management');
    
    $response = $this->get('/admin/users?lang=zh_TW');
    $response->assertSee('使用者管理');
}
```

#### 問題 5.2：Playwright 多語系測試不穩定
**症狀：**
- 測試結果不一致
- 語言切換操作失敗
- 元素定位錯誤

**可能原因：**
- 頁面載入時機問題
- 元素選擇器不穩定
- 非同步操作處理不當

**診斷步驟：**
```javascript
// 在 Playwright 測試中加入除錯
await page.screenshot({ path: 'debug-before-click.png' });
await page.click('.language-selector');
await page.waitForTimeout(1000);
await page.screenshot({ path: 'debug-after-click.png' });
```

**解決方案：**
```javascript
// 改進 Playwright 測試穩定性
async function switchLanguage(page, locale) {
    // 等待語言選擇器載入
    await page.waitForSelector('.language-selector', { state: 'visible' });
    
    // 點擊語言選擇器
    await page.click('.language-selector');
    
    // 等待下拉選單出現
    await page.waitForSelector(`[data-locale="${locale}"]`, { state: 'visible' });
    
    // 點擊目標語言
    await page.click(`[data-locale="${locale}"]`);
    
    // 等待頁面更新
    await page.waitForLoadState('networkidle');
    
    // 驗證語言切換成功
    const currentLocale = await page.getAttribute('html', 'lang');
    expect(currentLocale).toBe(locale);
}
```

## 診斷工具和命令

### 1. 語言檔案診斷

#### 完整性檢查
```bash
# 檢查所有語言檔案
docker-compose exec app php artisan lang:check

# 檢查特定語言
docker-compose exec app php artisan lang:check --locale=en

# 嚴格模式檢查
docker-compose exec app php artisan lang:check --strict
```

#### 語法檢查
```bash
# 檢查 PHP 語法
find lang/ -name "*.php" -exec php -l {} \;

# 檢查陣列結構
docker-compose exec app php -r "
foreach (glob('lang/zh_TW/*.php') as \$file) {
    \$content = include \$file;
    if (!is_array(\$content)) {
        echo 'Invalid array in: ' . \$file . PHP_EOL;
    }
}
"
```

### 2. 執行時診斷

#### 語言環境檢查
```bash
docker-compose exec app php artisan tinker
>>> app()->getLocale()
>>> app()->getFallbackLocale()
>>> config('app.locale')
>>> session('locale')
>>> auth()->user()?->locale
```

#### 翻譯載入檢查
```bash
>>> app('translator')->getLoader()->load('zh_TW', 'users')
>>> app('translator')->get('users.title')
>>> __('users.title')
>>> trans('users.title')
```

### 3. 日誌分析

#### 多語系錯誤日誌
```bash
# 查看多語系相關錯誤
docker-compose exec app grep "translation" storage/logs/laravel.log

# 查看缺少翻譯鍵的錯誤
docker-compose exec app grep "Missing translation" storage/logs/multilingual.log

# 即時監控日誌
docker-compose exec app tail -f storage/logs/multilingual.log
```

#### 效能分析
```bash
# 檢查語言檔案載入時間
docker-compose exec app php artisan tinker
>>> $start = microtime(true);
>>> app('translator')->getLoader()->load('zh_TW', 'users');
>>> echo (microtime(true) - $start) * 1000 . ' ms';
```

## 預防措施

### 1. 開發階段預防

#### 程式碼審核清單
- [ ] 所有顯示文字都使用翻譯函數
- [ ] 新增翻譯鍵同時更新兩種語言
- [ ] 翻譯參數使用正確
- [ ] 測試覆蓋多語系功能

#### 自動化檢查
```bash
# 在 CI/CD 中加入語言檔案檢查
docker-compose exec app php artisan lang:check --strict
if [ $? -ne 0 ]; then
    echo "Translation check failed"
    exit 1
fi
```

### 2. 部署階段預防

#### 部署前檢查
```bash
#!/bin/bash
# deploy-check.sh

echo "檢查語言檔案完整性..."
docker-compose exec app php artisan lang:check --strict

echo "執行多語系測試..."
docker-compose exec app php artisan test --testsuite=Multilingual

echo "檢查語言檔案語法..."
find lang/ -name "*.php" -exec php -l {} \;

echo "生成翻譯報告..."
docker-compose exec app php artisan lang:report --output=deployment-report.json
```

#### 部署後驗證
```bash
#!/bin/bash
# post-deploy-verify.sh

echo "清除語言快取..."
docker-compose exec app php artisan cache:clear

echo "測試語言切換功能..."
curl -s -H "Accept-Language: zh-TW" http://localhost/admin/login | grep -q "登入"
curl -s -H "Accept-Language: en" http://localhost/admin/login | grep -q "Login"

echo "檢查多語系日誌..."
docker-compose exec app tail -n 100 storage/logs/multilingual.log
```

### 3. 監控和維護

#### 定期檢查腳本
```bash
#!/bin/bash
# weekly-check.sh

echo "=== 每週多語系系統檢查 ==="
echo "日期: $(date)"

echo "1. 檢查語言檔案完整性"
docker-compose exec app php artisan lang:check

echo "2. 檢查未使用的翻譯鍵"
docker-compose exec app php artisan lang:unused

echo "3. 生成翻譯進度報告"
docker-compose exec app php artisan lang:progress

echo "4. 檢查多語系錯誤日誌"
error_count=$(docker-compose exec app grep -c "Missing translation" storage/logs/multilingual.log 2>/dev/null || echo "0")
echo "缺少翻譯鍵錯誤數量: $error_count"

if [ "$error_count" -gt "10" ]; then
    echo "⚠️  警告: 缺少翻譯鍵錯誤過多，需要處理"
fi

echo "=== 檢查完成 ==="
```

## 緊急處理程序

### 1. 語言功能完全失效

#### 緊急恢復步驟
```bash
# 1. 立即回滾到上一個穩定版本
git checkout HEAD~1 -- lang/

# 2. 清除所有快取
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear

# 3. 重新啟動服務
docker-compose restart app

# 4. 驗證基本功能
curl -s http://localhost/admin/login | grep -q "登入\|Login"
```

### 2. 部分翻譯顯示錯誤

#### 快速修復步驟
```bash
# 1. 識別問題翻譯鍵
docker-compose exec app grep -r "問題文字" lang/

# 2. 快速修復翻譯檔案
# 編輯相關語言檔案

# 3. 清除語言快取
docker-compose exec app php artisan cache:clear

# 4. 驗證修復結果
docker-compose exec app php artisan tinker
>>> __('問題.翻譯.鍵')
```

### 3. 效能嚴重下降

#### 緊急優化步驟
```bash
# 1. 啟用語言檔案快取
docker-compose exec app php artisan config:cache

# 2. 檢查並清理大型語言檔案
find lang/ -name "*.php" -size +100k

# 3. 重新啟動快取服務
docker-compose restart redis

# 4. 監控記憶體使用
docker stats --no-stream
```

這個故障排除指南提供了全面的問題診斷和解決方案，幫助維護多語系系統的穩定運行。