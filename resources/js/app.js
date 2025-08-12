import './bootstrap';
import Alpine from 'alpinejs';
import './touch-gestures';
import './lazy-loading';
import './service-worker-manager';
import './animations/interactive-animations';
import './animations/gesture-handler';

// 讓 Livewire 處理 Alpine.js 初始化
window.Alpine = Alpine;

// 效能優化指令
Alpine.directive('lazy-img', (el, { expression }) => {
    el.setAttribute('data-src', expression);
    el.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMSIgaGVpZ2h0PSIxIiB2aWV3Qm94PSIwIDAgMSAxIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxIiBoZWlnaHQ9IjEiIGZpbGw9InRyYW5zcGFyZW50Ii8+PC9zdmc+';
});

Alpine.directive('lazy-component', (el, { expression }) => {
    el.setAttribute('data-lazy-component', expression);
});

// 主題切換功能
document.addEventListener('DOMContentLoaded', function() {
    // 從 localStorage 載入主題設定
    const theme = localStorage.getItem('theme') || 'light';
    document.documentElement.classList.toggle('dark', theme === 'dark');
    
    // 主題切換按鈕事件監聽
    document.addEventListener('click', function(e) {
        if (e.target.matches('[data-theme-toggle]')) {
            const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.classList.toggle('dark', newTheme === 'dark');
            localStorage.setItem('theme', newTheme);
            
            // 觸發 Livewire 事件更新伺服器端主題設定
            if (window.Livewire) {
                window.Livewire.dispatch('themeChanged', { theme: newTheme });
            }
        }
    });
});

// 響應式佈局支援
document.addEventListener('DOMContentLoaded', function() {
    // 監聽視窗大小變化以支援響應式佈局
    function handleResize() {
        const isMobile = window.innerWidth < 1024;
        
        // 發送事件給 Livewire 元件
        if (window.Livewire) {
            window.Livewire.dispatch('setMobileMode', { isMobile: isMobile });
        }
    }
    
    // 初始化和監聽視窗大小變化
    handleResize();
    window.addEventListener('resize', handleResize);
});

// 通知自動隱藏功能
document.addEventListener('DOMContentLoaded', function() {
    const notifications = document.querySelectorAll('[data-auto-hide]');
    notifications.forEach(notification => {
        const delay = parseInt(notification.dataset.autoHide) || 5000;
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, delay);
    });
});

// 確認對話框功能
window.confirmAction = function(message, callback) {
    if (confirm(message)) {
        if (typeof callback === 'function') {
            callback();
        }
        return true;
    }
    return false;
};

// 表單驗證增強
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500');
                    isValid = false;
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('請填寫所有必填欄位');
            }
        });
    });
});

// 搜尋功能增強
window.initSearch = function(inputSelector, targetSelector) {
    const searchInput = document.querySelector(inputSelector);
    const searchTargets = document.querySelectorAll(targetSelector);
    
    if (searchInput && searchTargets.length > 0) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            searchTargets.forEach(target => {
                const text = target.textContent.toLowerCase();
                const shouldShow = text.includes(searchTerm);
                target.style.display = shouldShow ? '' : 'none';
            });
        });
    }
};

// 複製到剪貼簿功能
window.copyToClipboard = function(text) {
    navigator.clipboard.writeText(text).then(function() {
        // 顯示成功訊息
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-md shadow-lg z-50';
        notification.textContent = '已複製到剪貼簿';
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 2000);
    }).catch(function(err) {
        console.error('複製失敗:', err);
    });
};

// 圖片預覽功能
window.previewImage = function(input, previewSelector) {
    const file = input.files[0];
    const preview = document.querySelector(previewSelector);
    
    if (file && preview) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
};

// 載入狀態管理
window.showLoading = function(element) {
    if (element) {
        element.disabled = true;
        element.innerHTML = '<span class="loading-spinner"></span> 載入中...';
    }
};

window.hideLoading = function(element, originalText) {
    if (element) {
        element.disabled = false;
        element.innerHTML = originalText;
    }
};

// 效能優化初始化
document.addEventListener('DOMContentLoaded', function() {
    // 預載入關鍵資源
    if (window.lazyLoader) {
        window.lazyLoader.preloadCriticalResources();
    }
    
    // 初始化效能監控
    if ('performance' in window && 'PerformanceObserver' in window) {
        // 監控 LCP (Largest Contentful Paint)
        try {
            new PerformanceObserver((entryList) => {
                for (const entry of entryList.getEntries()) {
                    console.log('LCP:', entry.startTime);
                    // 可以發送到分析服務
                    if (window.gtag) {
                        window.gtag('event', 'LCP', {
                            value: Math.round(entry.startTime),
                            custom_parameter: 'admin_layout'
                        });
                    }
                }
            }).observe({ entryTypes: ['largest-contentful-paint'] });
        } catch (e) {
            console.warn('LCP 監控不支援:', e);
        }
        
        // 監控 FID (First Input Delay)
        try {
            new PerformanceObserver((entryList) => {
                for (const entry of entryList.getEntries()) {
                    const fid = entry.processingStart - entry.startTime;
                    console.log('FID:', fid);
                    if (window.gtag) {
                        window.gtag('event', 'FID', {
                            value: Math.round(fid),
                            custom_parameter: 'admin_layout'
                        });
                    }
                }
            }).observe({ entryTypes: ['first-input'] });
        } catch (e) {
            console.warn('FID 監控不支援:', e);
        }
        
        // 監控 CLS (Cumulative Layout Shift)
        try {
            new PerformanceObserver((entryList) => {
                for (const entry of entryList.getEntries()) {
                    if (!entry.hadRecentInput) {
                        console.log('CLS:', entry.value);
                        if (window.gtag) {
                            window.gtag('event', 'CLS', {
                                value: Math.round(entry.value * 1000),
                                custom_parameter: 'admin_layout'
                            });
                        }
                    }
                }
            }).observe({ entryTypes: ['layout-shift'] });
        } catch (e) {
            console.warn('CLS 監控不支援:', e);
        }
    }
    
    // 資源載入優化
    if ('requestIdleCallback' in window) {
        requestIdleCallback(() => {
            // 在瀏覽器空閒時預載入非關鍵資源
            const nonCriticalImages = document.querySelectorAll('img[data-preload="idle"]');
            nonCriticalImages.forEach(img => {
                if (img.dataset.src && window.lazyLoader) {
                    window.lazyLoader.preloadResource(img.dataset.src, 'image');
                }
            });
        });
    }
});