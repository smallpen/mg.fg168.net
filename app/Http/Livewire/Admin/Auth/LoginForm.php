<?php

namespace App\Http\Livewire\Admin\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * 管理後台登入表單元件
 * 
 * 處理使用者登入驗證和相關邏輯
 */
class LoginForm extends Component
{
    /**
     * 使用者名稱
     * 
     * @var string
     */
    public $username = '';

    /**
     * 密碼
     * 
     * @var string
     */
    public $password = '';

    /**
     * 記住我選項
     * 
     * @var bool
     */
    public $remember = false;

    /**
     * 驗證規則
     * 
     * @return array
     */
    protected function rules()
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:20'],
            'password' => ['required', 'string', 'min:6'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * 驗證訊息
     * 
     * @return array
     */
    protected function messages()
    {
        return [
            'username.required' => __('auth.validation.username_required'),
            'username.min' => __('auth.validation.username_min', ['min' => 3]),
            'username.max' => '使用者名稱不能超過 20 個字元',
            'password.required' => __('auth.validation.password_required'),
            'password.min' => __('auth.validation.password_min', ['min' => 6]),
        ];
    }

    /**
     * 即時驗證屬性
     * 
     * @param string $propertyName
     */
    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    /**
     * 處理登入請求
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login()
    {
        // 驗證輸入資料
        $this->validate();

        try {
            // 嘗試使用 username 進行認證
            $credentials = [
                'username' => $this->username,
                'password' => $this->password,
            ];

            if (Auth::attempt($credentials, $this->remember)) {
                // 登入成功，重新生成 session ID 防止 session fixation 攻擊
                if (request()->hasSession()) {
                    request()->session()->regenerate();
                }

                // 檢查使用者是否有管理員權限
                if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('super_admin')) {
                    Auth::logout();
                    throw ValidationException::withMessages([
                        'username' => '您沒有權限存取管理後台',
                    ]);
                }

                // 記錄登入日誌
                logger()->info('管理員登入成功', [
                    'user_id' => auth()->id(),
                    'username' => $this->username,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                // 重新導向到儀表板
                return redirect()->intended(route('admin.dashboard'));
            }

            // 登入失敗
            throw ValidationException::withMessages([
                'username' => __('auth.login.failed'),
            ]);

        } catch (ValidationException $e) {
            // 記錄登入失敗日誌
            logger()->warning('管理員登入失敗', [
                'username' => $this->username,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (\Exception $e) {
            // 記錄系統錯誤
            logger()->error('登入系統錯誤', [
                'username' => $this->username,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 顯示通用錯誤訊息
            throw ValidationException::withMessages([
                'username' => '登入時發生錯誤，請稍後再試',
            ]);
        }
    }

    /**
     * 渲染元件視圖
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.admin.auth.login-form');
    }
}