<?php

namespace App\Livewire\Admin\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SecuritySettings extends Component
{
    public $two_factor_enabled = false;
    public $login_notifications = true;
    public $security_alerts = true;
    public $session_timeout = 120; // 分鐘
    public $recent_activities = [];

    public function mount()
    {
        $user = Auth::user();
        
        $this->two_factor_enabled = $user->two_factor_enabled ?? false;
        $this->login_notifications = $user->login_notifications ?? true;
        $this->security_alerts = $user->security_alerts ?? true;
        $this->session_timeout = $user->session_timeout ?? 120;
        
        $this->loadRecentActivities();
    }

    public function toggleTwoFactor()
    {
        $this->authorize('profile.edit');
        
        $user = Auth::user();
        
        $this->two_factor_enabled = !$this->two_factor_enabled;
        
        $user->update([
            'two_factor_enabled' => $this->two_factor_enabled,
        ]);

        // 記錄活動
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'action' => $this->two_factor_enabled ? 'enabled' : 'disabled',
                'ip_address' => request()->ip(),
            ])
            ->log('兩步驟驗證' . ($this->two_factor_enabled ? '已啟用' : '已停用'));

        session()->flash('security_success', 
            '兩步驟驗證已' . ($this->two_factor_enabled ? '啟用' : '停用') . '！'
        );
    }

    public function updateNotificationSettings()
    {
        $this->authorize('profile.edit');
        
        $user = Auth::user();
        
        $user->update([
            'login_notifications' => $this->login_notifications,
            'security_alerts' => $this->security_alerts,
            'session_timeout' => $this->session_timeout,
        ]);

        // 記錄活動
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'login_notifications' => $this->login_notifications,
                'security_alerts' => $this->security_alerts,
                'session_timeout' => $this->session_timeout,
            ])
            ->log('安全通知設定已更新');

        session()->flash('security_success', '安全設定已更新！');
    }

    public function terminateOtherSessions()
    {
        $this->authorize('profile.edit');
        
        // 這裡可以實作終止其他會話的邏輯
        // 例如：清除其他裝置的 session
        
        session()->flash('security_success', '其他裝置的會話已終止！');
    }

    private function loadRecentActivities()
    {
        $user = Auth::user();
        
        // 載入最近的安全相關活動
        $activities = \Spatie\Activitylog\Models\Activity::where('causer_id', $user->id)
            ->where('causer_type', get_class($user))
            ->where(function ($query) {
                $query->where('log_name', 'security')
                      ->orWhere('description', 'like', '%登入%')
                      ->orWhere('description', 'like', '%密碼%')
                      ->orWhere('description', 'like', '%兩步驟%');
            })
            ->latest()
            ->limit(10)
            ->get();
            
        $this->recent_activities = $activities->map(function ($activity) {
            return [
                'description' => $activity->description,
                'created_at' => $activity->created_at,
                'ip_address' => $activity->properties['ip_address'] ?? 'N/A',
                'user_agent' => $this->parseUserAgent($activity->properties['user_agent'] ?? ''),
            ];
        })->toArray();
    }

    private function parseUserAgent($userAgent)
    {
        if (empty($userAgent)) {
            return 'Unknown';
        }
        
        // 簡單的 User Agent 解析
        if (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        }
        
        return 'Unknown Browser';
    }

    public function render()
    {
        return view('livewire.admin.profile.security-settings');
    }
}