<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * 輸入驗證和清理服務
 * 
 * 提供統一的輸入資料驗證和清理功能
 */
class InputValidationService
{
    /**
     * 驗證搜尋輸入
     * 
     * @param string $search
     * @return string
     * @throws ValidationException
     */
    public function validateSearchInput(string $search): string
    {
        $validator = Validator::make(['search' => $search], [
            'search' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\s\-_@.]+$/', // 只允許字母、數字、空格和常見符號
            ]
        ], [
            'search.regex' => '搜尋條件包含無效字元',
            'search.max' => '搜尋條件不能超過 255 個字元',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $this->sanitizeString($search);
    }

    /**
     * 驗證篩選條件
     * 
     * @param array $filters
     * @return array
     * @throws ValidationException
     */
    public function validateFilters(array $filters): array
    {
        $validator = Validator::make($filters, [
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:all,active,inactive',
            'role' => 'nullable|string|max:50',
            'sort_field' => 'nullable|string|in:name,username,email,created_at,is_active',
            'sort_direction' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:5|max:100',
        ], [
            'status.in' => '無效的狀態篩選條件',
            'sort_field.in' => '無效的排序欄位',
            'sort_direction.in' => '無效的排序方向',
            'per_page.min' => '每頁顯示數量不能少於 5 筆',
            'per_page.max' => '每頁顯示數量不能超過 100 筆',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 清理字串類型的篩選條件
        $cleanFilters = [];
        foreach ($filters as $key => $value) {
            if (is_string($value)) {
                $cleanFilters[$key] = $this->sanitizeString($value);
            } else {
                $cleanFilters[$key] = $value;
            }
        }

        return $cleanFilters;
    }

    /**
     * 驗證使用者 ID 陣列
     * 
     * @param array $userIds
     * @return array
     * @throws ValidationException
     */
    public function validateUserIds(array $userIds): array
    {
        $validator = Validator::make(['user_ids' => $userIds], [
            'user_ids' => 'required|array|max:100',
            'user_ids.*' => 'required|integer|min:1',
        ], [
            'user_ids.required' => '請選擇要操作的使用者',
            'user_ids.array' => '使用者 ID 格式錯誤',
            'user_ids.max' => '一次最多只能操作 100 個使用者',
            'user_ids.*.integer' => '使用者 ID 必須為整數',
            'user_ids.*.min' => '使用者 ID 必須大於 0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 移除重複的 ID 並重新索引
        return array_values(array_unique($userIds));
    }

    /**
     * 驗證單一使用者 ID
     * 
     * @param mixed $userId
     * @return int
     * @throws ValidationException
     */
    public function validateUserId($userId): int
    {
        $validator = Validator::make(['user_id' => $userId], [
            'user_id' => 'required|integer|min:1',
        ], [
            'user_id.required' => '使用者 ID 不能為空',
            'user_id.integer' => '使用者 ID 必須為整數',
            'user_id.min' => '使用者 ID 必須大於 0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return (int) $userId;
    }

    /**
     * 驗證通用 ID
     * 
     * @param mixed $id
     * @return int
     * @throws ValidationException
     */
    public function validateId($id): int
    {
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|integer|min:1',
        ], [
            'id.required' => 'ID 不能為空',
            'id.integer' => 'ID 必須為整數',
            'id.min' => 'ID 必須大於 0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return (int) $id;
    }

    /**
     * 驗證確認文字
     * 
     * @param string $confirmText
     * @param string $expectedText
     * @return bool
     * @throws ValidationException
     */
    public function validateConfirmText(string $confirmText, string $expectedText): bool
    {
        $validator = Validator::make([
            'confirm_text' => $confirmText,
            'expected_text' => $expectedText,
        ], [
            'confirm_text' => 'required|string',
            'expected_text' => 'required|string',
        ], [
            'confirm_text.required' => '請輸入確認文字',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $cleanConfirmText = $this->sanitizeString($confirmText);
        $cleanExpectedText = $this->sanitizeString($expectedText);

        if ($cleanConfirmText !== $cleanExpectedText) {
            throw ValidationException::withMessages([
                'confirm_text' => ['確認文字不符合，請重新輸入'],
            ]);
        }

        return true;
    }

    /**
     * 清理字串輸入
     * 
     * @param string $input
     * @return string
     */
    public function sanitizeString(string $input): string
    {
        // 移除前後空白
        $input = trim($input);
        
        // 移除多餘的空白字元
        $input = preg_replace('/\s+/', ' ', $input);
        
        // 移除潛在的 HTML 標籤
        $input = strip_tags($input);
        
        // 轉換特殊字元
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }

    /**
     * 驗證分頁參數
     * 
     * @param array $paginationData
     * @return array
     * @throws ValidationException
     */
    public function validatePagination(array $paginationData): array
    {
        $validator = Validator::make($paginationData, [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:5|max:100',
        ], [
            'page.integer' => '頁碼必須為整數',
            'page.min' => '頁碼必須大於 0',
            'per_page.integer' => '每頁顯示數量必須為整數',
            'per_page.min' => '每頁顯示數量不能少於 5 筆',
            'per_page.max' => '每頁顯示數量不能超過 100 筆',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return [
            'page' => $paginationData['page'] ?? 1,
            'per_page' => $paginationData['per_page'] ?? 15,
        ];
    }

    /**
     * 驗證排序參數
     * 
     * @param array $sortData
     * @return array
     * @throws ValidationException
     */
    public function validateSort(array $sortData): array
    {
        $allowedFields = ['name', 'username', 'email', 'created_at', 'is_active'];
        
        $validator = Validator::make($sortData, [
            'sort_field' => 'nullable|string|in:' . implode(',', $allowedFields),
            'sort_direction' => 'nullable|string|in:asc,desc',
        ], [
            'sort_field.in' => '無效的排序欄位',
            'sort_direction.in' => '無效的排序方向',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return [
            'sort_field' => $sortData['sort_field'] ?? 'created_at',
            'sort_direction' => $sortData['sort_direction'] ?? 'desc',
        ];
    }

    /**
     * 檢查輸入是否包含惡意內容
     * 
     * @param string $input
     * @return bool
     */
    public function containsMaliciousContent(string $input): bool
    {
        $maliciousPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
            '/eval\s*\(/i',
            '/expression\s*\(/i',
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 驗證並清理批量操作資料
     * 
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function validateBulkOperation(array $data): array
    {
        $validator = Validator::make($data, [
            'action' => 'required|string|in:activate,deactivate,delete',
            'user_ids' => 'required|array|max:100',
            'user_ids.*' => 'required|integer|min:1',
            'confirm' => 'required_if:action,delete|boolean',
        ], [
            'action.required' => '請選擇操作類型',
            'action.in' => '無效的操作類型',
            'user_ids.required' => '請選擇要操作的使用者',
            'user_ids.max' => '一次最多只能操作 100 個使用者',
            'confirm.required_if' => '刪除操作需要確認',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return [
            'action' => $data['action'],
            'user_ids' => array_values(array_unique($data['user_ids'])),
            'confirm' => $data['confirm'] ?? false,
        ];
    }

    /**
     * 清理輸入資料陣列
     * 
     * @param array $data
     * @return array
     */
    public function sanitizeInput(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * 驗證權限篩選條件
     * 
     * @param array $filters
     * @return array
     * @throws ValidationException
     */
    public function validatePermissionFilters(array $filters): array
    {
        $validator = Validator::make($filters, [
            'search' => 'nullable|string|max:255',
            'module' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'usage' => 'nullable|string|max:50',
            'sort_field' => 'nullable|string|in:name,module,type,display_name,created_at',
            'sort_direction' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:5|max:100',
        ], [
            'sort_field.in' => '無效的排序欄位',
            'sort_direction.in' => '無效的排序方向',
            'per_page.min' => '每頁顯示數量不能少於 5 筆',
            'per_page.max' => '每頁顯示數量不能超過 100 筆',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 清理字串類型的篩選條件
        $cleanFilters = [];
        foreach ($filters as $key => $value) {
            if (is_string($value)) {
                $cleanFilters[$key] = $this->sanitizeString($value);
            } else {
                $cleanFilters[$key] = $value;
            }
        }

        return $cleanFilters;
    }
}