<?php

namespace App\Livewire\Admin\Security;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * 維護模式管理元件
 * 
 * 負責管理系統的維護模式狀態
 */
class MaintenanceMode extends Component
{
    /**
     * 維護模式狀態
     */
    public bool $isMaintenanceMode = false;
    
    /**
     * 維護訊息
     */
    public string $maintenanceMessage = '';
    
    /**
     * 預計恢復時間
     */
    public string $estimatedRecoveryTime = '';
    
    /**
     * 允許的 IP 地址
     */
    public array $allowedIps = [];
    
    /**
     * 新增 IP 地址
     */
    public string $newIpAddress = '';
    
    /**
     * 是否正在處理
     */
    public bool $processing = false;
    
    /**
     * 維護模式設定
     */
    public array $maintenanceSettings = [
        'show_progress' => false,
        'allow_admin_access' => true,
        'redirect_url' => '',
        'custom_template' => false,
    ];
    
    /**
     * 初始化元件
     */
    public function mount()
    {
        $this->checkMaintenanceStatus();
        $this->loadMaintenanceSettings();
    }
    
    /**
     * 檢查維護模式狀態
     */
    public function checkMaintenanceStatus(): void
    {
        $this->isMaintenanceMode = app()->isDownForMaintenance();
        
        if ($this->isMaintenanceMode) {
            $this->loadMaintenanceData();
        }
    }
    
    /**
     * 載入維護模式資料
     */
    protected function loadMaintenanceData(): void
    {
        $maintenanceFile = storage_path('framework/maintenance.php');
        
        if (File::exists($maintenanceFile)) {
            $data = include $maintenanceFile;
            
            $this->maintenanceMessage = $data['message'] ?? '系統正在維護中，請稍後再試。';
            $this->estimatedRecoveryTime = $data['retry'] ?? '';
            $this->allowedIps = $data['allowed'] ?? [];
        }
    }
    
    /**
     * 載入維護模式設定
     */
    protected function loadMaintenanceSettings(): void
    {
        $settings = config('maintenance', []);
        
        $this->maintenanceSettings = array_merge($this->maintenanceSettings, $settings);
    }
    
    /**
     * 啟用維護模式
     */
    public function enableMaintenanceMode(): void
    {
        if (!auth()->user()->can('manage_system')) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '您沒有權限執行此操作'
            ]);
            return;
        }
        
        $this->processing = true;
        
        try {
            $options = [];
            
            // 設定維護訊息
            if (!empty($this->maintenanceMessage)) {
                $options['message'] = $this->maintenanceMessage;
            }
            
            // 設定預計恢復時間
            if (!empty($this->estimatedRecoveryTime)) {
                $options['retry'] = strtotime($this->estimatedRecoveryTime);
            }
            
            // 設定允許的 IP
            if (!empty($this->allowedIps)) {
                $options['allow'] = $this->allowedIps;
            }
            
            // 如果允許管理員存取，加入當前 IP
            if ($this->maintenanceSettings['allow_admin_access']) {
                $currentIp = request()->ip();
                if (!in_array($currentIp, $this->allowedIps)) {
                    $this->allowedIps[] = $currentIp;
                    $options['allow'] = $this->allowedIps;
                }
            }
            
            // 執行維護模式命令
            $command = 'down';
            foreach ($options as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $item) {
                        $command .= " --{$key}={$item}";
                    }
                } else {
                    $command .= " --{$key}=\"{$value}\"";
                }
            }
            
            Artisan::call($command);
            
            $this->isMaintenanceMode = true;
            
            // 記錄操作
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'message' => $this->maintenanceMessage,
                    'allowed_ips' => $this->allowedIps,
                    'estimated_recovery' => $this->estimatedRecoveryTime,
                ])
                ->log('啟用維護模式');
            
            $this->dispatch('toast', [
                'type' => 'success',
                'message' => '維護模式已啟用'
            ]);
            
            $this->dispatch('maintenance-mode-enabled');
            
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '啟用維護模式失敗：' . $e->getMessage()
            ]);
        } finally {
            $this->processing = false;
        }
    }
    
    /**
     * 停用維護模式
     */
    public function disableMaintenanceMode(): void
    {
        if (!auth()->user()->can('manage_system')) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '您沒有權限執行此操作'
            ]);
            return;
        }
        
        $this->processing = true;
        
        try {
            Artisan::call('up');
            
            $this->isMaintenanceMode = false;
            $this->maintenanceMessage = '';
            $this->estimatedRecoveryTime = '';
            $this->allowedIps = [];
            
            // 記錄操作
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'ip' => request()->ip(),
                ])
                ->log('停用維護模式');
            
            $this->dispatch('toast', [
                'type' => 'success',
                'message' => '維護模式已停用'
            ]);
            
            $this->dispatch('maintenance-mode-disabled');
            
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '停用維護模式失敗：' . $e->getMessage()
            ]);
        } finally {
            $this->processing = false;
        }
    }
    
    /**
     * 新增允許的 IP 地址
     */
    public function addAllowedIp(): void
    {
        if (empty($this->newIpAddress)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '請輸入有效的 IP 地址'
            ]);
            return;
        }
        
        // 驗證 IP 地址格式
        if (!filter_var($this->newIpAddress, FILTER_VALIDATE_IP)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'IP 地址格式不正確'
            ]);
            return;
        }
        
        // 檢查是否已存在
        if (in_array($this->newIpAddress, $this->allowedIps)) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => '此 IP 地址已在允許清單中'
            ]);
            return;
        }
        
        $this->allowedIps[] = $this->newIpAddress;
        $this->newIpAddress = '';
        
        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'IP 地址已新增到允許清單'
        ]);
    }
    
    /**
     * 移除允許的 IP 地址
     */
    public function removeAllowedIp(string $ip): void
    {
        $this->allowedIps = array_values(array_filter($this->allowedIps, function($allowedIp) use ($ip) {
            return $allowedIp !== $ip;
        }));
        
        $this->dispatch('toast', [
            'type' => 'info',
            'message' => 'IP 地址已從允許清單移除'
        ]);
    }
    
    /**
     * 新增當前 IP 到允許清單
     */
    public function addCurrentIp(): void
    {
        $currentIp = request()->ip();
        
        if (!in_array($currentIp, $this->allowedIps)) {
            $this->allowedIps[] = $currentIp;
            
            $this->dispatch('toast', [
                'type' => 'success',
                'message' => "當前 IP ({$currentIp}) 已新增到允許清單"
            ]);
        } else {
            $this->dispatch('toast', [
                'type' => 'info',
                'message' => '當前 IP 已在允許清單中'
            ]);
        }
    }
    
    /**
     * 更新維護模式設定
     */
    public function updateMaintenanceSettings(): void
    {
        try {
            // 這裡可以將設定儲存到設定檔或資料庫
            config(['maintenance' => $this->maintenanceSettings]);
            
            $this->dispatch('toast', [
                'type' => 'success',
                'message' => '維護模式設定已更新'
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '設定更新失敗：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 預覽維護頁面
     */
    public function previewMaintenancePage(): void
    {
        // 開啟新視窗預覽維護頁面
        $this->dispatch('open-preview', [
            'url' => route('maintenance.preview'),
            'message' => $this->maintenanceMessage,
        ]);
    }
    
    /**
     * 取得維護模式狀態文字
     */
    public function getMaintenanceStatusTextProperty(): string
    {
        return $this->isMaintenanceMode ? '啟用中' : '停用中';
    }
    
    /**
     * 取得維護模式狀態顏色
     */
    public function getMaintenanceStatusColorProperty(): string
    {
        return $this->isMaintenanceMode ? 'text-red-600' : 'text-green-600';
    }
    
    /**
     * 監聽維護模式狀態變更
     */
    #[On('check-maintenance-status')]
    public function handleMaintenanceStatusCheck(): void
    {
        $this->checkMaintenanceStatus();
    }
    
    /**
     * 驗證表單
     */
    protected function rules(): array
    {
        return [
            'maintenanceMessage' => 'required|string|max:500',
            'estimatedRecoveryTime' => 'nullable|date|after:now',
            'newIpAddress' => 'nullable|ip',
        ];
    }
    
    /**
     * 驗證訊息
     */
    protected function messages(): array
    {
        return [
            'maintenanceMessage.required' => '請輸入維護訊息',
            'maintenanceMessage.max' => '維護訊息不能超過 500 個字元',
            'estimatedRecoveryTime.date' => '請輸入有效的日期時間',
            'estimatedRecoveryTime.after' => '恢復時間必須是未來的時間',
            'newIpAddress.ip' => '請輸入有效的 IP 地址',
        ];
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.security.maintenance-mode');
    }
}