<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdatePermissionTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = Permission::all();
        
        foreach ($permissions as $permission) {
            $type = 'view'; // 預設類型
            
            if (str_contains($permission->name, '.view')) {
                $type = 'view';
            } elseif (str_contains($permission->name, '.create')) {
                $type = 'create';
            } elseif (str_contains($permission->name, '.edit')) {
                $type = 'edit';
            } elseif (str_contains($permission->name, '.delete')) {
                $type = 'delete';
            } elseif (str_contains($permission->name, '.manage') || str_contains($permission->name, '.assign')) {
                $type = 'manage';
            }
            
            $permission->update(['type' => $type]);
        }
        
        $this->command->info('權限類型更新完成');
    }
}
