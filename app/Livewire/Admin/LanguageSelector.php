<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

/**
 * 語言選擇器元件
 * 
 * 提供語言切換功能，支援正體中文和英文
 * 包含優化的使用者體驗：視覺回饋、載入動畫、確認機制
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
     * 語言切換狀態
     */
    public bool $isChanging = false;
    
    /**
     * 顯示確認對話框
     */
    public bool $showConfirmation = false;
    
    /**
     * 待切換的語言
     */
    public string $pendingLocale = '';
    
    /**
     * 語言切換成功狀態
     */
    public bool $switchSuccess = false;
    
    /**
     * 元件初始化
     */
    public function mount(): void
    {
        $this->currentLocale = App::getLocale();
    }
    
    /**
     * 初始化語言切換（顯示確認對話框）
     *
     * @param string $locale 要切換的語言代碼
     */
    public function initiateLanguageSwitch(string $locale)
    {
        // 如果是相同語言，不需要切換
        if ($locale === $this->currentLocale) {
            return;
        }
        
        // 驗證語言代碼是否支援
        if (!array_key_exists($locale, $this->supportedLocales)) {
            $this->dispatch('language-error', [
                'message' => __('admin.language.unsupported')
            ]);
            return;
        }
        
        // 設定待切換的語言並顯示確認對話框
        $this->pendingLocale = $locale;
        $this->showConfirmation = true;
        
        // 發送確認事件到前端
        $this->dispatch('language-switch-confirmation', [
            'from' => $this->supportedLocales[$this->currentLocale],
            'to' => $this->supportedLocales[$locale],
            'locale' => $locale
        ]);
    }
    
    /**
     * 確認語言切換
     */
    public function confirmLanguageSwitch()
    {
        if (empty($this->pendingLocale)) {
            return;
        }
        
        $this->switchLanguage($this->pendingLocale);
    }
    
    /**
     * 取消語言切換
     */
    public function cancelLanguageSwitch()
    {
        $this->showConfirmation = false;
        $this->pendingLocale = '';
        $this->dispatch('language-switch-cancelled');
    }
    
    /**
     * 切換語言（內部方法）
     *
     * @param string $locale 要切換的語言代碼
     */
    private function switchLanguage(string $locale)
    {
        $this->isChanging = true;
        $this->showConfirmation = false;
        
        try {
            // 記錄語言切換開始
            Log::info('Language switch initiated', [
                'user_id' => auth()->id(),
                'from_locale' => $this->currentLocale,
                'to_locale' => $locale,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
            
            // 設定應用程式語言
            App::setLocale($locale);
            $this->currentLocale = $locale;
            
            // 設定 Carbon 本地化
            \Carbon\Carbon::setLocale($locale);
            
            // 儲存語言偏好到 session（立即生效）
            Session::put('locale', $locale);
            Session::save(); // 強制儲存 session
            
            // 如果使用者已登入，更新使用者的語言偏好
            if (auth()->check()) {
                $user = auth()->user();
                $user->update([
                    'locale' => $locale,
                    'locale_updated_at' => now()
                ]);
                
                // 快取使用者語言偏好
                Cache::put("user_locale_{$user->id}", $locale, 3600);
            }
            
            // 設定成功狀態
            $this->switchSuccess = true;
            $this->pendingLocale = '';
            
            // 發送成功事件
            $languageName = $this->supportedLocales[$locale];
            $this->dispatch('language-switched', [
                'locale' => $locale,
                'language' => $languageName,
                'message' => __('admin.language.switched', ['language' => $languageName])
            ]);
            
            // 記錄語言切換成功
            Log::info('Language switch completed', [
                'user_id' => auth()->id(),
                'locale' => $locale,
                'success' => true
            ]);
            
        } catch (\Exception $e) {
            $this->isChanging = false;
            $this->switchSuccess = false;
            
            // 記錄錯誤
            Log::error('Language switch failed', [
                'user_id' => auth()->id(),
                'locale' => $locale,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 發送錯誤事件
            $this->dispatch('language-error', [
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
        $this->initiateLanguageSwitch($locale);
    }
    
    /**
     * 監聽來自 JavaScript 的直接切換請求（跳過確認）
     */
    #[On('switch-language-direct')]
    public function handleDirectLanguageSwitch($locale)
    {
        $this->switchLanguage($locale);
    }
    
    /**
     * 重置元件狀態
     */
    public function resetState()
    {
        $this->isChanging = false;
        $this->showConfirmation = false;
        $this->pendingLocale = '';
        $this->switchSuccess = false;
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
