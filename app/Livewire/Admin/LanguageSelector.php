<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

/**
 * 語言選擇器元件
 * 
 * 提供語言切換功能，支援正體中文和英文
 */
class LanguageSelector extends Component
{
    /**
     * 目前選擇的語言
     */
    public string $currentLocale;
    
    /**
     * 支援的語言列表
     */
    public array $supportedLocales = [
        'zh_TW' => '正體中文',
        'en' => 'English',
    ];
    
    /**
     * 元件初始化
     */
    public function mount(): void
    {
        $this->currentLocale = App::getLocale();
    }
    
    /**
     * 切換語言
     *
     * @param string $locale 要切換的語言代碼
     */
    public function switchLanguage(string $locale)
    {
        // 驗證語言代碼是否支援
        if (!array_key_exists($locale, $this->supportedLocales)) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('admin.language.unsupported')
            ]);
            return;
        }
        
        try {
            // 設定應用程式語言
            App::setLocale($locale);
            $this->currentLocale = $locale;
            
            // 設定 Carbon 本地化
            \Carbon\Carbon::setLocale($locale);
            
            // 儲存語言偏好到 session
            Session::put('locale', $locale);
            
            // 如果使用者已登入，更新使用者的語言偏好
            if (auth()->check()) {
                auth()->user()->update(['locale' => $locale]);
            }
            
            // 顯示成功訊息
            $languageName = $this->supportedLocales[$locale];
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => __('admin.language.switched', ['language' => $languageName])
            ]);
            
            // 發送語言切換事件（這會觸發頁面重新載入）
            $this->dispatch('language-changed', locale: $locale);
            
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('admin.messages.error.update_failed', ['item' => __('admin.language.title')])
            ]);
        }
    }

    /**
     * 監聽來自 JavaScript 的語言切換請求
     */
    #[On('switch-language')]
    public function handleLanguageSwitch($locale)
    {
        $this->switchLanguage($locale);
    }
    
    /**
     * 取得語言顯示名稱
     *
     * @param string $locale 語言代碼
     * @return string
     */
    public function getLanguageName(string $locale): string
    {
        return $this->supportedLocales[$locale] ?? $locale;
    }
    
    /**
     * 檢查是否為目前語言
     *
     * @param string $locale 語言代碼
     * @return bool
     */
    public function isCurrentLanguage(string $locale): bool
    {
        return $this->currentLocale === $locale;
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.language-selector');
    }
}
