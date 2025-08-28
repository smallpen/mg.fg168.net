# Livewire 表單重置批次處理工具

## 概述

本文檔說明如何使用 Livewire 表單重置批次處理和自動化修復工具。這些工具提供了強大的批次修復功能，支援優先級處理、進度監控和錯誤恢復機制。

## 核心元件

### 1. FixExecutor（修復執行器）
- **功能**：批次執行修復操作
- **特色**：支援任務佇列、並行處理、結果追蹤
- **位置**：`App\Services\LivewireFormReset\FixExecutor`

### 2. BatchProcessor（批次處理器）
- **功能**：按優先級分批處理元件
- **特色**：進度監控、失敗重試、錯誤恢復
- **位置**：`App\Services\LivewireFormReset\BatchProcessor`

### 3. ProgressMonitor（進度監控器）
- **功能**：即時追蹤修復進度、生成詳細報告
- **特色**：效能分析、資源監控、建議生成
- **位置**：`App\Services\LivewireFormReset\ProgressMonitor`

## 使用方式

### 命令列介面

#### 基本用法
```bash
# 批次修復所有元件
docker-compose exec app php artisan livewire:fix-form-reset

# 乾跑模式（不實際修改檔案）
docker-compose exec app php artisan livewire:fix-form-reset --dry-run

# 啟用進度監控和詳細報告
docker-compose exec app php artisan livewire:fix-form-reset --monitor --report
```

#### 優先級處理
```bash
# 按優先級處理所有元件
docker-compose exec app php artisan livewire:fix-form-reset --mode=priority

# 只處理高優先級元件
docker-compose exec app php artisan livewire:fix-form-reset --mode=priority --priority=high

# 並行處理
docker-compose exec app php artisan livewire:fix-form-reset --mode=priority --parallel --max-parallel=5
```

#### 單一元件修復
```bash
# 修復特定元件
docker-compose exec app php artisan livewire:fix-form-reset --mode=single --component=UserList
```

#### 進階選項
```bash
# 自訂批次大小和延遲
docker-compose exec app php artisan livewire:fix-form-reset \
  --batch-size=15 \
  --delay=5 \
  --retry=3 \
  --output-format=json
```

### 程式化使用

#### 使用 FixExecutor
```php
use App\Services\LivewireFormReset\FixExecutor;

$executor = new FixExecutor();

// 執行完整修復
$result = $executor->executeFullFix([
    'execution_mode' => 'parallel',
    'batch_size' => 10,
    'max_parallel' => 3,
]);

// 修復單一元件
$componentInfo = [...]; // 元件資訊
$result = $executor->executeSingleFix($componentInfo);

// 取得修復報告
$report = $executor->generateFixReport();
```

#### 使用 BatchProcessor
```php
use App\Services\LivewireFormReset\BatchProcessor;

$processor = new BatchProcessor();

// 按優先級處理
$result = $processor->processByPriority([
    'pause_on_error' => false,
    'batch_delay' => 10,
    'enable_notifications' => true,
]);

// 取得進度監控
$progress = $processor->getProgressMonitoring();

// 處理重試佇列
$retryResult = $processor->processRetryQueue();
```

#### 使用 ProgressMonitor
```php
use App\Services\LivewireFormReset\ProgressMonitor;

$monitor = new ProgressMonitor();

// 開始監控會話
$sessionId = $monitor->startMonitoringSession([
    'total_components' => 50,
]);

// 更新進度
$monitor->updateProgress([
    'processed_components' => 10,
    'current_component' => 'UserList',
    'stage' => 'processing',
]);

// 記錄元件結果
$monitor->recordComponentResult([
    'component' => 'UserList',
    'status' => 'success',
    'execution_time' => 1250,
]);

// 結束會話並取得報告
$report = $monitor->endMonitoringSession();
```

## 配置選項

### 優先級配置
```php
'very_high' => [
    'min_score' => 8.0,
    'max_score' => 10.0,
    'batch_size' => 5,
    'max_parallel' => 2,
    'retry_attempts' => 3,
    'retry_delay' => 30,
],
'high' => [
    'min_score' => 6.0,
    'max_score' => 8.0,
    'batch_size' => 8,
    'max_parallel' => 3,
    'retry_attempts' => 2,
    'retry_delay' => 60,
],
// ... 其他優先級
```

### 處理選項
```php
'pause_on_error' => false,           // 錯誤時是否暫停
'max_consecutive_failures' => 5,     // 最大連續失敗次數
'batch_delay' => 10,                 // 批次間延遲（秒）
'enable_notifications' => true,      // 啟用通知
'save_progress' => true,             // 儲存進度
```

### 報告配置
```php
'auto_save' => true,                 // 自動儲存報告
'save_interval' => 300,              // 儲存間隔（秒）
'snapshot_interval' => 60,           // 快照間隔（秒）
'max_snapshots' => 100,              // 最大快照數
'report_formats' => ['json', 'html', 'csv'],
```

## 監控和報告

### 即時進度追蹤
- 處理進度百分比
- 當前處理元件
- 估算完成時間
- 成功率統計
- 錯誤率監控

### 效能指標
- 記憶體使用情況
- 執行時間分析
- 吞吐量統計
- 資源使用率
- 瓶頸識別

### 詳細報告
- 執行摘要
- 進度分析
- 效能分析
- 錯誤分析
- 資源分析
- 優化建議

## 錯誤處理和恢復

### 自動重試機制
- 按優先級設定重試次數
- 指數退避延遲
- 失敗原因分析
- 自動恢復嘗試

### 錯誤模式識別
- 語法錯誤
- 權限錯誤
- 檔案不存在
- 未知錯誤

### 恢復策略
- 語法驗證和修復
- 檔案權限修復
- 遺失檔案重建
- 人工介入建議

## 最佳實踐

### 執行前準備
1. **備份重要檔案**
   ```bash
   # 建立備份
   cp -r app/Livewire app/Livewire.backup
   cp -r resources/views/livewire resources/views/livewire.backup
   ```

2. **使用乾跑模式測試**
   ```bash
   docker-compose exec app php artisan livewire:fix-form-reset --dry-run --report
   ```

3. **檢查系統資源**
   - 確保有足夠的記憶體
   - 檢查磁碟空間
   - 驗證檔案權限

### 執行期間監控
1. **啟用進度監控**
   ```bash
   docker-compose exec app php artisan livewire:fix-form-reset --monitor
   ```

2. **監控系統資源**
   - 記憶體使用情況
   - CPU 使用率
   - 磁碟 I/O

3. **檢查錯誤日誌**
   ```bash
   tail -f storage/logs/laravel.log
   ```

### 執行後驗證
1. **執行測試**
   ```bash
   docker-compose exec app php artisan test
   ```

2. **檢查修復結果**
   - 驗證表單重置功能
   - 測試前端同步
   - 確認無語法錯誤

3. **效能驗證**
   - 測量頁面載入時間
   - 檢查記憶體使用
   - 驗證響應速度

## 故障排除

### 常見問題

#### 1. 記憶體不足
```bash
# 增加 PHP 記憶體限制
docker-compose exec app php -d memory_limit=512M artisan livewire:fix-form-reset
```

#### 2. 權限錯誤
```bash
# 修復檔案權限
docker-compose exec app chown -R www-data:www-data storage/
docker-compose exec app chmod -R 755 storage/
```

#### 3. 佇列處理失敗
```bash
# 重啟佇列工作程序
docker-compose exec app php artisan queue:restart
```

#### 4. 修復驗證失敗
```bash
# 檢查語法錯誤
docker-compose exec app php -l app/Livewire/ComponentName.php
```

### 除錯技巧

#### 1. 啟用詳細日誌
```php
// 在 .env 中設定
LOG_LEVEL=debug
```

#### 2. 使用除錯模式
```bash
docker-compose exec app php artisan livewire:fix-form-reset --mode=single --component=TestComponent --dry-run
```

#### 3. 檢查修復報告
```bash
# 生成詳細報告
docker-compose exec app php artisan livewire:fix-form-reset --report --output-format=json
```

## 效能優化

### 批次大小調整
- 小批次：更好的錯誤隔離，但較慢
- 大批次：更快的處理速度，但錯誤影響範圍大
- 建議：根據系統資源和元件複雜度調整

### 並行處理
- 啟用並行處理可顯著提升速度
- 注意系統資源限制
- 監控記憶體和 CPU 使用情況

### 資源管理
- 定期清理臨時檔案
- 監控磁碟空間使用
- 適當設定快取策略

## 總結

Livewire 表單重置批次處理工具提供了強大而靈活的修復功能，支援：

- ✅ 批次自動化修復
- ✅ 優先級處理
- ✅ 即時進度監控
- ✅ 詳細效能分析
- ✅ 自動錯誤恢復
- ✅ 豐富的配置選項
- ✅ 完整的測試覆蓋

通過合理使用這些工具，可以大幅提升 Livewire 表單重置功能的修復效率和品質。