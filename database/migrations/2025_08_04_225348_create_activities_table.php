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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 活動類型：login, logout, create_user, update_user, etc.
            $table->string('event')->nullable(); // 事件名稱
            $table->string('description'); // 活動描述
            $table->string('module')->nullable(); // 模組名稱：users, roles, permissions, etc.
            $table->unsignedBigInteger('user_id')->nullable(); // 執行活動的使用者
            $table->unsignedBigInteger('subject_id')->nullable(); // 被操作的對象 ID
            $table->string('subject_type')->nullable(); // 被操作的對象類型
            $table->json('properties')->nullable(); // 額外的活動屬性
            $table->string('ip_address')->nullable(); // IP 位址
            $table->text('user_agent')->nullable(); // 使用者代理
            $table->string('result')->default('success'); // 操作結果：success, failed, warning
            $table->tinyInteger('risk_level')->default(1); // 風險等級：1-10
            $table->string('signature')->nullable(); // 數位簽章
            $table->timestamps();

            // 索引
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['module', 'created_at']);
            $table->index(['result', 'created_at']);
            $table->index(['risk_level', 'created_at']);
            $table->index('created_at');
            $table->index('ip_address');

            // 外鍵約束
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
