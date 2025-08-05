<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 主要資料庫種子檔案
 * 
 * 負責協調所有種子檔案的執行順序
 */
class DatabaseSeeder extends Seeder
{
    /**
     * 執行資料庫種子
     */
    public function run(): void
    {
        // 按照依賴順序執行種子檔案
        $this->call([
            PermissionSeeder::class,  // 先建立權限
            RoleSeeder::class,        // 再建立角色並指派權限
            UserSeeder::class,        // 最後建立使用者並指派角色
        ]);
    }
}