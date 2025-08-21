<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;

/**
 * 角色本地化輔助類別
 * 
 * 提供角色和權限的本地化名稱和描述
 */
class RoleLocalizationHelper
{
    /**
     * 取得本地化的權限名稱
     *
     * @param string $permissionName 權限名稱
     * @return string 本地化的權限名稱
     */
    public static function getPermissionDisplayName(string $permissionName): string
    {
        $translations = self::loadTranslations();
        
        if (isset($translations['permission_names'][$permissionName])) {
            return $translations['permission_names'][$permissionName];
        }
        
        // 如果翻譯不存在，回傳格式化的權限名稱
        return self::formatPermissionName($permissionName);
    }

    /**
     * 取得本地化的權限描述
     *
     * @param string $permissionName 權限名稱
     * @return string 本地化的權限描述
     */
    public static function getPermissionDescription(string $permissionName): string
    {
        $translations = self::loadTranslations();
        
        if (isset($translations['permission_descriptions'][$permissionName])) {
            return $translations['permission_descriptions'][$permissionName];
        }
        
        return '';
    }

    /**
     * 取得本地化的角色名稱
     *
     * @param string $roleName 角色名稱
     * @return string 本地化的角色名稱
     */
    public static function getRoleDisplayName(string $roleName): string
    {
        $translations = self::loadTranslations();
        
        if (isset($translations['role_names'][$roleName])) {
            return $translations['role_names'][$roleName];
        }
        
        // 嘗試從 admin.roles.names 取得
        $adminKey = "admin.roles.names.{$roleName}";
        $adminTranslated = __($adminKey);
        
        if ($adminTranslated !== $adminKey) {
            return $adminTranslated;
        }
        
        // 最後回傳格式化的角色名稱
        return self::formatRoleName($roleName);
    }

    /**
     * 取得本地化的角色描述
     *
     * @param string $roleName 角色名稱
     * @return string 本地化的角色描述
     */
    public static function getRoleDescription(string $roleName): string
    {
        $translations = self::loadTranslations();
        
        if (isset($translations['role_descriptions'][$roleName])) {
            return $translations['role_descriptions'][$roleName];
        }
        
        // 嘗試從 admin.roles.descriptions 取得
        $adminKey = "admin.roles.descriptions.{$roleName}";
        $adminTranslated = __($adminKey);
        
        if ($adminTranslated !== $adminKey) {
            return $adminTranslated;
        }
        
        return '';
    }

    /**
     * 取得本地化的模組名稱
     *
     * @param string $moduleName 模組名稱
     * @return string 本地化的模組名稱
     */
    public static function getModuleDisplayName(string $moduleName): string
    {
        $translations = self::loadTranslations();
        
        if (isset($translations['modules'][$moduleName])) {
            return $translations['modules'][$moduleName];
        }
        
        // 如果翻譯不存在，回傳格式化的模組名稱
        return self::formatModuleName($moduleName);
    }

    /**
     * 載入翻譯資料
     *
     * @return array 翻譯資料陣列
     */
    private static function loadTranslations(): array
    {
        static $translations = [];
        
        $locale = App::getLocale();
        
        // 如果該語言的翻譯尚未載入，則載入它
        if (!isset($translations[$locale])) {
            $filePath = base_path("lang/{$locale}/role_management.php");
            
            if (file_exists($filePath)) {
                $translations[$locale] = include $filePath;
            } else {
                // 回退到英文
                $fallbackPath = base_path("lang/en/role_management.php");
                if (file_exists($fallbackPath)) {
                    $translations[$locale] = include $fallbackPath;
                } else {
                    $translations[$locale] = [];
                }
            }
        }
        
        return $translations[$locale];
    }

    /**
     * 取得所有可用的權限本地化名稱
     *
     * @return array 權限名稱對應的本地化名稱陣列
     */
    public static function getAllPermissionNames(): array
    {
        $translations = self::loadTranslations();
        return $translations['permission_names'] ?? [];
    }

    /**
     * 取得所有可用的角色本地化名稱
     *
     * @return array 角色名稱對應的本地化名稱陣列
     */
    public static function getAllRoleNames(): array
    {
        $translations = self::loadTranslations();
        return $translations['role_names'] ?? [];
    }

    /**
     * 取得所有可用的模組本地化名稱
     *
     * @return array 模組名稱對應的本地化名稱陣列
     */
    public static function getAllModuleNames(): array
    {
        $translations = self::loadTranslations();
        return $translations['modules'] ?? [];
    }

    /**
     * 格式化權限名稱（當翻譯不存在時使用）
     *
     * @param string $permissionName 權限名稱
     * @return string 格式化的權限名稱
     */
    private static function formatPermissionName(string $permissionName): string
    {
        // 將 users.view 轉換為 "Users View"
        $parts = explode('.', $permissionName);
        $formatted = array_map(function ($part) {
            return ucfirst(str_replace('_', ' ', $part));
        }, $parts);
        
        return implode(' ', $formatted);
    }

    /**
     * 格式化角色名稱（當翻譯不存在時使用）
     *
     * @param string $roleName 角色名稱
     * @return string 格式化的角色名稱
     */
    private static function formatRoleName(string $roleName): string
    {
        // 將 super_admin 轉換為 "Super Admin"
        return ucwords(str_replace('_', ' ', $roleName));
    }

    /**
     * 格式化模組名稱（當翻譯不存在時使用）
     *
     * @param string $moduleName 模組名稱
     * @return string 格式化的模組名稱
     */
    private static function formatModuleName(string $moduleName): string
    {
        // 將 user_management 轉換為 "User Management"
        return ucwords(str_replace('_', ' ', $moduleName));
    }

    /**
     * 取得本地化的錯誤訊息
     *
     * @param string $errorKey 錯誤鍵值
     * @param array $parameters 參數
     * @return string 本地化的錯誤訊息
     */
    public static function getErrorMessage(string $errorKey, array $parameters = []): string
    {
        $locale = App::getLocale();
        $filePath = base_path("lang/{$locale}/role_errors.php");
        
        if (file_exists($filePath)) {
            $translations = include $filePath;
        } else {
            // 回退到英文
            $fallbackPath = base_path("lang/en/role_errors.php");
            if (file_exists($fallbackPath)) {
                $translations = include $fallbackPath;
            } else {
                return $errorKey;
            }
        }
        
        $keys = explode('.', $errorKey);
        $value = $translations;
        
        foreach ($keys as $key) {
            if (isset($value[$key])) {
                $value = $value[$key];
            } else {
                return $errorKey;
            }
        }
        
        // 替換參數
        if (is_string($value) && !empty($parameters)) {
            foreach ($parameters as $param => $replacement) {
                $value = str_replace(":{$param}", $replacement, $value);
            }
        }
        
        return $value;
    }

    /**
     * 取得本地化的成功訊息
     *
     * @param string $messageKey 訊息鍵值
     * @param array $parameters 參數
     * @return string 本地化的成功訊息
     */
    public static function getSuccessMessage(string $messageKey, array $parameters = []): string
    {
        $translations = self::loadTranslations();
        
        if (isset($translations['messages'][$messageKey])) {
            $message = $translations['messages'][$messageKey];
            
            // 替換參數
            if (!empty($parameters)) {
                foreach ($parameters as $param => $replacement) {
                    $message = str_replace(":{$param}", $replacement, $message);
                }
            }
            
            return $message;
        }
        
        return $messageKey;
    }

    /**
     * 取得本地化的驗證訊息
     *
     * @param string $validationKey 驗證鍵值
     * @param array $parameters 參數
     * @return string 本地化的驗證訊息
     */
    public static function getValidationMessage(string $validationKey, array $parameters = []): string
    {
        $translations = self::loadTranslations();
        
        if (isset($translations['validation'][$validationKey])) {
            $message = $translations['validation'][$validationKey];
            
            // 替換參數
            if (!empty($parameters)) {
                foreach ($parameters as $param => $replacement) {
                    $message = str_replace(":{$param}", $replacement, $message);
                }
            }
            
            return $message;
        }
        
        return $validationKey;
    }

    /**
     * 檢查當前語言是否為中文
     *
     * @return bool
     */
    public static function isChineseLocale(): bool
    {
        return in_array(App::getLocale(), ['zh_TW', 'zh_CN', 'zh']);
    }

    /**
     * 取得適合當前語言的日期格式
     *
     * @return string 日期格式
     */
    public static function getDateFormat(): string
    {
        if (self::isChineseLocale()) {
            return 'Y年m月d日 H:i';
        }
        
        return 'M j, Y H:i';
    }

    /**
     * 格式化日期為本地化格式
     *
     * @param \Carbon\Carbon|string $date 日期
     * @return string 格式化的日期
     */
    public static function formatDate($date): string
    {
        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }
        
        return $date->format(self::getDateFormat());
    }
}