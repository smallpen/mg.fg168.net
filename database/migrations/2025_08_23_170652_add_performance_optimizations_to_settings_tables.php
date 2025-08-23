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
        // 優化 settings 表的索引
        Schema::table('settings', function (Blueprint $table) {
            // 檢查索引是否已存在，避免重複建立
            $indexes = DB::select("SHOW INDEX FROM settings WHERE Key_name = 'idx_settings_public_category_sort'");
            if (empty($indexes)) {
                $table->index(['is_public', 'category', 'sort_order'], 'idx_settings_public_category_sort');
            }
            
            $indexes = DB::select("SHOW INDEX FROM settings WHERE Key_name = 'idx_settings_system_category'");
            if (empty($indexes)) {
                $table->index(['is_system', 'category'], 'idx_settings_system_category');
            }
            
            $indexes = DB::select("SHOW INDEX FROM settings WHERE Key_name = 'idx_settings_type_public'");
            if (empty($indexes)) {
                $table->index(['type', 'is_public'], 'idx_settings_type_public');
            }
            
            $indexes = DB::select("SHOW INDEX FROM settings WHERE Key_name = 'idx_settings_updated_category'");
            if (empty($indexes)) {
                $table->index(['updated_at', 'category'], 'idx_settings_updated_category');
            }
            
            // 新增全文搜尋索引（如果支援且不存在）
            if (Schema::hasColumn('settings', 'description')) {
                $indexes = DB::select("SHOW INDEX FROM settings WHERE Key_name = 'idx_settings_fulltext'");
                if (empty($indexes)) {
                    try {
                        $table->fullText(['key', 'description'], 'idx_settings_fulltext');
                    } catch (\Exception $e) {
                        // 如果不支援全文索引，忽略錯誤
                    }
                }
            }
        });

        // 優化 setting_changes 表的索引
        if (Schema::hasTable('setting_changes')) {
            Schema::table('setting_changes', function (Blueprint $table) {
                // 檢查索引是否已存在
                $indexes = DB::select("SHOW INDEX FROM setting_changes WHERE Key_name = 'idx_setting_changes_date_key'");
                if (empty($indexes)) {
                    $table->index(['created_at', 'setting_key'], 'idx_setting_changes_date_key');
                }
                
                $indexes = DB::select("SHOW INDEX FROM setting_changes WHERE Key_name = 'idx_setting_changes_user_date_key'");
                if (empty($indexes)) {
                    $table->index(['changed_by', 'created_at', 'setting_key'], 'idx_setting_changes_user_date_key');
                }
                
                // 跳過函數索引，因為 MySQL 版本可能不支援
            });
        }

        // 優化 setting_backups 表的索引
        if (Schema::hasTable('setting_backups')) {
            Schema::table('setting_backups', function (Blueprint $table) {
                // 檢查索引是否已存在
                $indexes = DB::select("SHOW INDEX FROM setting_backups WHERE Key_name = 'idx_setting_backups_type_date'");
                if (empty($indexes)) {
                    // 檢查 backup_type 欄位是否存在
                    if (Schema::hasColumn('setting_backups', 'backup_type')) {
                        $table->index(['backup_type', 'created_at'], 'idx_setting_backups_type_date');
                    }
                }
                
                $indexes = DB::select("SHOW INDEX FROM setting_backups WHERE Key_name = 'idx_setting_backups_user_type'");
                if (empty($indexes)) {
                    if (Schema::hasColumn('setting_backups', 'backup_type')) {
                        $table->index(['created_by', 'backup_type'], 'idx_setting_backups_user_type');
                    }
                }
                
                $indexes = DB::select("SHOW INDEX FROM setting_backups WHERE Key_name = 'idx_setting_backups_count_date'");
                if (empty($indexes)) {
                    // 檢查 settings_count 欄位是否存在
                    if (Schema::hasColumn('setting_backups', 'settings_count')) {
                        $table->index(['settings_count', 'created_at'], 'idx_setting_backups_count_date');
                    }
                }
            });
        }

        // 新增效能監控表
        Schema::create('setting_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type', 50)->comment('指標類型：cache_hit, query_time, batch_update');
            $table->string('operation', 100)->comment('操作名稱');
            $table->decimal('value', 10, 4)->comment('指標值');
            $table->string('unit', 20)->default('ms')->comment('單位');
            $table->json('metadata')->nullable()->comment('額外資料');
            $table->timestamp('recorded_at')->useCurrent()->comment('記錄時間');
            
            // 索引
            $table->index(['metric_type', 'recorded_at'], 'idx_perf_metrics_type_date');
            $table->index(['operation', 'recorded_at'], 'idx_perf_metrics_operation_date');
            $table->index('recorded_at', 'idx_perf_metrics_date');
        });

        // 新增設定快取表（用於持久化快取）
        Schema::create('setting_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key')->unique()->comment('快取鍵值');
            $table->longText('cache_data')->comment('快取資料');
            $table->string('cache_type', 50)->default('setting')->comment('快取類型');
            $table->timestamp('expires_at')->nullable()->comment('過期時間');
            $table->timestamps();
            
            // 索引
            $table->index(['cache_type', 'expires_at'], 'idx_setting_cache_type_expires');
            $table->index('expires_at', 'idx_setting_cache_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 移除 settings 表的新索引
        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex('idx_settings_public_category_sort');
            $table->dropIndex('idx_settings_system_category');
            $table->dropIndex('idx_settings_type_public');
            $table->dropIndex('idx_settings_updated_category');
            
            if (Schema::hasColumn('settings', 'description')) {
                $table->dropFullText('idx_settings_fulltext');
            }
        });

        // 移除 setting_changes 表的新索引
        if (Schema::hasTable('setting_changes')) {
            Schema::table('setting_changes', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_setting_changes_date_key');
                } catch (\Exception $e) {
                    // 索引可能不存在
                }
                try {
                    $table->dropIndex('idx_setting_changes_user_date_key');
                } catch (\Exception $e) {
                    // 索引可能不存在
                }
            });
        }

        // 移除 setting_backups 表的新索引
        if (Schema::hasTable('setting_backups')) {
            Schema::table('setting_backups', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_setting_backups_type_date');
                } catch (\Exception $e) {
                    // 索引可能不存在
                }
                try {
                    $table->dropIndex('idx_setting_backups_user_type');
                } catch (\Exception $e) {
                    // 索引可能不存在
                }
                try {
                    $table->dropIndex('idx_setting_backups_count_date');
                } catch (\Exception $e) {
                    // 索引可能不存在
                }
            });
        }

        // 刪除新建的表
        Schema::dropIfExists('setting_performance_metrics');
        Schema::dropIfExists('setting_cache');
    }
};
