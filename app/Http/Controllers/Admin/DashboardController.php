<?php

namespace App\Http\Controllers\Admin;

/**
 * 儀表板控制器
 * 
 * 處理管理後台儀表板相關功能
 */
class DashboardController extends AdminController
{
    /**
     * 顯示儀表板頁面
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.dashboard');
    }
}