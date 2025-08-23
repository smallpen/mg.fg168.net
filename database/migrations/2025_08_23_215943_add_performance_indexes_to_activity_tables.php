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
        // 為 activities 表新增效能優化索引
        Schema::table('activities', function (Blueprint $table) {
            // 複合索引用於常見查詢模式
            $table->index(['user_id', 'type', 'created_at'], 'idx_activities_user_type_time');
            $table->index(['subject_type', 'subject_id', 'created_at'], 'idx_activities_subject_time');
            $table->index(['ip_address', 'created_at'], 'idx_activities_ip_time');
            $table->index(['risk_level', 'created_at'], 'idx_activities_risk_time');
            $table->index(['result', 'type'], 'idx_activities_result_type');
            
            // 用於安全事件查詢的索引
            $table->index(['type', 'risk_level', 'created_at'], 'idx_activities_security_events');
            
            // 用於統計查詢的索引
            $table->index(['module', 'type', 'created_at'], 'idx_activities_stats');
        });

        // 為 security_alerts 表新增效能優化索引
        Schema::table('security_alerts', function (Blueprint $table) {
            // 複合索引用於常見查詢模式
            $table->index(['type', 'severity', 'created_at'], 'idx_alerts_type_severity_time');
            $table->index(['acknowledged_at', 'severity'], 'idx_alerts_ack_severity');
            $table->index(['rule_id', 'created_at'], 'idx_alerts_rule_time');
            
            // 用於儀表板查詢的索引
            $table->index(['severity', 'acknowledged_at', 'created_at'], 'idx_alerts_dashboard');
        });

        // 為 monitor_rules 表新增效能優化索引
        Schema::table('monitor_rules', function (Blueprint $table) {
            // 複合索引用於規則匹配
            $table->index(['is_active', 'priority', 'created_at'], 'idx_rules_active_priority');
            $table->index(['created_by', 'is_active'], 'idx_rules_creator_active');
        });

        // 新增 security_alerts 表的 rule_id 外鍵約束（在 monitor_rules 表建立後）
        Schema::table('security_alerts', function (Blueprint $table) {
            $table->foreign('rule_id')->references('id')->on('monitor_rules')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 移除 activities 表的效能索引
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('idx_activities_user_type_time');
            $table->dropIndex('idx_activities_subject_time');
            $table->dropIndex('idx_activities_ip_time');
            $table->dropIndex('idx_activities_risk_time');
            $table->dropIndex('idx_activities_result_type');
            $table->dropIndex('idx_activities_security_events');
            $table->dropIndex('idx_activities_stats');
        });

        // 移除 security_alerts 表的效能索引
        Schema::table('security_alerts', function (Blueprint $table) {
            $table->dropIndex('idx_alerts_type_severity_time');
            $table->dropIndex('idx_alerts_ack_severity');
            $table->dropIndex('idx_alerts_rule_time');
            $table->dropIndex('idx_alerts_dashboard');
        });

        // 移除 monitor_rules 表的效能索引
        Schema::table('monitor_rules', function (Blueprint $table) {
            $table->dropIndex('idx_rules_active_priority');
            $table->dropIndex('idx_rules_creator_active');
        });

        // 移除 security_alerts 表的 rule_id 外鍵約束
        Schema::table('security_alerts', function (Blueprint $table) {
            $table->dropForeign(['rule_id']);
        });
    }
};
