# Livewire 開發規範和程式碼審查清單

## 概述

本文件定義了 Livewire 元件開發的標準規範和程式碼審查清單，確保所有開發人員遵循一致的開發標準，特別是針對表單重置功能的實作。

## 新 Livewire 元件開發檢查清單

### 📋 開發前準備

- [ ] **需求分析**
  - [ ] 確認元件功能需求
  - [ ] 識別是否需要表單重置功能
  - [ ] 確定資料綁定類型需求
  - [ ] 評估效能要求

- [ ] **設計規劃**
  - [ ] 設計元件資料結構
  - [ ] 規劃 DOM 結構和 wire:key 策略
  - [ ] 確定事件處理機制
  - [ ] 設計測試策略

### 🏗️ 元件結構檢查

- [ ] **檔案結構**
  - [ ] 元件類別放在正確的命名空間 (`App\Livewire\`)
  - [ ] 視圖檔案使用 kebab-case 命名
  - [ ] 遵循 Livewire 3.0 目錄結構規範

- [ ] **類別定義**
  - [ ] 繼承自 `Livewire\Component`
  - [ ] 使用正確的命名空間宣告
  - [ ] 包含必要的 use 語句

```php
<?php

namespace App\Livewire\Admin\[Module];

use Livewire\Component;
use Livewire\Attributes\On;

class [ComponentName] extends Component
{
    // 元件實作
}
```

### 🎯 表單重置功能檢查

- [ ] **wire:model 使用**
  - [ ] 優先使用 `wire:model.defer` 而非 `wire:model.lazy`
  - [ ] 避免在重置場景使用 `wire:model.live`
  - [ ] 所有表單欄位都有適當的資料綁定

- [ ] **wire:key 屬性**
  - [ ] 所有動態元素都有唯一的 `wire:key`
  - [ ] 使用描述性的 key 命名
  - [ ] 迴圈中的元素使用唯一識別符

- [ ] **重置方法實作**
  - [ ] 包含完整的 `resetForm()` 方法
  - [ ] 正確的重置順序：reset → resetValidation → dispatch
  - [ ] 適當的事件觸發機制

```php
public function resetForm()
{
    // 1. 重置資料
    $this->reset(['field1', 'field2', 'field3']);
    
    // 2. 清除驗證錯誤
    $this->resetValidation();
    
    // 3. 重置狀態
    $this->showModal = false;
    
    // 4. 觸發刷新
    $this->dispatch('$refresh');
    
    // 5. 發送事件
    $this->dispatch('component-name-reset');
}
```

### 🎨 視圖檔案檢查

- [ ] **DOM 結構**
  - [ ] 遵循專案 UI 設計標準
  - [ ] 適當的 wire:key 屬性配置
  - [ ] 響應式設計實作
  - [ ] 深色模式支援

- [ ] **表單元素**
  - [ ] 所有輸入欄位都有 wire:model.defer
  - [ ] 表單有適當的提交和重置按鈕
  - [ ] 驗證錯誤顯示機制

```blade
<form wire:submit.prevent="submitForm" wire:key="main-form">
    <div class="space-y-6" wire:key="form-fields">
        <!-- 表單欄位 -->
        <input type="text" 
               wire:model.defer="fieldName" 
               wire:key="field-name-input"
               class="form-input">
    </div>
    
    <div class="flex justify-end space-x-3" wire:key="form-actions">
        <button type="button" 
                wire:click="resetForm" 
                wire:key="reset-button">
            重置
        </button>
        <button type="submit" wire:key="submit-button">
            儲存
        </button>
    </div>
</form>
```

### 🔧 JavaScript 整合檢查

- [ ] **事件監聽**
  - [ ] 適當的 Livewire 事件監聽器
  - [ ] 前端同步處理機制
  - [ ] 錯誤處理和使用者回饋

```blade
<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('component-name-reset', () => {
        // 前端重置處理邏輯
        console.log('🔄 元件已重置');
    });
});
</script>
```

### 🧪 測試檢查

- [ ] **單元測試**
  - [ ] 重置功能測試
  - [ ] 資料綁定測試
  - [ ] 事件觸發測試
  - [ ] 驗證邏輯測試

- [ ] **整合測試**
  - [ ] 前端互動測試
  - [ ] 資料庫狀態驗證
  - [ ] 跨瀏覽器相容性測試

## 程式碼審查重點項目和標準

### 🔍 審查檢查清單

#### 1. 架構和設計審查

- [ ] **元件職責**
  - [ ] 元件職責單一且明確
  - [ ] 沒有過度複雜的邏輯
  - [ ] 適當的關注點分離

- [ ] **資料流設計**
  - [ ] 資料流向清晰
  - [ ] 狀態管理合理
  - [ ] 避免不必要的資料傳遞

#### 2. 程式碼品質審查

- [ ] **命名規範**
  - [ ] 類別名稱使用 PascalCase
  - [ ] 方法名稱使用 camelCase
  - [ ] 屬性名稱具有描述性
  - [ ] 事件名稱遵循 kebab-case

- [ ] **程式碼結構**
  - [ ] 方法長度適中（建議 < 20 行）
  - [ ] 適當的註解和文檔
  - [ ] 錯誤處理機制完整
  - [ ] 遵循 PSR 標準

#### 3. Livewire 特定審查

- [ ] **資料綁定**
  - [ ] 使用正確的 wire:model 類型
  - [ ] 避免不必要的即時綁定
  - [ ] 資料驗證規則完整

- [ ] **事件處理**
  - [ ] 事件命名一致性
  - [ ] 適當的事件參數傳遞
  - [ ] 避免事件循環

- [ ] **效能考量**
  - [ ] 避免不必要的重新渲染
  - [ ] 適當的快取策略
  - [ ] 記憶體使用優化

#### 4. 表單重置特定審查

- [ ] **重置方法實作**
  - [ ] 完整的重置流程
  - [ ] 正確的執行順序
  - [ ] 適當的事件觸發

- [ ] **DOM 同步**
  - [ ] wire:key 屬性完整
  - [ ] 前端同步機制
  - [ ] 狀態一致性保證

### 📝 審查評分標準

#### 優秀 (90-100 分)
- 完全遵循所有開發規範
- 程式碼清晰易讀
- 測試覆蓋率 > 90%
- 效能優化良好
- 文檔完整

#### 良好 (80-89 分)
- 遵循大部分開發規範
- 程式碼結構合理
- 測試覆蓋率 > 80%
- 基本效能要求滿足
- 基本文檔完整

#### 需要改進 (70-79 分)
- 部分違反開發規範
- 程式碼需要重構
- 測試覆蓋率 > 70%
- 效能有改進空間
- 文檔不完整

#### 不合格 (< 70 分)
- 嚴重違反開發規範
- 程式碼品質差
- 測試覆蓋率 < 70%
- 效能問題嚴重
- 缺少必要文檔

### 🚨 常見問題檢查

#### 高優先級問題
- [ ] 缺少 wire:key 屬性
- [ ] 使用錯誤的 wire:model 類型
- [ ] 重置方法不完整
- [ ] 安全漏洞
- [ ] 效能問題

#### 中優先級問題
- [ ] 程式碼重複
- [ ] 命名不規範
- [ ] 缺少錯誤處理
- [ ] 測試不足
- [ ] 文檔缺失

#### 低優先級問題
- [ ] 程式碼風格不一致
- [ ] 註解不完整
- [ ] 優化機會
- [ ] 可讀性改進

## 自動化程式碼品質檢查工具

### 1. PHP CodeSniffer 配置

建立 `.phpcs.xml` 配置檔案：

```xml
<?xml version="1.0"?>
<ruleset name="Livewire Standards">
    <description>Livewire component coding standards</description>
    
    <!-- 檢查的檔案路徑 -->
    <file>app/Livewire</file>
    <file>resources/views/livewire</file>
    
    <!-- 使用的標準 -->
    <rule ref="PSR12"/>
    
    <!-- 自定義規則 -->
    <rule ref="Generic.Files.LineLength">
        <properties>
            <property name="lineLimit" value="120"/>
            <property name="absoluteLineLimit" value="150"/>
        </properties>
    </rule>
    
    <!-- Livewire 特定規則 -->
    <rule ref="Generic.NamingConventions.CamelCapsFunctionName"/>
    <rule ref="Generic.NamingConventions.UpperCaseConstantName"/>
</ruleset>
```

### 2. PHPStan 配置

建立 `phpstan.neon` 配置：

```neon
parameters:
    level: 8
    paths:
        - app/Livewire
    
    # Livewire 特定忽略
    ignoreErrors:
        - '#Call to an undefined method Livewire\\Component::\$dispatch\(\)#'
    
    # 自定義規則
    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
```

### 3. 自定義 Livewire 檢查腳本

建立 `scripts/check-livewire-standards.php`：

```php
#!/usr/bin/env php
<?php

/**
 * Livewire 元件標準檢查腳本
 */

class LivewireStandardsChecker
{
    private array $errors = [];
    private array $warnings = [];
    
    public function checkComponent(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $this->errors = [];
        $this->warnings = [];
        
        // 檢查命名空間
        $this->checkNamespace($content, $filePath);
        
        // 檢查 wire:model 使用
        $this->checkWireModelUsage($content);
        
        // 檢查重置方法
        $this->checkResetMethod($content);
        
        // 檢查 wire:key 屬性
        $this->checkWireKeyUsage($content);
        
        return [
            'errors' => $this->errors,
            'warnings' => $this->warnings
        ];
    }
    
    private function checkNamespace(string $content, string $filePath): void
    {
        if (!preg_match('/namespace App\\\\Livewire/', $content)) {
            $this->errors[] = "錯誤的命名空間，應使用 App\\Livewire";
        }
    }
    
    private function checkWireModelUsage(string $content): void
    {
        // 檢查是否使用了 wire:model.lazy
        if (preg_match('/wire:model\.lazy/', $content)) {
            $this->warnings[] = "建議使用 wire:model.defer 而非 wire:model.lazy";
        }
        
        // 檢查是否在表單中使用 wire:model.live
        if (preg_match('/wire:model\.live.*form/s', $content)) {
            $this->errors[] = "避免在表單中使用 wire:model.live";
        }
    }
    
    private function checkResetMethod(string $content): void
    {
        if (preg_match('/public function reset/', $content)) {
            // 檢查是否包含 $this->dispatch('$refresh')
            if (!preg_match('/\$this->dispatch\(\'\$refresh\'\)/', $content)) {
                $this->errors[] = "重置方法缺少 \$this->dispatch('\$refresh')";
            }
            
            // 檢查是否包含 resetValidation
            if (!preg_match('/\$this->resetValidation\(\)/', $content)) {
                $this->warnings[] = "建議在重置方法中加入 resetValidation()";
            }
        }
    }
    
    private function checkWireKeyUsage(string $content): void
    {
        // 檢查迴圈中是否使用 wire:key
        if (preg_match('/@foreach.*@endforeach/s', $content)) {
            if (!preg_match('/wire:key=/', $content)) {
                $this->errors[] = "迴圈中的元素缺少 wire:key 屬性";
            }
        }
    }
}

// 執行檢查
$checker = new LivewireStandardsChecker();
$livewireDir = 'app/Livewire';

if (is_dir($livewireDir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($livewireDir)
    );
    
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $results = $checker->checkComponent($file->getPathname());
            
            if (!empty($results['errors']) || !empty($results['warnings'])) {
                echo "檔案: " . $file->getPathname() . "\n";
                
                foreach ($results['errors'] as $error) {
                    echo "  ❌ 錯誤: $error\n";
                }
                
                foreach ($results['warnings'] as $warning) {
                    echo "  ⚠️  警告: $warning\n";
                }
                
                echo "\n";
            }
        }
    }
}
```

### 4. Git Hook 整合

建立 `.git/hooks/pre-commit`：

```bash
#!/bin/bash

echo "🔍 執行 Livewire 程式碼品質檢查..."

# 執行 PHP CodeSniffer
./vendor/bin/phpcs --standard=.phpcs.xml

# 執行 PHPStan
./vendor/bin/phpstan analyse

# 執行自定義 Livewire 檢查
php scripts/check-livewire-standards.php

# 檢查是否有錯誤
if [ $? -ne 0 ]; then
    echo "❌ 程式碼品質檢查失敗，請修正後再提交"
    exit 1
fi

echo "✅ 程式碼品質檢查通過"
```

### 5. CI/CD 整合

建立 `.github/workflows/livewire-quality.yml`：

```yaml
name: Livewire Code Quality

on: [push, pull_request]

jobs:
  quality-check:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        
    - name: Install dependencies
      run: composer install
      
    - name: Run PHP CodeSniffer
      run: ./vendor/bin/phpcs --standard=.phpcs.xml
      
    - name: Run PHPStan
      run: ./vendor/bin/phpstan analyse
      
    - name: Run Livewire Standards Check
      run: php scripts/check-livewire-standards.php
      
    - name: Run Tests
      run: php artisan test --filter=Livewire
```

## 開發工作流程

### 1. 開發前檢查
```bash
# 檢查開發環境
php artisan livewire:check-environment

# 更新依賴
composer update
npm update
```

### 2. 開發中檢查
```bash
# 即時程式碼檢查
./vendor/bin/phpcs app/Livewire/NewComponent.php

# 執行相關測試
php artisan test --filter=NewComponent
```

### 3. 開發後檢查
```bash
# 完整品質檢查
php scripts/check-livewire-standards.php

# 執行所有測試
php artisan test

# 生成程式碼覆蓋率報告
php artisan test --coverage
```

這個開發規範和審查清單確保了所有 Livewire 元件都遵循一致的高品質標準，特別是在表單重置功能的實作上。