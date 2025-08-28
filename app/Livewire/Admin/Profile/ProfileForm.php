<?php

namespace App\Livewire\Admin\Profile;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileForm extends Component
{
    use WithFileUploads;

    public $name;
    public $email;
    public $username;
    public $phone;
    public $bio;
    public $avatar;
    public $current_avatar;
    public $timezone;
    public $language_preference;
    public $theme_preference;
    public $email_notifications;
    public $browser_notifications;

    protected function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore(Auth::id())],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore(Auth::id())],
            'phone' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'max:2048'], // 2MB Max
            'timezone' => ['required', 'string'],
            'language_preference' => ['required', 'string'],
            'theme_preference' => ['required', 'in:light,dark,system'],
            'email_notifications' => ['boolean'],
            'browser_notifications' => ['boolean'],
        ];
    }

    protected $messages = [
        'name.required' => '姓名為必填欄位',
        'email.required' => '電子郵件為必填欄位',
        'email.email' => '請輸入有效的電子郵件格式',
        'email.unique' => '此電子郵件已被使用',
        'username.required' => '使用者名稱為必填欄位',
        'username.unique' => '此使用者名稱已被使用',
        'avatar.image' => '頭像必須是圖片檔案',
        'avatar.max' => '頭像檔案大小不能超過 2MB',
        'bio.max' => '個人簡介不能超過 500 個字元',
        'theme_preference.in' => '請選擇有效的主題偏好',
    ];

    public function mount()
    {
        $user = Auth::user();
        
        $this->name = $user->name;
        $this->email = $user->email;
        $this->username = $user->username;
        $this->phone = $user->phone;
        $this->bio = $user->bio;
        $this->current_avatar = $user->avatar;
        $this->timezone = $user->timezone ?? 'Asia/Taipei';
        $this->language_preference = $user->language_preference ?? 'zh_TW';
        $this->theme_preference = $user->theme_preference ?? 'light';
        $this->email_notifications = $user->email_notifications ?? true;
        $this->browser_notifications = $user->browser_notifications ?? true;
    }

    public function updateProfile()
    {
        $this->authorize('profile.edit');
        
        $this->validate();

        $user = Auth::user();
        
        // 處理頭像上傳
        if ($this->avatar) {
            // 刪除舊頭像
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            
            // 儲存新頭像
            $avatarPath = $this->avatar->store('avatars', 'public');
            $user->avatar = $avatarPath;
        }

        // 更新使用者資料
        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'avatar' => $user->avatar,
            'timezone' => $this->timezone,
            'language_preference' => $this->language_preference,
            'theme_preference' => $this->theme_preference,
            'email_notifications' => $this->email_notifications,
            'browser_notifications' => $this->browser_notifications,
        ]);

        // 記錄活動
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'updated_fields' => array_keys($this->getChangedFields($user)),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('個人資料已更新');

        $this->current_avatar = $user->avatar;
        $this->avatar = null;

        session()->flash('success', '個人資料已成功更新！');
        
        // 強制重新渲染元件以確保前端同步
        $this->dispatch('$refresh');
        
        // 觸發頁面重新載入以更新主題
        $this->dispatch('profile-updated');
    }

    public function removeAvatar()
    {
        $this->authorize('profile.edit');
        
        $user = Auth::user();
        
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
            $this->current_avatar = null;
            
            // 強制重新渲染元件以確保前端同步
            $this->dispatch('$refresh');
            
            session()->flash('success', '頭像已移除！');
        }
    }

    public function resetForm()
    {
        try {
        $this->authorize('profile.edit');
        
        \Log::info('🔄 ProfileForm - resetForm 被呼叫了！', [
            'timestamp' => now()->toISOString(),
            'user' => Auth::user()->username,
            'before' => [
                'name' => $this->name,
                'email' => $this->email,
                'username' => $this->username,
                'phone' => $this->phone,
                'bio' => $this->bio,
            ]
        ]);

        // 重新載入使用者資料
        $user = Auth::user();
        
        $this->name = $user->name;
        $this->email = $user->email;
        $this->username = $user->username;
        $this->phone = $user->phone;
        $this->bio = $user->bio;
        $this->current_avatar = $user->avatar;
        $this->timezone = $user->timezone ?? 'Asia/Taipei';
        $this->language_preference = $user->language_preference ?? 'zh_TW';
        $this->theme_preference = $user->theme_preference ?? 'light';
        $this->email_notifications = $user->email_notifications ?? true;
        $this->browser_notifications = $user->browser_notifications ?? true;
        
        // 清除上傳的頭像
        $this->avatar = null;
        
        // 強制重新渲染元件以確保前端同步
        $this->dispatch('$refresh');
        
        // 發送前端刷新事件
        $this->dispatch('profile-form-reset');
        
        \Log::info('🔄 ProfileForm - resetForm 完成！', [
            'after' => [
                'name' => $this->name,
                'email' => $this->email,
                'username' => $this->username,
                'phone' => $this->phone,
                'bio' => $this->bio,
            ]
        ]);

        session()->flash('success', '表單已重置為原始資料！');
    
        
        $this->resetValidation();
    } catch (\Exception $e) {
            \Log::error('重置方法執行失敗', [
                'method' => 'resetForm',
                'error' => $e->getMessage(),
                'component' => static::class,
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '重置操作失敗，請重試'
            ]);
        }}

    private function getChangedFields($user)
    {
        $original = $user->getOriginal();
        $current = $user->getAttributes();
        
        return array_diff_assoc($current, $original);
    }

    public function getTimezonesProperty()
    {
        return [
            'Asia/Taipei' => '台北 (UTC+8)',
            'Asia/Shanghai' => '上海 (UTC+8)',
            'Asia/Hong_Kong' => '香港 (UTC+8)',
            'Asia/Singapore' => '新加坡 (UTC+8)',
            'Asia/Tokyo' => '東京 (UTC+9)',
            'UTC' => 'UTC (UTC+0)',
            'America/New_York' => '紐約 (UTC-5)',
            'America/Los_Angeles' => '洛杉磯 (UTC-8)',
            'Europe/London' => '倫敦 (UTC+0)',
        ];
    }

    public function getLanguagesProperty()
    {
        return [
            'zh_TW' => '正體中文',
            'zh_CN' => '简体中文',
            'en' => 'English',
            'ja' => '日本語',
        ];
    }

    public function render()
    {
        return view('livewire.admin.profile.profile-form');
    }
}