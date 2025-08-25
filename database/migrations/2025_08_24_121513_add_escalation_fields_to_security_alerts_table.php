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
        Schema::table('security_alerts', function (Blueprint $table) {
            // Add escalation fields if they don't exist
            if (!Schema::hasColumn('security_alerts', 'escalated_at')) {
                $table->timestamp('escalated_at')->nullable()->after('acknowledged_by');
            }
            if (!Schema::hasColumn('security_alerts', 'escalated_by')) {
                $table->unsignedBigInteger('escalated_by')->nullable()->after('escalated_at');
            }
            if (!Schema::hasColumn('security_alerts', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('escalated_by');
            }
            if (!Schema::hasColumn('security_alerts', 'resolved_by')) {
                $table->unsignedBigInteger('resolved_by')->nullable()->after('resolved_at');
            }
            
            // Add foreign key constraints if they don't exist
            if (!Schema::hasColumn('security_alerts', 'escalated_by')) {
                $table->foreign('escalated_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('security_alerts', 'resolved_by')) {
                $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('security_alerts', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['escalated_by']);
            $table->dropForeign(['resolved_by']);
            
            // Drop columns
            $table->dropColumn(['escalated_at', 'escalated_by', 'resolved_at', 'resolved_by']);
        });
    }
};
