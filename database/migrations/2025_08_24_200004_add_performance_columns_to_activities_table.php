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
        Schema::table('activities', function (Blueprint $table) {
            // 壓縮相關欄位
            $table->timestamp('compressed_at')->nullable()->after('signature');
            $table->integer('original_size')->nullable()->after('compressed_at');
            $table->integer('compressed_size')->nullable()->after('original_size');
            
            // 歸檔相關欄位
            $table->timestamp('archived_at')->nullable()->after('compressed_size');
            $table->string('archive_file')->nullable()->after('archived_at');
            
            // 複製相關欄位
            $table->bigInteger('original_id')->nullable()->after('archive_file');
            $table->string('replica_shard')->nullable()->after('original_id');
            $table->timestamp('replicated_at')->nullable()->after('replica_shard');
            
            // 效能相關索引
            $table->index('compressed_at');
            $table->index('archived_at');
            $table->index(['original_id', 'replica_shard']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['original_id', 'replica_shard']);
            $table->dropIndex(['archived_at']);
            $table->dropIndex(['compressed_at']);
            
            $table->dropColumn([
                'compressed_at',
                'original_size', 
                'compressed_size',
                'archived_at',
                'archive_file',
                'original_id',
                'replica_shard',
                'replicated_at'
            ]);
        });
    }
};