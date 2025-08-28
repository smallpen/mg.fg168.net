<?php

namespace App\Livewire\Admin\Roles;

use App\Livewire\Admin\AdminComponent;
use Livewire\Attributes\On;

/**
 * 批量操作結果顯示元件
 * 
 * 提供批量操作結果的統一顯示和管理
 */
class BulkOperationResults extends AdminComponent
{
    // 結果顯示狀態
    public bool $showResults = false;
    public string $operationType = '';
    public array $results = [];
    public array $summary = [];

    /**
     * 顯示批量操作結果
     */
    #[On('show-bulk-operation-results')]
    public function showResults(array $data): void
    {
        $this->operationType = $data['operation_type'] ?? '';
        $this->results = $data['results'] ?? [];
        $this->summary = $this->calculateSummary($this->results);
        $this->showResults = true;
    }

    /**
     * 隱藏結果
     */
    public function hideResults(): void
    {
        $this->showResults = false;
        $this->reset(['operationType', 'results', 'summary']);
    }

    /**
     * 計算操作摘要
     */
    private function calculateSummary(array $results): array
    {
        $total = count($results);
        $successful = collect($results)->where('success', true)->count();
        $failed = $total - $successful;

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 1) : 0
        ];
    }

    /**
     * 取得操作類型標題
     */
    public function getOperationTitleProperty(): string
    {
        return match ($this->operationType) {
            'activate' => __('admin.roles.bulk_results.activate_title'),
            'deactivate' => __('admin.roles.bulk_results.deactivate_title'),
            'delete' => __('admin.roles.bulk_results.delete_title'),
            'permissions_add' => __('admin.roles.bulk_results.permissions_add_title'),
            'permissions_remove' => __('admin.roles.bulk_results.permissions_remove_title'),
            'permissions_replace' => __('admin.roles.bulk_results.permissions_replace_title'),
            default => __('admin.roles.bulk_results.default_title')
        };
    }

    /**
     * 取得操作類型圖示
     */
    public function getOperationIconProperty(): string
    {
        return match ($this->operationType) {
            'activate' => 'heroicon-o-play',
            'deactivate' => 'heroicon-o-pause',
            'delete' => 'heroicon-o-trash',
            'permissions_add', 'permissions_remove', 'permissions_replace' => 'heroicon-o-key',
            default => 'heroicon-o-information-circle'
        };
    }

    /**
     * 取得摘要狀態樣式
     */
    public function getSummaryStatusProperty(): string
    {
        if ($this->summary['failed'] === 0) {
            return 'success'; // 全部成功
        } elseif ($this->summary['successful'] === 0) {
            return 'error'; // 全部失敗
        } else {
            return 'warning'; // 部分成功
        }
    }

    /**
     * 取得成功的結果
     */
    public function getSuccessfulResultsProperty(): array
    {
        return collect($this->results)->where('success', true)->toArray();
    }

    /**
     * 取得失敗的結果
     */
    public function getFailedResultsProperty(): array
    {
        return collect($this->results)->where('success', false)->toArray();
    }

    /**
     * 匯出結果為 CSV
     */
    public function exportResults(): void
    {
        $filename = 'bulk_operation_results_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            
            // CSV 標題行
            fputcsv($file, [
                __('admin.roles.bulk_results.csv.role_name'),
                __('admin.roles.bulk_results.csv.status'),
                __('admin.roles.bulk_results.csv.message'),
                __('admin.roles.bulk_results.csv.timestamp')
            ]);

            // 資料行
            foreach ($this->results as $result) {
                fputcsv($file, [
                    $result['role']['display_name'] ?? $result['role']['name'] ?? '',
                    $result['success'] ? __('admin.roles.bulk_results.csv.success') : __('admin.roles.bulk_results.csv.failed'),
                    $result['message'] ?? '',
                    now()->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        $this->dispatch('download-csv', [
            'filename' => $filename,
            'callback' => $callback
        ]);
    }

    /**
     * 重試失敗的操作
     */
    public function retryFailedOperations(): void
    {
        $failedRoleIds = collect($this->results)
            ->where('success', false)
            ->pluck('role.id')
            ->toArray();

        if (!empty($failedRoleIds)) {
            $this->dispatch('retry-bulk-operation', [
                'operation_type' => $this->operationType,
                'role_ids' => $failedRoleIds
            ]);
        }
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.roles.bulk-operation-results');
    }
}