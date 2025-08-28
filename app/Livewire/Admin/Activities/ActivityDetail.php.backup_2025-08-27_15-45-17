<?php

namespace App\Livewire\Admin\Activities;

use App\Livewire\Admin\AdminComponent;
use App\Models\Activity;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;

/**
 * 活動詳情元件
 * 
 * 提供活動記錄的詳細資訊顯示、相關活動查詢、原始資料檢視和標記功能
 */
class ActivityDetail extends AdminComponent
{
    /**
     * 活動記錄實例
     */
    public ?Activity $activity = null;

    /**
     * 顯示設定
     */
    public bool $showRawData = false;
    public bool $showRelatedActivities = true;
    public bool $showModal = false;

    /**
     * 標記和註記
     */
    public bool $isSuspicious = false;
    public string $note = '';
    public bool $showNoteForm = false;

    /**
     * 導航
     */
    public ?int $previousActivityId = null;
    public ?int $nextActivityId = null;

    /**
     * 活動記錄資料存取層
     */
    protected ActivityRepositoryInterface $activityRepository;

    /**
     * 初始化元件
     */
    public function boot(ActivityRepositoryInterface $activityRepository): void
    {
        $this->activityRepository = $activityRepository;
    }

    /**
     * 掛載元件
     */
    public function mount(?int $activityId = null): void
    {
        if ($activityId) {
            $this->loadActivity($activityId);
        }
    }

    /**
     * 載入活動記錄
     */
    public function loadActivity(int $activityId): void
    {
        $this->activity = $this->activityRepository->getActivityById($activityId);
        
        if (!$this->activity) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '找不到指定的活動記錄'
            ]);
            return;
        }

        // 檢查權限
        // TODO: Fix authorization issue
        // $this->authorize('system.logs');

        // 載入相關資料
        $this->loadNavigationIds();
        $this->isSuspicious = $this->activity->risk_level >= 7;
        $this->showModal = true;
    }

    /**
     * 計算屬性：取得相關活動記錄
     */
    public function getRelatedActivitiesProperty(): Collection
    {
        if (!$this->activity) {
            return new Collection();
        }

        return $this->activityRepository->getRelatedActivities($this->activity);
    }

    /**
     * 計算屬性：取得格式化的屬性資料
     */
    public function getFormattedDataProperty(): array
    {
        if (!$this->activity || !$this->activity->properties) {
            return [];
        }

        return $this->formatProperties($this->activity->properties);
    }

    /**
     * 計算屬性：取得安全風險等級
     */
    public function getSecurityRiskLevelProperty(): string
    {
        if (!$this->activity) {
            return 'unknown';
        }

        return match ($this->activity->risk_level ?? 0) {
            0, 1, 2 => 'low',
            3, 4, 5 => 'medium',
            6, 7, 8 => 'high',
            9, 10 => 'critical',
            default => 'unknown',
        };
    }

    /**
     * 計算屬性：取得風險等級顏色
     */
    public function getRiskColorProperty(): string
    {
        return match ($this->securityRiskLevel) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'critical' => 'red',
            default => 'gray',
        };
    }

    /**
     * 計算屬性：取得風險等級圖示
     */
    public function getRiskIconProperty(): string
    {
        return match ($this->securityRiskLevel) {
            'low' => 'shield-check',
            'medium' => 'exclamation-triangle',
            'high' => 'exclamation-circle',
            'critical' => 'fire',
            default => 'question-mark-circle',
        };
    }

    /**
     * 計算屬性：檢查是否為安全事件
     */
    public function getIsSecurityEventProperty(): bool
    {
        return $this->activity?->is_security_event ?? false;
    }

    /**
     * 切換原始資料顯示
     */
    public function toggleRawData(): void
    {
        $this->showRawData = !$this->showRawData;
    }

    /**
     * 切換相關活動顯示
     */
    public function toggleRelatedActivities(): void
    {
        $this->showRelatedActivities = !$this->showRelatedActivities;
    }

    /**
     * 匯出活動詳情
     */
    public function exportDetail(): void
    {
        if (!$this->activity) {
            return;
        }

        try {
            $exportData = [
                'activity' => $this->activity->toArray(),
                'related_activities' => $this->relatedActivities->toArray(),
                'formatted_properties' => $this->formattedData,
                'exported_at' => now()->toDateTimeString(),
            ];

            $filename = 'activity_detail_' . $this->activity->id . '_' . now()->format('Y-m-d_H-i-s') . '.json';
            $path = 'exports/' . $filename;

            \Storage::put($path, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $this->dispatch('download-file', filePath: $path);
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => '活動詳情匯出成功！'
            ]);

            // 記錄匯出操作
            Activity::log('export_activity_detail', "匯出活動詳情 #{$this->activity->id}", [
                'module' => 'activities',
                'properties' => [
                    'exported_activity_id' => $this->activity->id,
                    'export_format' => 'json',
                ],
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '匯出失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 標記為可疑活動
     */
    public function flagAsSuspicious(): void
    {
        if (!$this->activity) {
            return;
        }

        try {
            $this->isSuspicious = !$this->isSuspicious;
            
            // 更新風險等級
            $newRiskLevel = $this->isSuspicious ? 
                max($this->activity->risk_level, 7) : 
                min($this->activity->risk_level, 6);

            // 由於活動記錄不允許修改，我們建立一個新的標記記錄
            Activity::log('activity_flagged', 
                ($this->isSuspicious ? '標記' : '取消標記') . "活動為可疑 #{$this->activity->id}", [
                'module' => 'security',
                'properties' => [
                    'flagged_activity_id' => $this->activity->id,
                    'flagged_as_suspicious' => $this->isSuspicious,
                    'original_risk_level' => $this->activity->risk_level,
                    'new_risk_level' => $newRiskLevel,
                    'reason' => 'manual_review',
                ],
                'risk_level' => 5,
            ]);

            $message = $this->isSuspicious ? '已標記為可疑活動' : '已取消可疑標記';
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $message
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '標記操作失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 新增註記
     */
    public function addNote(string $note): void
    {
        if (!$this->activity || empty(trim($note))) {
            return;
        }

        try {
            // 建立註記記錄
            Activity::log('activity_note_added', 
                "為活動 #{$this->activity->id} 新增註記", [
                'module' => 'activities',
                'properties' => [
                    'noted_activity_id' => $this->activity->id,
                    'note' => trim($note),
                    'note_length' => strlen(trim($note)),
                ],
                'risk_level' => 2,
            ]);

            $this->note = '';
            $this->showNoteForm = false;

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => '註記已新增'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '新增註記失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 顯示註記表單
     */
    public function showNoteForm(): void
    {
        $this->showNoteForm = true;
        $this->note = '';
    }

    /**
     * 隱藏註記表單
     */
    public function hideNoteForm(): void
    {
        $this->showNoteForm = false;
        $this->note = '';
    }

    /**
     * 導航到上一筆記錄
     */
    public function goToPrevious(): void
    {
        if ($this->previousActivityId) {
            $this->loadActivity($this->previousActivityId);
        }
    }

    /**
     * 導航到下一筆記錄
     */
    public function goToNext(): void
    {
        if ($this->nextActivityId) {
            $this->loadActivity($this->nextActivityId);
        }
    }

    /**
     * 關閉詳情對話框
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->activity = null;
        $this->reset([
            'showRawData', 'showRelatedActivities', 'isSuspicious', 
            'note', 'showNoteForm', 'previousActivityId', 'nextActivityId'
        ]);
    }

    /**
     * 檢視相關活動詳情
     */
    public function viewRelatedActivity(int $activityId): void
    {
        $this->loadActivity($activityId);
    }

    /**
     * 複製活動資料到剪貼簿
     */
    public function copyToClipboard(): void
    {
        if (!$this->activity) {
            return;
        }

        $copyData = [
            'ID' => $this->activity->id,
            '類型' => $this->activity->type,
            '描述' => $this->activity->description,
            '使用者' => $this->activity->user?->name ?? '系統',
            'IP位址' => $this->activity->ip_address,
            '時間' => $this->activity->created_at->format('Y-m-d H:i:s'),
            '風險等級' => $this->activity->risk_level_text,
        ];

        $copyText = '';
        foreach ($copyData as $key => $value) {
            $copyText .= "{$key}: {$value}\n";
        }

        $this->dispatch('copy-to-clipboard', text: $copyText);
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => '活動資料已複製到剪貼簿'
        ]);
    }

    /**
     * 監聽開啟活動詳情事件
     */
    #[On('open-activity-detail')]
    public function openActivityDetail(int $activityId): void
    {
        $this->loadActivity($activityId);
    }

    /**
     * 載入導航 ID
     */
    protected function loadNavigationIds(): void
    {
        if (!$this->activity) {
            return;
        }

        // 取得前後記錄的 ID（基於建立時間排序）
        $previousActivity = Activity::where('created_at', '<', $this->activity->created_at)
            ->orderBy('created_at', 'desc')
            ->first(['id']);

        $nextActivity = Activity::where('created_at', '>', $this->activity->created_at)
            ->orderBy('created_at', 'asc')
            ->first(['id']);

        $this->previousActivityId = $previousActivity?->id;
        $this->nextActivityId = $nextActivity?->id;
    }

    /**
     * 格式化屬性資料
     */
    protected function formatProperties(array $properties): array
    {
        $formatted = [];

        foreach ($properties as $key => $value) {
            $formattedKey = $this->formatPropertyKey($key);
            $formattedValue = $this->formatPropertyValue($value);
            
            $formatted[] = [
                'key' => $formattedKey,
                'value' => $formattedValue,
                'raw_key' => $key,
                'raw_value' => $value,
                'type' => gettype($value),
            ];
        }

        return $formatted;
    }

    /**
     * 格式化屬性鍵名
     */
    protected function formatPropertyKey(string $key): string
    {
        // 將底線轉換為空格並首字母大寫
        $formatted = str_replace('_', ' ', $key);
        $formatted = ucwords($formatted);

        // 特殊鍵名翻譯
        $translations = [
            'User Id' => '使用者 ID',
            'Username' => '使用者名稱',
            'Email' => '電子郵件',
            'Ip Address' => 'IP 位址',
            'User Agent' => '使用者代理',
            'Session Id' => 'Session ID',
            'Login Method' => '登入方式',
            'Logout Method' => '登出方式',
            'Remember Me' => '記住我',
            'Role Ids' => '角色 ID',
            'Permission Ids' => '權限 ID',
            'Old Value' => '舊值',
            'New Value' => '新值',
            'Changes' => '變更內容',
            'Reason' => '原因',
            'Success' => '成功',
            'Failed' => '失敗',
            'Error Message' => '錯誤訊息',
        ];

        return $translations[$formatted] ?? $formatted;
    }

    /**
     * 格式化屬性值
     */
    protected function formatPropertyValue($value): string
    {
        if (is_null($value)) {
            return '(空值)';
        }

        if (is_bool($value)) {
            return $value ? '是' : '否';
        }

        if (is_array($value)) {
            if (empty($value)) {
                return '(空陣列)';
            }
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        if (is_string($value)) {
            // 檢查是否為 JSON 字串
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }

            // 檢查是否為時間戳
            if (is_numeric($value) && strlen($value) === 10) {
                try {
                    $date = \Carbon\Carbon::createFromTimestamp($value);
                    return $date->format('Y-m-d H:i:s') . ' (' . $value . ')';
                } catch (\Exception $e) {
                    // 不是有效的時間戳，繼續處理
                }
            }

            // 限制字串長度
            if (strlen($value) > 200) {
                return substr($value, 0, 200) . '...';
            }
        }

        return (string) $value;
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.activities.activity-detail', [
            'relatedActivities' => $this->relatedActivities,
            'formattedData' => $this->formattedData,
            'securityRiskLevel' => $this->securityRiskLevel,
            'riskColor' => $this->riskColor,
            'riskIcon' => $this->riskIcon,
            'isSecurityEvent' => $this->isSecurityEvent,
        ]);
    }
}