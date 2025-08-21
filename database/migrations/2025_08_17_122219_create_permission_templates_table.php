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
        Schema::create('permission_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('模板名稱');
            $table->string('display_name')->comment('顯示名稱');
            $table->text('description')->nullable()->comment('模板描述');
            $table->string('module')->comment('適用模組');
            $table->json('permissions')->comment('權限配置 JSON');
            $table->boolean('is_system_template')->default(false)->comment('是否為系統預設模板');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->unsignedBigInteger('created_by')->nullable()->comment('建立者');
            $table->timestamps();
            
            // 索引
            $table->index(['module', 'is_active']);
            $table->index(['is_system_template', 'is_active']);
            
            // 外鍵約束
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_templates');
    }
};
