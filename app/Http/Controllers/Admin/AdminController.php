<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/**
 * 管理後台基礎控制器
 * 
 * 所有管理後台控制器的基礎類別，提供共用功能
 */
abstract class AdminController extends Controller
{
    /**
     * 建構函式
     * 
     * 設定管理後台的基本中介軟體
     */
    public function __construct()
    {
        // 確保使用者已登入
        $this->middleware('auth');
        
        // 檢查管理員權限
        $this->middleware('admin');
    }
}