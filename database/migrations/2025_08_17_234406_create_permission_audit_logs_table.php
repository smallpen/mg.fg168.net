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
        Schema::create('permission_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 50)->index(); // 操作類型
            $table->unsignedBigInteger('permission_id')->nullable()->index(); // 權限 ID
            $table->string('permission_name', 100)->nullable()->index(); // 權限名稱
            $table->string('permission_module', 50)->nullable()->index(); // 權限模組
            $table->unsignedBigInteger('user_id')->nullable()->index(); // 操作使用者 ID
            $table->string('username', 50)->nullable()->index(); // 操作使用者名稱
            $table->ipAddress('ip_address')->nullable()->index(); // IP 位址
            $table->text('user_agent')->nullable(); // 使用者代理
            $table->string('url', 500)->nullable(); // 請求 URL
            $table->string('method', 10)->nullable(); // HTTP 方法
            $table->json('data'); // 詳細資料（JSON 格式）
            $table->timestamps();
            
            // 外鍵約束
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            // 複合索引
            $table->index(['permission_id', 'action', 'created_at']);
            $table->index(['user_id', 'action', 'created_at']);
            $table->index(['created_at', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_audit_logs');
    }
};
