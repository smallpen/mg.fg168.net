<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行遷移
     */
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade')->comment('角色 ID');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade')->comment('權限 ID');
            $table->timestamps();
            
            // 建立複合唯一索引，防止重複指派相同權限
            $table->unique(['role_id', 'permission_id']);
        });
    }

    /**
     * 回滾遷移
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
