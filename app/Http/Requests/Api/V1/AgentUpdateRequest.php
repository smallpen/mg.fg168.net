<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgentUpdateRequest extends FormRequest
{
    /**
     * 判斷使用者是否有權限執行此請求
     */
    public function authorize(): bool
    {
        return $this->user()->can('agents.edit');
    }

    /**
     * 取得驗證規則
     */
    public function rules(): array
    {
        $agentId = $this->route('agent');

        return [
            'name' => 'sometimes|required|string|max:255',
            'username' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('agents', 'username')->ignore($agentId)->whereNull('deleted_at'),
            ],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('agents', 'email')->ignore($agentId)->whereNull('deleted_at'),
            ],
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ];
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
            'notes.max' => '備註不能超過 1000 個字元',
        ];
    }
}