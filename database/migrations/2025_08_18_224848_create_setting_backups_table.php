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
        Schema::create('setting_backups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('備份名稱');
            $table->text('description')->nullable()->comment('備份描述');
            $table->longText('settings_data')->comment('設定資料（JSON 格式）');
            $table->unsignedBigInteger('created_by')->comment('建立者 ID');
            $table->timestamps();
            
            // 外鍵約束
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            
            // 索引
            $table->index(['created_by', 'created_at'], 'idx_setting_backups_creator_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting_backups');
    }
};
