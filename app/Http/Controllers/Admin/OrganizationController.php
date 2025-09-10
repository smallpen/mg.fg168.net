<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    /**
     * 顯示組織架構圖表頁面
     */
    public function index(Request $request)
    {
        // 檢查權限
        $this->authorize('channels.agents.view');
        
        // 獲取根代理（如果指定）
        $rootAgent = null;
        if ($request->has('root') && $request->root) {
            $rootAgent = Agent::find($request->root);
        }
        
        return view('admin.channels.organization', compact('rootAgent'));
    }
}