<?php

/**
 * 設定匯入匯出功能演示
 * 
 * 這個檔案演示了如何使用設定匯入匯出功能
 */

require_once 'vendor/autoload.php';

// 模擬匯出的設定資料
$exportedSettings = [
    [
        'key' => 'app.name',
        'value' => 'My Application',
        'category' => 'basic',
        'type' => 'text',
        'description' => '應用程式名稱',
        'default_value' => 'Laravel Admin',
        'is_encrypted' => false,
        'is_system' => false,
        'is_public' => true,
        'sort_order' => 1,
        'exported_at' => '2025-08-19T15:30:00Z',
        'exported_by' => 'Admin User',
    ],
    [
        'key' => 'app.timezone',
        'value' => 'Asia/Taipei',
        'category' => 'basic',
        'type' => 'select',
        'description' => '系統時區',
        'default_value' => 'UTC',
        'is_encrypted' => false,
        'is_system' => true,
        'is_public' => true,
        'sort_order' => 2,
        'exported_at' => '2025-08-19T15:30:00Z',
        'exported_by' => 'Admin User',
    ],
    [
        'key' => 'security.password_min_length',
        'value' => 10,
        'category' => 'security',
        'type' => 'number',
        'description' => '密碼最小長度',
        'default_value' => 8,
        'is_encrypted' => false,
        'is_system' => false,
        'is_public' => false,
        'sort_order' => 1,
        'exported_at' => '2025-08-19T15:30:00Z',
        'exported_by' => 'Admin User',
    ],
    [
        'key' => 'mail.smtp_host',
        'value' => 'smtp.gmail.com',
        'category' => 'mail',
        'type' => 'text',
        'description' => 'SMTP 伺服器主機',
        'default_value' => 'localhost',
        'is_encrypted' => false,
        'is_system' => false,
        'is_public' => false,
        'sort_order' => 1,
        'exported_at' => '2025-08-19T15:30:00Z',
        'exported_by' => 'Admin User',
    ],
    [
        'key' => 'mail.smtp_password',
        'value' => 'encrypted_password_value',
        'category' => 'mail',
        'type' => 'password',
        'description' => 'SMTP 密碼',
        'default_value' => '',
        'is_encrypted' => true,
        'is_system' => false,
        'is_public' => false,
        'sort_order' => 3,
        'exported_at' => '2025-08-19T15:30:00Z',
        'exported_by' => 'Admin User',
    ]
];

// 將設定匯出為 JSON 檔案
$exportJson = json_encode($exportedSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents('settings_export_demo.json', $exportJson);

echo "=== 設定匯入匯出功能演示 ===\n\n";

echo "1. 匯出功能演示\n";
echo "   - 已匯出 " . count($exportedSettings) . " 個設定項目\n";
echo "   - 包含 " . count(array_unique(array_column($exportedSettings, 'category'))) . " 個分類\n";
echo "   - 檔案已儲存為: settings_export_demo.json\n\n";

echo "2. 匯出設定統計\n";
$stats = [
    'total' => count($exportedSettings),
    'by_category' => [],
    'by_type' => [],
    'system_settings' => 0,
    'encrypted_settings' => 0,
];

foreach ($exportedSettings as $setting) {
    // 按分類統計
    if (!isset($stats['by_category'][$setting['category']])) {
        $stats['by_category'][$setting['category']] = 0;
    }
    $stats['by_category'][$setting['category']]++;
    
    // 按類型統計
    if (!isset($stats['by_type'][$setting['type']])) {
        $stats['by_type'][$setting['type']] = 0;
    }
    $stats['by_type'][$setting['type']]++;
    
    // 系統設定統計
    if ($setting['is_system']) {
        $stats['system_settings']++;
    }
    
    // 加密設定統計
    if ($setting['is_encrypted']) {
        $stats['encrypted_settings']++;
    }
}

echo "   - 總設定數: {$stats['total']}\n";
echo "   - 系統設定: {$stats['system_settings']}\n";
echo "   - 加密設定: {$stats['encrypted_settings']}\n";
echo "   - 按分類分布:\n";
foreach ($stats['by_category'] as $category => $count) {
    echo "     * {$category}: {$count} 項\n";
}
echo "   - 按類型分布:\n";
foreach ($stats['by_type'] as $type => $count) {
    echo "     * {$type}: {$count} 項\n";
}

echo "\n3. 匯入衝突處理演示\n";

// 模擬現有設定
$existingSettings = [
    'app.name' => 'Current Application Name',
    'app.timezone' => 'UTC',
];

$conflicts = [];
foreach ($exportedSettings as $setting) {
    if (isset($existingSettings[$setting['key']])) {
        $conflicts[] = [
            'key' => $setting['key'],
            'existing_value' => $existingSettings[$setting['key']],
            'new_value' => $setting['value'],
            'has_conflict' => $existingSettings[$setting['key']] !== $setting['value'],
            'is_system' => $setting['is_system'],
        ];
    }
}

echo "   - 發現 " . count($conflicts) . " 個潛在衝突\n";
foreach ($conflicts as $conflict) {
    if ($conflict['has_conflict']) {
        echo "   - 衝突設定: {$conflict['key']}\n";
        echo "     現有值: {$conflict['existing_value']}\n";
        echo "     新值: {$conflict['new_value']}\n";
        echo "     系統設定: " . ($conflict['is_system'] ? '是' : '否') . "\n";
        echo "     建議處理方式: " . ($conflict['is_system'] ? '謹慎合併' : '可以覆蓋') . "\n\n";
    }
}

echo "4. 匯入選項說明\n";
echo "   - skip: 跳過衝突項目，保持現有設定\n";
echo "   - update: 完全覆蓋現有設定\n";
echo "   - merge: 智慧合併，保留系統屬性但更新值和描述\n";
echo "   - validate_data: 驗證匯入資料的格式和值\n";
echo "   - dry_run: 預覽匯入結果但不實際執行\n";
echo "   - selected_keys: 僅匯入指定的設定項目\n\n";

echo "5. 安全注意事項\n";
echo "   - 匯入前請備份現有設定\n";
echo "   - 系統設定需要管理員權限\n";
echo "   - 加密設定需要特殊處理\n";
echo "   - 建議在測試環境先驗證匯入結果\n";
echo "   - 生產環境匯入前請仔細檢查衝突項目\n\n";

echo "6. 使用範例\n";
echo "   // 匯出所有設定\n";
echo "   \$exported = \$repository->exportSettings();\n\n";
echo "   // 匯出特定分類\n";
echo "   \$exported = \$repository->exportSettings(['basic', 'security']);\n\n";
echo "   // 匯入設定（跳過衝突）\n";
echo "   \$result = \$repository->importSettings(\$data, [\n";
echo "       'conflict_resolution' => 'skip',\n";
echo "       'validate_data' => true,\n";
echo "   ]);\n\n";
echo "   // 預覽匯入結果\n";
echo "   \$result = \$repository->importSettings(\$data, [\n";
echo "       'dry_run' => true,\n";
echo "   ]);\n\n";

echo "演示完成！請查看 settings_export_demo.json 檔案以了解匯出格式。\n";