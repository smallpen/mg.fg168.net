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
        Schema::table('users', function (Blueprint $table) {
            // 個人資料欄位（avatar 已存在，跳過）
            $table->string('phone')->nullable()->after('email');
            $table->text('bio')->nullable()->after('phone');
            
            // 偏好設定欄位（theme_preference 已存在，需要修改類型）
            $table->string('timezone')->default('Asia/Taipei')->after('avatar');
            $table->string('language_preference')->default('zh_TW')->after('timezone');
            
            // 通知設定欄位
            $table->boolean('email_notifications')->default(true)->after('language_preference');
            $table->boolean('browser_notifications')->default(true)->after('email_notifications');
            
            // 安全設定欄位
            $table->boolean('two_factor_enabled')->default(false)->after('browser_notifications');
            $table->boolean('login_notifications')->default(true)->after('two_factor_enabled');
            $table->boolean('security_alerts')->default(true)->after('login_notifications');
            $table->integer('session_timeout')->default(120)->after('security_alerts'); // 分鐘
            
            // 索引
            $table->index(['email', 'is_active']);
            $table->index('username');
        });
        
        // 修改 theme_preference 欄位類型
        Schema::table('users', function (Blueprint $table) {
            $table->enum('theme_preference', ['light', 'dark', 'system'])->default('light')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 移除索引
            $table->dropIndex(['email', 'is_active']);
            $table->dropIndex(['username']);
            
            // 移除新增的欄位（保留原有的 avatar 和 theme_preference）
            $table->dropColumn([
                'phone',
                'bio', 
                'timezone',
                'language_preference',
                'email_notifications',
                'browser_notifications',
                'two_factor_enabled',
                'login_notifications',
                'security_alerts',
                'session_timeout'
            ]);
        });
        
        // 恢復 theme_preference 原始類型
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme_preference')->default('light')->change();
        });
    }
};
