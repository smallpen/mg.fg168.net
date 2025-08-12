<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

/**
 * 無障礙功能服務類別
 * 提供鍵盤導航、螢幕閱讀器支援、高對比模式等無障礙功能
 */
class AccessibilityService
{
    /**
     * 取得使用者的無障礙偏好設定
     */
    public function getUserAccessibilityPreferences(): array
    {
        $user = Auth::user();
        if (!$user) {
            return $this->getDefaultPreferences();
        }

        return Cache::remember(
            "accessibility_preferences_{$user->id}",
            3600,
            fn() => array_merge(
                $this->getDefaultPreferences(),
                $user->accessibility_preferences ?? []
            )
        );
    }

    /**
     * 儲存使用者的無障礙偏好設定
     */
    public function saveUserAccessibilityPreferences(array $preferences): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $user->update([
            'accessibility_preferences' => array_merge(
                $this->getUserAccessibilityPreferences(),
                $preferences
            )
        ]);

        Cache::forget("accessibility_preferences_{$user->id}");
    }

    /**
     * 取得預設的無障礙偏好設定
     */
    protected function getDefaultPreferences(): array
    {
        return [
            'high_contrast' => false,
            'large_text' => false,
            'reduced_motion' => false,
            'keyboard_navigation' => true,
            'screen_reader_support' => true,
            'focus_indicators' => true,
            'skip_links' => true,
        ];
    }

    /**
     * 產生 ARIA 標籤
     */
    public function generateAriaLabel(string $context, array $data = []): string
    {
        $labels = [
            'menu_toggle' => '切換選單',
            'search' => '搜尋',
            'notifications' => '通知中心',
            'user_menu' => '使用者選單',
            'theme_toggle' => '切換主題',
            'language_selector' => '語言選擇',
            'breadcrumb' => '麵包屑導航',
            'main_content' => '主要內容',
            'sidebar' => '側邊選單',
            'skip_to_content' => '跳至主要內容',
            'skip_to_navigation' => '跳至導航選單',
        ];

        $baseLabel = $labels[$context] ?? $context;

        if (!empty($data)) {
            $baseLabel .= '：' . implode('，', $data);
        }

        return $baseLabel;
    }

    /**
     * 產生鍵盤快捷鍵說明
     */
    public function getKeyboardShortcuts(): array
    {
        return [
            'navigation' => [
                'Alt + M' => '開啟/關閉選單',
                'Alt + S' => '聚焦搜尋框',
                'Alt + N' => '開啟通知中心',
                'Alt + U' => '開啟使用者選單',
                'Alt + T' => '切換主題',
                'Alt + H' => '回到首頁',
            ],
            'general' => [
                'Tab' => '移動到下一個元素',
                'Shift + Tab' => '移動到上一個元素',
                'Enter' => '啟動連結或按鈕',
                'Space' => '啟動按鈕或核取方塊',
                'Escape' => '關閉對話框或下拉選單',
            ],
            'menu' => [
                '↑ ↓' => '在選單項目間移動',
                '→' => '展開子選單',
                '←' => '收合子選單',
                'Home' => '移動到第一個項目',
                'End' => '移動到最後一個項目',
            ],
        ];
    }

    /**
     * 檢查是否啟用高對比模式
     */
    public function isHighContrastEnabled(): bool
    {
        return $this->getUserAccessibilityPreferences()['high_contrast'] ?? false;
    }

    /**
     * 檢查是否啟用大字體模式
     */
    public function isLargeTextEnabled(): bool
    {
        return $this->getUserAccessibilityPreferences()['large_text'] ?? false;
    }

    /**
     * 檢查是否啟用減少動畫模式
     */
    public function isReducedMotionEnabled(): bool
    {
        return $this->getUserAccessibilityPreferences()['reduced_motion'] ?? false;
    }

    /**
     * 產生無障礙 CSS 類別
     */
    public function getAccessibilityClasses(): string
    {
        $classes = [];
        $preferences = $this->getUserAccessibilityPreferences();

        if ($preferences['high_contrast']) {
            $classes[] = 'high-contrast';
        }

        if ($preferences['large_text']) {
            $classes[] = 'large-text';
        }

        if ($preferences['reduced_motion']) {
            $classes[] = 'reduced-motion';
        }

        if ($preferences['focus_indicators']) {
            $classes[] = 'enhanced-focus';
        }

        return implode(' ', $classes);
    }

    /**
     * 清除使用者無障礙偏好快取
     */
    public function clearUserPreferencesCache(): void
    {
        $user = Auth::user();
        if ($user) {
            Cache::forget("accessibility_preferences_{$user->id}");
        }
    }

    /**
     * 執行無障礙功能稽核
     */
    public function performAccessibilityAudit(): array
    {
        $auditService = app(AccessibilityAuditService::class);
        return $auditService->performAccessibilityAudit();
    }
}