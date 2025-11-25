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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('代理姓名');
            $table->string('username', 50)->comment('原始帳號');
            $table->string('account', 100)->unique()->comment('完整帳號 (prefix + username)');
            $table->string('email')->unique()->comment('電子郵件');
            $table->string('phone', 20)->nullable()->comment('電話號碼');
            $table->char('prefix', 1)->nullable()->comment('前置符號 (僅第一層代理)');
            $table->unsignedTinyInteger('level')->comment('代理層級');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('上層代理ID');
            $table->decimal('total_points', 15, 2)->default(0)->comment('總點數');
            $table->decimal('allocated_points', 15, 2)->default(0)->comment('已分配點數');
            $table->decimal('remaining_points', 15, 2)->default(0)->comment('剩餘點數');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->unsignedBigInteger('created_by')->nullable()->comment('建立者');
            $table->text('notes')->nullable()->comment('備註');
            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index(['level', 'is_active']);
            $table->index(['parent_id']);
            $table->index(['prefix', 'level']);
            $table->index(['account']);
            $table->index(['created_by']);

            // 外鍵約束
            $table->foreign('parent_id')->references('id')->on('agents')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // 唯一約束
            $table->unique(['prefix', 'level'], 'unique_prefix_per_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
