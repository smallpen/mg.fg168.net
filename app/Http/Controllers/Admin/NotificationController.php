<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * 顯示通知列表
     */
    public function index(): View
    {
        $this->authorize('notifications.view');
        
        return view('admin.notifications.index');
    }

    /**
     * 顯示建立通知表單
     */
    public function create(): View
    {
        $this->authorize('notifications.create');
        
        $users = User::where('is_active', true)->get(['id', 'name', 'username']);
        
        return view('admin.notifications.create', compact('users'));
    }

    /**
     * 顯示通知詳情
     */
    public function show(Notification $notification): View
    {
        $this->authorize('notifications.view');
        
        return view('admin.notifications.show', compact('notification'));
    }

    /**
     * 顯示編輯通知表單
     */
    public function edit(Notification $notification): View
    {
        $this->authorize('notifications.edit');
        
        $users = User::where('is_active', true)->get(['id', 'name', 'username']);
        
        return view('admin.notifications.edit', compact('notification', 'users'));
    }
}