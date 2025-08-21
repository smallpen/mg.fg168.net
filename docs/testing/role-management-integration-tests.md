# 角色管理整合測試指南

## 概述

本文檔說明如何執行和維護角色管理系統的整合測試。整合測試確保所有元件正確協作，包括 Livewire 元件、服務層、資料存取層和使用者介面。

## 測試架構

### 測試層次

```
┌─────────────────────────────────────────┐
│            整合測試架構                  │
├─────────────────────────────────────────┤
│  瀏覽器測試 (Browser Tests)              │
│  ├── UI 互動測試                        │
│  ├── JavaScript 功能測試                │
│  └── 端到端流程測試                     │
├─────────────────────────────────────────┤
│  API 整合測試 (API Integration)          │
│  ├── REST API 端點測試                  │
│  ├── Livewire 元件測試                  │
│  └── 資料驗證測試                       │
├─────────────────────────────────────────┤
│  服務整合測試 (Service Integration)      │
│  ├── 業務邏輯測試                       │
│  ├── 資料存取測試                       │
│  └── 快取機制測試                       │
├─────────────────────────────────────────┤
│  效能測試 (Performance Tests)            │
│  ├── 負載測試                          │
│  ├── 記憶體使用測試                     │
│  └── 查詢效能測試                       │
└─────────────────────────────────────────┘
```

## 快速開始

### 環境需求

- PHP 8.2+
- Laravel 10+
- Docker 和 Docker Compose
- Chrome 瀏覽器（用於瀏覽器測試）
- 至少 2GB 可用記憶體

### 安裝和設定

1. **安裝依賴**
   ```bash
   composer install
   npm install && npm run build
   ```

2. **設定測試環境**
   ```bash
   cp .env.testing.integration .env
   php artisan key:generate
   ```

3. **準備資料庫**
   ```bash
   php artisan migrate:fresh --force
   ```

### 執行測試

#### 使用測試腳本（推薦）

```bash
# 執行所有測試
./scripts/run-integration-tests.sh

# 執行特定類型的測試
./scripts/run-integration-tests.sh -t integration
./scripts/run-integration-tests.sh -t performance
./scripts/run-integration-tests.sh -t browser

# 生成覆蓋率報告
./scripts/run-integration-tests.sh -t integration -c

# 詳細輸出
./scripts/run-integration-tests.sh -t integration -v
```

#### 使用 Docker Compose

```bash
# 整合測試
docker-compose exec app php artisan test --testsuite="Role Management Integration"

# 效能測試
docker-compose exec app php artisan test --testsuite="Role Management Performance"

# 瀏覽器測試
docker-compose exec app php artisan dusk --testsuite="Role Management Browser"
```

#### 使用 PHPUnit 配置

```bash
# 使用自訂配置檔案
./vendor/bin/phpunit -c phpunit-integration.xml

# 執行特定測試群組
./vendor/bin/phpunit --group=role-management
./vendor/bin/phpunit --group=integration
```

## 測試類型詳解

### 1. 整合測試 (Integration Tests)

測試不同元件之間的協作，確保資料流和業務邏輯正確。

**位置**: `tests/Integration/RoleManagement/`

**主要測試案例**:
- 完整的角色 CRUD 流程
- 權限繼承機制
- 角色層級管理
- 批量操作功能

**執行方式**:
```bash
php artisan test tests/Integration/RoleManagement/
```

### 2. 效能測試 (Performance Tests)

驗證系統在不同負載下的效能表現。

**位置**: `tests/Performance/RoleManagement/`

**主要測試案例**:
- 大量資料載入效能
- 權限繼承計算效能
- 記憶體使用量測試
- 並發操作測試

**執行方式**:
```bash
php artisan test tests/Performance/RoleManagement/
```

**效能指標**:
- 頁面載入時間 < 2 秒
- 記憶體使用量 < 50MB
- 權限計算時間 < 0.5 秒

### 3. 瀏覽器測試 (Browser Tests)

使用真實瀏覽器測試使用者介面和互動功能。

**位置**: `tests/Browser/RoleManagement/`

**主要測試案例**:
- 角色列表頁面互動
- 權限矩陣操作
- 表單驗證和提交
- JavaScript 功能測試

**執行方式**:
```bash
php artisan dusk tests/Browser/RoleManagement/
```

### 4. API 測試 (API Tests)

測試 REST API 端點和 JSON 回應。

**位置**: `tests/Integration/RoleManagement/Api/`

**主要測試案例**:
- API 端點回應格式
- 輸入驗證
- 錯誤處理
- 認證和授權

## 測試資料管理

### 測試資料工廠

使用 `RoleTestFactory` 建立測試資料：

```php
// 建立基本角色
$role = Role::factory()->create();

// 建立具有權限的角色
$role = Role::factory()->withPermissions(5)->create();

// 建立角色層級結構
$role = Role::factory()->hierarchy(3)->create();

// 建立管理員角色
$role = Role::factory()->admin()->create();
```

### 測試輔助方法

使用 `RoleManagementTestHelpers` Trait：

```php
// 建立管理員使用者
$admin = $this->createAdminUser();

// 建立角色層級結構
$roles = $this->createRoleHierarchy(3);

// 驗證權限繼承
$this->assertPermissionInheritance($childRole, $parentRole);

// 測量執行時間
$metrics = $this->measureExecutionTime(function() {
    // 測試程式碼
});
```

## 測試配置

### 環境變數

測試環境使用 `.env.testing.integration` 檔案：

```env
# 資料庫配置
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# 快取配置
CACHE_DRIVER=array
QUEUE_CONNECTION=sync

# 效能測試配置
PERFORMANCE_MAX_EXECUTION_TIME=2.0
PERFORMANCE_MAX_MEMORY_USAGE=52428800
```

### PHPUnit 配置

`phpunit-integration.xml` 包含：

- 測試套件定義
- 覆蓋率設定
- 測試群組配置
- 日誌輸出設定

## 持續整合 (CI/CD)

### GitHub Actions

`.github/workflows/role-management-integration-tests.yml` 提供：

- 多 PHP 版本測試
- 並行測試執行
- 覆蓋率報告上傳
- 測試結果通知

### 本地 CI 模擬

```bash
# 模擬 CI 環境
export CI=true
./scripts/run-integration-tests.sh -t all -c
```

## 故障排除

### 常見問題

#### 1. 記憶體不足錯誤

**症狀**: `Fatal error: Allowed memory size exhausted`

**解決方案**:
```bash
# 增加 PHP 記憶體限制
php -d memory_limit=512M artisan test
```

#### 2. 資料庫連線錯誤

**症狀**: `SQLSTATE[HY000] [2002] Connection refused`

**解決方案**:
```bash
# 檢查 Docker 服務
docker-compose ps
docker-compose up -d mysql

# 重新執行遷移
php artisan migrate:fresh --force
```

#### 3. 瀏覽器測試失敗

**症狀**: `Chrome driver not found`

**解決方案**:
```bash
# 安裝 Chrome Driver
php artisan dusk:chrome-driver

# 檢查 Chrome 是否安裝
google-chrome --version
```

#### 4. 權限錯誤

**症狀**: `Permission denied`

**解決方案**:
```bash
# 修正檔案權限
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 除錯技巧

#### 1. 啟用詳細輸出

```bash
php artisan test --verbose
```

#### 2. 執行單一測試

```bash
php artisan test tests/Integration/RoleManagement/RoleManagementFlowTest.php::test_complete_role_creation_flow
```

#### 3. 使用除錯器

```php
// 在測試中添加斷點
\Debugbar::info('Debug point reached');
dd($variable);
```

#### 4. 檢查測試日誌

```bash
tail -f storage/logs/laravel.log
```

## 效能監控

### 效能指標

監控以下關鍵指標：

- **執行時間**: 每個測試的執行時間
- **記憶體使用**: 峰值記憶體使用量
- **查詢數量**: SQL 查詢執行次數
- **快取命中率**: 快取使用效率

### 效能報告

測試完成後查看效能報告：

```bash
# 查看覆蓋率報告
open coverage/integration/index.html

# 查看測試摘要
cat reports/test-summary.md
```

## 最佳實踐

### 測試撰寫

1. **使用描述性的測試名稱**
   ```php
   public function test_admin_can_create_role_with_permissions(): void
   ```

2. **遵循 AAA 模式**
   ```php
   // Arrange - 準備測試資料
   $admin = $this->createAdminUser();
   
   // Act - 執行測試動作
   $response = $this->actingAs($admin)->post('/admin/roles', $data);
   
   // Assert - 驗證結果
   $response->assertStatus(201);
   ```

3. **使用測試輔助方法**
   ```php
   $this->assertPermissionInheritance($child, $parent);
   $this->assertDatabaseConsistency();
   ```

### 測試維護

1. **定期更新測試資料**
2. **清理過時的測試案例**
3. **監控測試執行時間**
4. **保持測試環境與生產環境同步**

### 測試執行

1. **本地開發時執行快速測試**
   ```bash
   php artisan test --group=quick
   ```

2. **提交前執行完整測試**
   ```bash
   ./scripts/run-integration-tests.sh -t all
   ```

3. **定期執行效能測試**
   ```bash
   ./scripts/run-integration-tests.sh -t performance
   ```

## 參考資源

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [Livewire Testing Documentation](https://laravel-livewire.com/docs/testing)
- [Laravel Dusk Documentation](https://laravel.com/docs/dusk)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

## 支援

如果遇到測試相關問題，請：

1. 檢查本文檔的故障排除章節
2. 查看測試日誌檔案
3. 在專案 Issue 中回報問題
4. 聯繫開發團隊