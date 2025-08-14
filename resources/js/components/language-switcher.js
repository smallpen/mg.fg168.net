/**
 * 語言切換器 JavaScript 組件
 * 
 * 提供流暢的語言切換體驗和本地儲存功能
 */

class LanguageSwitcher {
    constructor() {
        this.currentLocale = document.documentElement.lang || 'zh_TW';
        this.supportedLocales = {
            'zh_TW': '正體中文',
            'en': 'English'
        };
        
        this.init();
    }

    /**
     * 初始化語言切換器
     */
    init() {
        this.bindEvents();
        this.loadStoredPreference();
        this.updatePageElements();
    }

    /**
     * 綁定事件監聽器
     */
    bindEvents() {
        // 監聽 Livewire 語言切換事件
        document.addEventListener('livewire:init', () => {
            Livewire.on('language-changed', (event) => {
                this.handleLanguageChange(event.locale);
            });
        });

        // 監聽鍵盤快捷鍵 (Ctrl+Shift+L)
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'L') {
                e.preventDefault();
                this.toggleLanguage();
            }
        });
    }

    /**
     * 載入儲存的語言偏好
     */
    loadStoredPreference() {
        const storedLocale = localStorage.getItem('preferred_locale');
        if (storedLocale && this.supportedLocales[storedLocale]) {
            if (storedLocale !== this.currentLocale) {
                this.switchLanguage(storedLocale);
            }
        }
    }

    /**
     * 處理語言切換
     */
    handleLanguageChange(locale) {
        this.currentLocale = locale;
        this.storePreference(locale);
        this.updatePageElements();
        this.showLanguageChangeNotification(locale);
        
        // 延遲重新載入頁面以顯示切換動畫
        setTimeout(() => {
            window.location.reload();
        }, 800);
    }

    /**
     * 切換語言
     */
    switchLanguage(locale) {
        if (!this.supportedLocales[locale]) {
            console.warn(`不支援的語言: ${locale}`);
            return;
        }

        // 觸發 Livewire 語言切換
        if (window.Livewire) {
            Livewire.dispatch('switch-language', { locale });
        } else {
            // 如果 Livewire 不可用，使用傳統方式
            window.location.href = `${window.location.pathname}?locale=${locale}`;
        }
    }

    /**
     * 切換到下一個語言
     */
    toggleLanguage() {
        const locales = Object.keys(this.supportedLocales);
        const currentIndex = locales.indexOf(this.currentLocale);
        const nextIndex = (currentIndex + 1) % locales.length;
        const nextLocale = locales[nextIndex];
        
        this.switchLanguage(nextLocale);
    }

    /**
     * 儲存語言偏好到本地儲存
     */
    storePreference(locale) {
        localStorage.setItem('preferred_locale', locale);
        
        // 同時設定 cookie 以供伺服器端使用
        document.cookie = `locale=${locale}; path=/; max-age=31536000`; // 1年
    }

    /**
     * 更新頁面元素
     */
    updatePageElements() {
        // 更新 HTML lang 屬性
        document.documentElement.lang = this.currentLocale;
        
        // 更新頁面標題中的語言指示
        this.updatePageTitle();
        
        // 更新方向性（如果需要支援 RTL 語言）
        this.updateTextDirection();
    }

    /**
     * 更新頁面標題
     */
    updatePageTitle() {
        const titleElement = document.querySelector('title');
        if (titleElement) {
            const currentTitle = titleElement.textContent;
            const languageName = this.supportedLocales[this.currentLocale];
            
            // 如果標題中沒有語言標識，添加它
            if (!currentTitle.includes(`[${languageName}]`)) {
                titleElement.textContent = `${currentTitle} [${languageName}]`;
            }
        }
    }

    /**
     * 更新文字方向
     */
    updateTextDirection() {
        // 目前支援的語言都是 LTR，但為未來擴展做準備
        const rtlLocales = []; // 如果需要支援阿拉伯語等 RTL 語言
        const isRtl = rtlLocales.includes(this.currentLocale);
        
        document.documentElement.dir = isRtl ? 'rtl' : 'ltr';
    }

    /**
     * 顯示語言切換通知
     */
    showLanguageChangeNotification(locale) {
        const languageName = this.supportedLocales[locale];
        
        // 建立通知元素
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 z-50 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300';
        notification.innerHTML = `
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                </svg>
                <span>${locale === 'zh_TW' ? '語言已切換為' : 'Language switched to'} ${languageName}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // 顯示動畫
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // 隱藏動畫
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 2000);
    }

    /**
     * 取得目前語言
     */
    getCurrentLocale() {
        return this.currentLocale;
    }

    /**
     * 取得支援的語言列表
     */
    getSupportedLocales() {
        return this.supportedLocales;
    }

    /**
     * 檢查是否支援指定語言
     */
    isLocaleSupported(locale) {
        return this.supportedLocales.hasOwnProperty(locale);
    }
}

// 初始化語言切換器
document.addEventListener('DOMContentLoaded', () => {
    window.languageSwitcher = new LanguageSwitcher();
});

// 匯出供其他模組使用
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LanguageSwitcher;
}