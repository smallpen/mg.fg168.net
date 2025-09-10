<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class PointTransactionRequest extends FormRequest
{
    /**
     * 判斷使用者是否有權限執行此請求
     */
    public function authorize(): bool
    {
        return $this->user()->can('points.manage');
    }

    /**
     * 取得驗證規則
     */
    public function rules(): array
    {
        return [
            'target_type' => 'required|string|in:agent,player',
            'target_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
            'from_agent_id' => 'nullable|exists:agents,id',
            'description' => 'nullable|string|max:255',
        ];
    }

    /**
     * 取得驗證錯誤訊息
     */
    public function messages(): array
    {
        return [
            'target_type.required' => '目標類型為必填欄位',
            'target_type.in' => '目標類型必須是 agent 或 player',
            'target_id.required' => '目標ID為必填欄位',
            'target_id.integer' => '目標ID必須是整數',
            'amount.required' => '點數金額為必填欄位',
            'amount.numeric' => '點數金額必須是數字',
            'amount.min' => '點數金額不能小於 0.01',
            'amount.max' => '點數金額不能超過 999,999,999.99',
            'from_agent_id.exists' => '指定的代理不存在',
            'description.max' => '描述不能超過 255 個字元',
        ];
    }

    /**
     * 配置驗證器實例
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // 驗證目標是否存在
            $targetType = $this->input('target_type');
            $targetId = $this->input('target_id');

            if ($targetType === 'agent') {
                $exists = \App\Models\Agent::where('id', $targetId)->exists();
                if (!$exists) {
                    $validator->errors()->add('target_id', '指定的代理不存在');
                }
            } elseif ($targetType === 'player') {
                $exists = \App\Models\Player::where('id', $targetId)->exists();
                if (!$exists) {
                    $validator->errors()->add('target_id', '指定的玩家不存在');
                }

                // 玩家操作必須指定來源代理
                if (!$this->filled('from_agent_id')) {
                    $validator->errors()->add('from_agent_id', '玩家點數操作必須指定來源代理');
                }
            }
        });
    }
}