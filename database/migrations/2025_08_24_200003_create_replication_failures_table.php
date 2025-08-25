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
        Schema::create('replication_failures', function (Blueprint $table) {
            $table->id();
            $table->string('target_shard');
            $table->bigInteger('original_activity_id');
            $table->json('activity_data');
            $table->text('error_message');
            $table->integer('attempts')->default(1);
            $table->timestamp('failed_at');
            $table->timestamp('retry_at')->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['target_shard', 'failed_at']);
            $table->index(['resolved', 'retry_at']);
            $table->index('original_activity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replication_failures');
    }
};