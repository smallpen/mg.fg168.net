/**
 * 後台登入功能測試腳本
 * 
 * 使用方式：在瀏覽器控制台中執行此腳本
 */

async function testLogin() {
    console.log('=== 開始測試後台登入功能 ===\n');
    
    const testResults = {
        pageLoad: false,
        formElements: false,
        livewireReady: false,
        loginAttempt: false,
        loginSuccess: false,
        errors: []
    };
    
    try {
        // 1. 檢查頁面載入
        console.log('1️⃣ 檢查頁面載入...');
        if (window.location.href.includes('/admin/login')) {
            testResults.pageLoad = true;
            console.log('✅ 登入頁面已載入');
        } else {
            testResults.errors.push('不在登入頁面');
            console.log('❌ 不在登入頁面，當前 URL:', window.location.href);
        }
        
        // 2. 檢查表單元素
        console.log('\n2️⃣ 檢查表單元素...');
        const usernameField = document.getElementById('username');
        const passwordField = document.getElementById('password');
        const submitButton = document.querySelector('button[type="submit"]');
        
        if (usernameField && passwordField && submitButton) {
            testResults.formElements = true;
            console.log('✅ 表單元素完整');
            console.log('   - 使用者名稱欄位:', usernameField ? '存在' : '不存在');
            console.log('   - 密碼欄位:', passwordField ? '存在' : '不存在');
            console.log('   - 提交按鈕:', submitButton ? '存在' : '不存在');
        } else {
            testResults.errors.push('表單元素不完整');
            console.log('❌ 表單元素不完整');
        }
        
        // 3. 檢查 Livewire 狀態
        console.log('\n3️⃣ 檢查 Livewire 狀態...');
        if (window.Livewire) {
            testResults.livewireReady = true;
            console.log('✅ Livewire 已載入');
            const components = window.Livewire.all();
            console.log('   - Livewire 元件數量:', components.length);
            if (components.length > 0) {
                console.log('   - 元件名稱:', components[0].name);
            }
        } else {
            testResults.errors.push('Livewire 未載入');
            console.log('❌ Livewire 未載入');
        }
        
        // 4. 檢查 CSRF Token
        console.log('\n4️⃣ 檢查 CSRF Token...');
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            console.log('✅ CSRF Token 存在');
        } else {
            testResults.errors.push('CSRF Token 不存在');
            console.log('❌ CSRF Token 不存在');
        }
        
        // 5. 檢查驗證錯誤
        console.log('\n5️⃣ 檢查驗證錯誤...');
        const errors = document.querySelectorAll('.form-error, .text-red-600, .text-red-500');
        if (errors.length > 0) {
            console.log('⚠️  發現驗證錯誤:');
            errors.forEach((error, index) => {
                console.log(`   ${index + 1}. ${error.textContent.trim()}`);
            });
        } else {
            console.log('✅ 沒有驗證錯誤');
        }
        
        // 6. 測試資料總結
        console.log('\n📊 測試資料總結:');
        console.log('   - 測試帳號: admin');
        console.log('   - 測試密碼: admin123');
        console.log('   - 登入 URL: http://localhost/admin/login');
        console.log('   - 預期重定向: http://localhost/admin/dashboard');
        
        // 7. 測試結果
        console.log('\n📋 測試結果:');
        console.log('   - 頁面載入:', testResults.pageLoad ? '✅' : '❌');
        console.log('   - 表單元素:', testResults.formElements ? '✅' : '❌');
        console.log('   - Livewire 就緒:', testResults.livewireReady ? '✅' : '❌');
        
        if (testResults.errors.length > 0) {
            console.log('\n❌ 發現問題:');
            testResults.errors.forEach((error, index) => {
                console.log(`   ${index + 1}. ${error}`);
            });
        }
        
        // 8. 提供手動測試指引
        console.log('\n📝 手動測試步驟:');
        console.log('   1. 在使用者名稱欄位輸入: admin');
        console.log('   2. 在密碼欄位輸入: admin123');
        console.log('   3. 點擊登入按鈕');
        console.log('   4. 應該會重定向到儀表板頁面');
        
        console.log('\n=== 測試完成 ===');
        
        return testResults;
        
    } catch (error) {
        console.error('❌ 測試過程中發生錯誤:', error);
        testResults.errors.push(error.message);
        return testResults;
    }
}

// 自動執行測試
console.log('🚀 載入登入測試腳本...\n');
console.log('執行測試請輸入: testLogin()');
console.log('或直接執行: await testLogin();\n');
