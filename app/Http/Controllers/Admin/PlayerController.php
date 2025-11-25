<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 玩家管理控制器
 * 
 * 負責處理玩家管理相關的 HTTP 請求
 * 主要提供視圖渲染，實際業務邏輯由 Livewire 元件處理
 */
class PlayerController extends Controller
{
    /**
     * 顯示玩家列表頁面
     * 
     * @return View
     */
    public function index(): View
    {
        // 權限檢查由路由中介軟體處理
        return view('admin.channels.players.index');
    }

    /**
     * 顯示建立玩家頁面
     * 
     * @return View
     */
    public function create(): View
    {
        // 權限檢查由路由中介軟體處理
        return view('admin.channels.players.create');
    }

    /**
     * 顯示玩家詳情頁面
     * 
     * @param Player $player
     * @return View
     */
    public function show(Player $player): View
    {
        // 權限檢查由路由中介軟體處理
        // 額外檢查是否可以存取此玩家（基於代理管轄範圍）
        $this->authorizePlayerAccess($player, 'view');

        return view('admin.channels.players.show', compact('player'));
    }

    /**
     * 顯示編輯玩家頁面
     * 
     * @param Player $player
     * @return View
     */
    public function edit(Player $player): View
    {
        // 權限檢查由路由中介軟體處理
        // 額外檢查是否可以編輯此玩家
        $this->authorizePlayerAccess($player, 'edit');

        return view('admin.channels.players.edit', compact('player'));
    }

    /**
     * 檢查玩家存取權限
     * 
     * 根據使用者角色和玩家隸屬代理關係檢查存取權限
     * 
     * @param Player $player
     * @param string $action
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function authorizePlayerAccess(Player $player, string $action): void
    {
        $user = auth()->user();
        
        // 系統管理員可以存取所有玩家
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return;
        }
        
        // 如果使用者本身是代理，檢查管轄範圍
        if ($user->agent) {
            $userAgent = $user->agent;
            
            switch ($action) {
                case 'view':
                    // 可以檢視管轄範圍內的玩家
                    if (!$this->isPlayerInHierarchy($userAgent, $player)) {
                        abort(403, '您沒有權限存取此玩家');
                    }
                    break;
                    
                case 'edit':
                    // 只能編輯直屬玩家
                    if ($player->agent_id !== $userAgent->id) {
                        abort(403, '您只能編輯直屬玩家');
                    }
                    break;
            }
        } else {
            // 非代理使用者需要有相應的系統權限
            abort(403, '您沒有權限存取玩家管理功能');
        }
    }

    /**
     * 檢查玩家是否在使用者的管轄範圍內
     * 
     * @param Agent $userAgent 使用者的代理
     * @param Player $player 目標玩家
     * @return bool
     */
    private function isPlayerInHierarchy(Agent $userAgent, Player $player): bool
    {
        // 如果玩家直屬於使用者代理
        if ($player->agent_id === $userAgent->id) {
            return true;
        }
        
        // 檢查玩家是否隸屬於下層代理
        $playerAgent = $player->agent;
        if (!$playerAgent) {
            return false;
        }
        
        $current = $playerAgent;
        while ($current->parent) {
            if ($current->parent_id === $userAgent->id) {
                return true;
            }
            $current = $current->parent;
        }
        
        return false;
    }
}