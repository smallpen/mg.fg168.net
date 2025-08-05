<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| 這個檔案是定義所有基於 Closure 的控制台指令的地方。
| 每個 Closure 都綁定到一個指令實例，允許簡單的方法與
| 每個指令的 IO 方法進行互動。
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('顯示一句鼓舞人心的話');

Artisan::command('admin:create-user {username} {name} {email} {--password=}', function () {
    $username = $this->argument('username');
    $name = $this->argument('name');
    $email = $this->argument('email');
    $password = $this->option('password') ?: $this->secret('請輸入密碼');
    
    $user = \App\Models\User::create([
        'username' => $username,
        'name' => $name,
        'email' => $email,
        'password' => bcrypt($password),
    ]);
    
    // 指派超級管理員角色
    $superAdminRole = \App\Models\Role::where('name', 'super_admin')->first();
    if ($superAdminRole) {
        $user->assignRole($superAdminRole);
    }
    
    $this->info("管理員使用者 {$username} 建立成功！");
})->purpose('建立管理員使用者');

Artisan::command('admin:setup', function () {
    $this->info('開始設定 Laravel Admin System...');
    
    // 執行資料庫遷移
    $this->call('migrate');
    
    // 執行種子檔案
    $this->call('db:seed');
    
    // 清除快取
    $this->call('cache:clear');
    $this->call('config:clear');
    $this->call('route:clear');
    $this->call('view:clear');
    
    $this->info('Laravel Admin System 設定完成！');
})->purpose('設定 Laravel Admin System');