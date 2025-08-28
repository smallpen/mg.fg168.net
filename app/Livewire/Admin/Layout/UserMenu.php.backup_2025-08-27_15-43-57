<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * 使用者選單元件
 * 
 * 負責顯示使用者下拉選單，包括：
 * - 使用者資訊顯示
 * - 個人設定和帳號管理連結
 * - 登出功能和 Session 管理
 * - 使用者頭像顯示和上傳
 */
class UserMenu extends Component
{
    use WithFileUploads;
    
    // 選單狀態
    public bool $isOpen = false;
    
    // 頭像上傳
    public $avatar;
    public bool $showAvatarUpload = false;
    
    // 個人資料編輯
    public bool $showProfileEdit = false;
    public string $name = '';
    public string $email = '';
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';
    
    // 主題偏好
    public string $themePreference = 'light';
    
    // 語言偏好
    public string $localePreference = 'zh_TW';
    
    /**
     * 元件初始化
     */
    public function mount()
    {
        $user = auth()->user();
        if ($user) {
            $this->name = $user->name ?? '';
            $this->email = $user->email ?? '';
            $this->themePreference = $user->theme_preference ?? 'light';
            $this->localePreference = $user->locale ?? 'zh_TW';
        }
    }
    
    /**
     * 計算屬性：取得當前使用者
     */
    public function getCurrentUserProperty()
    {
        return auth()->user();
    }
    
    /**
     * 計算屬性：取得使用者頭像 URL
     */
    public function getAvatarUrlProperty(): string
    {
        $user = $this->getCurrentUserProperty();
        
        if ($user && $user->avatar) {
            return Storage::url($user->avatar);
        }
        
        // 使用 Gravatar 作為預設頭像
        $email = $user ? $user->email : '';
        $hash = md5(strtolower(trim($email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=200";
    }
    
    /**
     * 計算屬性：取得使用者縮寫
     */
    public function getUserInitialsProperty(): string
    {
        $user = $this->getCurrentUserProperty();
        if (!$user) {
            return 'U';
        }
        
        $name = $user->name ?? $user->username ?? 'User';
        $words = explode(' ', $name);
        
        if (count($words) >= 2) {
            return mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1);
        }
        
        return mb_substr($name, 0, 1);
    }
    
    /**
     * 計算屬性：取得使用者顯示名稱
     */
    public function getUserDisplayNameProperty(): string
    {
        $user = $this->getCurrentUserProperty();
        return $user ? ($user->name ?? $user->username ?? '使用者') : '使用者';
    }
    
    /**
     * 計算屬性：取得使用者電子郵件
     */
    public function getUserEmailProperty(): string
    {
        $user = $this->getCurrentUserProperty();
        return $user ? $user->email ?? '' : '';
    }
    
    /**
     * 計算屬性：取得使用者角色
     */
    public function getUserRolesProperty(): string
    {
        $user = $this->getCurrentUserProperty();
        if (!$user || !$user->roles) {
            return '一般使用者';
        }
        
        return $user->roles->pluck('display_name')->join(', ') ?: '一般使用者';
    }
    
    /**
     * 切換選單顯示狀態
     */
    public function toggle(): void
    {
        $this->isOpen = !$this->isOpen;
        
        // 關閉其他選單
        if ($this->isOpen) {
            $this->dispatch('close-other-menus', except: 'user-menu');
        }
    }
    
    /**
     * 關閉選單
     */
    public function close(): void
    {
        $this->isOpen = false;
        $this->showAvatarUpload = false;
        $this->showProfileEdit = false;
        $this->resetValidation();
    }
    
    /**
     * 監聽關閉其他選單事件
     */
    #[On('close-other-menus')]
    public function handleCloseOtherMenus(string $except = ''): void
    {
        if ($except !== 'user-menu') {
            $this->close();
        }
    }
    
    /**
     * 顯示頭像上傳對話框
     */
    public function showAvatarUploadDialog(): void
    {
        $this->showAvatarUpload = true;
        $this->avatar = null;
    }
    
    /**
     * 上傳頭像
     */
    public function uploadAvatar(): void
    {
        $this->validate([
            'avatar' => 'required|image|max:2048', // 最大 2MB
        ], [
            'avatar.required' => '請選擇要上傳的頭像檔案',
            'avatar.image' => '頭像必須是圖片檔案',
            'avatar.max' => '頭像檔案大小不能超過 2MB',
        ]);
        
        $user = $this->getCurrentUserProperty();
        if (!$user) {
            session()->flash('error', '使用者未登入');
            return;
        }
        
        try {
            // 刪除舊頭像
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }
            
            // 儲存新頭像
            $path = $this->avatar->store('avatars', 'public');
            
            // 更新使用者資料
            $user->update(['avatar' => $path]);
            
            $this->showAvatarUpload = false;
            $this->avatar = null;
            
            session()->flash('success', '頭像更新成功');
            
            // 觸發頭像更新事件
            $this->dispatch('avatar-updated', avatarUrl: $this->avatarUrl);
            
        } catch (\Exception $e) {
            session()->flash('error', '頭像上傳失敗：' . $e->getMessage());
        }
    }
    
    /**
     * 移除頭像
     */
    public function removeAvatar(): void
    {
        $user = $this->getCurrentUserProperty();
        if (!$user) {
            return;
        }
        
        try {
            // 刪除頭像檔案
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }
            
            // 更新使用者資料
            $user->update(['avatar' => null]);
            
            session()->flash('success', '頭像已移除');
            
            // 觸發頭像更新事件
            $this->dispatch('avatar-updated', avatarUrl: $this->avatarUrl);
            
        } catch (\Exception $e) {
            session()->flash('error', '移除頭像失敗：' . $e->getMessage());
        }
    }
    
    /**
     * 顯示個人資料編輯對話框
     */
    public function showProfileEditDialog(): void
    {
        $this->showProfileEdit = true;
        
        // 重新載入使用者資料
        $user = $this->getCurrentUserProperty();
        if ($user) {
            $this->name = $user->name ?? '';
            $this->email = $user->email ?? '';
        }
    }
    
    /**
     * 更新個人資料
     */
    public function updateProfile(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
        ], [
            'name.required' => '姓名為必填欄位',
            'name.max' => '姓名長度不能超過 255 個字元',
            'email.required' => '電子郵件為必填欄位',
            'email.email' => '請輸入有效的電子郵件格式',
            'email.unique' => '此電子郵件已被使用',
        ]);
        
        $user = $this->getCurrentUserProperty();
        if (!$user) {
            session()->flash('error', '使用者未登入');
            return;
        }
        
        try {
            $user->update([
                'name' => $this->name,
                'email' => $this->email,
            ]);
            
            $this->showProfileEdit = false;
            session()->flash('success', '個人資料更新成功');
            
            // 觸發個人資料更新事件
            $this->dispatch('profile-updated');
            
        } catch (\Exception $e) {
            session()->flash('error', '個人資料更新失敗：' . $e->getMessage());
        }
    }
    
    /**
     * 更新密碼
     */
    public function updatePassword(): void
    {
        $this->validate([
            'currentPassword' => 'required',
            'newPassword' => ['required', 'confirmed', Password::min(8)],
        ], [
            'currentPassword.required' => '請輸入目前密碼',
            'newPassword.required' => '請輸入新密碼',
            'newPassword.confirmed' => '新密碼確認不符',
            'newPassword.min' => '新密碼至少需要 8 個字元',
        ]);
        
        $user = $this->getCurrentUserProperty();
        if (!$user) {
            session()->flash('error', '使用者未登入');
            return;
        }
        
        // 驗證目前密碼
        if (!Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', '目前密碼不正確');
            return;
        }
        
        try {
            $user->update([
                'password' => Hash::make($this->newPassword),
            ]);
            
            // 清空密碼欄位
            $this->currentPassword = '';
            $this->newPassword = '';
            $this->newPasswordConfirmation = '';
            
            session()->flash('success', '密碼更新成功');
            
            // 觸發密碼更新事件
            $this->dispatch('password-updated');
            
        } catch (\Exception $e) {
            session()->flash('error', '密碼更新失敗：' . $e->getMessage());
        }
    }
    
    /**
     * 更新主題偏好
     */
    public function updateThemePreference(string $theme): void
    {
        $user = $this->getCurrentUserProperty();
        if (!$user) {
            return;
        }
        
        try {
            $user->update(['theme_preference' => $theme]);
            $this->themePreference = $theme;
            
            // 觸發主題變更事件
            $this->dispatch('theme-changed', theme: $theme);
            
            session()->flash('success', '主題偏好已更新');
            
        } catch (\Exception $e) {
            session()->flash('error', '主題偏好更新失敗：' . $e->getMessage());
        }
    }
    
    /**
     * 更新語言偏好
     */
    public function updateLocalePreference(string $locale): void
    {
        $user = $this->getCurrentUserProperty();
        if (!$user) {
            return;
        }
        
        try {
            $user->update(['locale' => $locale]);
            $this->localePreference = $locale;
            
            // 設定應用程式語言
            app()->setLocale($locale);
            session()->put('locale', $locale);
            
            // 觸發語言變更事件
            $this->dispatch('locale-changed', locale: $locale);
            
            session()->flash('success', '語言偏好已更新');
            
        } catch (\Exception $e) {
            session()->flash('error', '語言偏好更新失敗：' . $e->getMessage());
        }
    }
    
    /**
     * 前往個人資料頁面
     */
    public function goToProfile()
    {
        return redirect()->route('admin.profile');
    }
    
    /**
     * 前往帳號設定頁面
     */
    public function goToAccountSettings()
    {
        return redirect()->route('admin.account.settings');
    }
    
    /**
     * 前往說明中心
     */
    public function goToHelpCenter()
    {
        return redirect()->route('admin.help');
    }
    
    /**
     * 登出功能
     */
    public function logout()
    {
        try {
            // 記錄登出活動
            activity()
                ->causedBy(auth()->user())
                ->log('使用者登出');
            
            // 清除 Session
            auth()->logout();
            session()->invalidate();
            session()->regenerateToken();
            
            // 觸發登出事件
            $this->dispatch('user-logged-out');
            
            return redirect()->route('admin.login')
                ->with('success', '您已成功登出');
                
        } catch (\Exception $e) {
            session()->flash('error', '登出失敗：' . $e->getMessage());
        }
    }
    
    /**
     * 強制登出其他裝置
     */
    public function logoutOtherDevices(): void
    {
        try {
            // Laravel 的 logoutOtherDevices 方法
            auth()->logoutOtherDevices($this->currentPassword);
            
            session()->flash('success', '已成功登出其他裝置');
            
            // 觸發其他裝置登出事件
            $this->dispatch('other-devices-logged-out');
            
        } catch (\Exception $e) {
            session()->flash('error', '登出其他裝置失敗：' . $e->getMessage());
        }
    }
    
    /**
     * 取得活躍 Session 數量
     */
    public function getActiveSessionsCountProperty(): int
    {
        // 這裡可以實作取得活躍 Session 數量的邏輯
        // 目前返回模擬數據
        return 1;
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.user-menu');
    }
}