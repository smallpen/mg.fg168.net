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
        Schema::create('activity_retention_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('政策名稱');
            $table->string('activity_type')->nullable()->comment('活動類型（null表示適用所有類型）');
            $table->string('module')->nullable()->comment('模組名稱（null表示適用所有模組）');
            $table->integer('retention_days')->comment('保留天數');
            $table->enum('action', ['delete', 'archive'])->default('archive')->comment('到期處理方式');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->integer('priority')->default(0)->comment('優先級（數字越大優先級越高）');
            $table->json('conditions')->nullable()->comment('額外條件（JSON格式）');
            $table->text('description')->nullable()->comment('政策描述');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->comment('建立者');
            $table->timestamp('last_executed_at')->nullable()->comment('最後執行時間');
            $table->timestamps();

            // 索引
            $table->index(['activity_type', 'module']);
            $table->index(['is_active', 'priority']);
            $table->index('last_executed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_retention_policies');
    }
};