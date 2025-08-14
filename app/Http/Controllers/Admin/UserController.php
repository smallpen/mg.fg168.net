<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;

/**
 * 使用者管理控制器
 * 
 * 處理使用者管理相關的頁面請求和權限檢查
 */
class UserController extends AdminController
{
    /**
     * 建構函式
     * 
     * 設定使用者管理的權限中介軟體
     */
    public function __construct()
    {
        parent::__construct();
        
        // 設定權限中介軟體
        $this->middleware('can:users.view')->only(['index', 'show']);
        $this->middleware('can:users.create')->only(['create']);
        $this->middleware('can:users.edit')->only(['edit']);
    }

    /**
     * 顯示使用者列表頁面
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        // 檢查使用者是否有檢視使用者的權限
        Gate::authorize('users.view');

        // 設定頁面標題和麵包屑
        $pageTitle = __('admin.users.title');
        $breadcrumbs = [
            ['name' => __('admin.navigation.dashboard'), 'url' => route('admin.dashboard')],
            ['name' => __('admin.users.title'), 'url' => null],
        ];

        return view('admin.users.index', compact('pageTitle', 'breadcrumbs'));
    }

    /**
     * 顯示建立使用者頁面
     *
     * @param Request $request
     * @return View
     */
    public function create(Request $request): View
    {
        // 檢查使用者是否有建立使用者的權限
        Gate::authorize('users.create');

        // 設定頁面標題和麵包屑
        $pageTitle = __('admin.users.create');
        $breadcrumbs = [
            ['name' => __('admin.navigation.dashboard'), 'url' => route('admin.dashboard')],
            ['name' => __('admin.users.title'), 'url' => route('admin.users.index')],
            ['name' => __('admin.users.create'), 'url' => null],
        ];

        return view('admin.users.create', compact('pageTitle', 'breadcrumbs'));
    }

    /**
     * 顯示使用者詳細資訊頁面
     *
     * @param User $user
     * @return View
     */
    public function show(User $user): View
    {
        // 檢查使用者是否有檢視使用者的權限
        Gate::authorize('users.view');

        // 載入使用者的角色資訊
        $user->load('roles');

        // 設定頁面標題和麵包屑
        $pageTitle = __('admin.users.view_user', ['name' => $user->name]);
        $breadcrumbs = [
            ['name' => __('admin.navigation.dashboard'), 'url' => route('admin.dashboard')],
            ['name' => __('admin.users.title'), 'url' => route('admin.users.index')],
            ['name' => $user->name, 'url' => null],
        ];

        return view('admin.users.show', compact('user', 'pageTitle', 'breadcrumbs'));
    }

    /**
     * 顯示編輯使用者頁面
     *
     * @param User $user
     * @return View
     */
    public function edit(User $user): View
    {
        // 檢查使用者是否有編輯使用者的權限
        Gate::authorize('users.edit');

        // 設定頁面標題和麵包屑
        $pageTitle = __('admin.users.edit_user', ['name' => $user->name]);
        $breadcrumbs = [
            ['name' => __('admin.navigation.dashboard'), 'url' => route('admin.dashboard')],
            ['name' => __('admin.users.title'), 'url' => route('admin.users.index')],
            ['name' => $user->name, 'url' => route('admin.users.show', $user)],
            ['name' => __('admin.users.edit'), 'url' => null],
        ];

        return view('admin.users.edit', compact('user', 'pageTitle', 'breadcrumbs'));
    }
}