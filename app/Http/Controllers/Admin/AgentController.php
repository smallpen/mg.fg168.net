<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 代理管理控制器
 * 
 * 負責處理代理相關的頁面路由和基本操作
 * 實際的業務邏輯由對應的 Livewire 元件處理
 */
class AgentController extends Controller
{
    /**
     * 代理列表頁面
     * 
     * @return View
     */
    public function index(): View
    {
        // 權限檢查由路由中介軟體處理
        return view('admin.channels.agents.index');
    }

    /**
     * 建立代理頁面
     * 
     * @param Request $request
     * @return View
     */
    public function create(Request $request): View
    {
        // 權限檢查由路由中介軟體處理
        $parentId = $request->get('parent_id');
        
        return view('admin.channels.agents.create', compact('parentId'));
    }

    /**
     * 代理詳情頁面
     * 
     * @param Agent $agent
     * @return View
     */
    public function show(Agent $agent): View
    {
        // 權限檢查由路由中介軟體處理
        // 額外檢查是否可以存取此代理（基於代理層級和管轄範圍）
        $this->authorizeAgentAccess($agent, 'view');
        
        // 預載入相關資料
        $agent->load(['parent', 'children', 'players', 'creator']);
        
        return view('admin.channels.agents.show', compact('agent'));
    }

    /**
     * 編輯代理頁面
     * 
     * @param Agent $agent
     * @return View
     */
    public function edit(Agent $agent): View
    {
        // 權限檢查由路由中介軟體處理
        // 額外檢查是否可以編輯此代理
        $this->authorizeAgentAccess($agent, 'edit');
        
        return view('admin.channels.agents.edit', compact('agent'));
    }

    /**
     * 代理點數管理頁面
     * 
     * @param Agent $agent
     * @return View
     */
    public function points(Agent $agent): View
    {
        // 權限檢查由路由中介軟體處理
        // 額外檢查是否可以管理此代理的點數
        $this->authorizeAgentAccess($agent, 'points');
        
        return view('admin.channels.agents.points', compact('agent'));
    }

    /**
     * 檢查代理存取權限
     * 
     * 根據使用者角色和代理層級關係檢查存取權限
     * 
     * @param Agent $agent
     * @param string $action
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function authorizeAgentAccess(Agent $agent, string $action): void
    {
        $user = auth()->user();
        
        // 系統管理員可以存取所有代理
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return;
        }
        
        // 如果使用者本身是代理，檢查管轄範圍
        if ($user->agent) {
            $userAgent = $user->agent;
            
            switch ($action) {
                case 'view':
                    // 可以檢視自己和下層代理
                    if (!$this->isAgentInHierarchy($userAgent, $agent)) {
                        abort(403, '您沒有權限存取此代理');
                    }
                    break;
                    
                case 'edit':
                    // 只能編輯直屬下層代理
                    if ($agent->parent_id !== $userAgent->id && $agent->id !== $userAgent->id) {
                        abort(403, '您只能編輯直屬下層代理或自己的資料');
                    }
                    break;
                    
                case 'points':
                    // 只能管理直屬下層代理的點數
                    if ($agent->parent_id !== $userAgent->id) {
                        abort(403, '您只能管理直屬下層代理的點數');
                    }
                    break;
            }
        } else {
            // 非代理使用者需要有相應的系統權限
            abort(403, '您沒有權限存取代理管理功能');
        }
    }

    /**
     * 檢查代理是否在使用者的管轄範圍內
     * 
     * @param Agent $userAgent 使用者的代理
     * @param Agent $targetAgent 目標代理
     * @return bool
     */
    private function isAgentInHierarchy(Agent $userAgent, Agent $targetAgent): bool
    {
        // 如果是自己
        if ($userAgent->id === $targetAgent->id) {
            return true;
        }
        
        // 檢查是否為下層代理
        $current = $targetAgent;
        while ($current->parent) {
            if ($current->parent_id === $userAgent->id) {
                return true;
            }
            $current = $current->parent;
        }
        
        return false;
    }
}