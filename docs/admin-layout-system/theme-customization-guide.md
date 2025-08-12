# 主題自訂指南

## 概述

管理後台主題系統基於 CSS 變數架構，支援亮色主題、暗色主題和自動模式。開發者可以輕鬆自訂主題顏色、建立新主題，或修改現有主題的視覺效果。

## 主題架構

### CSS 變數系統

主題系統使用 CSS 自訂屬性（CSS Variables）來管理顏色和樣式，確保主題切換的即時性和一致性。

```css
:root {
  /* 主要顏色 */
  --color-primary: #3B82F6;
  --color-primary-dark: #2563EB;
  --color-primary-light: #60A5FA;
  
  /* 次要顏色 */
  --color-secondary: #6B7280;
  --color-secondary-dark: #4B5563;
  --color-secondary-light: #9CA3AF;
  
  /* 狀態顏色 */
  --color-success: #10B981;
  --color-warning: #F59E0B;
  --color-danger: #EF4444;
  --color-info: #3B82F6;
  
  /* 背景顏色 */
  --bg-primary: #FFFFFF;
  --bg-secondary: #F9FAFB;
  --bg-tertiary: #F3F4F6;
  --bg-accent: #EFF6FF;
  
  /* 文字顏色 */
  --text-primary: #111827;
  --text-secondary: #6B7280;
  --text-tertiary: #9CA3AF;
  --text-inverse: #FFFFFF;
  
  /* 邊框顏色 */
  --border-primary: #E5E7EB;
  --border-secondary: #D1D5DB;
  --border-accent: #3B82F6;
  
  /* 陰影 */
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
  --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
  
  /* 圓角 */
  --radius-sm: 0.25rem;
  --radius-md: 0.375rem;
  --radius-lg: 0.5rem;
  --radius-xl: 0.75rem;
  
  /* 間距 */
  --spacing-xs: 0.25rem;
  --spacing-sm: 0.5rem;
  --spacing-md: 1rem;
  --spacing-lg: 1.5rem;
  --spacing-xl: 2rem;
}
```

### 暗色主題

```css
[data-theme="dark"] {
  /* 主要顏色 */
  --color-primary: #60A5FA;
  --color-primary-dark: #3B82F6;
  --color-primary-light: #93C5FD;
  
  /* 背景顏色 */
  --bg-primary: #111827;
  --bg-secondary: #1F2937;
  --bg-tertiary: #374151;
  --bg-accent: #1E3A8A;
  
  /* 文字顏色 */
  --text-primary: #F9FAFB;
  --text-secondary: #D1D5DB;
  --text-tertiary: #9CA3AF;
  --text-inverse: #111827;
  
  /* 邊框顏色 */
  --border-primary: #374151;
  --border-secondary: #4B5563;
  --border-accent: #60A5FA;
  
  /* 陰影 */
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.3);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.4);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.4);
  --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.4);
}
```

## 建立自訂主題

### 1. 定義新主題

建立新的主題檔案 `resources/css/themes/custom-theme.css`：

```css
[data-theme="custom"] {
  /* 自訂主色調 */
  --color-primary: #8B5CF6;
  --color-primary-dark: #7C3AED;
  --color-primary-light: #A78BFA;
  
  /* 自訂背景 */
  --bg-primary: #FEFEFE;
  --bg-secondary: #F8FAFC;
  --bg-tertiary: #F1F5F9;
  --bg-accent: #F3E8FF;
  
  /* 自訂文字顏色 */
  --text-primary: #0F172A;
  --text-secondary: #475569;
  --text-tertiary: #64748B;
  
  /* 自訂邊框 */
  --border-primary: #E2E8F0;
  --border-secondary: #CBD5E1;
  --border-accent: #8B5CF6;
  
  /* 自訂圓角 */
  --radius-sm: 0.5rem;
  --radius-md: 0.75rem;
  --radius-lg: 1rem;
  --radius-xl: 1.25rem;
}
```

### 2. 註冊新主題

在 `config/themes.php` 中註冊新主題：

```php
<?php

return [
    'available_themes' => [
        'light' => [
            'name' => '亮色主題',
            'icon' => 'sun',
            'css_file' => null, // 使用預設樣式
        ],
        'dark' => [
            'name' => '暗色主題',
            'icon' => 'moon',
            'css_file' => null, // 使用預設樣式
        ],
        'custom' => [
            'name' => '自訂主題',
            'icon' => 'palette',
            'css_file' => 'themes/custom-theme.css',
        ],
    ],
    
    'default_theme' => 'light',
    'auto_detect_system_theme' => true,
    'theme_transition_duration' => 300, // 毫秒
];
```

### 3. 更新主題切換元件

修改 `ThemeToggle.php` 以支援新主題：

```php
class ThemeToggle extends Component
{
    public string $currentTheme = 'light';
    
    public function getAvailableThemesProperty(): array
    {
        return config('themes.available_themes', []);
    }
    
    public function setTheme(string $theme): void
    {
        if (!array_key_exists($theme, $this->availableThemes)) {
            return;
        }
        
        $this->currentTheme = $theme;
        $this->saveThemePreference($theme);
        $this->dispatch('theme-changed', $theme);
    }
}
```

## 元件主題化

### 按鈕元件主題化

```css
.btn {
  /* 基礎樣式 */
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  font-weight: 500;
  transition: all 0.2s ease;
  
  /* 主要按鈕 */
  &.btn-primary {
    background-color: var(--color-primary);
    color: var(--text-inverse);
    border: 1px solid var(--color-primary);
    
    &:hover {
      background-color: var(--color-primary-dark);
      border-color: var(--color-primary-dark);
    }
    
    &:focus {
      box-shadow: 0 0 0 3px rgb(var(--color-primary) / 0.3);
    }
  }
  
  /* 次要按鈕 */
  &.btn-secondary {
    background-color: var(--bg-secondary);
    color: var(--text-primary);
    border: 1px solid var(--border-primary);
    
    &:hover {
      background-color: var(--bg-tertiary);
    }
  }
}
```

### 卡片元件主題化

```css
.card {
  background-color: var(--bg-primary);
  border: 1px solid var(--border-primary);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  
  .card-header {
    padding: var(--spacing-lg);
    border-bottom: 1px solid var(--border-primary);
    background-color: var(--bg-secondary);
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
  }
  
  .card-body {
    padding: var(--spacing-lg);
  }
  
  .card-footer {
    padding: var(--spacing-lg);
    border-top: 1px solid var(--border-primary);
    background-color: var(--bg-secondary);
    border-radius: 0 0 var(--radius-lg) var(--radius-lg);
  }
}
```

## 動態主題切換

### JavaScript 主題控制器

```javascript
class ThemeController {
    constructor() {
        this.currentTheme = this.getStoredTheme() || 'light';
        this.systemTheme = this.getSystemTheme();
        this.init();
    }
    
    init() {
        this.applyTheme(this.currentTheme);
        this.setupEventListeners();
        this.watchSystemTheme();
    }
    
    applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        this.loadThemeCSS(theme);
        this.updateThemeIcon(theme);
    }
    
    loadThemeCSS(theme) {
        const themeConfig = window.themeConfig?.available_themes?.[theme];
        if (themeConfig?.css_file) {
            this.loadCSS(`/css/${themeConfig.css_file}`);
        }
    }
    
    loadCSS(href) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        document.head.appendChild(link);
    }
    
    setTheme(theme) {
        this.currentTheme = theme;
        this.applyTheme(theme);
        this.storeTheme(theme);
        this.dispatchThemeChange(theme);
    }
    
    toggleTheme() {
        const themes = Object.keys(window.themeConfig?.available_themes || {});
        const currentIndex = themes.indexOf(this.currentTheme);
        const nextIndex = (currentIndex + 1) % themes.length;
        this.setTheme(themes[nextIndex]);
    }
    
    getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    
    watchSystemTheme() {
        window.matchMedia('(prefers-color-scheme: dark)')
            .addEventListener('change', (e) => {
                this.systemTheme = e.matches ? 'dark' : 'light';
                if (this.currentTheme === 'auto') {
                    this.applyTheme(this.systemTheme);
                }
            });
    }
    
    storeTheme(theme) {
        localStorage.setItem('admin-theme', theme);
    }
    
    getStoredTheme() {
        return localStorage.getItem('admin-theme');
    }
    
    dispatchThemeChange(theme) {
        window.dispatchEvent(new CustomEvent('theme-changed', {
            detail: { theme, systemTheme: this.systemTheme }
        }));
    }
}

// 初始化主題控制器
window.themeController = new ThemeController();
```

## 主題動畫效果

### 平滑過渡動畫

```css
/* 全域過渡效果 */
* {
  transition: 
    background-color var(--theme-transition-duration, 300ms) ease,
    color var(--theme-transition-duration, 300ms) ease,
    border-color var(--theme-transition-duration, 300ms) ease,
    box-shadow var(--theme-transition-duration, 300ms) ease;
}

/* 主題切換動畫 */
.theme-transition {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* 淡入淡出效果 */
@keyframes theme-fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.theme-fade-in {
  animation: theme-fade-in 0.3s ease-in-out;
}
```

### 主題切換按鈕動畫

```css
.theme-toggle {
  position: relative;
  overflow: hidden;
  
  .theme-icon {
    transition: transform 0.3s ease, opacity 0.3s ease;
  }
  
  &[data-theme="light"] .sun-icon {
    transform: rotate(0deg) scale(1);
    opacity: 1;
  }
  
  &[data-theme="light"] .moon-icon {
    transform: rotate(180deg) scale(0);
    opacity: 0;
  }
  
  &[data-theme="dark"] .sun-icon {
    transform: rotate(-180deg) scale(0);
    opacity: 0;
  }
  
  &[data-theme="dark"] .moon-icon {
    transform: rotate(0deg) scale(1);
    opacity: 1;
  }
}
```

## 主題測試

### 視覺回歸測試

```php
// tests/Feature/ThemeTest.php
class ThemeTest extends TestCase
{
    /** @test */
    public function it_can_switch_themes()
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
            ->livewire(ThemeToggle::class)
            ->call('setTheme', 'dark')
            ->assertDispatched('theme-changed', 'dark');
            
        $this->assertEquals('dark', $user->fresh()->theme_preference);
    }
    
    /** @test */
    public function it_persists_theme_preference()
    {
        $user = User::factory()->create(['theme_preference' => 'dark']);
        
        $this->actingAs($user)
            ->livewire(ThemeToggle::class)
            ->assertSet('currentTheme', 'dark');
    }
}
```

### CSS 變數測試

```javascript
// tests/js/theme.test.js
describe('Theme System', () => {
    test('applies correct CSS variables for light theme', () => {
        document.documentElement.setAttribute('data-theme', 'light');
        
        const primaryColor = getComputedStyle(document.documentElement)
            .getPropertyValue('--color-primary');
            
        expect(primaryColor.trim()).toBe('#3B82F6');
    });
    
    test('applies correct CSS variables for dark theme', () => {
        document.documentElement.setAttribute('data-theme', 'dark');
        
        const bgPrimary = getComputedStyle(document.documentElement)
            .getPropertyValue('--bg-primary');
            
        expect(bgPrimary.trim()).toBe('#111827');
    });
});
```

## 效能優化

### CSS 變數快取

```css
/* 使用 CSS 自訂屬性快取常用值 */
:root {
  --cached-primary-rgb: 59, 130, 246;
  --cached-shadow-color: rgb(var(--cached-primary-rgb) / 0.1);
}

.element {
  box-shadow: 0 4px 6px var(--cached-shadow-color);
}
```

### 主題預載入

```javascript
// 預載入主題 CSS
function preloadThemeCSS() {
    const themes = Object.keys(window.themeConfig?.available_themes || {});
    
    themes.forEach(theme => {
        const themeConfig = window.themeConfig.available_themes[theme];
        if (themeConfig.css_file) {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'style';
            link.href = `/css/${themeConfig.css_file}`;
            document.head.appendChild(link);
        }
    });
}
```

## 最佳實踐

1. **一致性**
   - 使用統一的 CSS 變數命名規範
   - 保持主題間的視覺一致性
   - 確保所有元件都支援主題切換

2. **效能**
   - 避免過度使用 CSS 變數
   - 使用適當的快取策略
   - 優化主題切換動畫

3. **可維護性**
   - 建立清晰的主題檔案結構
   - 使用語義化的變數名稱
   - 提供完整的主題文檔

4. **使用者體驗**
   - 提供平滑的主題切換動畫
   - 記住使用者的主題偏好
   - 支援系統主題自動檢測

5. **無障礙性**
   - 確保足夠的顏色對比度
   - 支援高對比模式
   - 提供主題切換的鍵盤快捷鍵