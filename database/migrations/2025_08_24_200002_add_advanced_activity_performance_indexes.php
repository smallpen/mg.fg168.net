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
        // 為 activities 表新增進階效能索引
        Schema::table('activities', function (Blueprint $table) {
            // 檢查索引是否已存在，避免重複建立
            try {
                // 覆蓋索引 - 包含常用查詢欄位
                $table->index(['created_at', 'type', 'user_id', 'result'], 'idx_activities_perf_covering');
                
                // 複合索引用於複雜查詢
                $table->index(['module', 'type', 'result', 'created_at'], 'idx_activities_perf_module');
                $table->index(['risk_level', 'type', 'created_at', 'user_id'], 'idx_activities_perf_security');
                
                // 用於搜尋的全文索引
                $table->fullText(['description'], 'idx_activities_perf_fulltext');
                
            } catch (\Exception $e) {
                // 如果索引已存在，忽略錯誤
            }
        });

        // 為 security_alerts 表新增進階索引
        Schema::table('security_alerts', function (Blueprint $table) {
            // 覆蓋索引用於儀表板查詢
            $table->index(['severity', 'acknowledged_at', 'created_at', 'type'], 'idx_alerts_dashboard_covering');
            
            // 用於趨勢分析的索引（使用 created_at 替代 DATE 函數）
            $table->index(['created_at', 'severity', 'type'], 'idx_alerts_trend_analysis');
            
            // 用於未處理警報的索引
            $table->index(['acknowledged_at', 'severity', 'created_at'], 'idx_alerts_unacknowledged');
        });

        // 為 monitor_rules 表新增效能索引
        Schema::table('monitor_rules', function (Blueprint $table) {
            // 用於規則匹配的索引
            $table->index(['is_active', 'priority', 'name'], 'idx_rules_matching');
            
            // 用於效能分析的索引
            $table->index(['triggered_count', 'last_triggered_at'], 'idx_rules_performance');
        });

        // 建立活動記錄效能監控表
        Schema::create('activity_query_performance', function (Blueprint $table) {
            $table->id();
            $table->string('query_type'); // list, stats, search, export
            $table->text('query_hash'); // 查詢的雜湊值
            $table->json('query_parameters')->nullable(); // 查詢參數
            $table->decimal('execution_time', 8, 3); // 執行時間（毫秒）
            $table->integer('result_count')->nullable(); // 結果數量
            $table->boolean('used_cache')->default(false); // 是否使用快取
            $table->string('index_used')->nullable(); // 使用的索引
            $table->timestamp('executed_at');
            $table->timestamps();
            
            // 效能監控索引
            $table->index(['query_type', 'executed_at']);
            $table->index(['execution_time', 'executed_at']);
            $table->index(['used_cache', 'query_type']);
        });

        // 建立索引使用統計表
        Schema::create('activity_index_stats', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->string('index_name');
            $table->bigInteger('usage_count')->default(0);
            $table->decimal('avg_execution_time', 8, 3)->default(0);
            $table->decimal('selectivity', 5, 4)->default(0); // 選擇性 0-1
            $table->bigInteger('rows_examined')->default(0);
            $table->bigInteger('rows_returned')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->unique(['table_name', 'index_name']);
            $table->index(['usage_count', 'last_used_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 移除活動記錄的進階索引
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('idx_activities_covering_advanced');
            $table->dropIndex('idx_activities_module_analysis');
            $table->dropIndex('idx_activities_security_analysis');
            // 移除的索引已經在上面處理
            $table->dropIndex('idx_activities_description_fulltext');
        });

        // 移除活動記錄的進階索引
        Schema::table('activities', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_activities_perf_covering');
                $table->dropIndex('idx_activities_perf_module');
                $table->dropIndex('idx_activities_perf_security');
                $table->dropIndex('idx_activities_perf_fulltext');
            } catch (\Exception $e) {
                // 忽略索引不存在的錯誤
            }
        });

        // 移除 security_alerts 表的進階索引
        if (Schema::hasTable('security_alerts')) {
            Schema::table('security_alerts', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_alerts_dashboard_covering');
                    $table->dropIndex('idx_alerts_trend_analysis');
                    $table->dropIndex('idx_alerts_unacknowledged');
                } catch (\Exception $e) {
                    // 忽略索引不存在的錯誤
                }
            });
        }

        // 移除 monitor_rules 表的效能索引
        if (Schema::hasTable('monitor_rules')) {
            Schema::table('monitor_rules', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_rules_matching');
                    $table->dropIndex('idx_rules_performance');
                } catch (\Exception $e) {
                    // 忽略索引不存在的錯誤
                }
            });
        }

        // 移除效能監控表
        Schema::dropIfExists('activity_index_stats');
        Schema::dropIfExists('activity_query_performance');
    }
};