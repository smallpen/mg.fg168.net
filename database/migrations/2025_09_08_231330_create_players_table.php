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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('玩家姓名');
            $table->string('username', 50)->comment('原始帳號');
            $table->string('account', 100)->unique()->comment('完整帳號 (prefix + username)');
            $table->string('email')->unique()->comment('電子郵件');
            $table->string('phone', 20)->nullable()->comment('電話號碼');
            $table->unsignedBigInteger('agent_id')->comment('隸屬代理ID');
            $table->decimal('points', 15, 2)->default(0)->comment('玩家點數');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->unsignedBigInteger('created_by')->nullable()->comment('建立者');
            $table->text('notes')->nullable()->comment('備註');
            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index(['agent_id', 'is_active']);
            $table->index(['account']);
            $table->index(['created_by']);
            $table->index(['points']);

            // 外鍵約束
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
