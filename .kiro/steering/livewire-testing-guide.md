# Livewire 表單測試指南

## 重要提醒

⚠️ **本專案使用 Laravel Livewire 3.0，所有表單都使用 `wire:model.lazy` 進行資料綁定。使用 Playwright 測試時必須遵循特殊的事件觸發流程，否則會導致測試卡住或失敗。**

## 問題背景

### 為什麼 Playwright 會在 Livewire 表單上卡住？

1. **延遲綁定機制**：`wire:model.lazy` 只在 `blur` 事件時同步資料
2. **JavaScript 依賴**：Livewire 需要 JavaScript 來處理表單提交和驗證
3. **非同步處理**：資料同步是非同步的，需要等待時間
4. **事件觸發不完整**：Playwright 的 `fill()` 方法無法觸發所有必要的 DOM 事件

### 常見錯誤現象

- `playwright.fill()` 執行後表單提交無反應
- 登入表單填寫完成但無法提交
- 表單驗證不觸發
- 頁面停留在原地不重定向

## 正確的 Livewire 表單測試方法

### 基本原則

1. **完整事件觸發**：必須觸發 `input` 和 `blur` 事件
2. **等待同步**：給 Livewire 足夠時間同步資料（建議 800ms）
3. **驗證狀態**：確認表單資料已正確綁定
4. **監控重定向**：等待並驗證頁面跳轉

### 標準流程

```javascript
// 1. 導航到頁面
await playwright.navigate('http://localhost/admin/login');

// 2. 等待 Livewire 完全載入
await playwright.evaluate(`
    new Promise((resolve) => {
        const checkLivewire = () => {
            if (window.Livewire && 
                document.getElementById('username') && 
                document.getElementById('password')) {
                resolve('ready');
            } else {
                setTimeout(checkLivewire, 100);
            }
        };
        checkLivewire();
    })
`);

// 3. 填寫表單並觸發完整事件
await playwright.evaluate(`
    // 填寫使用者名稱
    const usernameField = document.getElementById('username');
    usernameField.value = 'admin';
    usernameField.dispatchEvent(new Event('input', { bubbles: true }));
    usernameField.blur();
    
    // 填寫密碼
    const passwordField = document.getElementById('password');
    passwordField.value = 'password123';
    passwordField.dispatchEvent(new Event('input', { bubbles: true }));
    passwordField.blur();
`);

// 4. 等待 Livewire 同步資料
await playwright.evaluate('new Promise(resolve => setTimeout(resolve, 800))');

// 5. 驗證表單狀態（可選）
const formState = await playwright.evaluate(`
    ({
        username: document.getElementById('username').value,
        password: document.getElementById('password').value.length > 0 ? '***' : '',
        submitEnabled: !document.querySelector('button[type="submit"]').disabled
    })
`);

// 6. 提交表單
await playwright.click('button[type="submit"]');

// 7. 等待並驗證重定向
const loginResult = await playwright.evaluate(`
    new Promise((resolve) => {
        let attempts = 0;
        const checkRedirect = () => {
            attempts++;
            if (window.location.href.includes('/admin/dashboard')) {
                resolve('success');
            } else if (attempts > 15) {
                resolve('timeout');
            } else {
                setTimeout(checkRedirect, 400);
            }
        };
        setTimeout(checkRedirect, 1000);
    })
`);
```

## 預建的輔助函數

### 登入輔助函數

```javascript
/**
 * Livewire 登入輔助函數
 * @param {string} username - 使用者名稱
 * @param {string} password - 密碼
 * @returns {Promise<boolean>} - 登入是否成功
 */
async function livewireLogin(username = 'admin', password = 'password123') {
    console.log(`🔐 開始 Livewire 登入: ${username}`);
    
    try {
        // 導航到登入頁面
        await playwright.navigate('http://localhost/admin/login');
        
        // 等待 Livewire 載入
        await playwright.evaluate(`
            new Promise((resolve) => {
                const checkReady = () => {
                    if (window.Livewire && 
                        document.getElementById('username') && 
                        document.getElementById('password')) {
                        resolve('ready');
                    } else {
                        setTimeout(checkReady, 100);
                    }
                };
                checkReady();
            })
        `);
        
        // 填寫表單
        await playwright.evaluate(`
            const usernameField = document.getElementById('username');
            usernameField.value = '${username}';
            usernameField.dispatchEvent(new Event('input', { bubbles: true }));
            usernameField.blur();
            
            const passwordField = document.getElementById('password');
            passwordField.value = '${password}';
            passwordField.dispatchEvent(new Event('input', { bubbles: true }));
            passwordField.blur();
        `);
        
        // 等待同步
        await playwright.evaluate('new Promise(resolve => setTimeout(resolve, 800))');
        
        // 提交表單
        await playwright.click('button[type="submit"]');
        
        // 等待重定向
        const result = await playwright.evaluate(`
            new Promise((resolve) => {
                let attempts = 0;
                const checkLogin = () => {
                    attempts++;
                    if (window.location.href.includes('/admin/dashboard')) {
                        resolve('success');
                    } else if (attempts > 15) {
                        resolve('timeout');
                    } else {
                        setTimeout(checkLogin, 400);
                    }
                };
                setTimeout(checkLogin, 1000);
            })
        `);
        
        return result === 'success';
        
    } catch (error) {
        console.error('登入錯誤:', error.message);
        return false;
    }
}
```

## 常見 Livewire 表單場景

### 1. 登入表單
- **頁面**：`/admin/login`
- **欄位**：`#username`, `#password`, `#remember`
- **提交**：`button[type="submit"]`
- **成功重定向**：`/admin/dashboard`

### 2. 使用者建立表單
- **頁面**：`/admin/users/create`
- **欄位**：`#username`, `#name`, `#email`, `#password`
- **提交**：`button[type="submit"]`
- **成功重定向**：`/admin/users`

### 3. 角色編輯表單
- **頁面**：`/admin/roles/{id}/edit`
- **欄位**：`#name`, `#display_name`, 權限核取方塊
- **提交**：`button[type="submit"]`
- **成功重定向**：`/admin/roles`

## 偵錯技巧

### 檢查 Livewire 狀態
```javascript
// 檢查 Livewire 是否載入
const livewireStatus = await playwright.evaluate('window.Livewire ? "loaded" : "not loaded"');

// 檢查元件資料
const componentData = await playwright.evaluate(`
    window.Livewire.all().map(component => ({
        id: component.id,
        name: component.name,
        data: component.data
    }))
`);
```

### 檢查表單驗證錯誤
```javascript
const validationErrors = await playwright.evaluate(`
    Array.from(document.querySelectorAll('.text-red-600, .error, [class*="error"]'))
         .map(el => el.textContent.trim())
         .filter(text => text.length > 0)
`);
```

### 監控 AJAX 請求
```javascript
// 等待 Livewire AJAX 請求完成
await playwright.evaluate(`
    new Promise((resolve) => {
        const originalFetch = window.fetch;
        let pendingRequests = 0;
        
        window.fetch = function(...args) {
            pendingRequests++;
            return originalFetch.apply(this, args).finally(() => {
                pendingRequests--;
                if (pendingRequests === 0) {
                    setTimeout(resolve, 100);
                }
            });
        };
        
        // 如果沒有請求，直接完成
        if (pendingRequests === 0) {
            setTimeout(resolve, 100);
        }
    })
`);
```

## 效能最佳化

### 1. 重複使用瀏覽器實例
```javascript
// 避免每次測試都重新啟動瀏覽器
// 使用 playwright.navigate() 而不是重新建立連線
```

### 2. 批次操作
```javascript
// 一次性填寫所有欄位
await playwright.evaluate(`
    const fields = [
        { id: 'username', value: 'admin' },
        { id: 'password', value: 'password123' },
        { id: 'email', value: 'admin@example.com' }
    ];
    
    fields.forEach(field => {
        const element = document.getElementById(field.id);
        if (element) {
            element.value = field.value;
            element.dispatchEvent(new Event('input', { bubbles: true }));
            element.blur();
        }
    });
`);
```

### 3. 智慧等待
```javascript
// 根據實際需要調整等待時間
const waitTime = formComplexity > 5 ? 1200 : 800;
await playwright.evaluate(`new Promise(resolve => setTimeout(resolve, ${waitTime}))`);
```

## 錯誤處理

### 常見錯誤和解決方案

1. **表單提交無反應**
   - 確認已觸發 `blur` 事件
   - 增加等待時間到 1000ms
   - 檢查表單驗證錯誤

2. **重定向失敗**
   - 檢查登入憑證是否正確
   - 確認資料庫中有測試使用者
   - 檢查權限設定

3. **元素找不到**
   - 等待頁面完全載入
   - 檢查元素選擇器是否正確
   - 確認 Livewire 元件已渲染

### 錯誤恢復策略
```javascript
async function robustLivewireLogin(username, password, maxRetries = 3) {
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
            console.log(`登入嘗試 ${attempt}/${maxRetries}`);
            const success = await livewireLogin(username, password);
            if (success) return true;
        } catch (error) {
            console.log(`嘗試 ${attempt} 失敗:`, error.message);
            if (attempt < maxRetries) {
                await playwright.evaluate('new Promise(resolve => setTimeout(resolve, 2000))');
            }
        }
    }
    return false;
}
```

## 測試資料管理

### 確保測試資料存在
```javascript
// 測試前檢查使用者是否存在
const userExists = await mysql.executeQuery({
    query: "SELECT COUNT(*) as count FROM users WHERE username = 'admin'",
    database: "laravel_admin"
});

if (userExists[0].count === 0) {
    throw new Error('測試使用者不存在，請執行 db:seed');
}
```

## 最佳實踐總結

1. **總是使用完整的事件觸發流程**
2. **給 Livewire 足夠的同步時間**
3. **驗證表單狀態再提交**
4. **監控重定向結果**
5. **包含適當的錯誤處理**
6. **使用有意義的日誌記錄**
7. **建立可重複使用的輔助函數**

遵循這些指南可以確保 Livewire 表單測試的穩定性和可靠性，避免測試卡住或失敗的問題。