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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('範本唯一識別碼');
            $table->string('name')->comment('範本名稱');
            $table->string('category')->default('system')->comment('範本分類');
            $table->string('subject')->comment('郵件主旨');
            $table->text('content')->comment('郵件內容');
            $table->json('variables')->nullable()->comment('可用變數列表');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->boolean('is_system')->default(false)->comment('是否為系統預設範本');
            $table->text('description')->nullable()->comment('範本描述');
            $table->timestamps();
            
            $table->index(['category', 'is_active']);
            $table->index('is_system');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};