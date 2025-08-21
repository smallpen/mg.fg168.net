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
        Schema::create('permission_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->foreignId('depends_on_permission_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamps();

            // 確保同一個依賴關係不會重複
            $table->unique(['permission_id', 'depends_on_permission_id'], 'perm_dep_unique');
            
            // 建立索引以提升查詢效能
            $table->index(['permission_id'], 'perm_dep_perm_id_idx');
            $table->index(['depends_on_permission_id'], 'perm_dep_depends_on_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_dependencies');
    }
};
