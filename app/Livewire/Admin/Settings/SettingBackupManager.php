<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Models\SettingBackup;
use App\Repositories\SettingsRepositoryInterface;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\WithPagination;

/**
 * 設定備份管理元件
 * 
 * 提供設定備份的建立、列表、還原、比較和下載功能
 */
class SettingBackupManager extends AdminComponent
{
    use WithPagination;

    /**
     * 備份名稱
     */
    public string $backupName = '';

    /**
     * 備份描述
     */
    public string $backupDescription = '';

    /**
     * 顯示建立備份對話框
     */
    public bool $showCreateModal = false;

    /**
     * 顯示還原確認對話框
     */
    public bool $showRestoreModal = false;

    /**
     * 顯示比較對話框
     */
    public bool $showCompareModal = false;

    /**
     * 顯示刪除確認對話框
     */
    public bool $showDeleteModal = false;

    /**
     * 選中的備份
     */
    public ?SettingBackup $selectedBackup = null;

    /**
     * 比較結果
     */
    public array $compareResult = [];

    /**
     * 還原預覽資料
     */
    public array $restorePreview = [];

    /**
     * 搜尋關鍵字
     */
    public string $search = '';

    /**
     * 排序欄位
     */
    public string $sortBy = 'created_at';

    /**
     * 排序方向
     */
    public string $sortDirection = 'desc';

    /**
     * 每頁顯示數量
     */
    public int $perPage = 10;

    /**
     * URL 查詢字串屬性（用於狀態持久化）
     */
    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 10],
    ];

    /**
     * 取得設定資料庫
     */
    protected function getSettingsRepository(): SettingsRepositoryInterface
    {
        return app(SettingsRepositoryInterface::class);
    }

    /**
     * 取得備份列表
     */
    #[Computed]
    public function backups()
    {
        $query = SettingBackup::with('creator');

        // 搜尋條件
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        // 排序
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    /**
     * 取得統計資訊
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'total_backups' => SettingBackup::count(),
            'recent_backups' => SettingBackup::recent(7)->count(),
            'total_size' => $this->getTotalBackupSize(),
            'oldest_backup' => SettingBackup::oldest('created_at')->first()?->created_at,
        ];
    }

    /**
     * 開啟建立備份對話框
     */
    public function openCreateModal(): void
    {
        $this->backupName = '設定備份 ' . now()->format('Y-m-d H:i');
        $this->backupDescription = '';
        $this->showCreateModal = true;
    }

    /**
     * 建立備份
     */
    public function createBackup(): void
    {
        // 檢查使用者認證
        if (!auth()->check()) {
            $this->addFlash('error', '請先登入系統');
            return;
        }

        $this->validate([
            'backupName' => 'required|string|max:255',
            'backupDescription' => 'nullable|string|max:1000',
        ], [
            'backupName.required' => '備份名稱為必填項目',
            'backupName.max' => '備份名稱不能超過 255 個字元',
            'backupDescription.max' => '備份描述不能超過 1000 個字元',
        ]);

        try {
            \Log::info('開始建立備份', [
                'user_id' => auth()->user()->id,
                'username' => auth()->user()->username,
                'backup_name' => $this->backupName,
                'backup_description' => $this->backupDescription
            ]);

            $backup = $this->getSettingsRepository()->createBackup(
                $this->backupName,
                $this->backupDescription
            );

            \Log::info('備份建立成功', [
                'backup_id' => $backup->id,
                'user_id' => auth()->user()->id,
                'username' => auth()->user()->username
            ]);

            $this->addFlash('success', "備份 '{$backup->name}' 建立成功");
            $this->closeCreateModal();
            $this->dispatch('backup-created', backupId: $backup->id);
            
            // 重新整理列表
            $this->resetPage();
            unset($this->backups);

        } catch (\Exception $e) {
            \Log::error('建立備份失敗', [
                'error' => $e->getMessage(),
                'user_id' => auth()->user()->id ?? 'unknown',
                'username' => auth()->user()->username ?? 'unknown',
                'backup_name' => $this->backupName,
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addFlash('error', "建立備份時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 關閉建立備份對話框
     */
    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->backupName = '';
        $this->backupDescription = '';
    }

    /**
     * 開啟還原確認對話框
     */
    public function openRestoreModal(int $backupId): void
    {
        $this->selectedBackup = SettingBackup::find($backupId);
        
        if (!$this->selectedBackup) {
            $this->addFlash('error', '找不到指定的備份');
            return;
        }

        // 生成還原預覽
        $this->generateRestorePreview();
        $this->showRestoreModal = true;
    }

    /**
     * 還原備份
     */
    public function restoreBackup(): void
    {
        if (!$this->selectedBackup) {
            $this->addFlash('error', '未選擇備份');
            return;
        }

        try {
            $result = $this->getSettingsRepository()->restoreBackup($this->selectedBackup->id);
            
            if ($result) {
                $this->addFlash('success', "備份 '{$this->selectedBackup->name}' 還原成功");
                $this->dispatch('settings-restored', backupId: $this->selectedBackup->id);
                $this->dispatch('settings-bulk-updated'); // 通知其他元件更新
            } else {
                $this->addFlash('error', '備份還原失敗');
            }

            $this->closeRestoreModal();

        } catch (\Exception $e) {
            $this->addFlash('error', "還原備份時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 關閉還原對話框
     */
    public function closeRestoreModal(): void
    {
        $this->showRestoreModal = false;
        $this->selectedBackup = null;
        $this->restorePreview = [];
    }

    /**
     * 開啟比較對話框
     */
    public function openCompareModal(int $backupId): void
    {
        $this->selectedBackup = SettingBackup::find($backupId);
        
        if (!$this->selectedBackup) {
            $this->addFlash('error', '找不到指定的備份');
            return;
        }

        // 生成比較結果
        $this->compareResult = $this->selectedBackup->compare();
        $this->showCompareModal = true;
    }

    /**
     * 關閉比較對話框
     */
    public function closeCompareModal(): void
    {
        $this->showCompareModal = false;
        $this->selectedBackup = null;
        $this->compareResult = [];
    }

    /**
     * 開啟刪除確認對話框
     */
    public function openDeleteModal(int $backupId): void
    {
        $this->selectedBackup = SettingBackup::find($backupId);
        
        if (!$this->selectedBackup) {
            $this->addFlash('error', '找不到指定的備份');
            return;
        }

        $this->showDeleteModal = true;
    }

    /**
     * 刪除備份
     */
    public function deleteBackup(): void
    {
        if (!$this->selectedBackup) {
            $this->addFlash('error', '未選擇備份');
            return;
        }

        try {
            $backupName = $this->selectedBackup->name;
            $result = $this->getSettingsRepository()->deleteBackup($this->selectedBackup->id);
            
            if ($result) {
                $this->addFlash('success', "備份 '{$backupName}' 已刪除");
                
                // 重新整理列表
                $this->resetPage();
                unset($this->backups);
                unset($this->stats);
            } else {
                $this->addFlash('error', '刪除備份失敗');
            }

            $this->closeDeleteModal();

        } catch (\Exception $e) {
            $this->addFlash('error', "刪除備份時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 關閉刪除對話框
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->selectedBackup = null;
    }

    /**
     * 匯出所有備份
     */
    public function exportAllBackups(): void
    {
        try {
            $backups = SettingBackup::with('creator')->get();
            
            if ($backups->isEmpty()) {
                $this->addFlash('warning', '目前沒有備份可以匯出');
                return;
            }

            // 記錄匯出操作
            \Log::info('開始匯出所有備份', [
                'user_id' => auth()->id(),
                'backup_count' => $backups->count(),
            ]);

            $this->addFlash('success', "正在匯出 {$backups->count()} 個備份...");

            // 使用 JavaScript 重定向到下載路由
            $downloadUrl = route('admin.settings.backups.export-all');
            $this->js("window.location.href = '{$downloadUrl}';");

        } catch (\Exception $e) {
            \Log::error('匯出備份失敗', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            
            $this->addFlash('error', "匯出備份時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 下載備份
     */
    public function downloadBackup(int $backupId): void
    {
        $backup = SettingBackup::find($backupId);
        
        if (!$backup) {
            $this->addFlash('error', '找不到指定的備份');
            return;
        }

        try {
            // 記錄下載操作
            \Log::info('開始下載備份', [
                'user_id' => auth()->id(),
                'backup_id' => $backup->id,
                'backup_name' => $backup->name,
            ]);

            $this->addFlash('success', "正在下載備份 '{$backup->name}'...");

            // 使用 JavaScript 重定向到下載路由
            $downloadUrl = route('admin.settings.backups.download', $backup->id);
            $this->js("window.location.href = '{$downloadUrl}';");

        } catch (\Exception $e) {
            \Log::error('下載備份失敗', [
                'error' => $e->getMessage(),
                'backup_id' => $backupId,
                'user_id' => auth()->id(),
            ]);
            
            $this->addFlash('error', "下載備份時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 設定排序
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
        unset($this->backups);
    }

    /**
     * 清除搜尋
     */
    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
        unset($this->backups);
    }

    /**
     * 前往指定頁面
     */
    public function gotoPage(int $page): void
    {
        $this->setPage($page);
    }

    /**
     * 每頁顯示筆數更新時重置分頁
     */
    public function updatedPerPage(): void
    {
        try {
            // 驗證 perPage 值
            if (!in_array($this->perPage, [10, 25, 50])) {
                $this->perPage = 10; // 重置為預設值
            }
            
            $this->resetPage();
            unset($this->backups);
            
        } catch (\Exception $e) {
            logger()->error('Error updating perPage in backup manager', [
                'error' => $e->getMessage(),
                'perPage' => $this->perPage
            ]);
            
            // 重置為預設值
            $this->perPage = 10;
            $this->resetPage();
        }
    }

    /**
     * 生成還原預覽
     */
    protected function generateRestorePreview(): void
    {
        if (!$this->selectedBackup) {
            return;
        }

        $compareResult = $this->selectedBackup->compare();
        
        $this->restorePreview = [
            'will_add' => count($compareResult['added']),
            'will_update' => count($compareResult['modified']),
            'will_remove' => count($compareResult['removed']),
            'unchanged' => count($compareResult['unchanged']),
            'changes' => array_merge(
                $compareResult['added'],
                $compareResult['modified'],
                $compareResult['removed']
            ),
        ];
    }

    /**
     * 取得總備份大小
     */
    protected function getTotalBackupSize(): string
    {
        $totalSize = SettingBackup::all()->sum(function ($backup) {
            return strlen(json_encode($backup->settings_data));
        });

        if ($totalSize < 1024) {
            return $totalSize . ' B';
        } elseif ($totalSize < 1048576) {
            return round($totalSize / 1024, 2) . ' KB';
        } else {
            return round($totalSize / 1048576, 2) . ' MB';
        }
    }

    /**
     * 取得備份大小
     */
    public function getBackupSize(SettingBackup $backup): string
    {
        $size = strlen(json_encode($backup->settings_data));
        
        if ($size < 1024) {
            return $size . ' B';
        } elseif ($size < 1048576) {
            return round($size / 1024, 2) . ' KB';
        } else {
            return round($size / 1048576, 2) . ' MB';
        }
    }

    /**
     * 取得備份類型標籤
     */
    public function getBackupTypeLabel(string $type): string
    {
        return match($type) {
            'manual' => '手動',
            'auto' => '自動',
            'scheduled' => '排程',
            default => $type,
        };
    }

    /**
     * 取得備份類型顏色
     */
    public function getBackupTypeColor(string $type): string
    {
        return match($type) {
            'manual' => 'blue',
            'auto' => 'green',
            'scheduled' => 'purple',
            default => 'gray',
        };
    }

    /**
     * 監聽開啟備份對話框事件
     */
    #[On('open-backup-dialog')]
    public function handleOpenBackupDialog(): void
    {
        $this->openCreateModal();
    }

    /**
     * 監聽設定更新事件
     */
    #[On('setting-updated')]
    public function handleSettingUpdated(): void
    {
        // 如果有開啟的比較對話框，重新生成比較結果
        if ($this->showCompareModal && $this->selectedBackup) {
            $this->compareResult = $this->selectedBackup->compare();
        }
        
        // 如果有開啟的還原對話框，重新生成預覽
        if ($this->showRestoreModal && $this->selectedBackup) {
            $this->generateRestorePreview();
        }
    }

    /**
     * 監聽設定批量更新事件
     */
    #[On('settings-bulk-updated')]
    public function handleSettingsBulkUpdated(): void
    {
        $this->handleSettingUpdated();
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.settings.setting-backup-manager')
            ->layout('components.layouts.admin');
    }
}