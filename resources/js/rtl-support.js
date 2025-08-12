/**
 * RTL 語言支援 JavaScript 工具
 */

class RTLSupport {
    constructor() {
        this.isRTL = document.documentElement.dir === 'rtl';
        this.init();
    }

    /**
     * 初始化 RTL 支援
     */
    init() {
        this.setupBodyClass();
        this.setupEventListeners();
        this.adjustScrollbars();
        this.adjustAnimations();
    }

    /**
     * 設定 body 類別
     */
    setupBodyClass() {
        document.body.classList.toggle('rtl', this.isRTL);
        document.body.classList.toggle('ltr', !this.isRTL);
    }

    /**
     * 設定事件監聽器
     */
    setupEventListeners() {
        // 監聽語言變更事件
        document.addEventListener('locale-changed', (event) => {
            this.handleLocaleChange(event.detail.locale);
        });

        // 監聽 DOM 變更
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'dir') {
                    this.isRTL = document.documentElement.dir === 'rtl';
                    this.setupBodyClass();
                    this.adjustScrollbars();
                    this.adjustAnimations();
                }
            });
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['dir']
        });
    }

    /**
     * 處理語言變更
     */
    handleLocaleChange(locale) {
        const rtlLocales = ['ar', 'he', 'fa', 'ur'];
        const shouldBeRTL = rtlLocales.includes(locale);
        
        if (shouldBeRTL !== this.isRTL) {
            document.documentElement.dir = shouldBeRTL ? 'rtl' : 'ltr';
            this.isRTL = shouldBeRTL;
            this.setupBodyClass();
            this.adjustScrollbars();
            this.adjustAnimations();
        }
    }

    /**
     * 調整捲軸
     */
    adjustScrollbars() {
        // 對於某些瀏覽器，需要調整捲軸位置
        const scrollableElements = document.querySelectorAll('.scrollable, .overflow-auto, .overflow-x-auto');
        
        scrollableElements.forEach(element => {
            if (this.isRTL) {
                element.classList.add('rtl-scroll');
            } else {
                element.classList.remove('rtl-scroll');
            }
        });
    }

    /**
     * 調整動畫
     */
    adjustAnimations() {
        const animatedElements = document.querySelectorAll('[data-animation]');
        
        animatedElements.forEach(element => {
            const animation = element.dataset.animation;
            
            if (this.isRTL) {
                // 反轉動畫方向
                switch (animation) {
                    case 'slideInLeft':
                        element.style.animationName = 'slideInRight';
                        break;
                    case 'slideInRight':
                        element.style.animationName = 'slideInLeft';
                        break;
                    case 'slideOutLeft':
                        element.style.animationName = 'slideOutRight';
                        break;
                    case 'slideOutRight':
                        element.style.animationName = 'slideOutLeft';
                        break;
                }
            } else {
                element.style.animationName = animation;
            }
        });
    }

    /**
     * 取得方向性的值
     */
    getDirectionalValue(ltrValue, rtlValue) {
        return this.isRTL ? rtlValue : ltrValue;
    }

    /**
     * 取得開始位置（左或右）
     */
    getStartPosition() {
        return this.isRTL ? 'right' : 'left';
    }

    /**
     * 取得結束位置（右或左）
     */
    getEndPosition() {
        return this.isRTL ? 'left' : 'right';
    }

    /**
     * 調整元素的方向性樣式
     */
    adjustElementStyles(element, styles) {
        Object.keys(styles).forEach(property => {
            const value = styles[property];
            
            if (typeof value === 'object' && value.ltr && value.rtl) {
                element.style[property] = this.getDirectionalValue(value.ltr, value.rtl);
            } else {
                element.style[property] = value;
            }
        });
    }

    /**
     * 翻轉圖示
     */
    flipIcon(iconElement) {
        if (this.isRTL) {
            iconElement.classList.add('rtl-flip');
        } else {
            iconElement.classList.remove('rtl-flip');
        }
    }

    /**
     * 調整下拉選單位置
     */
    adjustDropdownPosition(dropdown, trigger) {
        const rect = trigger.getBoundingClientRect();
        const dropdownRect = dropdown.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        
        if (this.isRTL) {
            // RTL 模式下，優先從右側對齊
            if (rect.right - dropdownRect.width >= 0) {
                dropdown.style.right = '0';
                dropdown.style.left = 'auto';
            } else {
                dropdown.style.left = '0';
                dropdown.style.right = 'auto';
            }
        } else {
            // LTR 模式下，優先從左側對齊
            if (rect.left + dropdownRect.width <= viewportWidth) {
                dropdown.style.left = '0';
                dropdown.style.right = 'auto';
            } else {
                dropdown.style.right = '0';
                dropdown.style.left = 'auto';
            }
        }
    }

    /**
     * 調整工具提示位置
     */
    adjustTooltipPosition(tooltip, target) {
        const targetRect = target.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        
        // 重設位置
        tooltip.style.left = 'auto';
        tooltip.style.right = 'auto';
        
        if (this.isRTL) {
            const rightSpace = viewportWidth - targetRect.right;
            const leftSpace = targetRect.left;
            
            if (rightSpace >= tooltipRect.width / 2) {
                tooltip.style.right = rightSpace + 'px';
            } else if (leftSpace >= tooltipRect.width / 2) {
                tooltip.style.left = leftSpace + 'px';
            } else {
                tooltip.style.right = '10px';
            }
        } else {
            const leftSpace = targetRect.left;
            const rightSpace = viewportWidth - targetRect.right;
            
            if (leftSpace >= tooltipRect.width / 2) {
                tooltip.style.left = leftSpace + 'px';
            } else if (rightSpace >= tooltipRect.width / 2) {
                tooltip.style.right = rightSpace + 'px';
            } else {
                tooltip.style.left = '10px';
            }
        }
    }

    /**
     * 處理文字輸入方向
     */
    handleTextDirection(inputElement) {
        // 自動檢測文字方向
        inputElement.addEventListener('input', (e) => {
            const text = e.target.value;
            const rtlPattern = /[\u0590-\u05FF\u0600-\u06FF\u0750-\u077F]/;
            
            if (rtlPattern.test(text)) {
                e.target.style.direction = 'rtl';
                e.target.style.textAlign = 'right';
            } else {
                e.target.style.direction = 'ltr';
                e.target.style.textAlign = 'left';
            }
        });
    }

    /**
     * 格式化數字（保持 LTR）
     */
    formatNumber(number, locale = 'en-US') {
        const formatted = new Intl.NumberFormat(locale).format(number);
        
        // 在 RTL 環境中，數字應該保持 LTR 方向
        if (this.isRTL) {
            return `<span dir="ltr" style="unicode-bidi: embed;">${formatted}</span>`;
        }
        
        return formatted;
    }

    /**
     * 處理混合文字內容
     */
    handleMixedContent(element) {
        const text = element.textContent;
        const rtlPattern = /[\u0590-\u05FF\u0600-\u06FF\u0750-\u077F]/;
        const ltrPattern = /[A-Za-z0-9]/;
        
        if (rtlPattern.test(text) && ltrPattern.test(text)) {
            element.style.unicodeBidi = 'embed';
            element.classList.add('mixed-content');
        }
    }

    /**
     * 初始化表格的 RTL 支援
     */
    initTableRTL() {
        const tables = document.querySelectorAll('table');
        
        tables.forEach(table => {
            if (this.isRTL) {
                table.classList.add('rtl-table');
                
                // 調整表格標題的對齊
                const headers = table.querySelectorAll('th');
                headers.forEach(header => {
                    if (!header.style.textAlign) {
                        header.style.textAlign = 'right';
                    }
                });
                
                // 調整表格內容的對齊
                const cells = table.querySelectorAll('td');
                cells.forEach(cell => {
                    if (!cell.style.textAlign) {
                        cell.style.textAlign = 'right';
                    }
                });
            }
        });
    }

    /**
     * 處理鍵盤導航
     */
    handleKeyboardNavigation(event) {
        if (this.isRTL) {
            // 在 RTL 模式下反轉左右箭頭鍵
            switch (event.key) {
                case 'ArrowLeft':
                    // 觸發右箭頭的行為
                    event.preventDefault();
                    this.triggerArrowKey('ArrowRight', event.target);
                    break;
                case 'ArrowRight':
                    // 觸發左箭頭的行為
                    event.preventDefault();
                    this.triggerArrowKey('ArrowLeft', event.target);
                    break;
            }
        }
    }

    /**
     * 觸發箭頭鍵事件
     */
    triggerArrowKey(key, target) {
        const event = new KeyboardEvent('keydown', {
            key: key,
            code: key,
            bubbles: true,
            cancelable: true
        });
        
        target.dispatchEvent(event);
    }
}

// 初始化 RTL 支援
document.addEventListener('DOMContentLoaded', () => {
    window.rtlSupport = new RTLSupport();
});

// 匯出供其他模組使用
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RTLSupport;
}