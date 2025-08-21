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
        Schema::table('setting_backups', function (Blueprint $table) {
            $table->string('backup_type', 20)->default('manual')->after('created_by')->comment('備份類型：manual, auto, scheduled');
            $table->integer('settings_count')->default(0)->after('backup_type')->comment('設定項目數量');
            $table->string('checksum', 64)->nullable()->after('settings_count')->comment('資料校驗碼');
            
            // 新增索引
            $table->index('backup_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setting_backups', function (Blueprint $table) {
            $table->dropIndex(['backup_type']);
            $table->dropColumn(['backup_type', 'settings_count', 'checksum']);
        });
    }
};
