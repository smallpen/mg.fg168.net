<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 安全警報 API 資源
 */
class AlertResource extends JsonResource
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
            'severity' => $this->severity,
            'title' => $this->title,
            'description' => $this->description,
            'is_acknowledged' => $this->is_acknowledged,
            'acknowledged_at' => $this->acknowledged_at?->toISOString(),
            'acknowledged_by' => $this->whenLoaded('acknowledgedBy', function () {
                return [
                    'id' => $this->acknowledgedBy->id,
                    'name' => $this->acknowledgedBy->name,
                    'username' => $this->acknowledgedBy->username,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'severity_color' => $this->getSeverityColor(),
            'priority_score' => $this->getPriorityScore(),
        ];
    }

    /**
     * 取得嚴重程度顏色
     */
    private function getSeverityColor(): string
    {
        return match ($this->severity) {
            'low' => '#28a745',      // 綠色
            'medium' => '#ffc107',   // 黃色
            'high' => '#fd7e14',     // 橙色
            'critical' => '#dc3545', // 紅色
            default => '#6c757d'     // 灰色
        };
    }

    /**
     * 取得優先級分數
     */
    private function getPriorityScore(): int
    {
        return match ($this->severity) {
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'critical' => 4,
            default => 0
        };
    }
}