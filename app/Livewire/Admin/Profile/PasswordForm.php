<?php

namespace App\Livewire\Admin\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordForm extends Component
{
    public $current_password;
    public $password;
    public $password_confirmation;

    protected function rules()
    {
        return [
            'current_password' => ['required', function ($attribute, $value, $fail) {
                if (!Hash::check($value, Auth::user()->password)) {
                    $fail('目前密碼不正確');
                }
            }],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    protected $messages = [
        'current_password.required' => '請輸入目前密碼',
        'current_password.current_password' => '目前密碼不正確',
        'password.required' => '請輸入新密碼',
        'password.confirmed' => '新密碼確認不一致',
        'password.min' => '密碼至少需要 8 個字元',
    ];

    public function updatePassword()
    {
        $this->authorize('profile.edit');
        
        $this->validate();

        $user = Auth::user();
        
        $user->update([
            'password' => Hash::make($this->password),
        ]);

        // 記錄活動
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('密碼已更新');

        // 清空表單
        $this->reset(['current_password', 'password', 'password_confirmation']);

        session()->flash('password_success', '密碼已成功更新！');
    }

    public function render()
    {
        return view('livewire.admin.profile.password-form');
    }
}