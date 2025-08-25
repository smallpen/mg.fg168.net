<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 建立活動記錄分區表（按月分區）
        // 注意：MySQL 分區需要在建立表時定義，這裡我們建立一個新的分區表
        
        // 首先建立分區配置表
        Schema::create('activity_partitions', function (Blueprint $table) {
            $table->id();
            $table->string('partition_name')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('table_name');
            $table->boolean('is_active')->default(true);
            $table->bigInteger('record_count')->default(0);
            $table->timestamp('last_maintenance_at')->nullable();
            $table->timestamps();
            
            $table->index(['start_date', 'end_date']);
            $table->index(['is_active', 'start_date']);
        });

        // 建立分區管理日誌表
        Schema::create('partition_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('partition_name');
            $table->string('operation'); // create, drop, optimize, archive
            $table->text('details')->nullable();
            $table->string('status'); // success, failed, in_progress
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['partition_name', 'operation']);
            $table->index(['status', 'started_at']);
        });

        // 建立當前月份的分區記錄
        $currentMonth = now()->format('Y_m');
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        
        DB::table('activity_partitions')->insert([
            'partition_name' => "activities_{$currentMonth}",
            'start_date' => $startDate,
            'end_date' => $endDate,
            'table_name' => 'activities',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 建立下個月的分區記錄
        $nextMonth = now()->addMonth()->format('Y_m');
        $nextStartDate = now()->addMonth()->startOfMonth();
        $nextEndDate = now()->addMonth()->endOfMonth();
        
        DB::table('activity_partitions')->insert([
            'partition_name' => "activities_{$nextMonth}",
            'start_date' => $nextStartDate,
            'end_date' => $nextEndDate,
            'table_name' => 'activities',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partition_maintenance_logs');
        Schema::dropIfExists('activity_partitions');
    }
};