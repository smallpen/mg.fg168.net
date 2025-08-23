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
        Schema::create('security_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id'); // 關聯的活動記錄
            $table->string('type'); // 警報類型：login_failure, permission_escalation, etc.
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium'); // 嚴重程度
            $table->string('title'); // 警報標題
            $table->text('description'); // 警報描述
            $table->unsignedBigInteger('rule_id')->nullable(); // 觸發的監控規則 ID
            $table->timestamp('acknowledged_at')->nullable(); // 確認時間
            $table->unsignedBigInteger('acknowledged_by')->nullable(); // 確認者 ID
            $table->json('metadata')->nullable(); // 額外的元資料
            $table->timestamps();

            // 索引
            $table->index(['activity_id', 'created_at']);
            $table->index(['type', 'severity']);
            $table->index(['severity', 'acknowledged_at']);
            $table->index('acknowledged_at');
            $table->index('created_at');

            // 外鍵約束
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
            $table->foreign('acknowledged_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_alerts');
    }
};
