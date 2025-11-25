<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class PlayerIndexRequest extends FormRequest
{
    /**
     * 判斷使用者是否有權限執行此請求
     */
    public function authorize(): bool
    {
        return $this->user()->can('players.view');
    }

    /**
     * 取得驗證規則
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'agent_id' => 'nullable|exists:agents,id',
            'is_active' => 'nullable|boolean',
            'min_points' => 'nullable|numeric|min:0',
            'max_points' => 'nullable|numeric|min:0|gte:min_points',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|string|in:name,account,points,created_at',
            'sort_order' => 'nullable|string|in:asc,desc',
        ];
    }

    /**
     * 取得驗證錯誤訊息
     */
    public function messages(): array
    {
        return [
            'search.max' => '搜尋關鍵字不能超過 255 個字元',
            'agent_id.exists' => '指定的代理不存在',
            'min_points.numeric' => '最小點數必須是數字',
            'min_points.min' => '最小點數不能小於 0',
            'max_points.numeric' => '最大點數必須是數字',
            'max_points.min' => '最大點數不能小於 0',
            'max_points.gte' => '最大點數不能小於最小點數',
            'per_page.min' => '每頁筆數不能小於 1',
            'per_page.max' => '每頁筆數不能大於 100',
            'sort_by.in' => '排序欄位無效',
            'sort_order.in' => '排序方向必須是 asc 或 desc',
        ];
    }
}