<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * 活動記錄列表 API 請求驗證
 */
class ActivityIndexRequest extends FormRequest
{
    /**
     * 判斷使用者是否有權限執行此請求
     */
    public function authorize(): bool
    {
        return $this->user()->can('activity_logs.view');
    }

    /**
     * 取得驗證規則
     */
    public function rules(): array
    {
        return [
            // 分頁參數
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            
            // 排序參數
            'sort_field' => 'string|in:created_at,type,causer_id,subject_type,risk_level',
            'sort_direction' => 'string|in:asc,desc',
            
            // 篩選參數
            'search' => 'string|max:255',
            'date_from' => 'date',
            'date_to' => 'date|after_or_equal:date_from',
            'user_id' => 'integer|exists:users,id',
            'type' => 'string|max:100',
            'subject_type' => 'string|max:100',
            'result' => 'string|in:success,failed,warning',
            'risk_level' => 'integer|min:0|max:10',
            'ip_address' => 'ip',
            
            // 進階篩選
            'causer_type' => 'string|max:100',
            'has_properties' => 'boolean',
            'is_security_event' => 'boolean',
            
            // 時間範圍快捷選項
            'time_range' => 'string|in:1h,6h,12h,1d,3d,7d,30d,90d',
            
            // 匯出相關
            'include_properties' => 'boolean',
            'include_related' => 'boolean',
        ];
    }

    /**
     * 取得驗證錯誤訊息
     */
    public function messages(): array
    {
        return [
            'page.integer' => '頁碼必須是整數',
            'page.min' => '頁碼必須大於 0',
            'per_page.integer' => '每頁數量必須是整數',
            'per_page.min' => '每頁數量必須大於 0',
            'per_page.max' => '每頁數量不能超過 100',
            
            'sort_field.in' => '排序欄位無效',
            'sort_direction.in' => '排序方向必須是 asc 或 desc',
            
            'search.string' => '搜尋關鍵字必須是字串',
            'search.max' => '搜尋關鍵字不能超過 255 個字元',
            
            'date_from.date' => '開始日期格式無效',
            'date_to.date' => '結束日期格式無效',
            'date_to.after_or_equal' => '結束日期必須晚於或等於開始日期',
            
            'user_id.integer' => '使用者 ID 必須是整數',
            'user_id.exists' => '指定的使用者不存在',
            
            'type.string' => '活動類型必須是字串',
            'type.max' => '活動類型不能超過 100 個字元',
            
            'subject_type.string' => '操作對象類型必須是字串',
            'subject_type.max' => '操作對象類型不能超過 100 個字元',
            
            'result.in' => '操作結果必須是 success、failed 或 warning',
            
            'risk_level.integer' => '風險等級必須是整數',
            'risk_level.min' => '風險等級不能小於 0',
            'risk_level.max' => '風險等級不能大於 10',
            
            'ip_address.ip' => 'IP 位址格式無效',
            
            'causer_type.string' => '操作者類型必須是字串',
            'causer_type.max' => '操作者類型不能超過 100 個字元',
            
            'has_properties.boolean' => '屬性篩選必須是布林值',
            'is_security_event.boolean' => '安全事件篩選必須是布林值',
            
            'time_range.in' => '時間範圍選項無效',
            
            'include_properties.boolean' => '包含屬性必須是布林值',
            'include_related.boolean' => '包含相關記錄必須是布林值',
        ];
    }

    /**
     * 處理驗證失敗
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'error' => 'Validation Failed',
                'message' => '請求參數驗證失敗',
                'errors' => $validator->errors(),
                'code' => 'VALIDATION_ERROR'
            ], Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }

    /**
     * 取得已驗證的篩選參數
     */
    public function getFilters(): array
    {
        $filters = [];
        
        // 基本篩選
        if ($this->filled('search')) {
            $filters['search'] = $this->input('search');
        }
        
        if ($this->filled('date_from')) {
            $filters['date_from'] = $this->input('date_from');
        }
        
        if ($this->filled('date_to')) {
            $filters['date_to'] = $this->input('date_to');
        }
        
        if ($this->filled('user_id')) {
            $filters['user_id'] = $this->input('user_id');
        }
        
        if ($this->filled('type')) {
            $filters['type'] = $this->input('type');
        }
        
        if ($this->filled('subject_type')) {
            $filters['subject_type'] = $this->input('subject_type');
        }
        
        if ($this->filled('result')) {
            $filters['result'] = $this->input('result');
        }
        
        if ($this->filled('risk_level')) {
            $filters['risk_level'] = $this->input('risk_level');
        }
        
        if ($this->filled('ip_address')) {
            $filters['ip_address'] = $this->input('ip_address');
        }
        
        if ($this->filled('causer_type')) {
            $filters['causer_type'] = $this->input('causer_type');
        }
        
        // 布林篩選
        if ($this->filled('has_properties')) {
            $filters['has_properties'] = $this->boolean('has_properties');
        }
        
        if ($this->filled('is_security_event')) {
            $filters['is_security_event'] = $this->boolean('is_security_event');
        }
        
        // 時間範圍快捷選項
        if ($this->filled('time_range')) {
            $filters['time_range'] = $this->input('time_range');
        }
        
        return $filters;
    }

    /**
     * 取得排序參數
     */
    public function getSortOptions(): array
    {
        return [
            'field' => $this->input('sort_field', 'created_at'),
            'direction' => $this->input('sort_direction', 'desc')
        ];
    }

    /**
     * 取得分頁參數
     */
    public function getPaginationOptions(): array
    {
        return [
            'page' => $this->input('page', 1),
            'per_page' => $this->input('per_page', 50)
        ];
    }
}