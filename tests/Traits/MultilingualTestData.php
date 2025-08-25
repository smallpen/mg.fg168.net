<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;

/**
 * 多語系測試資料管理 Trait
 * 
 * 提供測試資料建立和語言環境設定的輔助方法
 */
trait MultilingualTestData
{
    /**
     * 測試用的語言檔案內容
     */
    protected array $testLanguageFiles = [
        'test_translations' => [
            'zh_TW' => [
                'common' => [
                    'save' => '儲存',
                    'cancel' => '取消',
                    'delete' => '刪除',
                    'edit' => '編輯',
                    'view' => '檢視',
                    'create' => '建立',
                    'update' => '更新',
                    'search' => '搜尋',
                    'filter' => '篩選',
                    'export' => '匯出',
                    'import' => '匯入',
                    'yes' => '是',
                    'no' => '否',
                    'confirm' => '確認',
                    'loading' => '載入中...',
                    'success' => '成功',
                    'error' => '錯誤',
                    'warning' => '警告',
                    'info' => '資訊'
                ],
                'messages' => [
                    'save_success' => '資料已成功儲存',
                    'delete_success' => '資料已成功刪除',
                    'update_success' => '資料已成功更新',
                    'create_success' => '資料已成功建立',
                    'operation_failed' => '操作失敗',
                    'no_data_found' => '找不到資料',
                    'confirm_delete' => '確定要刪除這筆資料嗎？',
                    'unsaved_changes' => '您有未儲存的變更',
                    'invalid_input' => '輸入資料無效',
                    'permission_denied' => '權限不足'
                ],
                'validation' => [
                    'required' => ':attribute 欄位為必填',
                    'email' => ':attribute 必須是有效的電子郵件地址',
                    'min' => ':attribute 至少需要 :min 個字元',
                    'max' => ':attribute 不能超過 :max 個字元',
                    'unique' => ':attribute 已經存在',
                    'confirmed' => ':attribute 確認不符',
                    'numeric' => ':attribute 必須是數字',
                    'date' => ':attribute 必須是有效的日期'
                ],
                'navigation' => [
                    'dashboard' => '儀表板',
                    'users' => '使用者管理',
                    'roles' => '角色管理',
                    'permissions' => '權限管理',
                    'settings' => '系統設定',
                    'profile' => '個人資料',
                    'logout' => '登出'
                ],
                'forms' => [
                    'username' => '使用者名稱',
                    'password' => '密碼',
                    'email' => '電子郵件',
                    'name' => '姓名',
                    'phone' => '電話',
                    'address' => '地址',
                    'birthday' => '生日',
                    'gender' => '性別',
                    'status' => '狀態'
                ]
            ],
            'en' => [
                'common' => [
                    'save' => 'Save',
                    'cancel' => 'Cancel',
                    'delete' => 'Delete',
                    'edit' => 'Edit',
                    'view' => 'View',
                    'create' => 'Create',
                    'update' => 'Update',
                    'search' => 'Search',
                    'filter' => 'Filter',
                    'export' => 'Export',
                    'import' => 'Import',
                    'yes' => 'Yes',
                    'no' => 'No',
                    'confirm' => 'Confirm',
                    'loading' => 'Loading...',
                    'success' => 'Success',
                    'error' => 'Error',
                    'warning' => 'Warning',
                    'info' => 'Information'
                ],
                'messages' => [
                    'save_success' => 'Data saved successfully',
                    'delete_success' => 'Data deleted successfully',
                    'update_success' => 'Data updated successfully',
                    'create_success' => 'Data created successfully',
                    'operation_failed' => 'Operation failed',
                    'no_data_found' => 'No data found',
                    'confirm_delete' => 'Are you sure you want to delete this item?',
                    'unsaved_changes' => 'You have unsaved changes',
                    'invalid_input' => 'Invalid input data',
                    'permission_denied' => 'Permission denied'
                ],
                'validation' => [
                    'required' => 'The :attribute field is required',
                    'email' => 'The :attribute must be a valid email address',
                    'min' => 'The :attribute must be at least :min characters',
                    'max' => 'The :attribute may not be greater than :max characters',
                    'unique' => 'The :attribute has already been taken',
                    'confirmed' => 'The :attribute confirmation does not match',
                    'numeric' => 'The :attribute must be a number',
                    'date' => 'The :attribute must be a valid date'
                ],
                'navigation' => [
                    'dashboard' => 'Dashboard',
                    'users' => 'User Management',
                    'roles' => 'Role Management',
                    'permissions' => 'Permission Management',
                    'settings' => 'System Settings',
                    'profile' => 'Profile',
                    'logout' => 'Logout'
                ],
                'forms' => [
                    'username' => 'Username',
                    'password' => 'Password',
                    'email' => 'Email',
                    'name' => 'Name',
                    'phone' => 'Phone',
                    'address' => 'Address',
                    'birthday' => 'Birthday',
                    'gender' => 'Gender',
                    'status' => 'Status'
                ]
            ]
        ]
    ];

    /**
     * 建立測試用的語言檔案
     *
     * @param string|null $filename
     * @return void
     */
    protected function createTestLanguageFiles(?string $filename = null): void
    {
        $filesToCreate = $filename ? [$filename => $this->testLanguageFiles[$filename]] : $this->testLanguageFiles;
        
        foreach ($filesToCreate as $file => $content) {
            foreach ($content as $locale => $translations) {
                $this->createLanguageFile($file, $locale, $translations);
            }
        }
    }

    /**
     * 建立單一語言檔案
     *
     * @param string $filename
     * @param string $locale
     * @param array $content
     * @return void
     */
    protected function createLanguageFile(string $filename, string $locale, array $content): void
    {
        $langPath = lang_path($locale);
        
        if (!File::exists($langPath)) {
            File::makeDirectory($langPath, 0755, true);
        }
        
        $filePath = $langPath . '/' . $filename . '.php';
        $phpContent = "<?php\n\nreturn " . var_export($content, true) . ";\n";
        
        File::put($filePath, $phpContent);
    }

    /**
     * 清理測試語言檔案
     *
     * @param string|null $filename
     * @return void
     */
    protected function cleanupTestLanguageFiles(?string $filename = null): void
    {
        $filesToClean = $filename ? [$filename] : array_keys($this->testLanguageFiles);
        
        foreach ($filesToClean as $file) {
            foreach (['zh_TW', 'en'] as $locale) {
                $filePath = lang_path($locale . '/' . $file . '.php');
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            }
        }
    }

    /**
     * 建立測試用的不完整語言檔案（用於測試語言檔案完整性檢查）
     *
     * @return void
     */
    protected function createIncompleteLanguageFiles(): void
    {
        // 建立完整的中文檔案
        $this->createLanguageFile('incomplete_test', 'zh_TW', [
            'section1' => [
                'key1' => '值1',
                'key2' => '值2',
                'key3' => '值3'
            ],
            'section2' => [
                'key4' => '值4',
                'key5' => '值5'
            ]
        ]);
        
        // 建立不完整的英文檔案（缺少一些鍵）
        $this->createLanguageFile('incomplete_test', 'en', [
            'section1' => [
                'key1' => 'Value 1',
                'key2' => 'Value 2'
                // 缺少 key3
            ],
            'section2' => [
                'key4' => 'Value 4'
                // 缺少 key5
            ]
        ]);
    }

    /**
     * 建立測試用的多餘鍵語言檔案
     *
     * @return void
     */
    protected function createExtraKeysLanguageFiles(): void
    {
        // 建立基礎中文檔案
        $this->createLanguageFile('extra_keys_test', 'zh_TW', [
            'section1' => [
                'key1' => '值1',
                'key2' => '值2'
            ]
        ]);
        
        // 建立有多餘鍵的英文檔案
        $this->createLanguageFile('extra_keys_test', 'en', [
            'section1' => [
                'key1' => 'Value 1',
                'key2' => 'Value 2',
                'extra_key' => 'Extra Value'  // 多餘的鍵
            ],
            'extra_section' => [  // 多餘的區段
                'extra_key2' => 'Extra Value 2'
            ]
        ]);
    }

    /**
     * 建立測試用的使用者資料
     *
     * @return array
     */
    protected function createMultilingualTestUsers(): array
    {
        return [
            'zh_TW_user' => $this->createUserWithLocale('zh_TW', [
                'name' => '中文使用者',
                'email' => 'zh_user@test.com'
            ]),
            'en_user' => $this->createUserWithLocale('en', [
                'name' => 'English User',
                'email' => 'en_user@test.com'
            ])
        ];
    }

    /**
     * 建立測試用的表單資料
     *
     * @return array
     */
    protected function getTestFormData(): array
    {
        return [
            'valid_data' => [
                'zh_TW' => [
                    'name' => '測試使用者',
                    'email' => 'test@example.com',
                    'username' => 'testuser'
                ],
                'en' => [
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'username' => 'testuser'
                ]
            ],
            'invalid_data' => [
                'name' => '',  // 必填欄位為空
                'email' => 'invalid-email',  // 無效的電子郵件
                'username' => 'a'  // 太短
            ]
        ];
    }

    /**
     * 建立測試用的導航資料
     *
     * @return array
     */
    protected function getTestNavigationData(): array
    {
        return [
            'zh_TW' => [
                '儀表板',
                '使用者管理',
                '角色管理',
                '權限管理',
                '系統設定'
            ],
            'en' => [
                'Dashboard',
                'User Management',
                'Role Management',
                'Permission Management',
                'System Settings'
            ]
        ];
    }

    /**
     * 建立測試用的錯誤訊息資料
     *
     * @return array
     */
    protected function getTestErrorMessages(): array
    {
        return [
            'zh_TW' => [
                '姓名欄位為必填',
                '電子郵件必須是有效的電子郵件地址',
                '使用者名稱至少需要 3 個字元'
            ],
            'en' => [
                'The name field is required',
                'The email must be a valid email address',
                'The username must be at least 3 characters'
            ]
        ];
    }

    /**
     * 建立測試用的成功訊息資料
     *
     * @return array
     */
    protected function getTestSuccessMessages(): array
    {
        return [
            'zh_TW' => '資料已成功儲存',
            'en' => 'Data saved successfully'
        ];
    }

    /**
     * 建立測試用的資料表格標題
     *
     * @return array
     */
    protected function getTestTableHeaders(): array
    {
        return [
            'zh_TW' => [
                '使用者名稱',
                '姓名',
                '電子郵件',
                '狀態',
                '建立時間',
                '操作'
            ],
            'en' => [
                'Username',
                'Name',
                'Email',
                'Status',
                'Created At',
                'Actions'
            ]
        ];
    }

    /**
     * 建立測試用的操作按鈕文字
     *
     * @return array
     */
    protected function getTestActionButtons(): array
    {
        return [
            'zh_TW' => [
                '檢視',
                '編輯',
                '刪除',
                '建立新項目'
            ],
            'en' => [
                'View',
                'Edit',
                'Delete',
                'Create New'
            ]
        ];
    }

    /**
     * 建立測試用的分頁文字
     *
     * @return array
     */
    protected function getTestPaginationTexts(): array
    {
        return [
            'zh_TW' => [
                '上一頁',
                '下一頁',
                '第一頁',
                '最後一頁',
                '顯示第'
            ],
            'en' => [
                'Previous',
                'Next',
                'First',
                'Last',
                'Showing'
            ]
        ];
    }

    /**
     * 建立測試用的搜尋相關文字
     *
     * @return array
     */
    protected function getTestSearchTexts(): array
    {
        return [
            'placeholders' => [
                'zh_TW' => '請輸入搜尋關鍵字...',
                'en' => 'Enter search keywords...'
            ],
            'no_results' => [
                'zh_TW' => '找不到符合條件的資料',
                'en' => 'No matching data found'
            ],
            'search_button' => [
                'zh_TW' => '搜尋',
                'en' => 'Search'
            ]
        ];
    }

    /**
     * 建立測試用的主題切換文字
     *
     * @return array
     */
    protected function getTestThemeToggleTexts(): array
    {
        return [
            'zh_TW' => [
                '深色模式',
                '淺色模式',
                '切換主題'
            ],
            'en' => [
                'Dark Mode',
                'Light Mode',
                'Toggle Theme'
            ]
        ];
    }

    /**
     * 建立測試用的語言選擇器選項
     *
     * @return array
     */
    protected function getTestLanguageOptions(): array
    {
        return [
            'zh_TW' => '繁體中文',
            'en' => 'English'
        ];
    }

    /**
     * 建立測試用的麵包屑導航
     *
     * @return array
     */
    protected function getTestBreadcrumbs(): array
    {
        return [
            'zh_TW' => [
                '首頁',
                '使用者管理',
                '建立使用者'
            ],
            'en' => [
                'Home',
                'User Management',
                'Create User'
            ]
        ];
    }

    /**
     * 建立測試用的模態對話框文字
     *
     * @return array
     */
    protected function getTestModalTexts(): array
    {
        return [
            'zh_TW' => [
                '確認刪除',
                '確定要刪除這筆資料嗎？',
                '此操作無法復原',
                '確認',
                '取消'
            ],
            'en' => [
                'Confirm Delete',
                'Are you sure you want to delete this item?',
                'This action cannot be undone',
                'Confirm',
                'Cancel'
            ]
        ];
    }

    /**
     * 重新載入語言檔案
     *
     * @return void
     */
    protected function reloadLanguageFiles(): void
    {
        // 清除語言快取
        if (method_exists(Lang::class, 'clearResolvedInstances')) {
            Lang::clearResolvedInstances();
        }
        
        // 重新載入語言檔案
        app('translator')->setLoaded([]);
    }

    /**
     * 驗證測試語言檔案是否正確建立
     *
     * @param string $filename
     * @return void
     */
    protected function assertTestLanguageFilesCreated(string $filename): void
    {
        foreach (['zh_TW', 'en'] as $locale) {
            $filePath = lang_path($locale . '/' . $filename . '.php');
            $this->assertFileExists($filePath, "語言檔案 {$filename}.php 在 {$locale} 語言中不存在");
            
            // 驗證檔案內容可以正確載入
            $content = include $filePath;
            $this->assertIsArray($content, "語言檔案 {$filename}.php 在 {$locale} 語言中的內容格式不正確");
        }
    }

    /**
     * 建立大量測試語言資料
     *
     * @param int $keyCount
     * @return void
     */
    protected function createLargeTestLanguageData(int $keyCount = 1000): void
    {
        $zhData = [];
        $enData = [];
        
        for ($i = 1; $i <= $keyCount; $i++) {
            $zhData["key_{$i}"] = "中文值_{$i}";
            $enData["key_{$i}"] = "English Value {$i}";
        }
        
        $this->createLanguageFile('large_test', 'zh_TW', $zhData);
        $this->createLanguageFile('large_test', 'en', $enData);
    }

    /**
     * 建立巢狀結構的測試語言資料
     *
     * @param int $depth
     * @param int $keysPerLevel
     * @return void
     */
    protected function createNestedTestLanguageData(int $depth = 3, int $keysPerLevel = 5): void
    {
        $zhData = $this->generateNestedData($depth, $keysPerLevel, '中文值');
        $enData = $this->generateNestedData($depth, $keysPerLevel, 'English Value');
        
        $this->createLanguageFile('nested_test', 'zh_TW', $zhData);
        $this->createLanguageFile('nested_test', 'en', $enData);
    }

    /**
     * 產生巢狀資料結構
     *
     * @param int $depth
     * @param int $keysPerLevel
     * @param string $valuePrefix
     * @param int $currentDepth
     * @return array
     */
    private function generateNestedData(int $depth, int $keysPerLevel, string $valuePrefix, int $currentDepth = 0): array
    {
        $data = [];
        
        for ($i = 1; $i <= $keysPerLevel; $i++) {
            $key = "level_{$currentDepth}_key_{$i}";
            
            if ($currentDepth < $depth - 1) {
                $data[$key] = $this->generateNestedData($depth, $keysPerLevel, $valuePrefix, $currentDepth + 1);
            } else {
                $data[$key] = "{$valuePrefix} {$currentDepth}_{$i}";
            }
        }
        
        return $data;
    }
}