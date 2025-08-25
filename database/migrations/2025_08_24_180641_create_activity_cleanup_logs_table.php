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
        Schema::create('activity_cleanup_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->nullable()->constrained('activity_retention_policies')->onDelete('set null')->comment('關聯的保留政策');
            $table->enum('type', ['manual', 'automatic'])->comment('清理類型');
            $table->enum('action', ['delete', 'archive'])->comment('執行的動作');
            $table->string('activity_type')->nullable()->comment('清理的活動類型');
            $table->string('module')->nullable()->comment('清理的模組');
            $table->date('date_from')->comment('清理的開始日期');
            $table->date('date_to')->comment('清理的結束日期');
            $table->integer('records_processed')->default(0)->comment('處理的記錄數');
            $table->integer('records_deleted')->default(0)->comment('刪除的記錄數');
            $table->integer('records_archived')->default(0)->comment('歸檔的記錄數');
            $table->string('archive_path')->nullable()->comment('歸檔檔案路徑');
            $table->enum('status', ['running', 'completed', 'failed'])->default('running')->comment('執行狀態');
            $table->text('error_message')->nullable()->comment('錯誤訊息');
            $table->integer('execution_time_seconds')->nullable()->comment('執行時間（秒）');
            $table->json('summary')->nullable()->comment('執行摘要（JSON格式）');
            $table->foreignId('executed_by')->nullable()->constrained('users')->onDelete('set null')->comment('執行者');
            $table->timestamp('started_at')->comment('開始時間');
            $table->timestamp('completed_at')->nullable()->comment('完成時間');
            $table->timestamps();

            // 索引
            $table->index(['type', 'status']);
            $table->index(['date_from', 'date_to']);
            $table->index('started_at');
            $table->index('policy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_cleanup_logs');
    }
};