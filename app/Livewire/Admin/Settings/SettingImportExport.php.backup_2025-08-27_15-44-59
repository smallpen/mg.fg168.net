<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Features\SupportFileUploads\WithFileUploads;

/**
 * 設定匯入匯出元件
 * 
 * 提供設定的匯入、匯出、衝突處理和結果報告功能
 */
class SettingImportExport extends AdminComponent
{
    use WithFileUploads;

    /**
     * 匯出相關屬性
     */
    public array $exportCategories = [];
    public bool $exportOnlyChanged = false;
    public bool $exportIncludeSystem = false;
    public string $exportFormat = 'json';

    /**
     * 匯入相關屬性
     */
    public $importFile = null;
    public array $importData = [];
    public array $importPreview = [];
    public array $importConflicts = [];
    public string $conflictResolution = 'skip'; // skip, update, merge
    public bool $validateImportData = true;
    public bool $dryRun = false;
    public array $selectedCategories = [];
    public array $selectedSettings = [];

    /**
     * 匯入結果
     */
    public array $importResults = [];
    public bool $showImportResults = false;

    /**
     * 對話框狀態
     */
    public bool $showExportDialog = false;
    public bool $showImportDialog = false;
    public bool $showConflictDialog = false;
    public bool $showPreviewDialog = false;

    /**
     * 步驟狀態
     */
    public string $currentStep = 'upload'; // upload, preview, conflicts, results

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
     * 取得所有可用分類
     */
    #[Computed]
    public function availableCategories(): array
    {
        return $this->getConfigService()->getCategories();
    }

    /**
     * 取得匯出統計
     */
    #[Computed]
    public function exportStats(): array
    {
        $query = $this->getSettingsRepository()->getAllSettings();

        if (!empty($this->exportCategories)) {
            $query = $query->whereIn('category', $this->exportCategories);
        }

        if ($this->exportOnlyChanged) {
            $query = $query->filter(function ($setting) {
                return $setting->is_changed;
            });
        }

        if (!$this->exportIncludeSystem) {
            $query = $query->where('is_system', false);
        }

        return [
            'total' => $query->count(),
            'categories' => $query->groupBy('category')->count(),
            'size_estimate' => round($query->count() * 0.5, 1) . ' KB', // 估算大小
        ];
    }

    /**
     * 開啟匯出對話框
     */
    public function openExportDialog(): void
    {
        $this->showExportDialog = true;
        $this->exportCategories = [];
        $this->exportOnlyChanged = false;
        $this->exportIncludeSystem = false;
    }

    /**
     * 關閉匯出對話框
     */
    public function closeExportDialog(): void
    {
        $this->showExportDialog = false;
    }

    /**
     * 執行匯出
     */
    public function executeExport(): void
    {
        try {
            $filters = [];
            
            if (!empty($this->exportCategories)) {
                $filters['categories'] = $this->exportCategories;
            }
            
            if ($this->exportOnlyChanged) {
                $filters['changed_only'] = true;
            }
            
            if (!$this->exportIncludeSystem) {
                $filters['exclude_system'] = true;
            }

            $data = $this->exportSettings($filters);
            
            $filename = $this->generateExportFilename();
            $content = $this->formatExportData($data);
            
            $this->dispatch('download-file', [
                'content' => $content,
                'filename' => $filename,
                'contentType' => $this->getContentType(),
            ]);

            $this->addFlash('success', "設定已匯出到 {$filename}");
            $this->closeExportDialog();

        } catch (\Exception $e) {
            $this->addFlash('error', "匯出設定時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 開啟匯入對話框
     */
    public function openImportDialog(): void
    {
        $this->resetImportState();
        $this->showImportDialog = true;
        $this->currentStep = 'upload';
    }

    /**
     * 關閉匯入對話框
     */
    public function closeImportDialog(): void
    {
        $this->showImportDialog = false;
        $this->resetImportState();
    }

    /**
     * 處理檔案上傳
     */
    public function updatedImportFile(): void
    {
        if (!$this->importFile) {
            return;
        }

        try {
            $this->validateImportFile();
            $this->parseImportFile();
            $this->generateImportPreview();
            $this->detectConflicts();
            
            if (!empty($this->importConflicts)) {
                $this->currentStep = 'conflicts';
            } else {
                $this->currentStep = 'preview';
            }

        } catch (\Exception $e) {
            $this->addFlash('error', "檔案處理失敗：{$e->getMessage()}");
            $this->importFile = null;
            $this->currentStep = 'upload';
        }
    }

    /**
     * 執行匯入預覽
     */
    public function previewImport(): void
    {
        try {
            $options = [
                'conflict_resolution' => $this->conflictResolution,
                'validate_data' => $this->validateImportData,
                'dry_run' => true,
                'selected_keys' => $this->selectedSettings,
            ];

            $filteredData = $this->getFilteredImportData();
            $results = $this->getSettingsRepository()->importSettings($filteredData, $options);
            
            $this->importResults = $results;
            $this->currentStep = 'preview';

        } catch (\Exception $e) {
            $this->addFlash('error', "預覽匯入時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 執行實際匯入
     */
    public function executeImport(): void
    {
        try {
            $options = [
                'conflict_resolution' => $this->conflictResolution,
                'validate_data' => $this->validateImportData,
                'dry_run' => false,
                'selected_keys' => $this->selectedSettings,
            ];

            $filteredData = $this->getFilteredImportData();
            $results = $this->getSettingsRepository()->importSettings($filteredData, $options);
            
            $this->importResults = $results;
            $this->currentStep = 'results';
            $this->showImportResults = true;

            if ($results['success']) {
                $this->dispatch('settings-imported');
                $this->addFlash('success', '設定匯入完成');
            } else {
                $this->addFlash('warning', '匯入完成，但有部分錯誤');
            }

        } catch (\Exception $e) {
            $this->addFlash('error', "匯入設定時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 切換分類選擇
     */
    public function toggleCategorySelection(string $category): void
    {
        if (in_array($category, $this->selectedCategories)) {
            $this->selectedCategories = array_diff($this->selectedCategories, [$category]);
            
            // 取消選中該分類下的所有設定
            $categorySettings = collect($this->importData)
                ->where('category', $category)
                ->pluck('key')
                ->toArray();
            
            $this->selectedSettings = array_diff($this->selectedSettings, $categorySettings);
        } else {
            $this->selectedCategories[] = $category;
            
            // 選中該分類下的所有設定
            $categorySettings = collect($this->importData)
                ->where('category', $category)
                ->pluck('key')
                ->toArray();
            
            $this->selectedSettings = array_unique(array_merge($this->selectedSettings, $categorySettings));
        }
    }

    /**
     * 切換設定選擇
     */
    public function toggleSettingSelection(string $key): void
    {
        if (in_array($key, $this->selectedSettings)) {
            $this->selectedSettings = array_diff($this->selectedSettings, [$key]);
        } else {
            $this->selectedSettings[] = $key;
        }
    }

    /**
     * 全選/取消全選
     */
    public function toggleSelectAll(): void
    {
        $allKeys = collect($this->importData)->pluck('key')->toArray();
        
        if (count($this->selectedSettings) === count($allKeys)) {
            $this->selectedSettings = [];
            $this->selectedCategories = [];
        } else {
            $this->selectedSettings = $allKeys;
            $this->selectedCategories = collect($this->importData)
                ->pluck('category')
                ->unique()
                ->toArray();
        }
    }

    /**
     * 重設匯入狀態
     */
    protected function resetImportState(): void
    {
        $this->importFile = null;
        $this->importData = [];
        $this->importPreview = [];
        $this->importConflicts = [];
        $this->importResults = [];
        $this->selectedCategories = [];
        $this->selectedSettings = [];
        $this->showImportResults = false;
        $this->currentStep = 'upload';
    }

    /**
     * 驗證匯入檔案
     */
    protected function validateImportFile(): void
    {
        if (!$this->importFile) {
            throw new \Exception('請選擇要匯入的檔案');
        }

        $extension = $this->importFile->getClientOriginalExtension();
        if (!in_array(strtolower($extension), ['json'])) {
            throw new \Exception('僅支援 JSON 格式的檔案');
        }

        $size = $this->importFile->getSize();
        if ($size > 10 * 1024 * 1024) { // 10MB
            throw new \Exception('檔案大小不能超過 10MB');
        }
    }

    /**
     * 解析匯入檔案
     */
    protected function parseImportFile(): void
    {
        $content = file_get_contents($this->importFile->getRealPath());
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('檔案格式錯誤：' . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new \Exception('檔案內容格式不正確');
        }

        // 檢查是否為設定匯出格式
        if (!$this->isValidSettingsFormat($data)) {
            throw new \Exception('檔案不是有效的設定匯出格式');
        }

        $this->importData = $data;
    }

    /**
     * 檢查是否為有效的設定格式
     */
    protected function isValidSettingsFormat(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        // 檢查第一個項目是否包含必要欄位
        $firstItem = reset($data);
        $requiredFields = ['key', 'value', 'category', 'type'];

        foreach ($requiredFields as $field) {
            if (!isset($firstItem[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 生成匯入預覽
     */
    protected function generateImportPreview(): void
    {
        $this->importPreview = [
            'total' => count($this->importData),
            'categories' => collect($this->importData)->groupBy('category')->map->count()->toArray(),
            'types' => collect($this->importData)->groupBy('type')->map->count()->toArray(),
            'new_settings' => 0,
            'existing_settings' => 0,
        ];

        foreach ($this->importData as $setting) {
            $existing = $this->getSettingsRepository()->getSetting($setting['key']);
            if ($existing) {
                $this->importPreview['existing_settings']++;
            } else {
                $this->importPreview['new_settings']++;
            }
        }

        // 預設選中所有設定
        $this->selectedSettings = collect($this->importData)->pluck('key')->toArray();
        $this->selectedCategories = collect($this->importData)->pluck('category')->unique()->toArray();
    }

    /**
     * 偵測衝突
     */
    protected function detectConflicts(): void
    {
        $this->importConflicts = [];

        foreach ($this->importData as $setting) {
            $existing = $this->getSettingsRepository()->getSetting($setting['key']);
            
            if ($existing) {
                $conflict = [
                    'key' => $setting['key'],
                    'category' => $setting['category'],
                    'existing_value' => $existing->value,
                    'new_value' => $setting['value'],
                    'existing_description' => $existing->description,
                    'new_description' => $setting['description'] ?? '',
                    'is_system' => $existing->is_system,
                    'has_value_conflict' => $existing->value !== $setting['value'],
                    'has_description_conflict' => $existing->description !== ($setting['description'] ?? ''),
                ];

                if ($conflict['has_value_conflict'] || $conflict['has_description_conflict']) {
                    $this->importConflicts[] = $conflict;
                }
            }
        }
    }

    /**
     * 取得篩選後的匯入資料
     */
    protected function getFilteredImportData(): array
    {
        if (empty($this->selectedSettings)) {
            return [];
        }

        return collect($this->importData)
            ->whereIn('key', $this->selectedSettings)
            ->toArray();
    }

    /**
     * 匯出設定
     */
    protected function exportSettings(array $filters = []): array
    {
        $settings = $this->getSettingsRepository()->getAllSettings();

        // 應用篩選
        if (!empty($filters['categories'])) {
            $settings = $settings->whereIn('category', $filters['categories']);
        }

        if (!empty($filters['changed_only'])) {
            $settings = $settings->filter(function ($setting) {
                return $setting->is_changed;
            });
        }

        if (!empty($filters['exclude_system'])) {
            $settings = $settings->where('is_system', false);
        }

        return $settings->map(function ($setting) {
            return [
                'key' => $setting->key,
                'value' => $setting->value,
                'category' => $setting->category,
                'type' => $setting->type,
                'options' => $setting->options,
                'description' => $setting->description,
                'default_value' => $setting->default_value,
                'is_encrypted' => $setting->is_encrypted,
                'is_system' => $setting->is_system,
                'is_public' => $setting->is_public,
                'sort_order' => $setting->sort_order,
                'exported_at' => now()->toISOString(),
                'exported_by' => auth()->user()->name ?? 'System',
            ];
        })->values()->toArray();
    }

    /**
     * 生成匯出檔名
     */
    protected function generateExportFilename(): string
    {
        $prefix = 'settings_export';
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        if (!empty($this->exportCategories)) {
            $categories = implode('-', $this->exportCategories);
            $prefix .= "_{$categories}";
        }
        
        if ($this->exportOnlyChanged) {
            $prefix .= '_changed';
        }
        
        return "{$prefix}_{$timestamp}.{$this->exportFormat}";
    }

    /**
     * 格式化匯出資料
     */
    protected function formatExportData(array $data): string
    {
        switch ($this->exportFormat) {
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            default:
                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 取得內容類型
     */
    protected function getContentType(): string
    {
        switch ($this->exportFormat) {
            case 'json':
                return 'application/json';
            default:
                return 'application/json';
        }
    }

    /**
     * 監聽開啟匯出對話框事件
     */
    #[On('open-export-dialog')]
    public function handleOpenExportDialog(): void
    {
        $this->openExportDialog();
    }

    /**
     * 監聽開啟匯入對話框事件
     */
    #[On('open-import-dialog')]
    public function handleOpenImportDialog(): void
    {
        $this->openImportDialog();
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.settings.setting-import-export');
    }
}