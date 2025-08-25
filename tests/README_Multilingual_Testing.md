# 多語系測試基礎設施使用指南

## 概述

本專案提供了完整的多語系測試基礎設施，包括基礎測試類別、輔助方法和測試資料管理工具，用於確保多語系功能的正確性和完整性。

## 核心元件

### 1. MultilingualTestCase 基礎測試類別

`MultilingualTestCase` 是所有多語系測試的基礎類別，提供以下功能：

- 語言切換和管理
- 翻譯內容驗證
- 語言檔案完整性檢查
- 測試資料設定

#### 基本使用方法

```php
use Tests\MultilingualTestCase;

class MyMultilingualTest extends MultilingualTestCase
{
    public function test_my_multilingual_feature(): void
    {
        // 切換到英文環境
        $this->setTestLocale('en');
        
        // 驗證翻譯內容
        $this->assertTranslation('my.translation.key', 'Expected English Text');
        
        // 在所有語言中執行測試
        $results = $this->runInAllLocales(function () {
            return __('my.translation.key');
        });
        
        // 驗證翻譯在所有語言中都存在
        $this->assertTranslationExistsInAllLocales('my.translation.key');
    }
}
```

### 2. MultilingualTestHelpers Trait

提供語言切換和多語系功能測試的輔助方法：

```php
use Tests\Traits\MultilingualTestHelpers;

class MyFeatureTest extends TestCase
{
    use MultilingualTestHelpers;
    
    public function test_page_language_switching(): void
    {
        // 測試頁面語言切換
        $this->assertPageLanguageSwitching('/admin/users', [
            'zh_TW' => ['使用者管理', '建立使用者'],
            'en' => ['User Management', 'Create User']
        ]);
        
        // 測試表單語言切換
        $this->assertFormLanguageSwitching('/admin/users/create', [
            'name' => 'name_field',
            'email' => 'email_field'
        ], [
            'zh_TW' => ['name_field' => '姓名', 'email_field' => '電子郵件'],
            'en' => ['name_field' => 'Name', 'email_field' => 'Email']
        ]);
    }
}
```

### 3. MultilingualTestData Trait

提供測試資料建立和管理功能：

```php
use Tests\Traits\MultilingualTestData;

class MyDataTest extends TestCase
{
    use MultilingualTestData;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試用的語言檔案
        $this->createTestLanguageFiles();
    }
    
    protected function tearDown(): void
    {
        // 清理測試語言檔案
        $this->cleanupTestLanguageFiles();
        
        parent::tearDown();
    }
}
```

## 主要功能

### 語言切換測試

#### 1. 基本語言切換

```php
// 設定測試語言
$this->setTestLocale('en');
$this->assertEquals('en', $this->getCurrentLocale());

// 在指定語言環境中執行測試
$this->withLocale('zh_TW', function () {
    $this->assertEquals('儲存', __('common.save'));
});
```

#### 2. 語言切換功能測試

```php
// 測試 URL 參數語言切換
$this->switchLanguage('en', '/admin/dashboard');

// 驗證語言切換功能
$this->assertLanguageSwitching('/admin/users', 'en');

// 驗證語言持久化
$this->assertLanguagePersistence('en');
```

### 翻譯內容驗證

#### 1. 翻譯存在性檢查

```php
// 檢查翻譯鍵是否存在
$this->assertTrue($this->translationExists('common.save', 'zh_TW'));

// 驗證翻譯在所有語言中都存在
$this->assertTranslationExistsInAllLocales('common.save');
```

#### 2. 翻譯內容驗證

```php
// 驗證翻譯內容
$this->assertTranslation('common.save', '儲存', [], 'zh_TW');
$this->assertTranslation('common.save', 'Save', [], 'en');

// 驗證翻譯內容在不同語言中是不同的
$this->assertTranslationDiffersAcrossLocales('common.save');
```

### 語言檔案完整性檢查

#### 1. 語言檔案完整性驗證

```php
// 檢查語言檔案完整性
$this->assertLanguageFileCompleteness('common');

// 產生完整性報告
$report = $this->generateLanguageCompletenessReport();
```

#### 2. 語言檔案統計

```php
// 取得語言檔案統計資訊
$stats = $this->getLanguageFileStats();

// 驗證統計資訊
$this->assertGreaterThan(0, $stats['zh_TW']['files']);
$this->assertGreaterThan(0, $stats['zh_TW']['keys']);
```

### 頁面多語系測試

#### 1. 頁面內容測試

```php
// 測試頁面語言切換
$this->assertPageLanguageSwitching('/admin/users', [
    'zh_TW' => ['使用者管理', '建立', '編輯', '刪除'],
    'en' => ['User Management', 'Create', 'Edit', 'Delete']
]);
```

#### 2. 表單多語系測試

```php
// 測試表單語言切換
$this->assertFormLanguageSwitching('/admin/users/create', [
    'input[name="name"]' => 'name_label',
    'input[name="email"]' => 'email_label'
], [
    'zh_TW' => ['name_label' => '姓名', 'email_label' => '電子郵件'],
    'en' => ['name_label' => 'Name', 'email_label' => 'Email']
]);
```

#### 3. 錯誤訊息測試

```php
// 測試錯誤訊息語言切換
$this->assertErrorMessageLanguageSwitching('/admin/users', [
    'name' => '',
    'email' => 'invalid-email'
], [
    'zh_TW' => ['姓名欄位為必填', '電子郵件格式不正確'],
    'en' => ['The name field is required', 'The email format is invalid']
]);
```

### 使用者語言偏好測試

#### 1. 使用者建立

```php
// 建立具有指定語言偏好的使用者
$user = $this->createUserWithLocale('en');

// 建立多個不同語言偏好的使用者
$users = $this->createUsersWithDifferentLocales(['zh_TW', 'en']);
```

#### 2. 語言偏好測試

```php
// 測試使用者語言偏好
$user = $this->createUserWithLocale('en');
$this->actingAs($user);

// 驗證語言偏好持久化
$this->assertLanguagePreferencePersistence('en');
```

### 瀏覽器語言偵測測試

```php
// 測試瀏覽器語言偵測
$this->assertBrowserLanguageDetection('en-US,en;q=0.9', 'en');
$this->assertBrowserLanguageDetection('zh-TW,zh;q=0.9', 'zh_TW');
```

## 測試資料管理

### 建立測試語言檔案

```php
// 建立所有測試語言檔案
$this->createTestLanguageFiles();

// 建立特定的測試語言檔案
$this->createTestLanguageFiles('my_test_file');

// 建立不完整的語言檔案（用於測試完整性檢查）
$this->createIncompleteLanguageFiles();
```

### 清理測試資料

```php
// 清理所有測試語言檔案
$this->cleanupTestLanguageFiles();

// 清理特定的測試語言檔案
$this->cleanupTestLanguageFiles('my_test_file');
```

### 預定義測試資料

```php
// 取得測試表單資料
$formData = $this->getTestFormData();

// 取得測試導航資料
$navigationData = $this->getTestNavigationData();

// 取得測試錯誤訊息
$errorMessages = $this->getTestErrorMessages();
```

## 效能測試

### 語言切換效能測試

```php
public function test_language_switching_performance(): void
{
    $startTime = microtime(true);
    
    for ($i = 0; $i < 100; $i++) {
        $locale = $i % 2 === 0 ? 'zh_TW' : 'en';
        $this->setTestLocale($locale);
        __('common.save');
    }
    
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    
    $this->assertLessThan(1.0, $executionTime, '語言切換效能過慢');
}
```

### 記憶體使用測試

```php
public function test_memory_usage(): void
{
    $initialMemory = memory_get_usage(true);
    
    // 執行多語系操作
    $this->runInAllLocales(function () {
        for ($i = 0; $i < 100; $i++) {
            __("test_translations.key_{$i}");
        }
    });
    
    $finalMemory = memory_get_usage(true);
    $memoryIncrease = $finalMemory - $initialMemory;
    
    $this->assertLessThan(5 * 1024 * 1024, $memoryIncrease, '記憶體使用過多');
}
```

## 報告生成

### 語言檔案完整性報告

```php
$report = $this->generateLanguageCompletenessReport();

// 報告結構
$report = [
    'summary' => [
        'en' => [
            'total_files' => 10,
            'total_missing_keys' => 5,
            'total_extra_keys' => 2,
            'files_with_issues' => 3
        ]
    ],
    'missing_keys' => [
        'en' => [
            'common' => ['key1', 'key2'],
            'messages' => ['key3']
        ]
    ],
    'extra_keys' => [
        'en' => [
            'common' => ['extra_key1']
        ]
    ],
    'files' => [
        'common' => [
            'base_keys_count' => 20,
            'locales' => [
                'en' => [
                    'keys_count' => 18,
                    'missing_count' => 2,
                    'extra_count' => 0,
                    'completeness' => 90.0
                ]
            ]
        ]
    ]
];
```

### 測試結果報告

```php
$testResults = [
    'test1' => ['status' => 'passed', 'locales' => ['zh_TW', 'en']],
    'test2' => ['status' => 'failed', 'error' => 'Translation missing']
];

$report = $this->generateLanguageSwitchingReport($testResults);
```

## 最佳實踐

### 1. 測試組織

- 將多語系測試分組到專門的測試類別中
- 使用描述性的測試方法名稱
- 為每個功能建立獨立的測試方法

### 2. 測試資料管理

- 在 `setUp()` 中建立測試語言檔案
- 在 `tearDown()` 中清理測試資料
- 使用預定義的測試資料以確保一致性

### 3. 效能考量

- 避免在測試中進行不必要的語言切換
- 使用快取機制減少重複的語言檔案載入
- 定期檢查測試執行時間和記憶體使用

### 4. 錯誤處理

- 測試各種邊界條件和錯誤情況
- 驗證錯誤訊息的多語系支援
- 確保錯誤處理不會影響語言設定

### 5. 持續整合

- 在 CI/CD 流程中包含多語系測試
- 定期檢查語言檔案完整性
- 監控多語系功能的效能指標

## 範例測試

### 完整的多語系功能測試

```php
<?php

namespace Tests\Feature;

use Tests\MultilingualTestCase;
use Tests\Traits\MultilingualTestHelpers;
use Tests\Traits\MultilingualTestData;

class UserManagementMultilingualTest extends MultilingualTestCase
{
    use MultilingualTestHelpers, MultilingualTestData;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestLanguageFiles();
    }
    
    protected function tearDown(): void
    {
        $this->cleanupTestLanguageFiles();
        parent::tearDown();
    }
    
    public function test_user_list_page_multilingual_support(): void
    {
        // 測試頁面語言切換
        $this->assertPageLanguageSwitching('/admin/users', [
            'zh_TW' => ['使用者管理', '建立使用者', '編輯', '刪除'],
            'en' => ['User Management', 'Create User', 'Edit', 'Delete']
        ]);
        
        // 測試資料表格語言切換
        $this->assertDataTableLanguageSwitching('/admin/users', [
            'zh_TW' => ['使用者名稱', '姓名', '電子郵件', '狀態'],
            'en' => ['Username', 'Name', 'Email', 'Status']
        ], [
            'zh_TW' => ['檢視', '編輯', '刪除'],
            'en' => ['View', 'Edit', 'Delete']
        ]);
    }
    
    public function test_user_creation_form_multilingual_support(): void
    {
        // 測試表單語言切換
        $this->assertFormLanguageSwitching('/admin/users/create', [
            'input[name="name"]' => 'name_label',
            'input[name="email"]' => 'email_label'
        ], [
            'zh_TW' => ['name_label' => '姓名', 'email_label' => '電子郵件'],
            'en' => ['name_label' => 'Name', 'email_label' => 'Email']
        ]);
        
        // 測試驗證錯誤訊息
        $this->assertErrorMessageLanguageSwitching('/admin/users', [
            'name' => '',
            'email' => 'invalid-email'
        ], [
            'zh_TW' => ['姓名欄位為必填'],
            'en' => ['The name field is required']
        ]);
    }
}
```

這個多語系測試基礎設施提供了完整的工具集，讓開發者能夠輕鬆地測試和驗證多語系功能的正確性和完整性。