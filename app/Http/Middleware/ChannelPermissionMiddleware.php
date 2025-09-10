<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Agent;
use App\Models\Player;

/**
 * 通路管理權限檢查中介軟體
 * 
 * 實作通路管理系統的權限控制和資料存取限制
 * 支援代理自主管理和基於角色的資料存取控制
 */
class ChannelPermissionMiddleware
{
    /**
     * 處理傳入的請求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $permission 需要檢查的權限
     * @param  string|null  $scope 存取範圍 (all|own|subordinate)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ?string $permission = null, ?string $scope = 'all'): Response
    {
        // 檢查使用者是否已登入
        if (!Auth::check()) {
            return $this->handleUnauthorized($request, '請先登入系統');
        }

        $user = Auth::user();

        // 檢查使用者帳號是否啟用
        if (!$user->is_active) {
            Auth::logout();
            return $this->handleUnauthorized($request, '您的帳號已被停用');
        }

        // 檢查基本權限
        if ($permission && !$user->hasPermission($permission)) {
            $this->logPermissionDenied($request, $user->id, $permission, 'insufficient_permission');
            return $this->handleForbidden($request, "您沒有 '{$permission}' 權限");
        }

        // 檢查資料存取範圍
        if (!$this->checkDataAccessScope($request, $user, $scope)) {
            $this->logPermissionDenied($request, $user->id, $permission, 'data_access_denied');
            return $this->handleForbidden($request, '您沒有存取此資料的權限');
        }

        // 記錄成功存取
        $this->logAccessSuccess($request, $user->id, $permission);

        return $next($request);
    }

    /**
     * 檢查資料存取範圍
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @param  string  $scope
     * @return bool
     */
    private function checkDataAccessScope(Request $request, $user, string $scope): bool
    {
        // 系統管理員可以存取所有資料
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // 如果範圍是 'all'，只有系統管理員可以存取
        if ($scope === 'all') {
            return false;
        }

        // 檢查代理自主管理權限
        if ($scope === 'subordinate' && $user->hasPermission('channels.agents.self_manage')) {
            return $this->checkSubordinateAccess($request, $user);
        }

        // 檢查自己的資料存取權限
        if ($scope === 'own') {
            return $this->checkOwnDataAccess($request, $user);
        }

        return false;
    }

    /**
     * 檢查下層資料存取權限
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return bool
     */
    private function checkSubordinateAccess(Request $request, $user): bool
    {
        // 取得使用者對應的代理記錄
        $userAgent = $this->getUserAgent($user);
        if (!$userAgent) {
            return false;
        }

        // 檢查路由參數中的代理或玩家ID
        $agentId = $request->route('agent');
        $playerId = $request->route('player');

        if ($agentId) {
            return $this->isSubordinateAgent($userAgent, $agentId);
        }

        if ($playerId) {
            return $this->isSubordinatePlayer($userAgent, $playerId);
        }

        // 如果沒有特定ID，允許存取（列表頁面會在查詢時限制範圍）
        return true;
    }

    /**
     * 檢查自己資料的存取權限
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return bool
     */
    private function checkOwnDataAccess(Request $request, $user): bool
    {
        $userAgent = $this->getUserAgent($user);
        if (!$userAgent) {
            return false;
        }

        $agentId = $request->route('agent');
        if ($agentId) {
            return $userAgent->id == $agentId;
        }

        return true;
    }

    /**
     * 取得使用者對應的代理記錄
     *
     * @param  \App\Models\User  $user
     * @return \App\Models\Agent|null
     */
    private function getUserAgent($user): ?Agent
    {
        // 這裡需要根據實際的使用者-代理關聯邏輯來實作
        // 暫時使用 email 或 username 來匹配
        return Agent::where('email', $user->email)
                   ->orWhere('username', $user->username)
                   ->first();
    }

    /**
     * 檢查是否為下層代理
     *
     * @param  \App\Models\Agent  $userAgent
     * @param  int  $targetAgentId
     * @return bool
     */
    private function isSubordinateAgent(Agent $userAgent, int $targetAgentId): bool
    {
        $targetAgent = Agent::find($targetAgentId);
        if (!$targetAgent) {
            return false;
        }

        // 檢查目標代理是否為使用者代理的下層
        return $this->isDescendant($userAgent, $targetAgent);
    }

    /**
     * 檢查是否為下層玩家
     *
     * @param  \App\Models\Agent  $userAgent
     * @param  int  $targetPlayerId
     * @return bool
     */
    private function isSubordinatePlayer(Agent $userAgent, int $targetPlayerId): bool
    {
        $targetPlayer = Player::find($targetPlayerId);
        if (!$targetPlayer) {
            return false;
        }

        // 檢查玩家的代理是否為使用者代理的下層（包含自己）
        return $targetPlayer->agent_id === $userAgent->id || 
               $this->isDescendant($userAgent, $targetPlayer->agent);
    }

    /**
     * 檢查是否為下層代理（遞迴檢查）
     *
     * @param  \App\Models\Agent  $ancestor
     * @param  \App\Models\Agent  $descendant
     * @return bool
     */
    private function isDescendant(Agent $ancestor, Agent $descendant): bool
    {
        if ($descendant->parent_id === $ancestor->id) {
            return true;
        }

        if ($descendant->parent_id && $descendant->parent) {
            return $this->isDescendant($ancestor, $descendant->parent);
        }

        return false;
    }

    /**
     * 處理未授權存取
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $message
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function handleUnauthorized(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect' => route('admin.login')
            ], 401);
        }

        session(['url.intended' => $request->url()]);
        return redirect()->route('admin.login')->with('error', $message);
    }

    /**
     * 處理權限不足
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $message
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function handleForbidden(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'insufficient_permission'
            ], 403);
        }

        if ($request->header('X-Livewire')) {
            abort(403, $message);
        }

        abort(403, $message);
    }

    /**
     * 記錄權限拒絕
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $userId
     * @param  string|null  $permission
     * @param  string  $reason
     * @return void
     */
    private function logPermissionDenied(Request $request, int $userId, ?string $permission, string $reason): void
    {
        Log::warning('Channel management access denied', [
            'user_id' => $userId,
            'permission' => $permission,
            'reason' => $reason,
            'url' => $request->url(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * 記錄成功存取
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $userId
     * @param  string|null  $permission
     * @return void
     */
    private function logAccessSuccess(Request $request, int $userId, ?string $permission): void
    {
        Log::info('Channel management access granted', [
            'user_id' => $userId,
            'permission' => $permission,
            'url' => $request->url(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ]);
    }
}