<?php

namespace App\Observers;

use App\Models\Setting;
use App\Models\SettingChange;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * 設定觀察者
 * 
 * 負責記錄設定的變更歷史和審計日誌
 */
class SettingObserver
{
    /**
     * Handle the Setting "creating" event.
     */
    public function creating(Setting $setting): void
    {
        // 記錄設定建立日誌
        Log::info('設定即將建立', [
            'setting_key' => $setting->key,
            'category' => $setting->category,
            'type' => $setting->type,
            'is_encrypted' => $setting->is_encrypted,
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Handle the Setting "created" event.
     */
    public function created(Setting $setting): void
    {
        // 記錄設定建立的變更記錄
        $this->logChange($setting, null, $setting->value, '建立設定');
        
        Log::info('設定已建立', [
            'setting_key' => $setting->key,
            'setting_id' => $setting->id,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Handle the Setting "updating" event.
     */
    public function updating(Setting $setting): void
    {
        Log::info('設定即將更新', [
            'setting_key' => $setting->key,
            'setting_id' => $setting->id,
            'old_value' => $this->maskSensitiveValue($setting, $setting->getOriginal('value')),
            'new_value' => $this->maskSensitiveValue($setting, $setting->value),
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Handle the Setting "updated" event.
     */
    public function updated(Setting $setting): void
    {
        // 檢查值是否真的有變更
        if ($setting->wasChanged('value')) {
            $oldValue = $setting->getOriginal('value');
            $newValue = $setting->value;
            
            // 記錄變更
            $this->logChange($setting, $oldValue, $newValue, '更新設定');
            
            // 清除快取
            $setting->clearCache();
            
            Log::info('設定已更新', [
                'setting_key' => $setting->key,
                'setting_id' => $setting->id,
                'user_id' => Auth::id(),
            ]);
        }
        
        // 檢查其他重要欄位的變更
        if ($setting->wasChanged(['is_encrypted', 'is_system', 'category', 'type'])) {
            Log::warning('設定重要屬性已變更', [
                'setting_key' => $setting->key,
                'setting_id' => $setting->id,
                'changes' => $setting->getChanges(),
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Handle the Setting "deleting" event.
     */
    public function deleting(Setting $setting): void
    {
        Log::warning('設定即將刪除', [
            'setting_key' => $setting->key,
            'setting_id' => $setting->id,
            'category' => $setting->category,
            'is_system' => $setting->is_system,
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Handle the Setting "deleted" event.
     */
    public function deleted(Setting $setting): void
    {
        // 記錄刪除變更
        $this->logChange($setting, $setting->value, null, '刪除設定');
        
        // 清除相關快取
        $setting->clearCache();
        
        Log::warning('設定已刪除', [
            'setting_key' => $setting->key,
            'setting_id' => $setting->id,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Handle the Setting "restored" event.
     */
    public function restored(Setting $setting): void
    {
        // 記錄還原變更
        $this->logChange($setting, null, $setting->value, '還原設定');
        
        Log::info('設定已還原', [
            'setting_key' => $setting->key,
            'setting_id' => $setting->id,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Handle the Setting "force deleted" event.
     */
    public function forceDeleted(Setting $setting): void
    {
        Log::critical('設定已強制刪除', [
            'setting_key' => $setting->key,
            'setting_id' => $setting->id,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * 記錄設定變更
     *
     * @param Setting $setting
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param string $reason
     * @return void
     */
    protected function logChange(Setting $setting, $oldValue, $newValue, string $reason = ''): void
    {
        // 只有在有使用者登入時才記錄變更
        if (!Auth::id()) {
            return;
        }

        try {
            SettingChange::create([
                'setting_key' => $setting->key,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'changed_by' => Auth::user() ? Auth::user()->id : null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::error('記錄設定變更失敗', [
                'setting_key' => $setting->key,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * 遮罩敏感設定值用於日誌記錄
     *
     * @param Setting $setting
     * @param mixed $value
     * @return mixed
     */
    protected function maskSensitiveValue(Setting $setting, $value)
    {
        if ($setting->is_encrypted || in_array($setting->type, ['password', 'api_key', 'secret', 'token'])) {
            return '***';
        }
        
        return $value;
    }
}
