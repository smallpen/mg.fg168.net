/**
 * 外觀設定 JavaScript 功能
 * 提供即時預覽和互動功能
 */

document.addEventListener('DOMContentLoaded', function() {
    // 顏色選擇器即時預覽
    const colorInputs = document.querySelectorAll('input[type="color"]');
    colorInputs.forEach(input => {
        input.addEventListener('input', function() {
            const settingKey = this.getAttribute('wire:model.defer');
            const value = this.value;
            
            // 觸發即時預覽
            if (window.Livewire) {
                window.Livewire.dispatch('appearance-setting-preview', {
                    key: settingKey,
                    value: value
                });
            }
            
            // 立即更新預覽區域的顏色
            updatePreviewColors(settingKey, value);
        });
    });

    // 主題選擇器即時預覽
    const themeSelect = document.querySelector('select[wire\\:model\\.defer="settings.appearance.default_theme"]');
    if (themeSelect) {
        themeSelect.addEventListener('change', function() {
            const value = this.value;
            updatePreviewTheme(value);
        });
    }

    // 響應式斷點即時預覽
    const breakpointInputs = document.querySelectorAll('input[wire\\:model\\.defer^="responsiveConfig"]');
    breakpointInputs.forEach(input => {
        input.addEventListener('input', function() {
            updateResponsivePreview();
        });
    });

    // 檔案上傳預覽
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            previewUploadedFile(this);
        });
    });
});

/**
 * 更新預覽區域的顏色
 */
function updatePreviewColors(settingKey, value) {
    const previewArea = document.querySelector('.bg-white.rounded-lg.shadow-sm');
    if (!previewArea) return;

    if (settingKey.includes('primary_color')) {
        // 更新主要顏色
        const primaryElements = previewArea.querySelectorAll('[style*="background-color"]');
        primaryElements.forEach(el => {
            if (el.style.backgroundColor.includes('#3B82F6') || el.classList.contains('bg-indigo-600')) {
                el.style.backgroundColor = value;
            }
        });

        const primaryLinks = previewArea.querySelectorAll('[style*="color"]');
        primaryLinks.forEach(el => {
            if (el.style.color.includes('#3B82F6')) {
                el.style.color = value;
            }
        });
    } else if (settingKey.includes('secondary_color')) {
        // 更新次要顏色
        const secondaryElements = previewArea.querySelectorAll('[style*="border-color"], [style*="color"]');
        secondaryElements.forEach(el => {
            if (el.style.borderColor && el.style.borderColor.includes('#6B7280')) {
                el.style.borderColor = value;
                el.style.color = value;
            }
        });
    }
}

/**
 * 更新預覽主題
 */
function updatePreviewTheme(theme) {
    const previewArea = document.querySelector('.bg-white.rounded-lg.shadow-sm');
    if (!previewArea) return;

    // 移除現有主題類別
    previewArea.classList.remove('theme-light', 'theme-dark', 'theme-auto');
    
    // 添加新主題類別
    previewArea.classList.add(`theme-${theme}`);

    // 根據主題調整顏色
    if (theme === 'dark') {
        previewArea.style.backgroundColor = '#1f2937';
        previewArea.style.color = '#f9fafb';
    } else {
        previewArea.style.backgroundColor = '#ffffff';
        previewArea.style.color = '#111827';
    }
}

/**
 * 更新響應式預覽
 */
function updateResponsivePreview() {
    const mobileBreakpoint = document.querySelector('input[wire\\:model\\.defer="responsiveConfig.mobile_breakpoint"]')?.value || 768;
    const tabletBreakpoint = document.querySelector('input[wire\\:model\\.defer="responsiveConfig.tablet_breakpoint"]')?.value || 1024;
    const desktopBreakpoint = document.querySelector('input[wire\\:model\\.defer="responsiveConfig.desktop_breakpoint"]')?.value || 1280;

    // 更新預覽資訊
    const previewInfo = document.querySelector('.mt-4.text-xs.text-gray-500');
    if (previewInfo) {
        const breakpointText = previewInfo.querySelector('p:last-child');
        if (breakpointText) {
            breakpointText.textContent = `斷點設定：手機 ≤ ${mobileBreakpoint}px，平板 ≤ ${tabletBreakpoint}px，桌面 ≥ ${desktopBreakpoint}px`;
        }
    }
}

/**
 * 預覽上傳的檔案
 */
function previewUploadedFile(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const previewContainer = input.parentNode;
            let previewImg = previewContainer.querySelector('.file-preview');
            
            if (!previewImg) {
                previewImg = document.createElement('img');
                previewImg.className = 'file-preview mt-2 h-20 object-cover rounded border';
                previewContainer.appendChild(previewImg);
            }
            
            previewImg.src = e.target.result;
            
            // 如果是 logo，同時更新預覽區域的 logo
            if (input.id === 'logo') {
                const previewLogo = document.querySelector('.bg-white.rounded-lg.shadow-sm img[alt="Logo"]');
                if (previewLogo) {
                    previewLogo.src = e.target.result;
                }
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * 自訂 CSS 即時預覽
 */
function previewCustomCSS(css) {
    // 移除現有的自訂 CSS
    const existingStyle = document.getElementById('custom-css-preview');
    if (existingStyle) {
        existingStyle.remove();
    }

    // 添加新的自訂 CSS
    if (css.trim()) {
        const style = document.createElement('style');
        style.id = 'custom-css-preview';
        style.textContent = css;
        document.head.appendChild(style);
    }
}

/**
 * 響應式裝置切換動畫
 */
function animateDeviceSwitch(device) {
    const previewFrame = document.querySelector('.bg-white.rounded-lg.shadow-sm');
    if (!previewFrame) return;

    // 添加過渡動畫
    previewFrame.style.transition = 'width 0.3s ease, max-width 0.3s ease';
    
    // 根據裝置類型調整寬度
    switch (device) {
        case 'mobile':
            previewFrame.style.width = '375px';
            break;
        case 'tablet':
            previewFrame.style.width = '768px';
            break;
        case 'desktop':
            previewFrame.style.width = '100%';
            break;
    }
}

// Livewire 事件監聽器
document.addEventListener('livewire:init', function() {
    // 監聽外觀預覽事件
    Livewire.on('appearance-preview-start', function(data) {
        console.log('外觀預覽已啟動', data);
    });

    Livewire.on('appearance-preview-stop', function() {
        // 清除自訂 CSS 預覽
        const existingStyle = document.getElementById('custom-css-preview');
        if (existingStyle) {
            existingStyle.remove();
        }
    });

    Livewire.on('appearance-preview-updated', function(data) {
        console.log('外觀預覽已更新', data);
    });

    Livewire.on('appearance-preview-device-changed', function(data) {
        animateDeviceSwitch(data.device);
    });

    Livewire.on('appearance-setting-preview', function(data) {
        if (data.key.includes('custom_css')) {
            previewCustomCSS(data.value);
        }
    });
});

// 鍵盤快捷鍵
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + P 切換預覽模式
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        const previewButton = document.querySelector('button[wire\\:click="togglePreview"]');
        if (previewButton) {
            previewButton.click();
        }
    }
    
    // Ctrl/Cmd + R 重設為預設值
    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
        e.preventDefault();
        const resetButton = document.querySelector('button[wire\\:click="resetToDefaults"]');
        if (resetButton) {
            resetButton.click();
        }
    }
});