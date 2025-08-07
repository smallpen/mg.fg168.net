<?php

namespace App\Http\Livewire\Admin\Layout;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

/**
 * 主題切換元件
 * 
 * 處理淺色和暗黑主題的切換功能
 */
class ThemeToggle extends Component
{
    /**
     * 當前主題
     * 
     * @var string
     */
    public $currentTheme;

    /**
     * 元件掛載時執行
     * 
     * @return void
     */
    public function mount()
    {
        // 從使用者偏好設定或預設值載入主題
        $this->currentTheme = Auth::user()?->theme_preference ?? 'light';
    }

    /**
     * 切換主題
     * 
     * @return void
     */
    public function toggleTheme()
    {
        // 切換主題狀態
        $this->currentTheme = $this->currentTheme === 'light' ? 'dark' : 'light';
        
        // 如果使用者已登入，儲存主題偏好設定
        if (Auth::check()) {
            Auth::user()->update([
                'theme_preference' => $this->currentTheme
            ]);
        }

        // 發送事件通知其他元件主題已變更
        $this->dispatch('theme-changed', ['theme' => $this->currentTheme]);
        
        // 更新頁面的 HTML class
        $this->dispatch('update-theme-class', ['theme' => $this->currentTheme]);
    }

    /**
     * 設定特定主題
     * 
     * @param string $theme
     * @return void
     */
    public function setTheme($theme)
    {
        if (!in_array($theme, ['light', 'dark'])) {
            return;
        }

        $this->currentTheme = $theme;
        
        // 如果使用者已登入，儲存主題偏好設定
        if (Auth::check()) {
            Auth::user()->update([
                'theme_preference' => $this->currentTheme
            ]);
        }

        // 發送事件通知其他元件主題已變更
        $this->dispatch('theme-changed', ['theme' => $this->currentTheme]);
        
        // 更新頁面的 HTML class
        $this->dispatch('update-theme-class', ['theme' => $this->currentTheme]);
    }

    /**
     * 檢查是否為暗黑主題
     * 
     * @return bool
     */
    public function isDarkTheme()
    {
        return $this->currentTheme === 'dark';
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