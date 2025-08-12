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
            // 檢查 locale 欄位是否已存在
            if (!Schema::hasColumn('users', 'locale')) {
                $table->string('locale', 10)->default('zh_TW')->after('theme_preference');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'locale')) {
                $table->dropColumn('locale');
            }
        });
    }
};
