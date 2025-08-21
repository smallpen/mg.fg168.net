<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * 系統設定 Facade
 *
 * @method static \Illuminate\Support\Collection getCategories()
 * @method static \Illuminate\Support\Collection getSettingsByCategory(string $category)
 * @method static \Illuminate\Support\Collection getAllSettings()
 * @method static array|null getSettingConfig(string $key)
 * @method static mixed getDefaultValue(string $key)
 * @method static string getValidationRules(string $key)
 * @method static string getSettingType(string $key)
 * @method static array getSettingOptions(string $key)
 * @method static bool isEncrypted(string $key)
 * @method static bool isPreviewable(string $key)
 * @method static array getDependencies(string $key)
 * @method static array getDependentSettings(string $key)
 * @method static array validateSetting(string $key, mixed $value, array $allSettings = [])
 * @method static bool areDependenciesSatisfied(string $key, array $allSettings)
 * @method static array getInputComponent(string $settingType)
 * @method static array getTestableSettings()
 * @method static string|null getTestGroup(string $key)
 * @method static string getDisplayValue(string $key, mixed $value)
 * @method static array getSearchKeywords(string $key)
 * @method static mixed formatValueForStorage(string $key, mixed $value)
 * @method static mixed formatValueForDisplay(string $key, mixed $value)
 *
 * @see \App\Helpers\SystemSettingsHelper
 */
class SystemSettings extends Facade
{
    /**
     * 取得 facade 的註冊名稱
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'system-settings';
    }
}