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
        Schema::table('monitor_rules', function (Blueprint $table) {
            // Add triggered_count column if it doesn't exist
            if (!Schema::hasColumn('monitor_rules', 'triggered_count')) {
                $table->integer('triggered_count')->default(0)->after('priority');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitor_rules', function (Blueprint $table) {
            if (Schema::hasColumn('monitor_rules', 'triggered_count')) {
                $table->dropColumn('triggered_count');
            }
        });
    }
};
