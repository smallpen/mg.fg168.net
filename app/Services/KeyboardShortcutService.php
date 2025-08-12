<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * 鍵盤快捷鍵服務
 * 負責管理全域鍵盤快捷鍵的註冊、檢測和執行
 */
class KeyboardShortcutService
{
    /**
     * 預設快捷鍵配置
     */
    protected array $defaultShortcuts = [
        // 導航快捷鍵
        'ctrl+shift+d' => [
            'action' => 'navigate',
            'target' => '/admin/dashboard',
            'description' => '前往儀表板',
            'category' => 'navigation',
            'enabled' => true,
        ],
        'ctrl+shift+u' => [
            'action' => 'navigate',
            'target' => '/admin/users',
            'description' => '前往使用者管理',
            'category' => 'navigation',
            'enabled' => true,
        ],
        'ctrl+shift+r' => [
            'action' => 'navigate',
            'target' => '/admin/roles',
            'description' => '前往角色管理',
            'category' => 'navigation',
            'enabled' => true,
        ],
        'ctrl+shift+p' => [
            'action' => 'navigate',
            'target' => '/admin/permissions',
            'description' => '前往權限管理',
            'category' => 'navigation',
            'enabled' => true,
        ],
        'ctrl+shift+s' => [
            'action' => 'navigate',
            'target' => '/admin/settings',
            'description' => '前往系統設定',
            'category' => 'navigation',
            'enabled' => true,
        ],
        
        // 功能快捷鍵
        'ctrl+k' => [
            'action' => 'toggle-search',
            'target' => null,
            'description' => '開啟/關閉全域搜尋',
            'category' => 'function',
            'enabled' => true,
        ],
        'ctrl+shift+n' => [
            'action' => 'toggle-notifications',
            'target' => null,
            'description' => '開啟/關閉通知中心',
            'category' => 'function',
            'enabled' => true,
        ],
        'ctrl+shift+t' => [
            'action' => 'toggle-theme',
            'target' => null,
            'description' => '切換主題',
            'category' => 'function',
            'enabled' => true,
        ],
        'ctrl+shift+m' => [
            'action' => 'toggle-sidebar',
            'target' => null,
            'description' => '收合/展開側邊選單',
            'category' => 'function',
            'enabled' => true,
        ],
        
        // 系統快捷鍵
        'ctrl+shift+h' => [
            'action' => 'show-help',
            'target' => null,
            'description' => '顯示快捷鍵說明',
            'category' => 'system',
            'enabled' => true,
        ],
        'escape' => [
            'action' => 'close-modal',
            'target' => null,
            'description' => '關閉對話框',
            'category' => 'system',
            'enabled' => true,
        ],
        'ctrl+shift+l' => [
            'action' => 'logout',
            'target' => null,
            'description' => '登出系統',
            'category' => 'system',
            'enabled' => true,
        ],
    ];

    /**
     * 快捷鍵分類
     */
    protected array $categories = [
        'navigation' => '導航',
        'function' => '功能',
        'system' => '系統',
        'custom' => '自訂',
    ];

    /**
     * 獲取使用者的快捷鍵配置
     */
    public function getUserShortcuts(?int $userId = null): Collection
    {
        $userId = $userId ?? (int) Auth::id();
        
        return Cache::remember(
            "keyboard_shortcuts_{$userId}",
            3600,
            function () use ($userId) {
                // 從資料庫獲取使用者自訂快捷鍵
                $userShortcuts = $this->getUserCustomShortcuts($userId);
                
                // 合併預設快捷鍵和使用者自訂快捷鍵
                $shortcuts = collect($this->defaultShortcuts);
                
                foreach ($userShortcuts as $key => $shortcut) {
                    $shortcuts->put($key, $shortcut);
                }
                
                return $shortcuts->filter(function ($shortcut) {
                    return $shortcut['enabled'] ?? true;
                });
            }
        );
    }

    /**
     * 註冊新的快捷鍵
     */
    public function registerShortcut(string $key, array $config, ?int $userId = null): bool
    {
        $userId = $userId ?? (int) Auth::id();
        
        // 檢查快捷鍵衝突
        if ($this->hasConflict($key, $userId)) {
            return false;
        }
        
        // 驗證快捷鍵格式
        if (!$this->isValidShortcutKey($key)) {
            return false;
        }
        
        // 儲存到資料庫
        $this->saveUserShortcut($userId, $key, $config);
        
        // 清除快取
        $this->clearUserShortcutsCache($userId);
        
        return true;
    }

    /**
     * 更新快捷鍵配置
     */
    public function updateShortcut(string $key, array $config, ?int $userId = null): bool
    {
        $userId = $userId ?? (int) Auth::id();
        
        // 更新資料庫
        $this->saveUserShortcut($userId, $key, $config);
        
        // 清除快取
        $this->clearUserShortcutsCache($userId);
        
        return true;
    }

    /**
     * 刪除快捷鍵
     */
    public function removeShortcut(string $key, ?int $userId = null): bool
    {
        $userId = $userId ?? (int) Auth::id();
        
        // 從資料庫刪除
        $this->deleteUserShortcut($userId, $key);
        
        // 清除快取
        $this->clearUserShortcutsCache($userId);
        
        return true;
    }

    /**
     * 檢查快捷鍵衝突
     */
    public function hasConflict(string $key, ?int $userId = null): bool
    {
        $userId = $userId ?? (int) Auth::id();
        $shortcuts = $this->getUserShortcuts($userId);
        
        return $shortcuts->has($key);
    }

    /**
     * 獲取快捷鍵衝突列表
     */
    public function getConflicts(array $newShortcuts, ?int $userId = null): array
    {
        $userId = $userId ?? (int) Auth::id();
        $existingShortcuts = $this->getUserShortcuts($userId);
        $conflicts = [];
        
        foreach ($newShortcuts as $key => $config) {
            if ($existingShortcuts->has($key)) {
                $conflicts[$key] = [
                    'existing' => $existingShortcuts->get($key),
                    'new' => $config,
                ];
            }
        }
        
        return $conflicts;
    }

    /**
     * 驗證快捷鍵格式
     */
    public function isValidShortcutKey(string $key): bool
    {
        $key = strtolower($key);
        
        // 特殊鍵可以單獨使用
        $specialKeys = ['escape', 'enter', 'tab', 'space'];
        if (in_array($key, $specialKeys)) {
            return true;
        }
        
        // 檢查基本格式：修飾鍵+字母/數字
        $pattern = '/^(ctrl\+|alt\+|shift\+|meta\+)+[a-z0-9]+$/';
        if (!preg_match($pattern, $key)) {
            return false;
        }
        
        // 檢查是否包含至少一個修飾鍵
        if (!preg_match('/(ctrl|alt|shift|meta)\+/', $key)) {
            return false;
        }
        
        // 檢查修飾鍵是否有效
        $parts = explode('+', $key);
        $validModifiers = ['ctrl', 'alt', 'shift', 'meta'];
        
        for ($i = 0; $i < count($parts) - 1; $i++) {
            if (!in_array($parts[$i], $validModifiers)) {
                return false;
            }
        }
        
        // 檢查最後一個部分是否為有效的按鍵
        $lastPart = end($parts);
        if (!preg_match('/^[a-z0-9]+$/', $lastPart)) {
            return false;
        }
        
        return true;
    }

    /**
     * 獲取快捷鍵分類
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * 根據分類獲取快捷鍵
     */
    public function getShortcutsByCategory(string $category, ?int $userId = null): Collection
    {
        return $this->getUserShortcuts($userId)->filter(function ($shortcut) use ($category) {
            return ($shortcut['category'] ?? 'custom') === $category;
        });
    }

    /**
     * 重置為預設快捷鍵
     */
    public function resetToDefaults(?int $userId = null): bool
    {
        $userId = $userId ?? (int) Auth::id();
        
        // 刪除所有使用者自訂快捷鍵
        $this->deleteAllUserShortcuts($userId);
        
        // 清除快取
        $this->clearUserShortcutsCache($userId);
        
        return true;
    }

    /**
     * 匯出快捷鍵配置
     */
    public function exportShortcuts(?int $userId = null): array
    {
        $userId = $userId ?? (int) Auth::id();
        return $this->getUserShortcuts($userId)->toArray();
    }

    /**
     * 匯入快捷鍵配置
     */
    public function importShortcuts(array $shortcuts, bool $overwrite = false, ?int $userId = null): array
    {
        $userId = $userId ?? (int) Auth::id();
        $results = [
            'imported' => 0,
            'skipped' => 0,
            'conflicts' => [],
        ];
        
        foreach ($shortcuts as $key => $config) {
            if (!$overwrite && $this->hasConflict($key, $userId)) {
                $results['conflicts'][] = $key;
                $results['skipped']++;
                continue;
            }
            
            if ($this->registerShortcut($key, $config, $userId)) {
                $results['imported']++;
            } else {
                $results['skipped']++;
            }
        }
        
        return $results;
    }

    /**
     * 從資料庫獲取使用者自訂快捷鍵
     */
    protected function getUserCustomShortcuts(int $userId): array
    {
        $shortcuts = \App\Models\UserKeyboardShortcut::forUser($userId)->get();
        
        $result = [];
        foreach ($shortcuts as $shortcut) {
            $result[$shortcut->shortcut_key] = $shortcut->toShortcutConfig();
        }
        
        return $result;
    }

    /**
     * 儲存使用者快捷鍵到資料庫
     */
    protected function saveUserShortcut(int $userId, string $key, array $config): void
    {
        \App\Models\UserKeyboardShortcut::updateOrCreate(
            [
                'user_id' => $userId,
                'shortcut_key' => $key,
            ],
            [
                'action' => $config['action'],
                'target' => $config['target'],
                'description' => $config['description'],
                'category' => $config['category'],
                'enabled' => $config['enabled'],
            ]
        );
    }

    /**
     * 從資料庫刪除使用者快捷鍵
     */
    protected function deleteUserShortcut(int $userId, string $key): void
    {
        \App\Models\UserKeyboardShortcut::where('user_id', $userId)
            ->where('shortcut_key', $key)
            ->delete();
    }

    /**
     * 刪除使用者所有自訂快捷鍵
     */
    protected function deleteAllUserShortcuts(int $userId): void
    {
        \App\Models\UserKeyboardShortcut::where('user_id', $userId)->delete();
    }

    /**
     * 清除使用者快捷鍵快取
     */
    protected function clearUserShortcutsCache(int $userId): void
    {
        Cache::forget("keyboard_shortcuts_{$userId}");
    }
}