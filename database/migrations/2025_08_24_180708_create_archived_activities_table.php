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
        Schema::create('archived_activities', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('original_id')->comment('原始活動記錄ID');
            $table->string('type')->comment('活動類型');
            $table->string('event')->nullable()->comment('事件名稱');
            $table->text('description')->comment('活動描述');
            $table->string('module')->nullable()->comment('模組名稱');
            $table->foreignId('user_id')->nullable()->comment('執行使用者ID');
            $table->string('user_name')->nullable()->comment('使用者名稱（快照）');
            $table->bigInteger('subject_id')->nullable()->comment('操作對象ID');
            $table->string('subject_type')->nullable()->comment('操作對象類型');
            $table->json('properties')->nullable()->comment('活動屬性');
            $table->string('ip_address')->nullable()->comment('IP位址');
            $table->text('user_agent')->nullable()->comment('使用者代理');
            $table->string('result')->default('success')->comment('執行結果');
            $table->integer('risk_level')->default(1)->comment('風險等級');
            $table->string('signature')->nullable()->comment('數位簽章');
            $table->timestamp('original_created_at')->comment('原始建立時間');
            $table->timestamp('archived_at')->comment('歸檔時間');
            $table->foreignId('archived_by')->nullable()->constrained('users')->onDelete('set null')->comment('歸檔執行者');
            $table->string('archive_reason')->nullable()->comment('歸檔原因');
            $table->timestamps();

            // 索引
            $table->index('original_id');
            $table->index(['type', 'module']);
            $table->index('user_id');
            $table->index(['subject_id', 'subject_type']);
            $table->index('original_created_at');
            $table->index('archived_at');
            $table->index('ip_address');
            $table->index('risk_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archived_activities');
    }
};