<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('roles')->onDelete('set null')->comment('父角色 ID');
            $table->boolean('is_system_role')->default(false)->comment('是否為系統角色');
            
            // 建立索引以提升查詢效能
            $table->index(['parent_id'], 'roles_parent_id_idx');
            $table->index(['is_system_role'], 'roles_is_system_role_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex('roles_parent_id_idx');
            $table->dropIndex('roles_is_system_role_idx');
            $table->dropColumn(['parent_id', 'is_system_role']);
        });
    }
};
