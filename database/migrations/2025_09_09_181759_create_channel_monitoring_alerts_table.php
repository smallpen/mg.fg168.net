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
        Schema::create('channel_monitoring_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type')->comment('警報類型');
            $table->enum('severity', ['low', 'medium', 'high', 'critical', 'error'])->comment('嚴重程度');
            $table->text('message')->comment('警報訊息');
            $table->json('data')->nullable()->comment('詳細資料');
            $table->boolean('resolved')->default(false)->comment('是否已解決');
            $table->timestamp('resolved_at')->nullable()->comment('解決時間');
            $table->unsignedBigInteger('resolved_by')->nullable()->comment('解決者');
            $table->text('resolution_notes')->nullable()->comment('解決備註');
            $table->timestamps();

            $table->index(['type', 'severity']);
            $table->index(['created_at', 'resolved']);
            $table->index('severity');
            
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_monitoring_alerts');
    }
};
