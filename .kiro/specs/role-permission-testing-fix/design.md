# 角色管理與權限管理功能測試與修正設計文件

## 概述

本設計文件詳細規劃如何系統性地測試角色管理與權限管理功能，識別問題並進行修正。採用 MCP 工具（Playwright + MySQL）進行自動化測試，結合手動驗證確保所有功能正常運作。

## 架構設計

### 測試架構

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Playwright    │    │     MySQL        │    │   測試報告      │
│   自動化測試    │◄──►│   資料驗證       │◄──►│   生成系統      │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                        │                        │
         ▼                        ▼                        ▼
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   功能測試      │    │   資料一致性     │    │   修正追蹤      │
│   執行器        │    │   檢查器         │    │   系統          │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

## 測試策略設計

### 1. 測試環境準備

#### 資料庫狀態確認
```sql
-- 檢查測試資料完整性
SELECT 
    (SELECT COUNT(*) FROM users) as user_count,
    (SELECT COUNT(*) FROM roles) as role_count,
    (SELECT COUNT(*) FROM permissions) as permission_count,
    (SELECT COUNT(*) FROM role_permissions) as role_permission_count,
    (SELECT COUNT(*) FROM user_roles) as user_role_count;

-- 檢查管理員帳號
SELECT id, username, name, email, is_active 
FROM users 
WHERE username = 'admin';

-- 檢查角色權限分配
SELECT r.name, r.display_name, COUNT(rp.permission_id) as permission_count
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
GROUP BY r.id, r.name, r.display_name;
```

#### 瀏覽器環境設定
```javascript
// Playwright 設定
const browserConfig = {
    headless: false,  // 顯示瀏覽器以便觀察
    viewport: { width: 1280, height: 720 },
    timeout: 30000,
    waitUntil: 'networkidle'
};
```

### 2. 角色管理功能測試設計

#### 2.1 角色列表頁面測試

**測試流程：**
1. 登入系統
2. 導航到角色管理頁面
3. 驗證頁面載入和資料顯示
4. 測試每個操作按鈕
5. 驗證資料庫狀態

**測試腳本結構：**
```javascript
async function testRoleListPage() {
    // 1. 登入並導航
    await livewireLogin('admin', 'password123');
    await playwright.navigate('http://localhost/admin/roles');
    
    // 2. 驗證頁面載入
    const pageTitle = await playwright.getVisibleText();
    assert(pageTitle.includes('角色管理'));
    
    // 3. 測試建立按鈕
    await testCreateRoleButton();
    
    // 4. 測試編輯按鈕
    await testEditRoleButton();
    
    // 5. 測試刪除按鈕
    await testDeleteRoleButton();
    
    // 6. 測試複製按鈕
    await testDuplicateRoleButton();
    
    // 7. 測試搜尋功能
    await testRoleSearch();
    
    // 8. 測試篩選功能
    await testRoleFilters();
    
    // 9. 測試批量操作
    await testRoleBulkActions();
}
```

#### 2.2 角色表單測試

**建立角色測試：**
```javascript
async function testCreateRole() {
    // 導航到建立頁面
    await playwright.click('a[href*="/admin/roles/create"]');
    
    // 等待表單載入
    await playwright.evaluate('new Promise(resolve => setTimeout(resolve, 1000))');
    
    // 填寫表單
    await playwright.evaluate(`
        document.getElementById('name').value = 'test_role_${Date.now()}';
        document.getElementById('name').dispatchEvent(new Event('input', { bubbles: true }));
        document.getElementById('name').blur();
        
        document.getElementById('display_name').value = '測試角色';
        document.getElementById('display_name').dispatchEvent(new Event('input', { bubbles: true }));
        document.getElementById('display_name').blur();
        
        document.getElementById('description').value = '這是一個測試角色';
        document.getElementById('description').dispatchEvent(new Event('input', { bubbles: true }));
        document.getElementById('description').blur();
    `);
    
    // 等待 Livewire 同步
    await playwright.evaluate('new Promise(resolve => setTimeout(resolve, 800))');
    
    // 提交表單
    await playwright.click('button[type="submit"]');
    
    // 驗證結果
    const result = await playwright.evaluate(`
        new Promise((resolve) => {
            let attempts = 0;
            const checkResult = () => {
                attempts++;
                if (window.location.href.includes('/admin/roles') && 
                    !window.location.href.includes('/create')) {
                    resolve('success');
                } else if (attempts > 10) {
                    resolve('timeout');
                } else {
                    setTimeout(checkResult, 500);
                }
            };
            setTimeout(checkResult, 1000);
        })
    `);
    
    return result === 'success';
}
```

### 3. 權限管理功能測試設計

#### 3.1 權限列表頁面測試

**測試流程：**
```javascript
async function testPermissionListPage() {
    // 導航到權限管理頁面
    await playwright.navigate('http://localhost/admin/permissions');
    
    // 驗證頁面載入
    await playwright.screenshot({ name: 'permission-list-loaded' });
    
    // 測試建立權限按鈕
    const createButtonExists = await playwright.evaluate(`
        document.querySelector('a[href*="/admin/permissions/create"]') !== null
    `);
    
    if (createButtonExists) {
        await testCreatePermissionButton();
    } else {
        console.log('❌ 建立權限按鈕不存在');
    }
    
    // 測試編輯權限按鈕
    await testEditPermissionButtons();
    
    // 測試刪除權限按鈕
    await testDeletePermissionButtons();
    
    // 測試搜尋功能
    await testPermissionSearch();
    
    // 測試篩選功能
    await testPermissionFilters();
}
```

#### 3.2 權限矩陣測試

**矩陣功能測試：**
```javascript
async function testPermissionMatrix() {
    // 導航到權限矩陣頁面
    await playwright.navigate('http://localhost/admin/permissions/matrix');
    
    // 等待矩陣載入
    await playwright.evaluate('new Promise(resolve => setTimeout(resolve, 2000))');
    
    // 截圖記錄初始狀態
    await playwright.screenshot({ name: 'permission-matrix-initial' });
    
    // 測試權限勾選功能
    const checkboxes = await playwright.evaluate(`
        Array.from(document.querySelectorAll('input[type="checkbox"]'))
             .map(cb => ({ id: cb.id, checked: cb.checked }))
    `);
    
    if (checkboxes.length > 0) {
        // 測試勾選第一個權限
        await playwright.click(`#${checkboxes[0].id}`);
        
        // 等待 AJAX 請求完成
        await playwright.evaluate('new Promise(resolve => setTimeout(resolve, 1000))');
        
        // 驗證狀態變更
        const newState = await playwright.evaluate(`
            document.getElementById('${checkboxes[0].id}').checked
        `);
        
        console.log(`權限勾選測試: ${newState ? '✅ 成功' : '❌ 失敗'}`);
    }
    
    // 測試批量權限設定
    await testBulkPermissionAssignment();
}
```

### 4. 資料一致性檢查設計

#### 4.1 角色權限一致性檢查

```sql
-- 檢查角色權限關聯的完整性
SELECT 
    r.name as role_name,
    COUNT(rp.permission_id) as assigned_permissions,
    COUNT(p.id) as valid_permissions
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
LEFT JOIN permissions p ON rp.permission_id = p.id
GROUP BY r.id, r.name
HAVING assigned_permissions != valid_permissions;

-- 檢查孤立的權限關聯
SELECT rp.role_id, rp.permission_id
FROM role_permissions rp
LEFT JOIN roles r ON rp.role_id = r.id
LEFT JOIN permissions p ON rp.permission_id = p.id
WHERE r.id IS NULL OR p.id IS NULL;
```

#### 4.2 使用者角色一致性檢查

```sql
-- 檢查使用者角色關聯的完整性
SELECT 
    u.username,
    COUNT(ur.role_id) as assigned_roles,
    COUNT(r.id) as valid_roles
FROM users u
LEFT JOIN user_roles ur ON u.id = ur.user_id
LEFT JOIN roles r ON ur.role_id = r.id
GROUP BY u.id, u.username
HAVING assigned_roles != valid_roles;
```

### 5. 錯誤處理和驗證測試設計

#### 5.1 表單驗證測試

```javascript
async function testFormValidation() {
    // 測試空白表單提交
    await playwright.navigate('http://localhost/admin/roles/create');
    await playwright.click('button[type="submit"]');
    
    // 檢查驗證錯誤訊息
    const errorMessages = await playwright.evaluate(`
        Array.from(document.querySelectorAll('.text-red-600, .error, [class*="error"]'))
             .map(el => el.textContent.trim())
             .filter(text => text.length > 0)
    `);
    
    console.log('驗證錯誤訊息:', errorMessages);
    
    // 測試重複名稱驗證
    await testDuplicateNameValidation();
    
    // 測試無效格式驗證
    await testInvalidFormatValidation();
}
```

#### 5.2 權限檢查測試

```javascript
async function testPermissionChecks() {
    // 登出管理員
    await playwright.evaluate('document.querySelector("form[action*=logout]").submit()');
    
    // 嘗試以一般使用者身份存取
    await livewireLogin('user', 'password123');
    
    // 嘗試存取角色管理頁面
    await playwright.navigate('http://localhost/admin/roles');
    
    // 檢查是否被正確阻止
    const currentUrl = await playwright.evaluate('window.location.href');
    const isBlocked = !currentUrl.includes('/admin/roles') || 
                     await playwright.evaluate('document.body.textContent.includes("權限不足")');
    
    console.log(`權限檢查測試: ${isBlocked ? '✅ 正確阻止' : '❌ 未正確阻止'}`);
}
```

### 6. 效能測試設計

#### 6.1 頁面載入效能測試

```javascript
async function testPageLoadPerformance() {
    const startTime = Date.now();
    
    await playwright.navigate('http://localhost/admin/roles');
    
    // 等待頁面完全載入
    await playwright.evaluate(`
        new Promise((resolve) => {
            if (document.readyState === 'complete') {
                resolve();
            } else {
                window.addEventListener('load', resolve);
            }
        })
    `);
    
    const loadTime = Date.now() - startTime;
    console.log(`角色列表頁面載入時間: ${loadTime}ms`);
    
    // 檢查是否符合效能要求（2秒內）
    const performanceOk = loadTime < 2000;
    console.log(`效能測試: ${performanceOk ? '✅ 通過' : '❌ 未達標準'}`);
    
    return { loadTime, performanceOk };
}
```

### 7. 修正策略設計

#### 7.1 問題分類和優先級

```javascript
const issueCategories = {
    CRITICAL: {
        priority: 1,
        description: '核心功能完全無法使用',
        examples: ['登入失敗', '頁面無法載入', '資料庫錯誤']
    },
    HIGH: {
        priority: 2,
        description: '重要功能有問題但有替代方案',
        examples: ['按鈕無反應', '表單驗證失效', '搜尋功能異常']
    },
    MEDIUM: {
        priority: 3,
        description: '功能可用但體驗不佳',
        examples: ['載入速度慢', '介面不直觀', '錯誤訊息不清楚']
    },
    LOW: {
        priority: 4,
        description: '小問題或改進建議',
        examples: ['樣式問題', '文字錯誤', '小的使用性問題']
    }
};
```

#### 7.2 修正流程設計

```javascript
async function fixIssue(issue) {
    console.log(`🔧 開始修正問題: ${issue.description}`);
    
    // 1. 備份當前狀態
    await backupCurrentState(issue.component);
    
    // 2. 識別問題根因
    const rootCause = await identifyRootCause(issue);
    
    // 3. 實施修正
    const fixResult = await implementFix(issue, rootCause);
    
    // 4. 驗證修正結果
    const verificationResult = await verifyFix(issue);
    
    // 5. 記錄修正過程
    await logFixProcess(issue, fixResult, verificationResult);
    
    return verificationResult;
}
```

### 8. 測試報告設計

#### 8.1 測試結果記錄結構

```javascript
const testReport = {
    timestamp: new Date().toISOString(),
    environment: {
        browser: 'chromium',
        viewport: '1280x720',
        database: 'laravel_admin'
    },
    testSuites: {
        roleManagement: {
            totalTests: 0,
            passedTests: 0,
            failedTests: 0,
            issues: []
        },
        permissionManagement: {
            totalTests: 0,
            passedTests: 0,
            failedTests: 0,
            issues: []
        }
    },
    performance: {
        pageLoadTimes: {},
        databaseQueryTimes: {}
    },
    fixes: {
        applied: [],
        pending: [],
        failed: []
    }
};
```

#### 8.2 問題追蹤系統

```javascript
class IssueTracker {
    constructor() {
        this.issues = [];
        this.fixes = [];
    }
    
    reportIssue(component, description, severity, evidence) {
        const issue = {
            id: `issue_${Date.now()}`,
            component,
            description,
            severity,
            evidence,
            status: 'open',
            createdAt: new Date().toISOString()
        };
        
        this.issues.push(issue);
        return issue;
    }
    
    applyFix(issueId, fixDescription, implementation) {
        const fix = {
            id: `fix_${Date.now()}`,
            issueId,
            description: fixDescription,
            implementation,
            appliedAt: new Date().toISOString(),
            status: 'applied'
        };
        
        this.fixes.push(fix);
        
        // 更新問題狀態
        const issue = this.issues.find(i => i.id === issueId);
        if (issue) {
            issue.status = 'fixed';
            issue.fixedAt = fix.appliedAt;
        }
        
        return fix;
    }
    
    generateReport() {
        return {
            summary: {
                totalIssues: this.issues.length,
                openIssues: this.issues.filter(i => i.status === 'open').length,
                fixedIssues: this.issues.filter(i => i.status === 'fixed').length,
                totalFixes: this.fixes.length
            },
            issues: this.issues,
            fixes: this.fixes
        };
    }
}
```

## 實作計劃

### 階段 1：環境準備和基礎測試
1. 確認測試環境和資料完整性
2. 建立測試輔助函數和工具
3. 實作基本的頁面導航和登入測試

### 階段 2：角色管理功能測試
1. 角色列表頁面功能測試
2. 角色建立和編輯功能測試
3. 角色刪除和複製功能測試
4. 搜尋、篩選和批量操作測試

### 階段 3：權限管理功能測試
1. 權限列表頁面功能測試
2. 權限建立和編輯功能測試
3. 權限矩陣功能測試
4. 權限依賴關係測試

### 階段 4：整合和驗證測試
1. 資料一致性檢查
2. 權限控制驗證
3. 效能測試
4. 錯誤處理測試

### 階段 5：問題修正和驗證
1. 問題分析和分類
2. 修正實施
3. 修正驗證
4. 回歸測試

### 階段 6：報告和文檔
1. 測試報告生成
2. 修正記錄整理
3. 使用者文檔更新
4. 維護指南建立

這個設計確保了全面、系統性的測試和修正流程，能夠識別並解決角色管理與權限管理功能中的所有問題。