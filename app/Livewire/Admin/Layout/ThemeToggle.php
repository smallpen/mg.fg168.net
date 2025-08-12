<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;

/**
 * 主題切換元件
 * 
 * 處理亮色、暗色和自動主題的切換功能
 * 支援系統主題檢測和使用者偏好儲存
 */
class ThemeToggle extends Component
{
    /**
     * 當前主題
     * 
     * @var string
     */
    public string $currentTheme = 'light';

    /**
     * 可用的主題選項
     * 
     * @var array
     */
    public array $availableThemes = ['light', 'dark', 'auto'];

    /**
     * 自訂主題設定
     * 
     * @var array
     */
    public array $customThemes = [];

    /**
     * 當前預覽的主題
     * 
     * @var string|null
     */
    public ?string $previewTheme = null;

    /**
     * 是否啟用自動主題模式
     * 
     * @var bool
     */
    public bool $autoTheme = false;

    /**
     * 是否正在過渡中
     * 
     * @var bool
     */
    public bool $isTransitioning = false;

    /**
     * 是否正在載入主題
     * 
     * @var bool
     */
    public bool $isLoading = false;

    /**
     * 元件掛載時執行
     * 
     * @return void
     */
    public function mount()
    {
        // 從使用者偏好設定或預設值載入主題
        $this->currentTheme = Auth::user()?->theme_preference ?? 'light';
        
        // 載入自訂主題設定
        $this->loadCustomThemes();
        
        // 確保主題值有效
        $allThemes = array_merge($this->availableThemes, array_keys($this->customThemes));
        if (!in_array($this->currentTheme, $allThemes)) {
            $this->currentTheme = 'light';
        }

        // 檢查是否啟用自動主題
        $this->autoTheme = $this->currentTheme === 'auto';
    }

    /**
     * 切換主題（循環切換）
     * 
     * @return void
     */
    public function toggleTheme()
    {
        $this->isLoading = true;
        
        // 循環切換主題：light -> dark -> auto -> light
        $currentIndex = array_search($this->currentTheme, $this->availableThemes);
        $nextIndex = ($currentIndex + 1) % count($this->availableThemes);
        $this->currentTheme = $this->availableThemes[$nextIndex];
        
        $this->applyTheme($this->currentTheme);
        $this->saveThemePreference($this->currentTheme);
        
        $this->isLoading = false;
    }

    /**
     * 設定特定主題
     * 
     * @param string $theme
     * @return void
     */
    public function setTheme(string $theme): void
    {
        $allThemes = array_merge($this->availableThemes, array_keys($this->customThemes));
        if (!in_array($theme, $allThemes)) {
            return;
        }

        $this->isLoading = true;
        $this->isTransitioning = true;
        $this->currentTheme = $theme;
        
        // 如果是自訂主題，應用自訂主題設定
        if (isset($this->customThemes[$theme])) {
            $this->applyCustomTheme($this->customThemes[$theme]);
        } else {
            $this->applyTheme($theme);
        }
        
        $this->saveThemePreference($theme);
        
        // 延遲重置載入狀態，讓動畫完成
        $this->dispatch('theme-transition-complete');
        
        $this->isLoading = false;
        $this->isTransitioning = false;
    }

    /**
     * 取得主題圖示
     * 
     * @return string
     */
    public function getThemeIconProperty(): string
    {
        return match($this->currentTheme) {
            'light' => 'sun',
            'dark' => 'moon',
            'auto' => 'computer',
            default => 'sun'
        };
    }

    /**
     * 取得主題名稱
     * 
     * @return string
     */
    public function getThemeNameProperty(): string
    {
        return match($this->currentTheme) {
            'light' => '亮色主題',
            'dark' => '暗色主題',
            'auto' => '自動模式',
            default => '亮色主題'
        };
    }

    /**
     * 檢查是否為暗色主題
     * 
     * @return bool
     */
    public function isDarkTheme(): bool
    {
        if ($this->currentTheme === 'dark') {
            return true;
        }
        
        if ($this->currentTheme === 'auto') {
            return $this->detectSystemTheme() === 'dark';
        }
        
        return false;
    }

    /**
     * 檢查是否為自動模式
     * 
     * @return bool
     */
    public function isAutoMode(): bool
    {
        return $this->currentTheme === 'auto';
    }

    /**
     * 檢測系統主題偏好
     * 
     * @return string
     */
    public function detectSystemTheme(): string
    {
        // 這個方法主要在前端 JavaScript 中實作
        // 後端只提供預設值
        return 'light';
    }

    /**
     * 應用主題設定
     * 
     * @param string $theme
     * @return void
     */
    protected function applyTheme(string $theme): void
    {
        // 發送事件通知其他元件主題已變更
        $this->dispatch('theme-changed', theme: $theme);
        
        // 更新頁面的 HTML 屬性
        $this->dispatch('update-theme-attribute', theme: $theme);
        
        // 如果是自動模式，檢測系統主題
        if ($theme === 'auto') {
            $this->dispatch('detect-system-theme');
        }
    }

    /**
     * 儲存主題偏好設定
     * 
     * @param string $theme
     * @return void
     */
    protected function saveThemePreference(string $theme): void
    {
        // 如果使用者已登入，儲存主題偏好設定到資料庫
        if (Auth::check()) {
            try {
                Auth::user()->update([
                    'theme_preference' => $theme
                ]);
            } catch (\Exception $e) {
                // 記錄錯誤但不中斷使用者體驗
                logger()->error('Failed to save theme preference: ' . $e->getMessage());
            }
        }
        
        // 同時儲存到瀏覽器 localStorage（透過前端 JavaScript）
        $this->dispatch('save-theme-to-storage', theme: $theme);
    }

    /**
     * 處理系統主題變更事件
     * 
     * @param string $systemTheme
     * @return void
     */
    #[On('system-theme-changed')]
    public function handleSystemThemeChange(string $systemTheme): void
    {
        // 只有在自動模式下才響應系統主題變更
        if ($this->currentTheme === 'auto') {
            $this->dispatch('update-theme-attribute', theme: $systemTheme);
        }
    }

    /**
     * 重新載入主題設定
     * 
     * @return void
     */
    public function refreshTheme(): void
    {
        $this->mount();
        $this->applyTheme($this->currentTheme);
    }

    /**
     * 處理全域主題切換事件
     * 
     * @return void
     */
    #[On('toggle-theme-global')]
    public function handleGlobalToggle(): void
    {
        $this->toggleTheme();
    }

    /**
     * 處理全域主題設定事件
     * 
     * @param string $theme
     * @return void
     */
    #[On('set-theme-global')]
    public function handleGlobalSetTheme(string $theme): void
    {
        $this->setTheme($theme);
    }

    /**
     * 處理鍵盤快捷鍵切換事件
     * 
     * @return void
     */
    #[On('toggle-theme-shortcut')]
    public function handleShortcutToggle(): void
    {
        $this->toggleTheme();
    }

    /**
     * 同步本地存儲的主題到後端
     * 
     * @param string $theme
     * @return void
     */
    #[On('sync-theme-from-storage')]
    public function syncThemeFromStorage(string $theme): void
    {
        if (in_array($theme, $this->availableThemes)) {
            $this->currentTheme = $theme;
            $this->saveThemePreference($theme);
        }
    }

    /**
     * 載入自訂主題設定
     * 
     * @return void
     */
    protected function loadCustomThemes(): void
    {
        // 從設定檔或資料庫載入自訂主題
        $this->customThemes = config('themes.custom', []);
        
        // 如果使用者有自訂主題設定，合併進來
        if (Auth::check() && Auth::user()->custom_themes) {
            $userCustomThemes = json_decode(Auth::user()->custom_themes, true) ?? [];
            $this->customThemes = array_merge($this->customThemes, $userCustomThemes);
        }
    }

    /**
     * 設定自訂主題
     * 
     * @param array $themeConfig
     * @return void
     */
    public function setCustomTheme(array $themeConfig): void
    {
        if (!isset($themeConfig['name']) || !isset($themeConfig['colors'])) {
            return;
        }

        $themeName = $themeConfig['name'];
        $this->customThemes[$themeName] = $themeConfig;
        
        // 儲存自訂主題到使用者設定
        if (Auth::check()) {
            Auth::user()->update([
                'custom_themes' => json_encode($this->customThemes)
            ]);
        }

        // 應用自訂主題
        $this->currentTheme = $themeName;
        $this->applyCustomTheme($themeConfig);
        
        $this->dispatch('custom-theme-applied', theme: $themeName, config: $themeConfig);
    }

    /**
     * 應用自訂主題
     * 
     * @param array $themeConfig
     * @return void
     */
    protected function applyCustomTheme(array $themeConfig): void
    {
        $this->dispatch('apply-custom-theme', config: $themeConfig);
    }

    /**
     * 預覽主題
     * 
     * @param string $theme
     * @return void
     */
    public function previewTheme(string $theme): void
    {
        $this->previewTheme = $theme;
        $this->dispatch('preview-theme', theme: $theme);
    }

    /**
     * 取消預覽主題
     * 
     * @return void
     */
    public function cancelPreview(): void
    {
        $this->previewTheme = null;
        $this->dispatch('cancel-theme-preview');
        $this->applyTheme($this->currentTheme);
    }

    /**
     * 確認預覽主題
     * 
     * @return void
     */
    public function confirmPreview(): void
    {
        if ($this->previewTheme) {
            $this->setTheme($this->previewTheme);
            $this->previewTheme = null;
        }
    }

    /**
     * 啟用自動主題模式
     * 
     * @return void
     */
    public function enableAutoTheme(): void
    {
        $this->autoTheme = true;
        $this->setTheme('auto');
    }

    /**
     * 停用自動主題模式
     * 
     * @return void
     */
    public function disableAutoTheme(): void
    {
        $this->autoTheme = false;
        // 根據當前系統主題設定為亮色或暗色
        $systemTheme = $this->detectSystemTheme();
        $this->setTheme($systemTheme);
    }

    /**
     * 處理鍵盤快捷鍵
     * 
     * @param string $shortcut
     * @return void
     */
    public function handleKeyboardShortcut(string $shortcut): void
    {
        switch ($shortcut) {
            case 'ctrl+shift+t':
                $this->toggleTheme();
                break;
            case 'ctrl+shift+l':
                $this->setTheme('light');
                break;
            case 'ctrl+shift+d':
                $this->setTheme('dark');
                break;
            case 'ctrl+shift+a':
                $this->setTheme('auto');
                break;
        }
    }

    /**
     * 檢查主題相容性
     * 
     * @return void
     */
    public function checkThemeCompatibility(): void
    {
        $compatibility = [
            'css_variables' => $this->supportsCSSVariables(),
            'dark_mode' => $this->supportsDarkMode(),
            'animations' => $this->supportsAnimations(),
            'local_storage' => $this->supportsLocalStorage(),
        ];

        $this->dispatch('theme-compatibility-checked', compatibility: $compatibility);
    }

    /**
     * 檢查是否支援 CSS 變數
     * 
     * @return bool
     */
    protected function supportsCSSVariables(): bool
    {
        // 在實際應用中，這會透過 JavaScript 檢測
        return true;
    }

    /**
     * 檢查是否支援暗色模式
     * 
     * @return bool
     */
    protected function supportsDarkMode(): bool
    {
        // 在實際應用中，這會透過 JavaScript 檢測
        return true;
    }

    /**
     * 檢查是否支援動畫
     * 
     * @return bool
     */
    protected function supportsAnimations(): bool
    {
        // 在實際應用中，這會透過 JavaScript 檢測
        return true;
    }

    /**
     * 檢查是否支援本地存儲
     * 
     * @return bool
     */
    protected function supportsLocalStorage(): bool
    {
        // 在實際應用中，這會透過 JavaScript 檢測
        return true;
    }

    /**
     * 渲染元件
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.admin.layout.theme-toggle');
    }
}