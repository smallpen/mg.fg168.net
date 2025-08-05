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
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('使用者 ID');
            $table->foreignId('role_id')->constrained()->onDelete('cascade')->comment('角色 ID');
            $table->timestamps();
            
            // 建立複合唯一索引，防止重複指派相同角色
            $table->unique(['user_id', 'role_id']);
        });
    }

    /**
     * 回滾遷移
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
