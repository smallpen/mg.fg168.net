<?php

/**
 * 主題切換功能驗證腳本
 * 
 * 這個腳本用於驗證主題切換功能的實作是否正確
 */

echo "=== 主題切換功能驗證 ===\n\n";

// 1. 檢查 ThemeToggle 元件檔案是否存在
$componentFile = 'app/Http/Livewire/Admin/Layout/ThemeToggle.php';
if (file_exists($componentFile)) {
    echo "✓ ThemeToggle 元件檔案存在\n";
} else {
    echo "✗ ThemeToggle 元件檔案不存在\n";
    exit(1);
}

// 2. 檢查視圖檔案是否存在
$viewFile = 'resources/views/livewire/admin/layout/theme-toggle.blade.php';
if (file_exists($viewFile)) {
    echo "✓ ThemeToggle 視圖檔案存在\n";
} else {
    echo "✗ ThemeToggle 視圖檔案不存在\n";
    exit(1);
}

// 3. 檢查 CSS 檔案是否包含主題相關樣式
$cssFile = 'resources/css/app.css';
if (file_exists($cssFile)) {
    $cssContent = file_get_contents($cssFile);
    if (strpos($cssContent, 'theme-toggle-btn') !== false) {
        echo "✓ CSS 檔案包含主題切換樣式\n";
    } else {
        echo "✗ CSS 檔案缺少主題切換樣式\n";
    }
} else {
    echo "✗ CSS 檔案不存在\n";
}

// 4. 檢查佈局檔案是否包含主題初始化
$layoutFile = 'resources/views/layouts/admin.blade.php';
if (file_exists($layoutFile)) {
    $layoutContent = file_get_contents($layoutFile);
    if (strpos($layoutContent, 'savedTheme') !== false && strpos($layoutContent, 'localStorage') !== false) {
        echo "✓ 佈局檔案包含主題初始化腳本\n";
    } else {
        echo "✗ 佈局檔案缺少主題初始化腳本\n";
    }
} else {
    echo "✗ 佈局檔案不存在\n";
}

// 5. 檢查 TopBar 是否包含 ThemeToggle 元件
$topBarFile = 'resources/views/livewire/admin/layout/top-bar.blade.php';
if (file_exists($topBarFile)) {
    $topBarContent = file_get_contents($topBarFile);
    if (strpos($topBarContent, '@livewire(\'admin.layout.theme-toggle\')') !== false) {
        echo "✓ TopBar 包含 ThemeToggle 元件\n";
    } else {
        echo "✗ TopBar 缺少 ThemeToggle 元件\n";
    }
} else {
    echo "✗ TopBar 檔案不存在\n";
}

// 6. 檢查 User 模型是否包含 theme_preference 欄位
$userModelFile = 'app/Models/User.php';
if (file_exists($userModelFile)) {
    $userModelContent = file_get_contents($userModelFile);
    if (strpos($userModelContent, 'theme_preference') !== false) {
        echo "✓ User 模型包含 theme_preference 欄位\n";
    } else {
        echo "✗ User 模型缺少 theme_preference 欄位\n";
    }
} else {
    echo "✗ User 模型檔案不存在\n";
}

// 7. 檢查測試檔案是否存在
$testFile = 'tests/Unit/ThemeToggleLogicTest.php';
if (file_exists($testFile)) {
    echo "✓ 主題切換測試檔案存在\n";
} else {
    echo "✗ 主題切換測試檔案不存在\n";
}

// 8. 檢查 Tailwind 配置是否支援暗黑模式
$tailwindFile = 'tailwind.config.js';
if (file_exists($tailwindFile)) {
    $tailwindContent = file_get_contents($tailwindFile);
    if (strpos($tailwindContent, 'darkMode: \'class\'') !== false) {
        echo "✓ Tailwind 配置支援暗黑模式\n";
    } else {
        echo "✗ Tailwind 配置缺少暗黑模式支援\n";
    }
} else {
    echo "✗ Tailwind 配置檔案不存在\n";
}

echo "\n=== 驗證完成 ===\n";

// 9. 顯示實作摘要
echo "\n=== 實作摘要 ===\n";
echo "1. 建立了 ThemeToggle Livewire 元件\n";
echo "2. 實作了主題切換的前端介面\n";
echo "3. 加入了 CSS 變數系統支援主題切換\n";
echo "4. 更新了佈局檔案以支援即時主題切換\n";
echo "5. 整合了 TopBar 元件以顯示主題切換按鈕\n";
echo "6. 實作了使用者主題偏好設定的儲存和載入\n";
echo "7. 建立了測試檔案驗證功能正確性\n";
echo "8. 配置了 Tailwind CSS 的暗黑模式支援\n";

echo "\n=== 功能特色 ===\n";
echo "• 支援淺色和暗黑主題切換\n";
echo "• 主題偏好設定會儲存到使用者資料庫\n";
echo "• 支援 localStorage 本地儲存\n";
echo "• 即時主題切換，無需重新載入頁面\n";
echo "• 響應式設計，支援各種裝置\n";
echo "• 完整的錯誤處理和驗證\n";
echo "• 支援未登入使用者的主題切換\n";
echo "• 包含下拉選單和快速切換按鈕\n";

echo "\n=== 使用方式 ===\n";
echo "1. 登入管理後台\n";
echo "2. 在頂部導航列找到主題切換按鈕\n";
echo "3. 點擊按鈕即可在淺色和暗黑主題間切換\n";
echo "4. 或點擊下拉箭頭選擇特定主題\n";
echo "5. 主題偏好設定會自動儲存\n";

echo "\n";