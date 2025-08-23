<?php

namespace App\Observers;

use App\Models\Activity;
use App\Services\ActivityIntegrityService;
use App\Services\SensitiveDataFilter;
use Illuminate\Support\Facades\Log;

/**
 * 活動記錄觀察者
 * 
 * 處理活動記錄的完整性保護和敏感資料過濾
 */
class ActivityObserver
{
    /**
     * 完整性服務
     */
    protected ActivityIntegrityService $integrityService;
    
    /**
     * 敏感資料過濾服務
     */
    protected SensitiveDataFilter $sensitiveDataFilter;
    
    /**
     * 建構子
     */
    public function __construct(
        ActivityIntegrityService $integrityService,
        SensitiveDataFilter $sensitiveDataFilter
    ) {
        $this->integrityService = $integrityService;
        $this->sensitiveDataFilter = $sensitiveDataFilter;
    }
    
    /**
     * 建立活動記錄前的處理
     * 
     * @param Activity $activity 活動記錄
     * @return void
     */
    public function creating(Activity $activity): void
    {
        // 過濾敏感資料
        if ($activity->properties) {
            $activity->properties = $this->sensitiveDataFilter->filterProperties($activity->properties);
        }
        
        // 過濾描述中的敏感資料
        if ($activity->description) {
            $activity->description = $this->sensitiveDataFilter->filterText($activity->description);
        }
        
        // 生成數位簽章
        $this->generateSignature($activity);
    }
    
    /**
     * 活動記錄建立後的處理
     * 
     * @param Activity $activity 活動記錄
     * @return void
     */
    public function created(Activity $activity): void
    {
        // 記錄活動建立事件
        Log::info('活動記錄已建立', [
            'activity_id' => $activity->id,
            'type' => $activity->type,
            'user_id' => $activity->user_id,
            'has_signature' => !empty($activity->signature)
        ]);
    }
    
    /**
     * 更新活動記錄前的處理
     * 
     * @param Activity $activity 活動記錄
     * @return void
     */
    public function updating(Activity $activity): void
    {
        // 檢查是否嘗試修改受保護的欄位
        $protectedFields = ['type', 'description', 'user_id', 'subject_id', 'created_at', 'signature'];
        $changedFields = array_keys($activity->getDirty());
        $protectedChanges = array_intersect($changedFields, $protectedFields);
        
        if (!empty($protectedChanges)) {
            // 記錄篡改嘗試
            Log::warning('檢測到活動記錄篡改嘗試', [
                'activity_id' => $activity->id,
                'changed_fields' => $protectedChanges,
                'original_data' => $activity->getOriginal(),
                'new_data' => $activity->getDirty(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'user_id' => auth()->id()
            ]);
            
            // 阻止修改（拋出例外）
            throw new \Exception('活動記錄不允許修改，以確保審計追蹤的完整性');
        }
    }
    
    /**
     * 刪除活動記錄前的處理
     * 
     * @param Activity $activity 活動記錄
     * @return void
     */
    public function deleting(Activity $activity): void
    {
        // 記錄刪除嘗試
        Log::warning('檢測到活動記錄刪除嘗試', [
            'activity_id' => $activity->id,
            'type' => $activity->type,
            'user_id' => $activity->user_id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'current_user_id' => auth()->id()
        ]);
        
        // 阻止刪除（拋出例外）
        throw new \Exception('活動記錄不允許刪除，以確保審計追蹤的完整性');
    }
    
    /**
     * 為活動記錄生成數位簽章
     * 
     * @param Activity $activity 活動記錄
     * @return void
     */
    protected function generateSignature(Activity $activity): void
    {
        try {
            // 準備簽章資料
            $data = [
                'type' => $activity->type,
                'description' => $activity->description,
                'subject_type' => $activity->subject_type,
                'subject_id' => $activity->subject_id,
                'causer_type' => $activity->user_id ? 'App\\Models\\User' : null,
                'causer_id' => $activity->user_id,
                'properties' => $activity->properties,
                'ip_address' => $activity->ip_address,
                'user_agent' => $activity->user_agent,
                'result' => $activity->result,
                'risk_level' => $activity->risk_level,
                'created_at' => $activity->created_at ?? now()
            ];
            
            // 生成簽章
            $signature = $this->integrityService->generateSignature($data);
            $activity->signature = $signature;
            
        } catch (\Exception $e) {
            Log::error('生成活動記錄簽章失敗', [
                'activity_type' => $activity->type,
                'error' => $e->getMessage()
            ]);
            
            // 在生產環境中，可能需要拋出例外以確保完整性
            if (app()->environment('production')) {
                throw new \Exception('無法生成活動記錄簽章，操作已中止');
            }
        }
    }
}