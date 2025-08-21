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
        // 新增全文搜尋索引（如果支援）
        $this->addFullTextIndexes();
        
        // 新增分割槽支援（大型資料集）
        $this->addPartitioningSupport();
        
        // 新增快取相關資料表
        $this->createCacheOptimizationTables();
        
        // 新增效能監控資料表
        $this->createPerformanceMonitoringTables();
        
        // 優化現有索引
        $this->optimizeExistingIndexes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 移除效能監控資料表
        Schema::dropIfExists('permission_performance_metrics');
        Schema::dropIfExists('permission_cache_statistics');
        
        // 移除全文搜尋索引
        $this->removeFullTextIndexes();
        
        // 移除優化索引
        $this->removeOptimizedIndexes();
    }

    /**
     * 新增全文搜尋索引
     */
    private function addFullTextIndexes(): void
    {
        try {
            // 檢查 MySQL 版本是否支援 InnoDB 全文搜尋
            $version = DB::select("SELECT VERSION() as version")[0]->version;
            $majorVersion = (int) explode('.', $version)[0];
            
            if ($majorVersion >= 8 || ($majorVersion == 5 && (int) explode('.', $version)[1] >= 7)) {
                Schema::table('permissions', function (Blueprint $table) {
                    if (!$this->fullTextIndexExists('permissions', 'permissions_fulltext_idx')) {
                        DB::statement('ALTER TABLE permissions ADD FULLTEXT permissions_fulltext_idx (name, display_name, description)');
                    }
                });
            }
        } catch (\Exception $e) {
            // 如果無法建立全文索引，記錄警告但不中斷遷移
            \Log::warning('無法建立全文搜尋索引: ' . $e->getMessage());
        }
    }

    /**
     * 新增分割槽支援
     */
    private function addPartitioningSupport(): void
    {
        try {
            // 為 permission_audit_logs 表新增分割槽（按月分割）
            if (Schema::hasTable('permission_audit_logs')) {
                $tableExists = DB::select("SHOW TABLES LIKE 'permission_audit_logs'");
                if (!empty($tableExists)) {
                    // 檢查是否已經分割槽
                    $partitions = DB::select("
                        SELECT PARTITION_NAME 
                        FROM INFORMATION_SCHEMA.PARTITIONS 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'permission_audit_logs' 
                        AND PARTITION_NAME IS NOT NULL
                    ");
                    
                    if (empty($partitions)) {
                        DB::statement("
                            ALTER TABLE permission_audit_logs
                            PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
                                PARTITION p202501 VALUES LESS THAN (202502),
                                PARTITION p202502 VALUES LESS THAN (202503),
                                PARTITION p202503 VALUES LESS THAN (202504),
                                PARTITION p202504 VALUES LESS THAN (202505),
                                PARTITION p202505 VALUES LESS THAN (202506),
                                PARTITION p202506 VALUES LESS THAN (202507),
                                PARTITION p202507 VALUES LESS THAN (202508),
                                PARTITION p202508 VALUES LESS THAN (202509),
                                PARTITION p202509 VALUES LESS THAN (202510),
                                PARTITION p202510 VALUES LESS THAN (202511),
                                PARTITION p202511 VALUES LESS THAN (202512),
                                PARTITION p202512 VALUES LESS THAN (202601),
                                PARTITION p_future VALUES LESS THAN MAXVALUE
                            )
                        ");
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::warning('無法建立資料表分割槽: ' . $e->getMessage());
        }
    }

    /**
     * 建立快取優化相關資料表
     */
    private function createCacheOptimizationTables(): void
    {
        // 權限快取統計資料表
        if (!Schema::hasTable('permission_cache_statistics')) {
            Schema::create('permission_cache_statistics', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key')->index();
            $table->integer('hit_count')->default(0);
            $table->integer('miss_count')->default(0);
            $table->decimal('hit_rate', 5, 4)->default(0);
            $table->integer('size_bytes')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            // 索引
            $table->index(['cache_key', 'last_accessed_at'], 'cache_stats_key_accessed_idx');
            $table->index(['hit_rate', 'hit_count'], 'cache_stats_hit_rate_idx');
            $table->index(['expires_at'], 'cache_stats_expires_idx');
            });
        }
    }

    /**
     * 建立效能監控資料表
     */
    private function createPerformanceMonitoringTables(): void
    {
        // 權限效能指標資料表
        if (!Schema::hasTable('permission_performance_metrics')) {
            Schema::create('permission_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('operation_type')->index(); // query, cache, dependency_check, etc.
            $table->string('operation_name')->index();
            $table->decimal('execution_time_ms', 10, 3);
            $table->integer('memory_usage_bytes')->nullable();
            $table->integer('query_count')->default(1);
            $table->json('metadata')->nullable(); // 額外的效能資料
            $table->timestamp('measured_at');
            $table->timestamps();
            
            // 複合索引用於效能分析
            $table->index(['operation_type', 'operation_name', 'measured_at'], 'perf_metrics_op_type_name_time_idx');
            $table->index(['execution_time_ms', 'measured_at'], 'perf_metrics_exec_time_idx');
            $table->index(['memory_usage_bytes', 'measured_at'], 'perf_metrics_memory_time_idx');
            });
        }
    }

    /**
     * 優化現有索引
     */
    private function optimizeExistingIndexes(): void
    {
        // 為 permissions 表新增覆蓋索引
        Schema::table('permissions', function (Blueprint $table) {
            // 覆蓋索引：包含常用查詢的所有欄位（縮短以避免鍵長度限制）
            if (!$this->indexExists('permissions', 'permissions_covering_idx')) {
                $table->index(['module', 'type', 'id'], 'permissions_covering_idx');
            }
            
            // 搜尋優化索引
            if (!$this->indexExists('permissions', 'permissions_search_optimized_idx')) {
                try {
                    DB::statement('ALTER TABLE permissions ADD INDEX permissions_search_optimized_idx (name(20), display_name(20), module)');
                } catch (\Exception $e) {
                    // 如果無法建立前綴索引，建立簡化版本
                    if (!$this->indexExists('permissions', 'permissions_search_simple_idx')) {
                        $table->index(['name', 'module'], 'permissions_search_simple_idx');
                    }
                }
            }
        });

        // 為 role_permissions 表新增覆蓋索引
        Schema::table('role_permissions', function (Blueprint $table) {
            if (!$this->indexExists('role_permissions', 'role_perms_covering_idx')) {
                $table->index(['role_id', 'permission_id', 'created_at'], 'role_perms_covering_idx');
            }
        });

        // 為 permission_dependencies 表新增覆蓋索引
        Schema::table('permission_dependencies', function (Blueprint $table) {
            if (!$this->indexExists('permission_dependencies', 'perm_deps_covering_idx')) {
                $table->index(['permission_id', 'depends_on_permission_id', 'created_at'], 'perm_deps_covering_idx');
            }
        });
    }

    /**
     * 移除全文搜尋索引
     */
    private function removeFullTextIndexes(): void
    {
        try {
            Schema::table('permissions', function (Blueprint $table) {
                if ($this->fullTextIndexExists('permissions', 'permissions_fulltext_idx')) {
                    DB::statement('ALTER TABLE permissions DROP INDEX permissions_fulltext_idx');
                }
            });
        } catch (\Exception $e) {
            \Log::warning('無法移除全文搜尋索引: ' . $e->getMessage());
        }
    }

    /**
     * 移除優化索引
     */
    private function removeOptimizedIndexes(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            if ($this->indexExists('permissions', 'permissions_covering_idx')) {
                $table->dropIndex('permissions_covering_idx');
            }
            if ($this->indexExists('permissions', 'permissions_search_optimized_idx')) {
                try {
                    DB::statement('ALTER TABLE permissions DROP INDEX permissions_search_optimized_idx');
                } catch (\Exception $e) {
                    // 索引可能不存在
                }
            }
            if ($this->indexExists('permissions', 'permissions_search_simple_idx')) {
                $table->dropIndex('permissions_search_simple_idx');
            }
        });

        Schema::table('role_permissions', function (Blueprint $table) {
            if ($this->indexExists('role_permissions', 'role_perms_covering_idx')) {
                $table->dropIndex('role_perms_covering_idx');
            }
        });

        Schema::table('permission_dependencies', function (Blueprint $table) {
            if ($this->indexExists('permission_dependencies', 'perm_deps_covering_idx')) {
                $table->dropIndex('perm_deps_covering_idx');
            }
        });
    }

    /**
     * 檢查索引是否存在
     *
     * @param string $table 資料表名稱
     * @param string $index 索引名稱
     * @return bool
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
            return !empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 檢查全文索引是否存在
     *
     * @param string $table 資料表名稱
     * @param string $index 索引名稱
     * @return bool
     */
    private function fullTextIndexExists(string $table, string $index): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ? AND Index_type = 'FULLTEXT'", [$index]);
            return !empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }
};