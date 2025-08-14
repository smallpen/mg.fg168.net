<?php

namespace App\Services;

use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * 使用者友善錯誤訊息服務
 * 
 * 將技術性錯誤轉換為使用者容易理解的訊息
 */
class UserFriendlyErrorService
{
    /**
     * 取得使用者友善的錯誤訊息
     *
     * @param string $errorType 錯誤類型
     * @param string|null $specificError 具體錯誤
     * @param array $context 上下文資料
     * @return array 錯誤訊息資料
     */
    public function getFriendlyMessage(string $errorType, ?string $specificError = null, array $context = []): array
    {
        return match ($errorType) {
            'permission_error' => $this->getPermissionErrorMessage($specificError, $context),
            'validation_error' => $this->getValidationErrorMessage($specificError, $context),
            'network_error' => $this->getNetworkErrorMessage($specificError, $context),
            'database_error' => $this->getDatabaseErrorMessage($specificError, $context),
            'user_operation_error' => $this->getUserOperationErrorMessage($specificError, $context),
            'system_error' => $this->getSystemErrorMessage($specificError, $context),
            default => $this->getGenericErrorMessage($specificError, $context),
        };
    }

    /**
     * 取得權限錯誤訊息
     *
     * @param string|null $permission 權限名稱
     * @param array $context 上下文資料
     * @return array 錯誤訊息資料
     */
    private function getPermissionErrorMessage(?string $permission, array $context): array
    {
        $messages = [
            'users.view' => [
                'title' => '無法檢視使用者',
                'message' => '您沒有檢視使用者資料的權限。請聯繫系統管理員申請相關權限。',
                'icon' => 'shield-exclamation',
                'actions' => [
                    ['label' => '返回首頁', 'action' => 'redirect', 'url' => '/admin/dashboard'],
                    ['label' => '聯繫管理員', 'action' => 'contact_admin'],
                ],
            ],
            'users.create' => [
                'title' => '無法建立使用者',
                'message' => '您沒有建立新使用者的權限。此功能需要管理員權限。',
                'icon' => 'user-plus',
                'actions' => [
                    ['label' => '返回使用者列表', 'action' => 'redirect', 'url' => '/admin/users'],
                ],
            ],
            'users.edit' => [
                'title' => '無法編輯使用者',
                'message' => '您沒有編輯使用者資料的權限。只有具備相應權限的管理員才能修改使用者資料。',
                'icon' => 'pencil',
                'actions' => [
                    ['label' => '檢視使用者', 'action' => 'view_user'],
                    ['label' => '返回列表', 'action' => 'redirect', 'url' => '/admin/users'],
                ],
            ],
            'users.delete' => [
                'title' => '無法刪除使用者',
                'message' => '您沒有刪除使用者的權限。此操作需要高級管理員權限。',
                'icon' => 'trash',
                'actions' => [
                    ['label' => '返回列表', 'action' => 'redirect', 'url' => '/admin/users'],
                ],
            ],
        ];

        $defaultMessage = [
            'title' => '權限不足',
            'message' => '您沒有執行此操作的權限。請聯繫系統管理員。',
            'icon' => 'shield-exclamation',
            'actions' => [
                ['label' => '返回', 'action' => 'go_back'],
            ],
        ];

        return $messages[$permission] ?? $defaultMessage;
    }

    /**
     * 取得驗證錯誤訊息
     *
     * @param string|null $field 欄位名稱
     * @param array $context 上下文資料
     * @return array 錯誤訊息資料
     */
    private function getValidationErrorMessage(?string $field, array $context): array
    {
        $fieldMessages = [
            'username' => [
                'title' => '使用者名稱格式錯誤',
                'message' => '使用者名稱只能包含字母、數字和底線，長度為 3-20 個字元。',
                'icon' => 'user',
            ],
            'email' => [
                'title' => '電子郵件格式錯誤',
                'message' => '請輸入有效的電子郵件地址，例如：user@example.com',
                'icon' => 'envelope',
            ],
            'password' => [
                'title' => '密碼格式錯誤',
                'message' => '密碼必須至少包含 8 個字元，包括大小寫字母、數字和特殊符號。',
                'icon' => 'key',
            ],
            'name' => [
                'title' => '姓名格式錯誤',
                'message' => '姓名不能為空，且長度不能超過 50 個字元。',
                'icon' => 'user-circle',
            ],
        ];

        $defaultMessage = [
            'title' => '資料格式錯誤',
            'message' => '請檢查輸入的資料格式是否正確，並確保所有必填欄位都已填寫。',
            'icon' => 'exclamation-triangle',
        ];

        $baseMessage = $fieldMessages[$field] ?? $defaultMessage;

        return array_merge($baseMessage, [
            'actions' => [
                ['label' => '重新輸入', 'action' => 'retry'],
                ['label' => '查看說明', 'action' => 'show_help'],
            ],
        ]);
    }

    /**
     * 取得網路錯誤訊息
     *
     * @param string|null $errorType 錯誤類型
     * @param array $context 上下文資料
     * @return array 錯誤訊息資料
     */
    private function getNetworkErrorMessage(?string $errorType, array $context): array
    {
        $messages = [
            'connection_timeout' => [
                'title' => '連線逾時',
                'message' => '網路連線逾時，可能是網路速度較慢或伺服器繁忙。請稍後再試。',
                'icon' => 'clock',
            ],
            'connection_refused' => [
                'title' => '連線被拒絕',
                'message' => '無法連接到伺服器，請檢查網路連線或稍後再試。',
                'icon' => 'wifi-slash',
            ],
            'dns_error' => [
                'title' => 'DNS 解析錯誤',
                'message' => '無法解析伺服器地址，請檢查網路設定。',
                'icon' => 'globe',
            ],
        ];

        $defaultMessage = [
            'title' => '網路連線異常',
            'message' => '網路連線發生問題，請檢查您的網路設定後重試。',
            'icon' => 'wifi-slash',
        ];

        $baseMessage = $messages[$errorType] ?? $defaultMessage;

        return array_merge($baseMessage, [
            'actions' => [
                ['label' => '重試', 'action' => 'retry', 'delay' => 3000],
                ['label' => '檢查網路', 'action' => 'check_network'],
                ['label' => '重新整理頁面', 'action' => 'refresh'],
            ],
        ]);
    }

    /**
     * 取得資料庫錯誤訊息
     *
     * @param string|null $errorCode 錯誤代碼
     * @param array $context 上下文資料
     * @return array 錯誤訊息資料
     */
    private function getDatabaseErrorMessage(?string $errorCode, array $context): array
    {
        $messages = [
            '1062' => [
                'title' => '資料重複',
                'message' => '您輸入的資料已經存在，請檢查使用者名稱或電子郵件是否重複。',
                'icon' => 'duplicate',
            ],
            '1451' => [
                'title' => '無法刪除',
                'message' => '此使用者有相關的資料記錄，無法直接刪除。請先處理相關資料。',
                'icon' => 'link',
            ],
            '1452' => [
                'title' => '資料關聯錯誤',
                'message' => '資料關聯發生錯誤，請檢查相關資料是否存在。',
                'icon' => 'link-slash',
            ],
            '2002' => [
                'title' => '資料庫連線失敗',
                'message' => '無法連接到資料庫，請稍後再試。',
                'icon' => 'database',
            ],
        ];

        $defaultMessage = [
            'title' => '資料處理錯誤',
            'message' => '資料處理時發生錯誤，請稍後再試或聯繫系統管理員。',
            'icon' => 'database',
        ];

        $baseMessage = $messages[$errorCode] ?? $defaultMessage;

        return array_merge($baseMessage, [
            'actions' => [
                ['label' => '重試', 'action' => 'retry'],
                ['label' => '返回', 'action' => 'go_back'],
                ['label' => '聯繫支援', 'action' => 'contact_support'],
            ],
        ]);
    }

    /**
     * 取得使用者操作錯誤訊息
     *
     * @param string|null $operation 操作名稱
     * @param array $context 上下文資料
     * @return array 錯誤訊息資料
     */
    private function getUserOperationErrorMessage(?string $operation, array $context): array
    {
        $messages = [
            'create_user' => [
                'title' => '建立使用者失敗',
                'message' => '建立新使用者時發生錯誤，請檢查輸入的資料是否正確。',
                'icon' => 'user-plus',
            ],
            'update_user' => [
                'title' => '更新使用者失敗',
                'message' => '更新使用者資料時發生錯誤，請稍後再試。',
                'icon' => 'user-edit',
            ],
            'delete_user' => [
                'title' => '刪除使用者失敗',
                'message' => '刪除使用者時發生錯誤，可能是因為該使用者有相關的資料記錄。',
                'icon' => 'user-minus',
            ],
            'toggle_status' => [
                'title' => '狀態切換失敗',
                'message' => '切換使用者狀態時發生錯誤，請稍後再試。',
                'icon' => 'toggle-on',
            ],
            'bulk_activate' => [
                'title' => '批量啟用失敗',
                'message' => '批量啟用使用者時發生錯誤，部分使用者可能未能成功啟用。',
                'icon' => 'users',
            ],
            'bulk_deactivate' => [
                'title' => '批量停用失敗',
                'message' => '批量停用使用者時發生錯誤，部分使用者可能未能成功停用。',
                'icon' => 'users-slash',
            ],
        ];

        $defaultMessage = [
            'title' => '操作失敗',
            'message' => '執行操作時發生錯誤，請稍後再試。',
            'icon' => 'exclamation-circle',
        ];

        $baseMessage = $messages[$operation] ?? $defaultMessage;

        return array_merge($baseMessage, [
            'actions' => [
                ['label' => '重試', 'action' => 'retry'],
                ['label' => '返回列表', 'action' => 'redirect', 'url' => '/admin/users'],
            ],
        ]);
    }

    /**
     * 取得系統錯誤訊息
     *
     * @param string|null $errorType 錯誤類型
     * @param array $context 上下文資料
     * @return array 錯誤訊息資料
     */
    private function getSystemErrorMessage(?string $errorType, array $context): array
    {
        return [
            'title' => '系統錯誤',
            'message' => '系統發生未預期的錯誤，我們已記錄此問題並會盡快修復。請稍後再試或聯繫系統管理員。',
            'icon' => 'exclamation-triangle',
            'actions' => [
                ['label' => '重新整理', 'action' => 'refresh'],
                ['label' => '返回首頁', 'action' => 'redirect', 'url' => '/admin/dashboard'],
                ['label' => '聯繫支援', 'action' => 'contact_support'],
            ],
        ];
    }

    /**
     * 取得通用錯誤訊息
     *
     * @param string|null $error 錯誤訊息
     * @param array $context 上下文資料
     * @return array 錯誤訊息資料
     */
    private function getGenericErrorMessage(?string $error, array $context): array
    {
        return [
            'title' => '發生錯誤',
            'message' => $error ?? '發生未知錯誤，請稍後再試。',
            'icon' => 'exclamation-circle',
            'actions' => [
                ['label' => '重試', 'action' => 'retry'],
                ['label' => '返回', 'action' => 'go_back'],
            ],
        ];
    }

    /**
     * 格式化驗證錯誤訊息
     *
     * @param ValidationException $exception 驗證例外
     * @return array 格式化的錯誤訊息
     */
    public function formatValidationErrors(ValidationException $exception): array
    {
        $errors = [];
        
        foreach ($exception->errors() as $field => $messages) {
            $friendlyFieldName = $this->getFriendlyFieldName($field);
            $errors[$field] = [
                'field' => $friendlyFieldName,
                'messages' => $messages,
                'first_message' => $messages[0] ?? '',
            ];
        }

        return [
            'title' => '資料驗證失敗',
            'message' => '請修正以下錯誤後重新提交：',
            'icon' => 'exclamation-triangle',
            'errors' => $errors,
            'actions' => [
                ['label' => '重新輸入', 'action' => 'retry'],
            ],
        ];
    }

    /**
     * 取得友善的欄位名稱
     *
     * @param string $field 欄位名稱
     * @return string 友善的欄位名稱
     */
    private function getFriendlyFieldName(string $field): string
    {
        $fieldNames = [
            'username' => '使用者名稱',
            'email' => '電子郵件',
            'password' => '密碼',
            'password_confirmation' => '確認密碼',
            'name' => '姓名',
            'first_name' => '名字',
            'last_name' => '姓氏',
            'phone' => '電話號碼',
            'role' => '角色',
            'status' => '狀態',
            'is_active' => '啟用狀態',
        ];

        return $fieldNames[$field] ?? $field;
    }

    /**
     * 取得錯誤嚴重程度的顏色和圖示
     *
     * @param string $severity 嚴重程度
     * @return array 顏色和圖示資訊
     */
    public function getSeverityStyle(string $severity): array
    {
        return match ($severity) {
            'low' => [
                'color' => 'blue',
                'bg_color' => 'bg-blue-50',
                'text_color' => 'text-blue-800',
                'border_color' => 'border-blue-200',
                'icon_color' => 'text-blue-400',
            ],
            'medium' => [
                'color' => 'yellow',
                'bg_color' => 'bg-yellow-50',
                'text_color' => 'text-yellow-800',
                'border_color' => 'border-yellow-200',
                'icon_color' => 'text-yellow-400',
            ],
            'high' => [
                'color' => 'red',
                'bg_color' => 'bg-red-50',
                'text_color' => 'text-red-800',
                'border_color' => 'border-red-200',
                'icon_color' => 'text-red-400',
            ],
            'critical' => [
                'color' => 'red',
                'bg_color' => 'bg-red-100',
                'text_color' => 'text-red-900',
                'border_color' => 'border-red-300',
                'icon_color' => 'text-red-500',
            ],
            default => [
                'color' => 'gray',
                'bg_color' => 'bg-gray-50',
                'text_color' => 'text-gray-800',
                'border_color' => 'border-gray-200',
                'icon_color' => 'text-gray-400',
            ],
        };
    }
}