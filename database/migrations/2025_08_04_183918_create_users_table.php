<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行遷移
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique()->comment('使用者名稱');
            $table->string('name')->comment('使用者姓名');
            $table->string('email')->nullable()->comment('電子郵件');
            $table->timestamp('email_verified_at')->nullable()->comment('電子郵件驗證時間');
            $table->string('password')->comment('密碼');
            $table->string('theme_preference')->default('light')->comment('主題偏好設定');
            $table->string('locale')->default('zh_TW')->comment('語言偏好設定');
            $table->boolean('is_active')->default(true)->comment('帳號是否啟用');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * 回滾遷移
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
