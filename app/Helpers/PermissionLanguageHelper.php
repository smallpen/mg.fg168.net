<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;

class PermissionLanguageHelper
{
    /**
     * 取得權限管理相關的翻譯
     * Get permission management related translations
     *
     * @param string $key 翻譯鍵值 / Translation key
     * @param array $replace 替換參數 / Replace parameters
     * @param string|null $locale 語言環境 / Locale
     * @return string
     */
    public static function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        return __($key, $replace, $locale);
    }

    /**
     * 取得權限相關翻譯
     * Get permission related translations
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    public static function permission(string $key, array $replace = [], ?string $locale = null): string
    {
        return __("permissions.{$key}", $replace, $locale);
    }

    /**
     * 取得權限錯誤訊息翻譯
     * Get permission error message translations
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    public static function error(string $key, array $replace = [], ?string $locale = null): string
    {
        return __("permission_errors.{$key}", $replace, $locale);
    }

    /**
     * 取得權限成功訊息翻譯
     * Get permission success message translations
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    public static function message(string $key, array $replace = [], ?string $locale = null): string
    {
        return __("permission_messages.{$key}", $replace, $locale);
    }

    /**
     * 取得權限驗證訊息翻譯
     * Get permission validation message translations
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    public static function validation(string $key, array $replace = [], ?string $locale = null): string
    {
        return __("permission_validation.{$key}", $replace, $locale);
    }

    /**
     * 取得權限 UI 翻譯
     * Get permission UI translations
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    public static function ui(string $key, array $replace = [], ?string $locale = null): string
    {
        return __("permission_ui.{$key}", $replace, $locale);
    }

    /**
     * 取得權限類型翻譯
     * Get permission type translations
     *
     * @param string $type
     * @param string|null $locale
     * @return string
     */
    public static function type(string $type, ?string $locale = null): string
    {
        return __("permissions.types.{$type}", [], $locale);
    }

    /**
     * 取得模組翻譯
     * Get module translations
     *
     * @param string $module
     * @param string|null $locale
     * @return string
     */
    public static function module(string $module, ?string $locale = null): string
    {
        return __("permissions.modules.{$module}", [], $locale);
    }

    /**
     * 取得狀態翻譯
     * Get status translations
     *
     * @param string $status
     * @param string|null $locale
     * @return string
     */
    public static function status(string $status, ?string $locale = null): string
    {
        return __("permissions.status.{$status}", [], $locale);
    }

    /**
     * 取得所有可用的權限類型
     * Get all available permission types
     *
     * @param string|null $locale
     * @return array
     */
    public static function getAllTypes(?string $locale = null): array
    {
        $types = ['view', 'create', 'edit', 'delete', 'manage', 'admin', 'system'];
        $result = [];

        foreach ($types as $type) {
            $result[$type] = self::type($type, $locale);
        }

        return $result;
    }

    /**
     * 取得所有可用的模組
     * Get all available modules
     *
     * @param string|null $locale
     * @return array
     */
    public static function getAllModules(?string $locale = null): array
    {
        $modules = ['users', 'roles', 'permissions', 'dashboard', 'settings', 'reports', 'audit', 'system'];
        $result = [];

        foreach ($modules as $module) {
            $result[$module] = self::module($module, $locale);
        }

        return $result;
    }

    /**
     * 取得所有可用的狀態
     * Get all available statuses
     *
     * @param string|null $locale
     * @return array
     */
    public static function getAllStatuses(?string $locale = null): array
    {
        $statuses = ['active', 'inactive', 'system', 'used', 'unused', 'deprecated'];
        $result = [];

        foreach ($statuses as $status) {
            $result[$status] = self::status($status, $locale);
        }

        return $result;
    }

    /**
     * 檢查當前語言環境是否為中文
     * Check if current locale is Chinese
     *
     * @return bool
     */
    public static function isChineseLocale(): bool
    {
        return in_array(App::getLocale(), ['zh', 'zh_TW', 'zh_CN']);
    }

    /**
     * 取得本地化的權限名稱格式說明
     * Get localized permission name format description
     *
     * @param string|null $locale
     * @return string
     */
    public static function getPermissionNameFormatHelp(?string $locale = null): string
    {
        return self::ui('tooltips.permission_name_help', [], $locale);
    }

    /**
     * 取得本地化的日期時間格式
     * Get localized datetime format
     *
     * @param \Carbon\Carbon|string $datetime
     * @param string|null $locale
     * @return string
     */
    public static function formatDateTime($datetime, ?string $locale = null): string
    {
        if (!$datetime) {
            return '-';
        }

        $carbon = $datetime instanceof \Carbon\Carbon ? $datetime : \Carbon\Carbon::parse($datetime);
        
        if (self::isChineseLocale()) {
            return $carbon->format('Y年m月d日 H:i:s');
        }

        return $carbon->format('M d, Y H:i:s');
    }

    /**
     * 取得本地化的數字格式
     * Get localized number format
     *
     * @param int|float $number
     * @param string|null $locale
     * @return string
     */
    public static function formatNumber($number, ?string $locale = null): string
    {
        if (self::isChineseLocale()) {
            return number_format($number, 0, '.', ',');
        }

        return number_format($number);
    }
}