<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Models\Setting;
use App\Models\SettingChange;
use App\Models\User;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\WithPagination;

/**
 * 設定變更歷史元件
 * 
 * 提供設定變更記錄的查詢、篩選、搜尋和回復功能
 */
class SettingChangeHistory extends AdminComponent
{
    use WithPagination;

    /**
     * 搜尋關鍵字
     */
    public string $search = '';

    /**
     * 設定鍵值篩選
     */
    public string $settingKeyFilter = '';

    /**
     * 分類篩選
     */
    public string $categoryFilter = 'all';

    /**
     * 使用者篩選
     */
    public string $userFilter = 'all';

    /**
     * 日期範圍篩選 - 開始日期
     */
    public string $dateFrom = '';

    /**
     * 日期範圍篩選 - 結束日期
     */
    public string $dateTo = '';

    /**
     * 重要變更篩選
     */
    public bool $importantOnly = false;

    /**
     * 每頁顯示數量
     */
    public int $perPage = 20;

    /**
     * 排序欄位
     */
    public string $sortBy = 'created_at';

    /**
     * 排序方向
     */
    public string $sortDirection = 'desc';

    /**
     * 顯示回復確認對話框
     */
    public bool $showRestoreModal = false;

    /**
     * 選中的變更記錄
     */
    public ?SettingChange $selectedChange = null;

    /**
     * 顯示變更詳情對話框
     */
    public bool $showDetailsModal = false;

    /**
     * 顯示比較對話框
     */
    public bool $showCompareModal = false;

    /**
     * 比較的變更記錄
     */
    public array $compareChanges = [];

    /**
     * 顯示通知設定對話框
     */
    public bool $showNotificationModal = false;

    /**
     * 通知設定
     */
    public array $notificationSettings = [
        'email_enabled' => false,
        'important_only' => true,
        'categories' => [],
        'users' => [],
    ];

    /**
     * 初始化元件
     */
    public function mount(): void
    {
        parent::mount();
        
        // 設定預設日期範圍（最近30天）
        $this->dateTo = now()->format('Y-m-d');
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        
        // 載入通知設定
        $this->loadNotificationSettings();
    }

    /**
     * 取得設定資料庫
     */
    protected function getSettingsRepository(): SettingsRepositoryInterface
    {
        return app(SettingsRepositoryInterface::class);
    }

    /**
     * 取得配置服務
     */
    protected function getConfigService(): ConfigurationService
    {
        return app(ConfigurationService::class);
    }

    /**
     * 取得變更記錄列表
     */
    #[Computed]
    public function changes()
    {
        $query = SettingChange::with(['setting', 'user'])
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('setting_key', 'like', "%{$this->search}%")
                      ->orWhere('reason', 'like', "%{$this->search}%")
                      ->orWhereHas('user', function (Builder $userQuery) {
                          $userQuery->where('name', 'like', "%{$this->search}%")
                                   ->orWhere('username', 'like', "%{$this->search}%");
                      });
                });
            })
            ->when($this->settingKeyFilter, function (Builder $query) {
                $query->where('setting_key', $this->settingKeyFilter);
            })
            ->when($this->categoryFilter !== 'all', function (Builder $query) {
                $query->whereHas('setting', function (Builder $q) {
                    $q->where('category', $this->categoryFilter);
                });
            })
            ->when($this->userFilter !== 'all', function (Builder $query) {
                $query->where('changed_by', $this->userFilter);
            })
            ->when($this->dateFrom, function (Builder $query) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function (Builder $query) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            })
            ->when($this->importantOnly, function (Builder $query) {
                $query->important();
            })
            ->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    /**
     * 取得所有可用分類
     */
    #[Computed]
    public function categories(): array
    {
        return $this->getConfigService()->getCategories();
    }

    /**
     * 取得所有使用者
     */
    #[Computed]
    public function users(): Collection
    {
        return User::select('id', 'name', 'username')
            ->whereHas('settingChanges')
            ->orderBy('name')
            ->get();
    }

    /**
     * 取得統計資訊
     */
    #[Computed]
    public function stats(): array
    {
        $baseQuery = SettingChange::query();
        
        // 應用相同的篩選條件
        if ($this->dateFrom) {
            $baseQuery->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $baseQuery->whereDate('created_at', '<=', $this->dateTo);
        }

        $totalChanges = (clone $baseQuery)->count();
        $importantChanges = (clone $baseQuery)->important()->count();
        $uniqueSettings = (clone $baseQuery)->distinct('setting_key')->count('setting_key');
        $uniqueUsers = (clone $baseQuery)->distinct('changed_by')->count('changed_by');

        return [
            'total_changes' => $totalChanges,
            'important_changes' => $importantChanges,
            'unique_settings' => $uniqueSettings,
            'unique_users' => $uniqueUsers,
            'filtered_count' => $this->changes->total(),
        ];
    }

    /**
     * 取得最近活動的設定
     */
    #[Computed]
    public function recentActiveSettings(): Collection
    {
        return SettingChange::select('setting_key')
            ->with('setting')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('setting_key')
            ->orderByRaw('MAX(created_at) DESC')
            ->limit(10)
            ->get()
            ->pluck('setting')
            ->filter();
    }

    /**
     * 排序變更記錄
     */
    public function sortBy(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'desc';
        }

        $this->resetPage();
    }

    /**
     * 設定每頁顯示數量
     */
    public function setPerPage(int $perPage): void
    {
        $this->perPage = $perPage;
        $this->resetPage();
    }

    /**
     * 清除所有篩選
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->settingKeyFilter = '';
        $this->categoryFilter = 'all';
        $this->userFilter = 'all';
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->importantOnly = false;
        $this->resetPage();
    }

    /**
     * 設定快速日期範圍
     */
    public function setDateRange(string $range): void
    {
        $this->dateTo = now()->format('Y-m-d');
        
        switch ($range) {
            case 'today':
                $this->dateFrom = now()->format('Y-m-d');
                break;
            case 'week':
                $this->dateFrom = now()->subWeek()->format('Y-m-d');
                break;
            case 'month':
                $this->dateFrom = now()->subMonth()->format('Y-m-d');
                break;
            case 'quarter':
                $this->dateFrom = now()->subMonths(3)->format('Y-m-d');
                break;
            case 'year':
                $this->dateFrom = now()->subYear()->format('Y-m-d');
                break;
        }
        
        $this->resetPage();
    }

    /**
     * 顯示變更詳情
     */
    public function showDetails(int $changeId): void
    {
        $this->selectedChange = SettingChange::with(['setting', 'user'])->find($changeId);
        
        if ($this->selectedChange) {
            $this->showDetailsModal = true;
        }
    }

    /**
     * 關閉詳情對話框
     */
    public function closeDetailsModal(): void
    {
        $this->showDetailsModal = false;
        $this->selectedChange = null;
    }

    /**
     * 開啟回復確認對話框
     */
    public function confirmRestore(int $changeId): void
    {
        $this->selectedChange = SettingChange::with(['setting', 'user'])->find($changeId);
        
        if ($this->selectedChange) {
            $this->showRestoreModal = true;
        }
    }

    /**
     * 執行回復操作
     */
    public function executeRestore(): void
    {
        if (!$this->selectedChange) {
            $this->addFlash('error', '找不到要回復的變更記錄');
            return;
        }

        try {
            $settingKey = $this->selectedChange->setting_key;
            $oldValue = $this->selectedChange->old_value;
            
            // 執行回復
            $result = $this->getSettingsRepository()->updateSetting($settingKey, $oldValue);
            
            if ($result) {
                // 記錄回復操作
                SettingChange::create([
                    'setting_key' => $settingKey,
                    'old_value' => $this->selectedChange->new_value,
                    'new_value' => $oldValue,
                    'changed_by' => auth()->id(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'reason' => "回復到 {$this->selectedChange->created_at->format('Y-m-d H:i:s')} 的版本",
                ]);

                $this->dispatch('setting-updated', settingKey: $settingKey);
                $this->addFlash('success', "設定 '{$settingKey}' 已成功回復");
                
                // 發送通知
                $this->sendChangeNotification($settingKey, 'restored');
                
            } else {
                $this->addFlash('error', '設定回復失敗');
            }

        } catch (\Exception $e) {
            $this->addFlash('error', "設定回復時發生錯誤：{$e->getMessage()}");
        } finally {
            $this->closeRestoreModal();
        }
    }

    /**
     * 關閉回復對話框
     */
    public function closeRestoreModal(): void
    {
        $this->showRestoreModal = false;
        $this->selectedChange = null;
    }

    /**
     * 開啟比較對話框
     */
    public function openCompareModal(array $changeIds): void
    {
        if (count($changeIds) < 2) {
            $this->addFlash('warning', '請選擇至少兩個變更記錄進行比較');
            return;
        }

        $this->compareChanges = SettingChange::with(['setting', 'user'])
            ->whereIn('id', $changeIds)
            ->orderBy('created_at')
            ->get()
            ->toArray();
            
        $this->showCompareModal = true;
    }

    /**
     * 關閉比較對話框
     */
    public function closeCompareModal(): void
    {
        $this->showCompareModal = false;
        $this->compareChanges = [];
    }

    /**
     * 開啟通知設定對話框
     */
    public function openNotificationSettings(): void
    {
        $this->showNotificationModal = true;
    }

    /**
     * 儲存通知設定
     */
    public function saveNotificationSettings(): void
    {
        try {
            // 儲存到使用者偏好設定或系統設定
            $this->getSettingsRepository()->updateSetting(
                'notifications.change_history',
                $this->notificationSettings
            );

            $this->addFlash('success', '通知設定已儲存');
            $this->closeNotificationModal();

        } catch (\Exception $e) {
            $this->addFlash('error', "儲存通知設定時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 關閉通知設定對話框
     */
    public function closeNotificationModal(): void
    {
        $this->showNotificationModal = false;
    }

    /**
     * 載入通知設定
     */
    protected function loadNotificationSettings(): void
    {
        $setting = $this->getSettingsRepository()->getSetting('notifications.change_history');
        
        if ($setting && $setting->value) {
            $this->notificationSettings = array_merge(
                $this->notificationSettings,
                $setting->value
            );
        }
    }

    /**
     * 發送變更通知
     */
    protected function sendChangeNotification(string $settingKey, string $action = 'changed'): void
    {
        if (!$this->notificationSettings['email_enabled']) {
            return;
        }

        try {
            // 取得最新的變更記錄
            $latestChange = SettingChange::where('setting_key', $settingKey)
                ->with(['setting', 'user'])
                ->latest()
                ->first();

            if (!$latestChange) {
                return;
            }

            // 檢查是否只發送重要變更通知
            if ($this->notificationSettings['important_only'] && !$latestChange->is_important_change) {
                return;
            }

            // 檢查分類篩選
            if (!empty($this->notificationSettings['categories'])) {
                $setting = $latestChange->setting;
                if (!$setting || !in_array($setting->category, $this->notificationSettings['categories'])) {
                    return;
                }
            }

            // 取得要通知的使用者
            $notificationUsers = $this->getNotificationUsers();

            // 發送郵件通知
            foreach ($notificationUsers as $user) {
                \Mail::to($user->email)->queue(
                    new \App\Mail\SettingChangeNotification($latestChange, $action)
                );
            }

            \Log::info('設定變更通知已發送', [
                'setting_key' => $settingKey,
                'action' => $action,
                'user_id' => auth()->id(),
                'notification_count' => count($notificationUsers),
                'timestamp' => now(),
            ]);

        } catch (\Exception $e) {
            \Log::error('發送設定變更通知失敗', [
                'setting_key' => $settingKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 取得要通知的使用者列表
     */
    protected function getNotificationUsers(): array
    {
        // 如果指定了特定使用者，使用指定的使用者
        if (!empty($this->notificationSettings['users'])) {
            return User::whereIn('id', $this->notificationSettings['users'])
                ->where('is_active', true)
                ->get()
                ->toArray();
        }

        // 否則通知所有有系統設定權限的管理員
        return User::whereHas('roles.permissions', function ($query) {
                $query->where('name', 'system.settings');
            })
            ->where('is_active', true)
            ->get()
            ->toArray();
    }

    /**
     * 匯出變更記錄
     */
    public function exportChanges(): void
    {
        try {
            $changes = $this->changes->items();
            
            $exportData = collect($changes)->map(function ($change) {
                return [
                    'setting_key' => $change->setting_key,
                    'setting_name' => $change->setting?->description ?? $change->setting_key,
                    'category' => $change->setting?->category ?? '',
                    'old_value' => $this->formatValueForExport($change->old_value),
                    'new_value' => $this->formatValueForExport($change->new_value),
                    'changed_by' => $change->user?->name ?? '',
                    'changed_at' => $change->created_at->format('Y-m-d H:i:s'),
                    'ip_address' => $change->ip_address,
                    'reason' => $change->reason ?? '',
                ];
            });

            $filename = 'setting_changes_' . now()->format('Y-m-d_H-i-s') . '.json';
            
            $this->dispatch('download-file', [
                'filename' => $filename,
                'content' => $exportData->toJson(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'mimeType' => 'application/json'
            ]);

        } catch (\Exception $e) {
            $this->addFlash('error', "匯出變更記錄時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 格式化值用於匯出
     */
    public function formatValueForExport($value): string
    {
        if ($value === null) {
            return '(null)';
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        return (string) $value;
    }

    /**
     * 監聽設定更新事件
     */
    #[On('setting-updated')]
    public function handleSettingUpdated(): void
    {
        // 重新整理變更記錄
        unset($this->changes);
        unset($this->stats);
        unset($this->recentActiveSettings);
    }

    /**
     * 取得變更類型圖示
     */
    public function getChangeTypeIcon(SettingChange $change): string
    {
        if ($change->reason && str_contains($change->reason, '回復')) {
            return 'arrow-uturn-left';
        }
        
        if ($change->old_value === null) {
            return 'plus-circle';
        }
        
        if ($change->new_value === null) {
            return 'minus-circle';
        }
        
        return 'pencil-square';
    }

    /**
     * 取得變更類型文字
     */
    public function getChangeTypeText(SettingChange $change): string
    {
        if ($change->reason && str_contains($change->reason, '回復')) {
            return '回復';
        }
        
        if ($change->old_value === null) {
            return '新增';
        }
        
        if ($change->new_value === null) {
            return '刪除';
        }
        
        return '修改';
    }

    /**
     * 取得變更重要性標籤
     */
    public function getImportanceLabel(SettingChange $change): string
    {
        return $change->is_important_change ? '重要' : '一般';
    }

    /**
     * 取得變更重要性顏色
     */
    public function getImportanceColor(SettingChange $change): string
    {
        return $change->is_important_change ? 'red' : 'gray';
    }

    /**
     * 格式化顯示值
     */
    public function formatDisplayValue($value, int $maxLength = 50): string
    {
        if ($value === null) {
            return '(空值)';
        }
        
        if (is_bool($value)) {
            return $value ? '是' : '否';
        }
        
        if (is_array($value)) {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE);
            return strlen($json) > $maxLength ? substr($json, 0, $maxLength - 3) . '...' : $json;
        }
        
        $stringValue = (string) $value;
        return strlen($stringValue) > $maxLength ? substr($stringValue, 0, $maxLength - 3) . '...' : $stringValue;
    }



    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.settings.setting-change-history')
            ->layout('components.layouts.admin');
    }
}