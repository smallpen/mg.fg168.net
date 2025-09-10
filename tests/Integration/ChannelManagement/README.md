# 通路管理系統整合測試

## 概述

本目錄包含通路管理系統的完整整合測試套件，涵蓋所有需求的驗證和業務流程測試。

## 測試結構

### 測試檔案組織

```
tests/Integration/ChannelManagement/
├── README.md                              # 本說明文件
├── ChannelManagementTestSuite.php         # 基礎設定和環境測試
├── AgentManagementIntegrationTest.php     # 代理管理完整流程測試
├── PointManagementIntegrationTest.php     # 點數管理完整流程測試
├── PlayerManagementIntegrationTest.php    # 玩家管理完整流程測試
├── ChannelManagementIntegrationTest.php   # 綜合業務流程測試
└── run-channel-management-tests.php       # 測試執行腳本
```

### 測試覆蓋範圍

#### 1. 代理管理測試 (AgentManagementIntegrationTest)
- **需求覆蓋**: 需求 1, 2, 3, 11, 12, 13, 17
- **測試內容**:
  - 完整的代理建立流程
  - 前置符號系統完整性
  - 點數管理完整流程
  - 代理刪除和點數回收
  - 代理帳號更新和前置符號同步
  - 多層級代理結構的完整性
  - 點數稽核和一致性檢查
  - 併發點數操作的資料一致性

#### 2. 點數管理測試 (PointManagementIntegrationTest)
- **需求覆蓋**: 需求 11, 12, 13, 14, 15, 16, 17
- **測試內容**:
  - 代理點數分配完整流程
  - 代理點數回收完整流程
  - 玩家點數管理完整流程
  - 玩家點數回收完整流程
  - 點數限制與業務控制
  - 複雜的多層級點數分配和回收
  - 點數稽核和資料完整性
  - 併發點數操作的資料一致性

#### 3. 玩家管理測試 (PlayerManagementIntegrationTest)
- **需求覆蓋**: 需求 6, 7, 8, 14, 15, 16, 17
- **測試內容**:
  - 完整的玩家建立流程
  - 玩家代理關聯和帳號管理
  - 玩家點數管理和限制
  - 玩家刪除和點數回收
  - 玩家帳號更新和驗證
  - 多層級代理結構中的玩家管理
  - 玩家資料完整性和驗證
  - 批次玩家操作

#### 4. 綜合業務流程測試 (ChannelManagementIntegrationTest)
- **需求覆蓋**: 需求 1-17 (所有需求)
- **測試內容**:
  - 完整的通路管理業務流程
  - 多層級代理結構建立和管理
  - 權限控制和資料存取限制
  - 業務規則和限制驗證
  - 資料完整性和一致性驗證
  - 複雜業務場景測試
  - 異常情況處理
  - 效能和記憶體使用測試

#### 5. 基礎設定測試 (ChannelManagementTestSuite)
- **測試內容**:
  - 資料庫連接和基本設定
  - 基本權限和角色設定
  - 模型關聯驗證
  - 服務類別可用性
  - 例外類別定義
  - 資料庫約束和索引
  - 效能基準測試

## 執行測試

### 方法一：使用測試執行腳本（推薦）

```bash
# 在專案根目錄執行
php tests/Integration/ChannelManagement/run-channel-management-tests.php

# 或在 Docker 環境中執行
docker-compose exec app php tests/Integration/ChannelManagement/run-channel-management-tests.php
```

### 方法二：使用 PHPUnit 直接執行

```bash
# 執行所有通路管理整合測試
docker-compose exec app php vendor/bin/phpunit tests/Integration/ChannelManagement/

# 執行特定測試類別
docker-compose exec app php vendor/bin/phpunit tests/Integration/ChannelManagement/AgentManagementIntegrationTest.php

# 執行特定測試方法
docker-compose exec app php vendor/bin/phpunit --filter test_complete_agent_creation_workflow tests/Integration/ChannelManagement/AgentManagementIntegrationTest.php

# 生成測試覆蓋率報告
docker-compose exec app php vendor/bin/phpunit --coverage-html storage/coverage tests/Integration/ChannelManagement/
```

### 方法三：執行單一測試

```bash
# 執行基礎設定測試
docker-compose exec app php vendor/bin/phpunit tests/Integration/ChannelManagement/ChannelManagementTestSuite.php

# 執行代理管理測試
docker-compose exec app php vendor/bin/phpunit tests/Integration/ChannelManagement/AgentManagementIntegrationTest.php

# 執行點數管理測試
docker-compose exec app php vendor/bin/phpunit tests/Integration/ChannelManagement/PointManagementIntegrationTest.php

# 執行玩家管理測試
docker-compose exec app php vendor/bin/phpunit tests/Integration/ChannelManagement/PlayerManagementIntegrationTest.php

# 執行綜合業務流程測試
docker-compose exec app php vendor/bin/phpunit tests/Integration/ChannelManagement/ChannelManagementIntegrationTest.php
```

## 測試前準備

### 1. 環境設定

確保以下環境已正確設定：

```bash
# 啟動 Docker 服務
docker-compose up -d

# 確認資料庫連接正常
docker-compose exec app php artisan migrate:status

# 執行資料庫遷移（如需要）
docker-compose exec app php artisan migrate:fresh

# 執行基礎 Seeder
docker-compose exec app php artisan db:seed --class=PermissionSeeder
docker-compose exec app php artisan db:seed --class=RoleSeeder
docker-compose exec app php artisan db:seed --class=UserSeeder
```

### 2. 依賴檢查

確保以下組件已正確實作：

- **模型類別**: `Agent`, `Player`, `PointTransaction`
- **服務類別**: `AgentService`, `PlayerService`, `PointService`
- **例外類別**: `InsufficientPointsException`, `InvalidPrefixException`, `PrefixAlreadyExistsException`, `AgentHasDependenciesException`
- **資料庫遷移**: agents, players, point_transactions 資料表
- **權限系統**: 基本權限和角色設定

## 測試報告

### 執行結果

測試執行腳本會生成詳細的測試報告，包括：

- 測試執行摘要
- 各測試類別的執行結果
- 失敗測試的錯誤詳情
- 執行時間統計
- 改進建議

### 日誌檔案

測試日誌會儲存在 `storage/logs/channel-management-tests-{timestamp}.log`

### 覆蓋率報告

使用 `--coverage-html` 選項可生成 HTML 格式的覆蓋率報告：

```bash
docker-compose exec app php vendor/bin/phpunit --coverage-html storage/coverage tests/Integration/ChannelManagement/
```

報告會儲存在 `storage/coverage/` 目錄中。

## 測試資料管理

### 資料隔離

每個測試類別使用 `RefreshDatabase` trait 確保資料隔離：

- 每個測試方法執行前會重置資料庫
- 測試資料不會影響其他測試
- 使用 Factory 生成一致的測試資料

### 測試資料清理

測試完成後會自動清理：

- 資料庫資料會自動回滾
- 臨時檔案會被清除
- 快取會被清理

## 故障排除

### 常見問題

#### 1. 資料庫連接失敗
```bash
# 檢查 Docker 服務狀態
docker-compose ps

# 檢查資料庫連接
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

#### 2. 模型或服務類別不存在
```bash
# 檢查類別是否存在
docker-compose exec app php artisan tinker
>>> class_exists(\App\Models\Agent::class);
>>> class_exists(\App\Services\AgentService::class);
```

#### 3. 權限或角色缺失
```bash
# 重新執行 Seeder
docker-compose exec app php artisan db:seed --class=PermissionSeeder
docker-compose exec app php artisan db:seed --class=RoleSeeder
```

#### 4. 測試執行超時
```bash
# 增加 PHP 執行時間限制
# 在 php.ini 中設定 max_execution_time = 300
```

### 除錯技巧

#### 1. 啟用詳細輸出
```bash
docker-compose exec app php vendor/bin/phpunit --testdox --verbose tests/Integration/ChannelManagement/
```

#### 2. 執行特定測試方法
```bash
docker-compose exec app php vendor/bin/phpunit --filter test_method_name tests/Integration/ChannelManagement/TestClass.php
```

#### 3. 停止在第一個失敗
```bash
docker-compose exec app php vendor/bin/phpunit --stop-on-failure tests/Integration/ChannelManagement/
```

#### 4. 檢查測試資料
在測試方法中加入除錯程式碼：
```php
// 檢查資料庫狀態
dump(Agent::count());
dump(Player::count());
dump(PointTransaction::count());

// 檢查特定記錄
dump(Agent::find(1)->toArray());
```

## 效能考量

### 測試執行時間

- 單一測試類別: 通常 10-30 秒
- 完整測試套件: 通常 2-5 分鐘
- 大量資料測試: 可能需要更長時間

### 記憶體使用

- 基本測試: 約 50-100MB
- 大量資料測試: 約 200-500MB
- 如果記憶體不足，考慮分批執行測試

### 最佳化建議

1. **並行執行**: 使用 `--parallel` 選項（需要額外設定）
2. **選擇性執行**: 只執行相關的測試類別
3. **資料庫最佳化**: 使用記憶體資料庫進行測試
4. **快取清理**: 定期清理測試快取

## 持續整合

### CI/CD 整合

在 CI/CD 管道中整合這些測試：

```yaml
# .github/workflows/tests.yml 範例
- name: Run Channel Management Integration Tests
  run: |
    docker-compose exec -T app php tests/Integration/ChannelManagement/run-channel-management-tests.php
```

### 測試排程

建議定期執行完整的整合測試：

- 每次程式碼提交後
- 每日自動執行
- 部署前必須執行

## 維護指南

### 新增測試

1. 在對應的測試類別中新增測試方法
2. 遵循現有的命名慣例
3. 確保測試資料隔離
4. 更新本 README 文件

### 更新測試

1. 當業務需求變更時，更新對應測試
2. 確保測試仍然覆蓋所有需求
3. 更新測試文檔和註解

### 效能監控

1. 定期檢查測試執行時間
2. 監控記憶體使用量
3. 最佳化慢速測試

## 相關文檔

- [通路管理需求文件](../../../.kiro/specs/channel-management/requirements.md)
- [通路管理設計文件](../../../.kiro/specs/channel-management/design.md)
- [通路管理任務清單](../../../.kiro/specs/channel-management/tasks.md)
- [Laravel 測試文檔](https://laravel.com/docs/testing)
- [PHPUnit 文檔](https://phpunit.de/documentation.html)

## 聯絡資訊

如有測試相關問題，請聯絡開發團隊或查看專案文檔。