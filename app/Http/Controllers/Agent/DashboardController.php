<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 代理自主管理儀表板控制器
 * 
 * 提供代理專屬的管理介面，允許代理管理其下層代理和玩家
 */
class DashboardController extends Controller
{
    /**
     * 代理儀表板首頁
     * 
     * @return View
     */
    public function index(): View
    {
        $agent = $this->getCurrentAgent();
        
        return view('agent.dashboard.index', compact('agent'));
    }

    /**
     * 代理組織架構頁面
     * 
     * @return View
     */
    public function organization(): View
    {
        $agent = $this->getCurrentAgent();
        
        return view('agent.dashboard.organization', compact('agent'));
    }

    /**
     * 下層代理管理頁面
     * 
     * @return View
     */
    public function agents(): View
    {
        $agent = $this->getCurrentAgent();
        
        return view('agent.dashboard.agents', compact('agent'));
    }

    /**
     * 建立下層代理頁面
     * 
     * @return View
     */
    public function createAgent(): View
    {
        $agent = $this->getCurrentAgent();
        
        return view('agent.dashboard.create-agent', compact('agent'));
    }

    /**
     * 玩家管理頁面
     * 
     * @return View
     */
    public function players(): View
    {
        $agent = $this->getCurrentAgent();
        
        return view('agent.dashboard.players', compact('agent'));
    }

    /**
     * 建立玩家頁面
     * 
     * @return View
     */
    public function createPlayer(): View
    {
        $agent = $this->getCurrentAgent();
        
        return view('agent.dashboard.create-player', compact('agent'));
    }

    /**
     * 點數管理頁面
     * 
     * @return View
     */
    public function points(): View
    {
        $agent = $this->getCurrentAgent();
        
        return view('agent.dashboard.points', compact('agent'));
    }

    /**
     * 統計報表頁面
     * 
     * @return View
     */
    public function statistics(): View
    {
        $agent = $this->getCurrentAgent();
        
        return view('agent.dashboard.statistics', compact('agent'));
    }

    /**
     * 取得當前使用者的代理資料
     * 
     * @return Agent
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function getCurrentAgent(): Agent
    {
        $user = auth()->user();
        
        // 檢查使用者是否為代理
        if (!$user->agent) {
            abort(403, '您不是代理，無法存取此功能');
        }
        
        // 檢查代理是否啟用
        if (!$user->agent->is_active) {
            abort(403, '您的代理帳號已被停用');
        }
        
        return $user->agent->load(['parent', 'children', 'players']);
    }
}