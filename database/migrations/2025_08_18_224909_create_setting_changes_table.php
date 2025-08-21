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
        Schema::create('setting_changes', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->comment('設定鍵值');
            $table->json('old_value')->nullable()->comment('舊值');
            $table->json('new_value')->nullable()->comment('新值');
            $table->unsignedBigInteger('changed_by')->comment('變更者 ID');
            $table->string('ip_address', 45)->nullable()->comment('IP 位址');
            $table->text('reason')->nullable()->comment('變更原因');
            $table->timestamps();
            
            // 外鍵約束
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('setting_key')->references('key')->on('settings')->onDelete('cascade');
            
            // 索引
            $table->index(['setting_key', 'created_at'], 'idx_setting_changes_key_date');
            $table->index(['changed_by', 'created_at'], 'idx_setting_changes_user_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting_changes');
    }
};
