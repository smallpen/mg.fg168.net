/**
 * 圖片和元件延遲載入模組
 * 
 * 提供圖片延遲載入、元件懶載入和資源預載入功能
 */

class LazyLoadingManager {
    constructor() {
        this.imageObserver = null;
        this.componentObserver = null;
        this.preloadQueue = [];
        this.loadedImages = new Set();
        this.loadedComponents = new Set();
        
        this.init();
    }

    /**
     * 初始化延遲載入管理器
     */
    init() {
        if ('IntersectionObserver' in window) {
            this.setupImageObserver();
            this.setupComponentObserver();
            this.observeExistingElements();
        } else {
            // 降級處理：直接載入所有資源
            this.fallbackLoading();
        }

        // 監聽 DOM 變更以處理動態新增的元素
        this.setupMutationObserver();
        
        // 預載入關鍵資源
        this.preloadCriticalResources();
    }

    /**
     * 設定圖片觀察器
     */
    setupImageObserver() {
        const options = {
            root: null,
            rootMargin: '50px',
            threshold: 0.1
        };

        this.imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadImage(entry.target);
                    this.imageObserver.unobserve(entry.target);
                }
            });
        }, options);
    }

    /**
     * 設定元件觀察器
     */
    setupComponentObserver() {
        const options = {
            root: null,
            rootMargin: '100px',
            threshold: 0.1
        };

        this.componentObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadComponent(entry.target);
                    this.componentObserver.unobserve(entry.target);
                }
            });
        }, options);
    }

    /**
     * 觀察現有元素
     */
    observeExistingElements() {
        // 觀察延遲載入圖片
        document.querySelectorAll('img[data-src]').forEach(img => {
            this.imageObserver.observe(img);
        });

        // 觀察延遲載入元件
        document.querySelectorAll('[data-lazy-component]').forEach(component => {
            this.componentObserver.observe(component);
        });
    }

    /**
     * 載入圖片
     */
    loadImage(img) {
        const src = img.dataset.src;
        const srcset = img.dataset.srcset;
        
        if (!src || this.loadedImages.has(src)) {
            return;
        }

        // 顯示載入動畫
        img.classList.add('loading');

        // 建立新圖片物件進行預載入
        const imageLoader = new Image();
        
        imageLoader.onload = () => {
            // 載入成功後更新 src
            img.src = src;
            if (srcset) {
                img.srcset = srcset;
            }
            
            // 移除載入狀態
            img.classList.remove('loading');
            img.classList.add('loaded');
            
            // 觸發載入完成事件
            img.dispatchEvent(new CustomEvent('imageLoaded', {
                detail: { src, element: img }
            }));
            
            this.loadedImages.add(src);
        };

        imageLoader.onerror = () => {
            // 載入失敗處理
            img.classList.remove('loading');
            img.classList.add('error');
            
            // 設定錯誤圖片
            if (img.dataset.fallback) {
                img.src = img.dataset.fallback;
            }
            
            console.warn('圖片載入失敗:', src);
        };

        // 開始載入
        imageLoader.src = src;
        if (srcset) {
            imageLoader.srcset = srcset;
        }
    }

    /**
     * 載入 Livewire 元件
     */
    loadComponent(element) {
        const componentName = element.dataset.lazyComponent;
        
        if (!componentName || this.loadedComponents.has(componentName)) {
            return;
        }

        // 顯示載入狀態
        element.classList.add('component-loading');

        // 取得元件屬性
        const attributes = this.extractComponentAttributes(element);

        // 使用 Livewire 載入元件
        if (window.Livewire) {
            window.Livewire.start();
            
            // 建立 Livewire 元件
            const componentHtml = `<livewire:${componentName} ${attributes} />`;
            
            // 替換佔位符內容
            fetch('/admin/components/render', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    component: componentName,
                    attributes: this.parseAttributes(attributes)
                })
            })
            .then(response => response.text())
            .then(html => {
                element.innerHTML = html;
                element.classList.remove('component-loading');
                element.classList.add('component-loaded');
                
                // 重新初始化 Livewire
                if (window.Livewire) {
                    window.Livewire.rescan();
                }
                
                this.loadedComponents.add(componentName);
                
                // 觸發元件載入完成事件
                element.dispatchEvent(new CustomEvent('componentLoaded', {
                    detail: { component: componentName, element }
                }));
            })
            .catch(error => {
                console.error('元件載入失敗:', error);
                element.classList.remove('component-loading');
                element.classList.add('component-error');
            });
        }
    }

    /**
     * 提取元件屬性
     */
    extractComponentAttributes(element) {
        const attributes = [];
        
        Array.from(element.attributes).forEach(attr => {
            if (attr.name.startsWith('data-') && attr.name !== 'data-lazy-component') {
                const propName = attr.name.replace('data-', '').replace(/-/g, '_');
                attributes.push(`${propName}="${attr.value}"`);
            }
        });
        
        return attributes.join(' ');
    }

    /**
     * 解析屬性字串為物件
     */
    parseAttributes(attributeString) {
        const attributes = {};
        const regex = /(\w+)="([^"]*)"/g;
        let match;
        
        while ((match = regex.exec(attributeString)) !== null) {
            attributes[match[1]] = match[2];
        }
        
        return attributes;
    }

    /**
     * 設定 DOM 變更觀察器
     */
    setupMutationObserver() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        // 檢查新增的圖片
                        if (node.matches && node.matches('img[data-src]')) {
                            this.imageObserver.observe(node);
                        }
                        
                        // 檢查新增的元件
                        if (node.matches && node.matches('[data-lazy-component]')) {
                            this.componentObserver.observe(node);
                        }
                        
                        // 檢查子元素
                        const lazyImages = node.querySelectorAll && node.querySelectorAll('img[data-src]');
                        const lazyComponents = node.querySelectorAll && node.querySelectorAll('[data-lazy-component]');
                        
                        if (lazyImages) {
                            lazyImages.forEach(img => this.imageObserver.observe(img));
                        }
                        
                        if (lazyComponents) {
                            lazyComponents.forEach(component => this.componentObserver.observe(component));
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * 預載入關鍵資源
     */
    preloadCriticalResources() {
        // 動態獲取 Vite 建置的檔案名稱
        const manifest = this.getViteManifest();
        if (manifest) {
            // 預載入關鍵 CSS
            const appCss = manifest['resources/css/app.css']?.file;
            const adminCss = manifest['resources/css/admin-layout.css']?.file;
            
            if (appCss) this.preloadResource(`/build/${appCss}`, 'style');
            if (adminCss) this.preloadResource(`/build/${adminCss}`, 'style');
            
            // 預載入關鍵 JS
            const appJs = manifest['resources/js/app.js']?.file;
            const adminJs = manifest['resources/js/admin-layout.js']?.file;
            
            if (appJs) this.preloadResource(`/build/${appJs}`, 'script');
            if (adminJs) this.preloadResource(`/build/${adminJs}`, 'script');
        }
        
        // 預載入關鍵圖片
        const criticalImages = document.querySelectorAll('img[data-critical]');
        criticalImages.forEach(img => {
            if (img.dataset.src) {
                this.preloadResource(img.dataset.src, 'image');
            }
        });
    }

    /**
     * 獲取 Vite manifest
     */
    getViteManifest() {
        try {
            // 嘗試從全域變數獲取（如果有的話）
            if (window.viteManifest) {
                return window.viteManifest;
            }
            
            // 或者返回 null，讓預載入功能優雅降級
            return null;
        } catch (error) {
            console.warn('無法獲取 Vite manifest:', error);
            return null;
        }
    }

    /**
     * 預載入資源
     */
    preloadResource(href, as) {
        if (this.preloadQueue.includes(href)) {
            return;
        }

        const link = document.createElement('link');
        link.rel = 'preload';
        link.href = href;
        link.as = as;
        
        if (as === 'style') {
            link.onload = () => {
                link.rel = 'stylesheet';
            };
        }
        
        document.head.appendChild(link);
        this.preloadQueue.push(href);
    }

    /**
     * 降級處理：直接載入所有資源
     */
    fallbackLoading() {
        // 載入所有延遲圖片
        document.querySelectorAll('img[data-src]').forEach(img => {
            img.src = img.dataset.src;
            if (img.dataset.srcset) {
                img.srcset = img.dataset.srcset;
            }
        });

        // 載入所有延遲元件
        document.querySelectorAll('[data-lazy-component]').forEach(element => {
            this.loadComponent(element);
        });
    }

    /**
     * 手動觸發載入
     */
    loadAll() {
        document.querySelectorAll('img[data-src]').forEach(img => {
            this.loadImage(img);
        });

        document.querySelectorAll('[data-lazy-component]').forEach(component => {
            this.loadComponent(component);
        });
    }

    /**
     * 取得載入統計
     */
    getStats() {
        return {
            loadedImages: this.loadedImages.size,
            loadedComponents: this.loadedComponents.size,
            preloadedResources: this.preloadQueue.length,
            observerSupported: 'IntersectionObserver' in window
        };
    }

    /**
     * 清理資源
     */
    destroy() {
        if (this.imageObserver) {
            this.imageObserver.disconnect();
        }
        
        if (this.componentObserver) {
            this.componentObserver.disconnect();
        }
        
        this.loadedImages.clear();
        this.loadedComponents.clear();
        this.preloadQueue = [];
    }
}

// 全域實例
window.LazyLoadingManager = LazyLoadingManager;

// 自動初始化
document.addEventListener('DOMContentLoaded', () => {
    window.lazyLoader = new LazyLoadingManager();
});

// Alpine.js 整合
document.addEventListener('alpine:init', () => {
    Alpine.directive('lazy-img', (el, { expression }) => {
        el.setAttribute('data-src', expression);
        el.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMSIgaGVpZ2h0PSIxIiB2aWV3Qm94PSIwIDAgMSAxIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxIiBoZWlnaHQ9IjEiIGZpbGw9InRyYW5zcGFyZW50Ii8+PC9zdmc+';
        
        if (window.lazyLoader && window.lazyLoader.imageObserver) {
            window.lazyLoader.imageObserver.observe(el);
        }
    });
});

export default LazyLoadingManager;