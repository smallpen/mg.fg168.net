# 快取事件記錄優化指南

## 問題描述

在活動記錄系統中，每次瀏覽活動列表頁面都會觸發快取查詢，進而記錄 `cache_hit` 事件。這導致活動日誌被大量的快取事件淹沒，影響真正重要事件的可見性。

### 問題現象
- 每次瀏覽活動列表就會產生一次 `系統事件: cache_hit` 記錄
- 活動日誌中充滿了重複的快取事件
- 重要的使用者操作和安全事件被快取事件掩蓋
- 影響活動日誌的可讀性和分析價值

## 根本原因

1. **快取事件監聽器過於敏感**：`SystemEventListener` 監聽所有快取事件
2. **缺乏頻率限制**：同一個快取鍵的事件會重複記錄
3. **預設啟用**：在除錯模式下自動啟用快取事件記錄
4. **範圍過廣**：包含了活動記錄相關的快取鍵

## 解決方案

### 1. 配置化快取事件記錄

在 `config/activity-log.php` 中新增系統事件記錄設定：

```php
'system_events' => [
    // 是否記錄快取事件（預設關閉，因為太頻繁）
    'log_cache_events' => env('ACTIVITY_LOG_CACHE_EVENTS', false),
    
    // 快取事件記錄節流時間（秒）
    'cache_event_throttle' => env('ACTIVITY_CACHE_EVENT_THROTTLE', 300),
    
    // 其他系統事件設定...
],
```

### 2. 更新服務提供者

修改 `ActivityLogServiceProvider` 使快取事件記錄可配置：

```php
// 只在除錯模式且明確啟用快取事件記錄時才註冊
if (config('app.debug') && config('activity-log.system_events.log_cache_events', false)) {
    // 註冊快取事件監聽器...
}
```

### 3. 實作頻率限制

在 `SystemEventListener` 中新增 `shouldLogCacheEvent` 方法：

```php
protected function shouldLogCacheEvent(string $key, string $type): bool
{
    $throttleKey = "cache_event_throttle:{$key}:{$type}";
    $throttleWindow = config('activity-log.system_events.cache_event_throttle', 300);
    
    if (cache()->has($throttleKey)) {
        return false;
    }
    
    cache()->put($throttleKey, true, $throttleWindow);
    return true;
}
```

### 4. 縮小重要快取鍵範圍

移除活動記錄相關的快取鍵模式：

```php
protected function isImportantCacheKey(string $key): bool
{
    $importantPatterns = [
        'user_permissions_',
        'role_permissions_',
        'system_settings_',
        // 移除 'activity_log_' 模式
    ];
    // ...
}
```

## 配置選項

### 環境變數設定

在 `.env` 檔案中可以設定以下變數：

```env
# 是否記錄快取事件（預設關閉）
ACTIVITY_LOG_CACHE_EVENTS=false

# 快取事件節流時間（秒，預設5分鐘）
ACTIVITY_CACHE_EVENT_THROTTLE=300

# 是否記錄資料庫查詢事件
ACTIVITY_LOG_QUERY_EVENTS=false

# 慢查詢閾值（毫秒）
ACTIVITY_SLOW_QUERY_THRESHOLD=1000
```

### 啟用快取事件記錄

如果需要監控快取效能，可以臨時啟用：

```env
# 啟用快取事件記錄（僅用於除錯）
ACTIVITY_LOG_CACHE_EVENTS=true

# 設定較長的節流時間以減少記錄頻率
ACTIVITY_CACHE_EVENT_THROTTLE=600  # 10分鐘
```

## 使用建議

### 1. 生產環境
- **建議關閉**快取事件記錄 (`ACTIVITY_LOG_CACHE_EVENTS=false`)
- 專注於記錄使用者操作和安全事件
- 使用專門的效能監控工具來追蹤快取效能

### 2. 開發環境
- 可以選擇性啟用快取事件記錄進行除錯
- 設定適當的節流時間避免日誌過載
- 定期清理測試產生的活動記錄

### 3. 效能監控
- 使用 `ActivityCacheService` 的統計功能監控快取效能
- 透過 `getCacheStats()` 方法取得快取命中率等指標
- 設定專門的監控儀表板而非依賴活動日誌

## 測試驗證

### 1. 驗證快取事件記錄已關閉

```bash
# 檢查配置
php artisan config:show activity-log.system_events.log_cache_events

# 瀏覽活動列表頁面，確認不再產生 cache_hit 事件
```

### 2. 驗證節流機制

```bash
# 臨時啟用快取事件記錄
ACTIVITY_LOG_CACHE_EVENTS=true php artisan config:cache

# 多次瀏覽同一頁面，確認在節流時間內不會重複記錄
```

### 3. 驗證重要事件仍正常記錄

```bash
# 執行使用者操作，確認重要事件仍正常記錄
# 例如：登入、建立使用者、變更權限等
```

## 效能影響

### 優化前
- 每次頁面載入產生多個快取事件記錄
- 資料庫寫入頻繁
- 活動日誌表快速增長
- 查詢效能下降

### 優化後
- 大幅減少不必要的活動記錄
- 降低資料庫寫入壓力
- 提升活動日誌的可讀性
- 改善整體系統效能

## 監控建議

### 1. 活動記錄統計
```php
// 監控活動記錄的類型分佈
Activity::selectRaw('type, COUNT(*) as count')
    ->groupBy('type')
    ->orderBy('count', 'desc')
    ->get();
```

### 2. 快取效能監控
```php
// 使用專門的快取統計
$cacheService = app(ActivityCacheService::class);
$stats = $cacheService->getCacheStats();
```

### 3. 系統效能指標
- 監控活動記錄表的增長速度
- 追蹤資料庫寫入 QPS
- 觀察頁面載入時間的改善

## 總結

通過實作配置化的快取事件記錄、頻率限制和範圍縮小，我們成功解決了快取事件記錄過於頻繁的問題。這個優化：

1. **提升使用者體驗**：活動日誌更清晰，重要事件更容易發現
2. **改善系統效能**：減少不必要的資料庫寫入
3. **增強可維護性**：提供靈活的配置選項
4. **保持功能完整性**：在需要時仍可啟用快取事件記錄

建議在生產環境中保持快取事件記錄關閉，並使用專門的監控工具來追蹤系統效能。