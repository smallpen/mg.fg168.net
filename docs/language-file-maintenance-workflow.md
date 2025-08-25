# 語言檔案維護流程文檔

## 概述

本文檔詳細說明語言檔案的維護流程，包括新增、修改、刪除翻譯內容的標準作業程序，以及品質保證和版本控制流程。

## 語言檔案結構

### 目錄結構
```
lang/
├── zh_TW/           # 正體中文翻譯檔案
│   ├── auth.php     # 認證相關翻譯
│   ├── common.php   # 通用翻譯
│   ├── dashboard.php # 儀表板翻譯
│   ├── users.php    # 使用者管理翻譯
│   ├── roles.php    # 角色管理翻譯
│   ├── permissions.php # 權限管理翻譯
│   ├── settings.php # 設定翻譯
│   ├── theme.php    # 主題翻譯
│   └── validation.php # 驗證訊息翻譯
└── en/              # 英文翻譯檔案
    ├── auth.php
    ├── common.php
    ├── dashboard.php
    ├── users.php
    ├── roles.php
    ├── permissions.php
    ├── settings.php
    ├── theme.php
    └── validation.php
```

### 檔案命名規範
- 使用小寫字母和底線
- 按功能模組分組：`users.php`, `roles.php`, `permissions.php`
- 通用翻譯使用 `common.php`
- 驗證訊息使用 `validation.php`

## 維護流程

### 1. 新增翻譯內容

#### 步驟 1：確定翻譯鍵結構
```php
// 良好的翻譯鍵結構範例
'users' => [
    'title' => '使用者管理',
    'actions' => [
        'create' => '建立使用者',
        'edit' => '編輯使用者',
        'delete' => '刪除使用者',
    ],
    'fields' => [
        'username' => '使用者名稱',
        'email' => '電子郵件',
        'name' => '姓名',
    ],
    'messages' => [
        'created' => '使用者建立成功',
        'updated' => '使用者更新成功',
        'deleted' => '使用者刪除成功',
    ],
],
```

#### 步驟 2：同時更新兩種語言
```bash
# 1. 編輯正體中文檔案
vim lang/zh_TW/users.php

# 2. 編輯對應的英文檔案
vim lang/en/users.php
```

#### 步驟 3：驗證翻譯完整性
```bash
# 執行語言檔案完整性檢查
docker-compose exec app php artisan lang:check

# 檢查特定檔案
docker-compose exec app php artisan lang:check --file=users
```

#### 步驟 4：測試翻譯顯示
```bash
# 清除快取
docker-compose exec app php artisan cache:clear

# 執行多語系測試
docker-compose exec app php artisan test --testsuite=Multilingual
```

### 2. 修改現有翻譯

#### 步驟 1：識別需要修改的翻譯鍵
```bash
# 搜尋特定翻譯鍵的使用位置
grep -r "users.title" resources/views/
grep -r "users.title" app/
```

#### 步驟 2：同步修改所有語言版本
```php
// 修改前 - lang/zh_TW/users.php
'title' => '用戶管理',

// 修改後 - lang/zh_TW/users.php  
'title' => '使用者管理',
```

```php
// 對應修改 - lang/en/users.php
'title' => 'User Management',
```

#### 步驟 3：更新相關文檔
- 更新 API 文檔中的翻譯範例
- 更新使用者手冊中的截圖
- 更新測試案例中的預期文字

### 3. 刪除過時翻譯

#### 步驟 1：確認翻譯鍵未被使用
```bash
# 搜尋翻譯鍵使用情況
grep -r "old.translation.key" app/
grep -r "old.translation.key" resources/
grep -r "old.translation.key" tests/
```

#### 步驟 2：從所有語言檔案中移除
```php
// 從 lang/zh_TW/example.php 中移除
// 'old_key' => '舊的翻譯',

// 從 lang/en/example.php 中移除  
// 'old_key' => 'Old Translation',
```

#### 步驟 3：清理相關測試
```php
// 移除測試中對舊翻譯鍵的引用
// $this->assertSee(__('example.old_key'));
```

## 品質保證流程

### 1. 翻譯品質檢查

#### 語言品質標準
- **正確性**：翻譯內容準確無誤
- **一致性**：術語使用統一
- **適切性**：符合目標語言文化
- **完整性**：所有翻譯鍵都有對應翻譯

#### 檢查清單
- [ ] 翻譯內容語法正確
- [ ] 專業術語使用一致
- [ ] 語調和風格統一
- [ ] 沒有硬編碼文字
- [ ] 參數替換正確
- [ ] 複數形式處理正確

### 2. 技術品質檢查

#### 自動化檢查
```bash
# 執行語言檔案語法檢查
docker-compose exec app php -l lang/zh_TW/*.php
docker-compose exec app php -l lang/en/*.php

# 執行翻譯完整性檢查
docker-compose exec app php artisan lang:check --strict

# 執行翻譯鍵使用情況檢查
docker-compose exec app php artisan lang:unused
```

#### 手動檢查
- 檢查陣列結構是否一致
- 確認翻譯鍵命名符合規範
- 驗證參數佔位符正確
- 測試特殊字符處理

### 3. 測試流程

#### 單元測試
```php
// tests/Unit/LanguageTest.php
public function test_translation_keys_exist()
{
    $zhKeys = $this->getTranslationKeys('zh_TW');
    $enKeys = $this->getTranslationKeys('en');
    
    $this->assertEquals($zhKeys, $enKeys, 'Translation keys mismatch');
}

public function test_translation_parameters()
{
    $translation = __('messages.welcome', ['name' => 'Test']);
    $this->assertStringContains('Test', $translation);
}
```

#### 整合測試
```php
// tests/Feature/MultilingualTest.php
public function test_language_switching()
{
    $this->get('/admin/users?lang=en')
         ->assertSee('User Management');
         
    $this->get('/admin/users?lang=zh_TW')
         ->assertSee('使用者管理');
}
```

#### 端到端測試
```bash
# 執行 Playwright 多語系測試
php execute-multilingual-login-tests.php
php run-comprehensive-multilingual-tests.php
```

## 版本控制流程

### 1. Git 工作流程

#### 分支策略
```bash
# 建立翻譯功能分支
git checkout -b feature/add-notification-translations

# 提交翻譯變更
git add lang/
git commit -m "Add notification module translations

- Add zh_TW translations for notification management
- Add en translations for notification management  
- Update common.php with notification-related terms"
```

#### 提交訊息規範
```
類型(範圍): 簡短描述

詳細說明翻譯變更內容
- 新增的翻譯模組
- 修改的翻譯內容
- 影響的功能範圍

範例：
feat(i18n): add user management translations
fix(i18n): correct role permission translations
docs(i18n): update translation maintenance guide
```

### 2. 程式碼審核

#### 審核重點
- [ ] 翻譯內容準確性
- [ ] 兩種語言版本一致性
- [ ] 翻譯鍵命名規範
- [ ] 檔案結構正確性
- [ ] 測試覆蓋完整性

#### 審核工具
```bash
# 使用 diff 比較翻譯檔案結構
diff <(php -r "print_r(include 'lang/zh_TW/users.php');") \
     <(php -r "print_r(include 'lang/en/users.php');")

# 檢查翻譯鍵一致性
docker-compose exec app php artisan lang:compare zh_TW en
```

### 3. 部署流程

#### 部署前檢查
```bash
# 1. 執行完整的語言檔案檢查
docker-compose exec app php artisan lang:check --all

# 2. 執行多語系測試套件
docker-compose exec app php artisan test --testsuite=Multilingual

# 3. 生成翻譯報告
docker-compose exec app php artisan lang:report --output=deployment-report.json
```

#### 部署後驗證
```bash
# 1. 清除語言快取
docker-compose exec app php artisan cache:clear

# 2. 驗證語言切換功能
curl -H "Accept-Language: zh-TW" http://localhost/admin/login
curl -H "Accept-Language: en" http://localhost/admin/login

# 3. 檢查錯誤日誌
docker-compose exec app tail -f storage/logs/multilingual.log
```

## 自動化工具

### 1. 語言檔案管理命令

#### 檢查命令
```bash
# 檢查所有語言檔案完整性
php artisan lang:check

# 檢查特定語言檔案
php artisan lang:check --locale=en --file=users

# 嚴格模式檢查（包含未使用的翻譯鍵）
php artisan lang:check --strict
```

#### 同步命令
```bash
# 同步語言檔案結構
php artisan lang:sync

# 從主語言同步到其他語言
php artisan lang:sync --from=zh_TW --to=en

# 只同步缺少的翻譯鍵
php artisan lang:sync --missing-only
```

#### 報告命令
```bash
# 生成翻譯完整性報告
php artisan lang:report

# 生成 JSON 格式報告
php artisan lang:report --format=json --output=translation-report.json

# 生成翻譯進度報告
php artisan lang:progress
```

### 2. 持續整合腳本

#### GitHub Actions 範例
```yaml
# .github/workflows/translation-check.yml
name: Translation Check

on: [push, pull_request]

jobs:
  translation-check:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          
      - name: Install dependencies
        run: composer install
        
      - name: Check translation completeness
        run: php artisan lang:check --strict
        
      - name: Run multilingual tests
        run: php artisan test --testsuite=Multilingual
```

#### 預提交鉤子
```bash
#!/bin/sh
# .git/hooks/pre-commit

# 檢查語言檔案語法
php -l lang/zh_TW/*.php
php -l lang/en/*.php

# 檢查翻譯完整性
php artisan lang:check --strict

# 如果檢查失敗，阻止提交
if [ $? -ne 0 ]; then
    echo "Translation check failed. Please fix the issues before committing."
    exit 1
fi
```

## 監控和維護

### 1. 日常監控

#### 監控指標
- 缺少翻譯鍵的錯誤數量
- 語言切換失敗率
- 語言檔案載入時間
- 使用者語言偏好分佈

#### 監控工具
```bash
# 檢查多語系錯誤日誌
grep "Missing translation" storage/logs/multilingual.log | wc -l

# 分析語言使用統計
docker-compose exec app php artisan tinker
>>> DB::table('users')->select('locale', DB::raw('count(*) as count'))->groupBy('locale')->get()
```

### 2. 定期維護

#### 每週維護任務
- [ ] 檢查新增功能的翻譯完整性
- [ ] 審核使用者回饋的翻譯問題
- [ ] 更新翻譯進度報告
- [ ] 清理未使用的翻譯鍵

#### 每月維護任務
- [ ] 全面檢查翻譯品質
- [ ] 更新翻譯風格指南
- [ ] 分析語言使用統計
- [ ] 優化語言檔案效能

#### 每季維護任務
- [ ] 審核翻譯術語一致性
- [ ] 更新多語系文檔
- [ ] 評估新語言支援需求
- [ ] 進行翻譯品質審核

## 最佳實踐總結

### 開發階段
1. **同步開發**：新功能開發時同步建立翻譯
2. **測試驅動**：先寫翻譯測試再實作功能
3. **程式碼審核**：翻譯變更必須經過審核
4. **文檔更新**：及時更新相關文檔

### 維護階段
1. **定期檢查**：建立定期檢查機制
2. **自動化工具**：使用自動化工具提高效率
3. **品質監控**：持續監控翻譯品質
4. **使用者回饋**：收集和處理使用者回饋

### 團隊協作
1. **角色分工**：明確翻譯維護責任
2. **溝通機制**：建立有效的溝通管道
3. **知識分享**：定期分享最佳實踐
4. **培訓計畫**：為團隊成員提供培訓

這個維護流程確保了語言檔案的品質和一致性，為多語系系統的穩定運行提供了保障。