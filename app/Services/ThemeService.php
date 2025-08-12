<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * 主題管理服務
 * 
 * 處理主題的載入、驗證、快取和應用邏輯
 */
class ThemeService
{
    /**
     * 預設主題
     */
    const DEFAULT_THEME = 'light';

    /**
     * 可用的內建主題
     */
    const BUILT_IN_THEMES = ['light', 'dark', 'auto'];

    /**
     * 快取鍵前綴
     */
    const CACHE_PREFIX = 'theme_';

    /**
     * 取得使用者的主題設定
     * 
     * @param User|null $user
     * @return string
     */
    public function getUserTheme(?User $user = null): string
    {
        if (!$user) {
            return self::DEFAULT_THEME;
        }

        $theme = $user->theme_preference ?? self::DEFAULT_THEME;
        
        // 驗證主題是否有效
        if (!$this->isValidTheme($theme, $user)) {
            return self::DEFAULT_THEME;
        }

        return $theme;
    }

    /**
     * 設定使用者主題
     * 
     * @param User $user
     * @param string $theme
     * @return bool
     */
    public function setUserTheme(User $user, string $theme): bool
    {
        if (!$this->isValidTheme($theme, $user)) {
            return false;
        }

        $user->update(['theme_preference' => $theme]);
        
        // 清除快取
        $this->clearUserThemeCache($user);
        
        return true;
    }

    /**
     * 取得所有可用主題
     * 
     * @param User|null $user
     * @return array
     */
    public function getAvailableThemes(?User $user = null): array
    {
        $cacheKey = self::CACHE_PREFIX . 'available_' . ($user ? $user->id : 'guest');
        
        return Cache::remember($cacheKey, 3600, function () use ($user) {
            $themes = [];
            
            // 內建主題
            foreach (self::BUILT_IN_THEMES as $theme) {
                $themes[$theme] = Config::get("themes.available.{$theme}", [
                    'name' => ucfirst($theme),
                    'icon' => $theme === 'light' ? 'sun' : ($theme === 'dark' ? 'moon' : 'computer'),
                    'description' => "Built-in {$theme} theme",
                ]);
            }
            
            // 系統自訂主題
            $customThemes = Config::get('themes.custom', []);
            foreach ($customThemes as $key => $config) {
                $themes[$key] = $config;
            }
            
            // 使用者自訂主題
            if ($user && $user->custom_themes) {
                $userThemes = is_array($user->custom_themes) 
                    ? $user->custom_themes 
                    : json_decode($user->custom_themes, true) ?? [];
                    
                foreach ($userThemes as $key => $config) {
                    $themes[$key] = $config;
                }
            }
            
            return $themes;
        });
    }

    /**
     * 驗證主題是否有效
     * 
     * @param string $theme
     * @param User|null $user
     * @return bool
     */
    public function isValidTheme(string $theme, ?User $user = null): bool
    {
        $availableThemes = $this->getAvailableThemes($user);
        return array_key_exists($theme, $availableThemes);
    }

    /**
     * 建立自訂主題
     * 
     * @param User $user
     * @param array $themeConfig
     * @return string 主題鍵值
     * @throws ValidationException
     */
    public function createCustomTheme(User $user, array $themeConfig): string
    {
        // 驗證主題配置
        $this->validateThemeConfig($themeConfig);
        
        $themeName = $themeConfig['name'];
        $themeKey = $this->generateThemeKey($themeName);
        
        // 取得現有的自訂主題
        $customThemes = $user->custom_themes ?? [];
        
        // 新增新主題
        $customThemes[$themeKey] = $themeConfig;
        
        // 儲存到資料庫
        $user->update(['custom_themes' => $customThemes]);
        
        // 清除快取
        $this->clearUserThemeCache($user);
        
        return $themeKey;
    }

    /**
     * 更新自訂主題
     * 
     * @param User $user
     * @param string $themeKey
     * @param array $themeConfig
     * @return bool
     * @throws ValidationException
     */
    public function updateCustomTheme(User $user, string $themeKey, array $themeConfig): bool
    {
        // 驗證主題配置
        $this->validateThemeConfig($themeConfig);
        
        $customThemes = $user->custom_themes ?? [];
        
        if (!isset($customThemes[$themeKey])) {
            return false;
        }
        
        // 更新主題配置
        $customThemes[$themeKey] = array_merge($customThemes[$themeKey], $themeConfig);
        
        // 儲存到資料庫
        $user->update(['custom_themes' => $customThemes]);
        
        // 清除快取
        $this->clearUserThemeCache($user);
        
        return true;
    }

    /**
     * 刪除自訂主題
     * 
     * @param User $user
     * @param string $themeKey
     * @return bool
     */
    public function deleteCustomTheme(User $user, string $themeKey): bool
    {
        $customThemes = $user->custom_themes ?? [];
        
        if (!isset($customThemes[$themeKey])) {
            return false;
        }
        
        // 如果使用者目前使用的是要刪除的主題，切換到預設主題
        if ($user->theme_preference === $themeKey) {
            $user->update(['theme_preference' => self::DEFAULT_THEME]);
        }
        
        // 移除主題
        unset($customThemes[$themeKey]);
        
        // 儲存到資料庫
        $user->update(['custom_themes' => $customThemes]);
        
        // 清除快取
        $this->clearUserThemeCache($user);
        
        return true;
    }

    /**
     * 匯出主題配置
     * 
     * @param User $user
     * @param string $themeKey
     * @return array|null
     */
    public function exportTheme(User $user, string $themeKey): ?array
    {
        $availableThemes = $this->getAvailableThemes($user);
        
        if (!isset($availableThemes[$themeKey])) {
            return null;
        }
        
        return [
            'version' => '1.0',
            'theme' => $availableThemes[$themeKey],
            'exported_at' => now()->toISOString(),
            'exported_by' => $user->name,
        ];
    }

    /**
     * 匯入主題配置
     * 
     * @param User $user
     * @param array $importData
     * @return string 主題鍵值
     * @throws ValidationException
     */
    public function importTheme(User $user, array $importData): string
    {
        // 驗證匯入資料格式
        $validator = Validator::make($importData, [
            'version' => 'required|string',
            'theme' => 'required|array',
            'theme.name' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        $themeConfig = $importData['theme'];
        
        // 驗證主題配置
        $this->validateThemeConfig($themeConfig);
        
        // 建立主題
        return $this->createCustomTheme($user, $themeConfig);
    }

    /**
     * 取得主題的 CSS 變數
     * 
     * @param string $theme
     * @param User|null $user
     * @return array
     */
    public function getThemeCSSVariables(string $theme, ?User $user = null): array
    {
        $availableThemes = $this->getAvailableThemes($user);
        
        if (!isset($availableThemes[$theme])) {
            return [];
        }
        
        $themeConfig = $availableThemes[$theme];
        $cssVariables = [];
        
        // 轉換顏色配置為 CSS 變數
        if (isset($themeConfig['colors'])) {
            foreach ($themeConfig['colors'] as $key => $value) {
                $cssVariables["--color-{$key}"] = $value;
            }
        }
        
        if (isset($themeConfig['backgrounds'])) {
            foreach ($themeConfig['backgrounds'] as $key => $value) {
                $cssVariables["--bg-{$key}"] = $value;
            }
        }
        
        if (isset($themeConfig['texts'])) {
            foreach ($themeConfig['texts'] as $key => $value) {
                $cssVariables["--text-{$key}"] = $value;
            }
        }
        
        if (isset($themeConfig['borders'])) {
            foreach ($themeConfig['borders'] as $key => $value) {
                $cssVariables["--border-{$key}"] = $value;
            }
        }
        
        return $cssVariables;
    }

    /**
     * 驗證主題配置
     * 
     * @param array $themeConfig
     * @throws ValidationException
     */
    protected function validateThemeConfig(array $themeConfig): void
    {
        $rules = Config::get('themes.validation', [
            'name' => 'required|string|max:50',
            'colors.primary' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);
        
        $validator = Validator::make($themeConfig, $rules);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * 產生主題鍵值
     * 
     * @param string $name
     * @return string
     */
    protected function generateThemeKey(string $name): string
    {
        $key = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $name));
        $key = preg_replace('/\s+/', '_', trim($key));
        
        // 確保鍵值唯一
        $originalKey = $key;
        $counter = 1;
        
        while (in_array($key, self::BUILT_IN_THEMES)) {
            $key = $originalKey . '_' . $counter;
            $counter++;
        }
        
        return $key;
    }

    /**
     * 清除使用者主題快取
     * 
     * @param User $user
     */
    protected function clearUserThemeCache(User $user): void
    {
        $keys = [
            self::CACHE_PREFIX . 'available_' . $user->id,
            self::CACHE_PREFIX . 'user_' . $user->id,
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * 清除所有主題快取
     */
    public function clearAllThemeCache(): void
    {
        Cache::flush(); // 簡單粗暴的方式，實際應用中可能需要更精確的快取管理
    }

    /**
     * 取得主題統計資訊
     * 
     * @return array
     */
    public function getThemeStatistics(): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'statistics', 3600, function () {
            $stats = [
                'total_users' => User::count(),
                'theme_usage' => [],
                'custom_themes_count' => 0,
            ];
            
            // 統計主題使用情況
            $themeUsage = User::selectRaw('theme_preference, COUNT(*) as count')
                ->groupBy('theme_preference')
                ->pluck('count', 'theme_preference')
                ->toArray();
                
            $stats['theme_usage'] = $themeUsage;
            
            // 統計自訂主題數量
            $customThemesCount = User::whereNotNull('custom_themes')
                ->where('custom_themes', '!=', '[]')
                ->count();
                
            $stats['custom_themes_count'] = $customThemesCount;
            
            return $stats;
        });
    }
}