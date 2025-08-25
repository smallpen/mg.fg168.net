<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 活動記錄 API 資源
 */
class ActivityResource extends JsonResource
{
    /**
     * 將資源轉換為陣列
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'description' => $this->description,
            'result' => $this->result,
            'risk_level' => $this->risk_level,
            'risk_level_text' => $this->getRiskLevelText(),
            
            // 操作者資訊
            'causer' => $this->when($this->causer, [
                'id' => $this->causer?->id,
                'type' => $this->causer_type,
                'name' => $this->causer?->name ?? $this->causer?->username,
                'display_name' => $this->causer?->display_name,
            ]),
            
            // 操作對象資訊
            'subject' => $this->when($this->subject, [
                'id' => $this->subject?->id,
                'type' => $this->subject_type,
                'name' => $this->getSubjectName(),
            ]),
            
            // 技術資訊
            'ip_address' => $this->ip_address,
            'user_agent' => $this->when($request->user()->can('system.logs'), $this->user_agent),
            
            // 屬性資料（根據權限決定是否包含敏感資訊）
            'properties' => $this->when(
                $request->user()->can('system.logs'),
                $this->getFilteredProperties()
            ),
            
            // 時間資訊
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // 完整性驗證
            'is_verified' => $this->when(
                $request->user()->can('system.logs'),
                $this->verifyIntegrity()
            ),
            
            // 安全標記
            'is_security_event' => $this->getIsSecurityEventAttribute(),
            
            // 相關記錄數量
            'related_count' => $this->when(
                $request->input('include_related'),
                $this->getRelatedActivitiesAttribute()->count()
            ),
            
            // API 連結
            'links' => [
                'self' => url("api/v1/activities/{$this->id}"),
                'related' => url("api/v1/activities/{$this->id}/related"),
            ],
        ];
    }

    /**
     * 取得風險等級文字描述
     */
    private function getRiskLevelText(): string
    {
        return match ($this->risk_level) {
            0, 1, 2 => '低風險',
            3, 4, 5 => '中風險',
            6, 7, 8 => '高風險',
            9, 10 => '極高風險',
            default => '未知'
        };
    }

    /**
     * 取得操作對象名稱
     */
    private function getSubjectName(): ?string
    {
        if (!$this->subject) {
            return null;
        }

        // 根據不同的模型類型返回適當的名稱
        return match ($this->subject_type) {
            'App\\Models\\User' => $this->subject->display_name ?? $this->subject->name,
            'App\\Models\\Role' => $this->subject->display_name ?? $this->subject->name,
            'App\\Models\\Permission' => $this->subject->display_name ?? $this->subject->name,
            default => $this->subject->name ?? $this->subject->title ?? "#{$this->subject->id}"
        };
    }

    /**
     * 取得過濾後的屬性資料
     */
    private function getFilteredProperties(): array
    {
        $properties = $this->properties ?? [];
        
        // 移除敏感資訊
        $sensitiveKeys = ['password', 'token', 'secret', 'key', 'api_key'];
        
        return $this->filterSensitiveData($properties, $sensitiveKeys);
    }

    /**
     * 遞迴過濾敏感資料
     */
    private function filterSensitiveData(array $data, array $sensitiveKeys): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->filterSensitiveData($value, $sensitiveKeys);
            } elseif (is_string($key)) {
                foreach ($sensitiveKeys as $sensitiveKey) {
                    if (stripos($key, $sensitiveKey) !== false) {
                        $data[$key] = '[FILTERED]';
                        break;
                    }
                }
            }
        }
        
        return $data;
    }

    /**
     * 取得額外的中繼資料
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'api_version' => 'v1',
                'timestamp' => now()->toISOString(),
                'user_permissions' => [
                    'can_view_details' => $request->user()->can('system.logs'),
                    'can_export' => $request->user()->can('activity_logs.export'),
                    'can_delete' => $request->user()->can('activity_logs.delete'),
                ],
            ],
        ];
    }
}