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
            // Only add is_encrypted column as others already exist
            if (!Schema::hasColumn('setting_backups', 'is_encrypted')) {
                $table->boolean('is_encrypted')->default(false)->after('checksum');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setting_backups', function (Blueprint $table) {
            if (Schema::hasColumn('setting_backups', 'is_encrypted')) {
                $table->dropColumn('is_encrypted');
            }
        });
    }
};
