<?php

namespace Database\Seeders;

use App\Models\PermissionTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * 權限模板資料填充器
 * 
 * 建立系統預設的權限模板
 */
class PermissionTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 建立系統預設模板
        PermissionTemplate::createSystemTemplates();
        
        $this->command->info('系統權限模板建立完成');
    }
}
