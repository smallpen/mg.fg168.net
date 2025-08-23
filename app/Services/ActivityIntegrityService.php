<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 活動記錄完整性保護服務
 * 
 * 提供數位簽章生成、驗證和完整性檢查功能
 * 確保活動記錄的不可篡改性和審計追蹤完整性
 */
class ActivityIntegrityService
{
    /**
     * 簽章演算法
     */
    private const SIGNATURE_ALGORITHM = 'sha256';
    
    /**
     * 簽章版本
     */
    private const SIGNATURE_VERSION = 'v1';
    
    /**
     * 為活動記錄生成數位簽章
     * 
     * @param array $data 活動記錄資料
     * @return string 數位簽章
     */
    public function generateSignature(array $data): string
    {
        // 移除不參與簽章的欄位
        $signatureData = $this->prepareDataForSignature($data);
        
        // 將資料轉換為標準化的 JSON 字串
        $payload = json_encode($signatureData, 64 | 256); // JSON_SORT_KEYS | JSON_UNESCAPED_UNICODE
        
        // 生成 HMAC 簽章
        $signature = hash_hmac(self::SIGNATURE_ALGORITHM, $payload, $this->getSigningKey());
        
        // 加上版本前綴
        return self::SIGNATURE_VERSION . ':' . $signature;
    }
    
    /**
     * 驗證活動記錄的完整性
     * 
     * @param Activity $activity 活動記錄
     * @return bool 驗證結果
     */
    public function verifyActivity(Activity $activity): bool
    {
        try {
            // 準備驗證資料
            $data = $this->prepareActivityDataForVerification($activity);
            
            // 生成預期的簽章
            $expectedSignature = $this->generateSignature($data);
            
            // 比較簽章
            return hash_equals($expectedSignature, $activity->signature ?? '');
        } catch (\Exception $e) {
            Log::error('活動記錄完整性驗證失敗', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * 批量驗證活動記錄完整性
     * 
     * @param Collection $activities 活動記錄集合
     * @return array 驗證結果 [activity_id => bool]
     */
    public function verifyBatch(Collection $activities): array
    {
        $results = [];
        
        foreach ($activities as $activity) {
            $results[$activity->id] = $this->verifyActivity($activity);
        }
        
        return $results;
    }
    
    /**
     * 執行完整性檢查並生成報告
     * 
     * @param array $options 檢查選項
     * @return array 完整性檢查報告
     */
    public function performIntegrityCheck(array $options = []): array
    {
        $startTime = microtime(true);
        $batchSize = $options['batch_size'] ?? 1000;
        $dateFrom = $options['date_from'] ?? null;
        $dateTo = $options['date_to'] ?? null;
        
        $report = [
            'started_at' => Carbon::now(),
            'total_checked' => 0,
            'valid_records' => 0,
            'invalid_records' => 0,
            'missing_signatures' => 0,
            'corrupted_records' => [],
            'execution_time' => 0,
            'status' => 'completed'
        ];
        
        try {
            // 建立查詢
            $query = Activity::query();
            
            if ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            }
            
            if ($dateTo) {
                $query->where('created_at', '<=', $dateTo);
            }
            
            // 分批處理
            $query->chunk($batchSize, function ($activities) use (&$report) {
                foreach ($activities as $activity) {
                    $report['total_checked']++;
                    
                    // 檢查是否有簽章
                    if (empty($activity->signature)) {
                        $report['missing_signatures']++;
                        continue;
                    }
                    
                    // 驗證完整性
                    if ($this->verifyActivity($activity)) {
                        $report['valid_records']++;
                    } else {
                        $report['invalid_records']++;
                        $report['corrupted_records'][] = [
                            'id' => $activity->id,
                            'type' => $activity->type,
                            'created_at' => $activity->created_at,
                            'causer_id' => $activity->causer_id
                        ];
                    }
                }
            });
            
        } catch (\Exception $e) {
            $report['status'] = 'failed';
            $report['error'] = $e->getMessage();
            
            Log::error('完整性檢查執行失敗', [
                'error' => $e->getMessage(),
                'options' => $options
            ]);
        }
        
        $report['execution_time'] = round(microtime(true) - $startTime, 2);
        $report['completed_at'] = Carbon::now();
        
        // 記錄檢查結果
        Log::info('活動記錄完整性檢查完成', $report);
        
        return $report;
    }
    
    /**
     * 檢測記錄篡改嘗試
     * 
     * @param Activity $activity 活動記錄
     * @param array $originalData 原始資料
     * @return bool 是否檢測到篡改
     */
    public function detectTamperingAttempt(Activity $activity, array $originalData): bool
    {
        // 比較關鍵欄位
        $criticalFields = ['type', 'description', 'user_id', 'subject_id', 'created_at'];
        
        foreach ($criticalFields as $field) {
            $currentValue = $activity->getAttribute($field);
            
            // 處理欄位名稱映射
            $originalField = $field === 'user_id' ? 'causer_id' : $field;
            $originalValue = $originalData[$originalField] ?? null;
            
            if ($currentValue !== $originalValue) {
                // 記錄篡改嘗試
                Log::warning('檢測到活動記錄篡改嘗試', [
                    'activity_id' => $activity->id,
                    'field' => $field,
                    'original_value' => $originalValue,
                    'current_value' => $currentValue,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]);
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 重新生成活動記錄的簽章
     * 
     * @param Activity $activity 活動記錄
     * @return string 新的簽章
     */
    public function regenerateSignature(Activity $activity): string
    {
        $data = $this->prepareActivityDataForVerification($activity);
        $newSignature = $this->generateSignature($data);
        
        // 更新簽章
        $activity->update(['signature' => $newSignature]);
        
        Log::info('重新生成活動記錄簽章', [
            'activity_id' => $activity->id,
            'old_signature' => $activity->getOriginal('signature'),
            'new_signature' => $newSignature
        ]);
        
        return $newSignature;
    }
    
    /**
     * 準備用於簽章的資料
     * 
     * @param array $data 原始資料
     * @return array 處理後的資料
     */
    private function prepareDataForSignature(array $data): array
    {
        // 移除不參與簽章的欄位
        $excludeFields = ['id', 'signature', 'updated_at'];
        
        $signatureData = [];
        foreach ($data as $key => $value) {
            if (!in_array($key, $excludeFields)) {
                $signatureData[$key] = $value;
            }
        }
        
        // 確保時間戳記格式一致
        if (isset($signatureData['created_at'])) {
            $signatureData['created_at'] = Carbon::parse($signatureData['created_at'])->toISOString();
        }
        
        return $signatureData;
    }
    
    /**
     * 準備活動記錄資料用於驗證
     * 
     * @param Activity $activity 活動記錄
     * @return array 驗證資料
     */
    private function prepareActivityDataForVerification(Activity $activity): array
    {
        return $this->prepareDataForSignature([
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
            'created_at' => $activity->created_at
        ]);
    }
    
    /**
     * 取得簽章金鑰
     * 
     * @return string 簽章金鑰
     */
    private function getSigningKey(): string
    {
        $key = config('app.key');
        
        if (empty($key)) {
            throw new \RuntimeException('應用程式金鑰未設定，無法生成簽章');
        }
        
        // 移除 base64: 前綴（如果存在）
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }
        
        return $key . ':activity_integrity';
    }
}