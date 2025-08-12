<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

/**
 * 語言選擇器元件
 * 
 * 提供多語言切換功能，支援即時語言切換和使用者偏好設定儲存
 */
class LanguageSelector extends Component
{
    /**
     * 當前選中的語言
     */
    public string $currentLocale;
    
    /**
     * 是否顯示下拉選單
     */
    public bool $isOpen = false;
    
    /**
     * 支援的語言列表
     */
    public array $availableLocales = [
        'zh_TW' => [
            'name' => '正體中文',
            'flag' => '🇹🇼',
            'code' => 'zh_TW'
        ],
        'en' => [
            'name' => 'English',
            'flag' => '🇺🇸',
            'code' => 'en'
        ]
    ];
    
    /**
     * 元件初始化
     */
    public function mount(): void
    {
        $this->currentLocale = App::getLocale();
    }
    
    /**
     * 取得當前語言資訊
     */
    public function getCurrentLanguageProperty(): array
    {
        return $this->availableLocales[$this->currentLocale] ?? $this->availableLocales['zh_TW'];
    }
    
    /**
     * 切換下拉選單顯示狀態
     */
    public function toggleDropdown(): void
    {
        $this->isOpen = !$this->isOpen;
    }
    
    /**
     * 關閉下拉選單
     */
    public function closeDropdown(): void
    {
        $this->isOpen = false;
    }
    
    /**
     * 切換語言
     */
    public function switchLanguage(string $locale): void
    {
        // 驗證語言是否支援
        if (!array_key_exists($locale, $this->availableLocales)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => __('admin.language.unsupported')
            ]);
            return;
        }
        
        // 設定應用程式語言
        App::setLocale($locale);
        
        // 儲存到 Session
        Session::put('locale', $locale);
        
        // 如果使用者已登入，儲存到使用者偏好設定
        if (Auth::check()) {
            $user = Auth::user();
            $user->update(['locale' => $locale]);
        }
        
        // 更新當前語言
        $this->currentLocale = $locale;
        
        // 關閉下拉選單
        $this->isOpen = false;
        
        // 觸發語言變更事件
        $this->dispatch('locale-changed', locale: $locale);
        
        // 顯示成功訊息
        $languageName = $this->availableLocales[$locale]['name'];
        $this->dispatch('toast', [
            'type' => 'success',
            'message' => __('admin.language.switched', ['language' => $languageName])
        ]);
        
        // 重新載入頁面以應用新語言
        $this->redirect(request()->url());
    }
    
    /**
     * 取得語言選項列表（排除當前語言）
     */
    public function getLanguageOptionsProperty(): array
    {
        return array_filter(
            $this->availableLocales,
            fn($locale, $code) => $code !== $this->currentLocale,
            ARRAY_FILTER_USE_BOTH
        );
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.language-selector');
    }
}