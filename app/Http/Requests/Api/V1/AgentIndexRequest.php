<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AgentIndexRequest extends FormRequest
{
    /**
     * 判斷使用者是否有權限執行此請求
     */
    public function authorize(): bool
    {
        return $this->user()->can('agents.view');
    }

    /**
     * 取得驗證規則
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'level' => 'nullable|integer|min:1|max:10',
            'prefix' => 'nullable|string|size:1|regex:/^[a-z]$/',
            'is_active' => 'nullable|boolean',
            'parent_id' => 'nullable|exists:agents,id',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|string|in:name,account,level,total_points,remaining_points,created_at',
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
            'level.min' => '代理層級不能小於 1',
            'level.max' => '代理層級不能大於 10',
            'prefix.size' => '前置符號必須是單一字元',
            'prefix.regex' => '前置符號必須是 a-z 的小寫字母',
            'parent_id.exists' => '指定的上層代理不存在',
            'per_page.min' => '每頁筆數不能小於 1',
            'per_page.max' => '每頁筆數不能大於 100',
            'sort_by.in' => '排序欄位無效',
            'sort_order.in' => '排序方向必須是 asc 或 desc',
        ];
    }
}