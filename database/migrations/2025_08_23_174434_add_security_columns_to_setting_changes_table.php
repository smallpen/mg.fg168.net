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
        Schema::table('setting_changes', function (Blueprint $table) {
            // Columns already exist, no need to add them
            // This migration is kept for consistency but does nothing
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setting_changes', function (Blueprint $table) {
            // No columns to drop as they weren't added
        });
    }
};
