<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgentStoreRequest extends FormRequest
{
    /**
     * 判斷使用者是否有權限執行此請求
     */
    public function authorize(): bool
    {
        return $this->user()->can('agents.create');
    }

    /**
     * 取得驗證規則
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('agents', 'username')->whereNull('deleted_at'),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('agents', 'email')->whereNull('deleted_at'),
            ],
            'phone' => 'nullable|string|max:20',
            'initial_points' => 'required|numeric|min:0|max:999999999.99',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ];

        // 根據是否有 parent_id 來決定驗證規則
        if (!$this->filled('parent_id')) {
            // 第一層代理需要前置符號
            $rules['prefix'] = [
                'required',
                'string',
                'size:1',
                'regex:/^[a-z]$/',
                Rule::unique('agents', 'prefix')->whereNull('deleted_at'),
            ];
        } else {
            // 下層代理需要選擇上層代理
            $rules['parent_id'] = 'required|exists:agents,id';
        }

        return $rules;
    }

    /**
     * 取得驗證錯誤訊息
     */
    public function messages(): array
    {
        return [
            'name.required' => '代理姓名為必填欄位',
            'name.max' => '代理姓名不能超過 255 個字元',
            'username.required' => '使用者名稱為必填欄位',
            'username.max' => '使用者名稱不能超過 50 個字元',
            'username.regex' => '使用者名稱只能包含字母、數字和底線',
            'username.unique' => '此使用者名稱已被使用',
            'email.required' => '電子郵件為必填欄位',
            'email.email' => '電子郵件格式不正確',
            'email.unique' => '此電子郵件已被使用',
            'phone.max' => '電話號碼不能超過 20 個字元',
            'prefix.required' => '第一層代理必須設定前置符號',
            'prefix.size' => '前置符號必須是單一字元',
            'prefix.regex' => '前置符號必須是 a-z 的小寫字母',
            'prefix.unique' => '此前置符號已被使用',
            'parent_id.required' => '下層代理必須選擇上層代理',
            'parent_id.exists' => '指定的上層代理不存在',
            'initial_points.required' => '初始點數為必填欄位',
            'initial_points.numeric' => '初始點數必須是數字',
            'initial_points.min' => '初始點數不能小於 0',
            'initial_points.max' => '初始點數不能超過 999,999,999.99',
            'notes.max' => '備註不能超過 1000 個字元',
        ];
    }

    /**
     * 準備驗證資料
     */
    protected function prepareForValidation(): void
    {
        // 設定預設值
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }

        // 設定建立者
        $this->merge(['created_by' => $this->user()->id]);
    }
}