<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Services\SecurityScanService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;

/**
 * 安全檢測元件
 * 
 * 提供系統安全檢測功能，包括配置檢查、檔案權限檢查、資料庫安全檢查等
 */
class SecurityScan extends AdminComponent
{
    /**
     * 檢測結果
     */
    public ?array $scanResults = null;

    /**
     * 正在掃描
     */
    public bool $scanning = false;

    /**
     * 顯示詳細資訊的類別
     */
    public array $expandedCategories = [];

    /**
     * 篩選狀態
     */
    public string $filterStatus = 'all'; // all, pass, warning, fail

    /**
     * 搜尋關鍵字
     */
    public string $search = '';

    /**
     * 自動重新整理
     */
    public bool $autoRefresh = false;

    /**
     * 初始化元件
     */
    public function mount(): void
    {
        parent::mount();
        
        // 載入快取的結果
        $scanResults = $this->getSecurityScanService()->getCachedResults();
        $this->scanResults = $scanResults;
        
        // 如果沒有快取結果，執行一次掃描
        if (!$this->scanResults) {
            $this->runScan();
        }
    }

    /**
     * 取得安全檢測服務實例
     */
    protected function getSecurityScanService(): SecurityScanService
    {
        return app(SecurityScanService::class);
    }

    /**
     * 執行安全掃描
     */
    public function runScan(): void
    {
        $this->scanning = true;
        
        try {
            Log::info('開始執行安全檢測', [
                'user_id' => auth()->id(),
                'ip' => request()->ip()
            ]);

            $this->scanResults = $this->getSecurityScanService()->runFullScan();
            
            $this->addFlash('success', '安全檢測完成');
            
            // 如果有嚴重問題，顯示警告
            if ($this->scanResults['failed_checks'] > 0) {
                $this->addFlash('warning', "發現 {$this->scanResults['failed_checks']} 個安全問題需要處理");
            }

        } catch (\Exception $e) {
            Log::error('安全檢測失敗', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            $this->addFlash('error', '安全檢測失敗: ' . $e->getMessage());
        } finally {
            $this->scanning = false;
        }
    }

    /**
     * 清除快取並重新掃描
     */
    public function refreshScan(): void
    {
        $this->getSecurityScanService()->clearCache();
        $this->runScan();
    }

    /**
     * 切換類別展開狀態
     */
    public function toggleCategory(string $category): void
    {
        if (in_array($category, $this->expandedCategories)) {
            $this->expandedCategories = array_diff($this->expandedCategories, [$category]);
        } else {
            $this->expandedCategories[] = $category;
        }
    }

    /**
     * 展開所有類別
     */
    public function expandAll(): void
    {
        if ($this->scanResults) {
            $this->expandedCategories = array_keys($this->scanResults['categories']);
        }
    }

    /**
     * 收合所有類別
     */
    public function collapseAll(): void
    {
        $this->expandedCategories = [];
    }

    /**
     * 取得篩選後的類別
     */
    #[Computed]
    public function filteredCategories(): array
    {
        if (!$this->scanResults || empty($this->scanResults['categories'])) {
            return [];
        }

        $categories = $this->scanResults['categories'];

        // 根據搜尋關鍵字篩選
        if (!empty($this->search)) {
            foreach ($categories as $key => $category) {
                $matchingChecks = array_filter($category['checks'], function ($check) {
                    return str_contains(strtolower($check['name']), strtolower($this->search)) ||
                           str_contains(strtolower($check['message']), strtolower($this->search));
                });

                if (empty($matchingChecks) && 
                    !str_contains(strtolower($category['name']), strtolower($this->search))) {
                    unset($categories[$key]);
                } else {
                    $categories[$key]['checks'] = array_values($matchingChecks);
                }
            }
        }

        // 根據狀態篩選
        if ($this->filterStatus !== 'all') {
            foreach ($categories as $key => $category) {
                $matchingChecks = array_filter($category['checks'], function ($check) {
                    return $check['status'] === $this->filterStatus;
                });

                if (empty($matchingChecks)) {
                    unset($categories[$key]);
                } else {
                    $categories[$key]['checks'] = array_values($matchingChecks);
                }
            }
        }

        return $categories;
    }

    /**
     * 取得安全等級
     */
    #[Computed]
    public function securityLevel(): array
    {
        if (!$this->scanResults) {
            return [
                'level' => 'unknown',
                'text' => '未知',
                'color' => 'gray',
                'description' => '尚未執行安全檢測'
            ];
        }

        $score = $this->scanResults['overall_score'];

        if ($score >= 90) {
            return [
                'level' => 'excellent',
                'text' => '優秀',
                'color' => 'green',
                'description' => '系統安全性優秀，所有檢查項目都通過'
            ];
        } elseif ($score >= 75) {
            return [
                'level' => 'good',
                'text' => '良好',
                'color' => 'blue',
                'description' => '系統安全性良好，有少數項目需要注意'
            ];
        } elseif ($score >= 60) {
            return [
                'level' => 'fair',
                'text' => '普通',
                'color' => 'yellow',
                'description' => '系統安全性普通，建議改善部分設定'
            ];
        } elseif ($score >= 40) {
            return [
                'level' => 'poor',
                'text' => '不佳',
                'color' => 'orange',
                'description' => '系統安全性不佳，存在多個安全風險'
            ];
        } else {
            return [
                'level' => 'critical',
                'text' => '危險',
                'color' => 'red',
                'description' => '系統安全性危險，存在嚴重安全漏洞'
            ];
        }
    }

    /**
     * 取得狀態統計
     */
    #[Computed]
    public function statusStats(): array
    {
        if (!$this->scanResults) {
            return [
                'pass' => 0,
                'warning' => 0,
                'fail' => 0,
                'error' => 0
            ];
        }

        $stats = [
            'pass' => 0,
            'warning' => 0,
            'fail' => 0,
            'error' => 0
        ];

        foreach ($this->scanResults['categories'] as $category) {
            foreach ($category['checks'] as $check) {
                $stats[$check['status']]++;
            }
        }

        return $stats;
    }

    /**
     * 取得建議修復的項目
     */
    #[Computed]
    public function recommendedFixes(): array
    {
        if (!$this->scanResults) {
            return [];
        }

        $fixes = [];
        
        foreach ($this->scanResults['categories'] as $categoryKey => $category) {
            foreach ($category['checks'] as $check) {
                if ($check['status'] === 'fail') {
                    $fixes[] = [
                        'priority' => 'high',
                        'category' => $category['name'],
                        'check' => $check['name'],
                        'message' => $check['message'],
                        'action' => $this->getRecommendedAction($check)
                    ];
                } elseif ($check['status'] === 'warning') {
                    $fixes[] = [
                        'priority' => 'medium',
                        'category' => $category['name'],
                        'check' => $check['name'],
                        'message' => $check['message'],
                        'action' => $this->getRecommendedAction($check)
                    ];
                }
            }
        }

        // 按優先級排序
        usort($fixes, function ($a, $b) {
            $priorities = ['high' => 3, 'medium' => 2, 'low' => 1];
            return $priorities[$b['priority']] - $priorities[$a['priority']];
        });

        return array_slice($fixes, 0, 10); // 只顯示前 10 個
    }

    /**
     * 取得建議的修復動作
     */
    protected function getRecommendedAction(array $check): string
    {
        $name = $check['name'];
        
        return match(true) {
            str_contains($name, 'PHP 版本') => '升級 PHP 至最新穩定版本',
            str_contains($name, '危險函數') => '在 php.ini 中停用危險函數',
            str_contains($name, 'Debug 模式') => '在 .env 中設定 APP_DEBUG=false',
            str_contains($name, 'APP_KEY') => '執行 php artisan key:generate',
            str_contains($name, 'HTTPS') => '配置 SSL 憑證並強制使用 HTTPS',
            str_contains($name, '密碼政策') => '在安全設定中加強密碼要求',
            str_contains($name, '檔案權限') => '調整檔案權限為適當的數值',
            str_contains($name, 'IP 限制') => '在安全設定中配置允許的 IP 清單',
            str_contains($name, '預設使用者') => '移除或修改預設使用者帳號',
            str_contains($name, '弱密碼') => '要求使用者更新為強密碼',
            default => '請參考檢測結果詳細資訊進行修復'
        };
    }

    /**
     * 匯出檢測報告
     */
    public function exportReport(): void
    {
        if (!$this->scanResults) {
            $this->addFlash('error', '沒有檢測結果可以匯出');
            return;
        }

        try {
            $filename = 'security_scan_report_' . date('Y-m-d_H-i-s') . '.json';
            $content = json_encode($this->scanResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            // 觸發下載
            $this->dispatch('download-file', [
                'filename' => $filename,
                'content' => $content,
                'type' => 'application/json'
            ]);

            Log::info('安全檢測報告已匯出', [
                'filename' => $filename,
                'user_id' => auth()->id()
            ]);

            $this->addFlash('success', '檢測報告已匯出');

        } catch (\Exception $e) {
            Log::error('匯出檢測報告失敗', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            $this->addFlash('error', '匯出報告失敗: ' . $e->getMessage());
        }
    }

    /**
     * 取得狀態圖示
     */
    public function getStatusIcon(string $status): string
    {
        return match($status) {
            'pass' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'warning' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
            'fail' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
            'error' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            default => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
        };
    }

    /**
     * 取得狀態顏色
     */
    public function getStatusColor(string $status): string
    {
        return match($status) {
            'pass' => 'text-green-600',
            'warning' => 'text-yellow-600',
            'fail' => 'text-red-600',
            'error' => 'text-gray-600',
            default => 'text-gray-400'
        };
    }

    /**
     * 取得狀態背景色
     */
    public function getStatusBgColor(string $status): string
    {
        return match($status) {
            'pass' => 'bg-green-100 dark:bg-green-900/20',
            'warning' => 'bg-yellow-100 dark:bg-yellow-900/20',
            'fail' => 'bg-red-100 dark:bg-red-900/20',
            'error' => 'bg-gray-100 dark:bg-gray-900/20',
            default => 'bg-gray-50 dark:bg-gray-800'
        };
    }

    /**
     * 檢查類別是否展開
     */
    public function isCategoryExpanded(string $category): bool
    {
        return in_array($category, $this->expandedCategories);
    }

    /**
     * 取得掃描時間格式化字串
     */
    #[Computed]
    public function scanTimeFormatted(): ?string
    {
        if (!$this->scanResults || !isset($this->scanResults['scan_time'])) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($this->scanResults['scan_time'])
                ->setTimezone(config('app.timezone'))
                ->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return $this->scanResults['scan_time'];
        }
    }

    /**
     * 重設篩選條件
     */
    public function resetFilters(): void
    {
        $this->filterStatus = 'all';
        $this->search = '';
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.settings.security-scan');
    }
}