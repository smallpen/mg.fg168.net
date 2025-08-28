<?php

namespace Tests\Unit\LivewireFormReset;

use Tests\TestCase;
use App\Services\LivewireFormReset\BatchProcessor;
use App\Services\LivewireFormReset\FixExecutor;
use App\Services\LivewireFormReset\ComponentClassifier;
use Illuminate\Support\Collection;

/**
 * BatchProcessor 測試
 */
class BatchProcessorTest extends TestCase
{
    protected BatchProcessor $batchProcessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batchProcessor = new BatchProcessor();
    }

    /**
     * 測試建立 BatchProcessor 實例
     */
    public function test_can_create_batch_processor_instance(): void
    {
        $this->assertInstanceOf(BatchProcessor::class, $this->batchProcessor);
    }

    /**
     * 測試取得進度監控
     */
    public function test_can_get_progress_monitoring(): void
    {
        $progress = $this->batchProcessor->getProgressMonitoring();
        
        $this->assertIsArray($progress);
        $this->assertArrayHasKey('current_status', $progress);
        $this->assertArrayHasKey('overall_progress', $progress);
        $this->assertArrayHasKey('current_batch', $progress);
        $this->assertArrayHasKey('total_batches', $progress);
        $this->assertArrayHasKey('current_priority', $progress);
        $this->assertArrayHasKey('processed_components', $progress);
        $this->assertArrayHasKey('total_components', $progress);
        $this->assertArrayHasKey('success_rate', $progress);
        $this->assertArrayHasKey('estimated_completion', $progress);
        $this->assertArrayHasKey('retry_queue_size', $progress);
        $this->assertArrayHasKey('recent_errors', $progress);
    }

    /**
     * 測試初始狀態
     */
    public function test_initial_batch_state(): void
    {
        $progress = $this->batchProcessor->getProgressMonitoring();
        
        $this->assertEquals('idle', $progress['current_status']);
        $this->assertEquals(0, $progress['current_batch']);
        $this->assertEquals(0, $progress['total_batches']);
        $this->assertEquals(0, $progress['processed_components']);
        $this->assertEquals(0, $progress['total_components']);
        $this->assertEquals(0, $progress['retry_queue_size']);
        $this->assertIsArray($progress['recent_errors']);
    }

    /**
     * 測試優先級配置
     */
    public function test_priority_configuration(): void
    {
        // 使用反射來測試私有屬性
        $reflection = new \ReflectionClass($this->batchProcessor);
        $property = $reflection->getProperty('priorityConfig');
        $property->setAccessible(true);
        $priorityConfig = $property->getValue($this->batchProcessor);
        
        $this->assertIsArray($priorityConfig);
        $this->assertArrayHasKey('very_high', $priorityConfig);
        $this->assertArrayHasKey('high', $priorityConfig);
        $this->assertArrayHasKey('medium', $priorityConfig);
        $this->assertArrayHasKey('low', $priorityConfig);
        $this->assertArrayHasKey('very_low', $priorityConfig);
        
        // 檢查每個優先級的配置結構
        foreach ($priorityConfig as $level => $config) {
            $this->assertArrayHasKey('min_score', $config);
            $this->assertArrayHasKey('max_score', $config);
            $this->assertArrayHasKey('batch_size', $config);
            $this->assertArrayHasKey('max_parallel', $config);
            $this->assertArrayHasKey('retry_attempts', $config);
            $this->assertArrayHasKey('retry_delay', $config);
        }
    }

    /**
     * 測試處理選項
     */
    public function test_processing_options(): void
    {
        // 使用反射來測試私有屬性
        $reflection = new \ReflectionClass($this->batchProcessor);
        $property = $reflection->getProperty('processingOptions');
        $property->setAccessible(true);
        $processingOptions = $property->getValue($this->batchProcessor);
        
        $this->assertIsArray($processingOptions);
        $this->assertArrayHasKey('pause_on_error', $processingOptions);
        $this->assertArrayHasKey('max_consecutive_failures', $processingOptions);
        $this->assertArrayHasKey('batch_delay', $processingOptions);
        $this->assertArrayHasKey('enable_notifications', $processingOptions);
        $this->assertArrayHasKey('save_progress', $processingOptions);
        $this->assertArrayHasKey('progress_cache_key', $processingOptions);
        
        // 檢查預設值
        $this->assertFalse($processingOptions['pause_on_error']);
        $this->assertEquals(5, $processingOptions['max_consecutive_failures']);
        $this->assertEquals(10, $processingOptions['batch_delay']);
        $this->assertTrue($processingOptions['enable_notifications']);
        $this->assertTrue($processingOptions['save_progress']);
        $this->assertEquals('livewire_form_reset_progress', $processingOptions['progress_cache_key']);
    }

    /**
     * 測試處理重試佇列
     */
    public function test_process_retry_queue(): void
    {
        $result = $this->batchProcessor->processRetryQueue();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('processed', $result);
        $this->assertArrayHasKey('successful', $result);
        $this->assertArrayHasKey('failed', $result);
        
        // 初始狀態下重試佇列應該是空的
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['successful']);
        $this->assertEquals(0, $result['failed']);
    }

    /**
     * 測試實作錯誤恢復機制
     */
    public function test_implement_error_recovery(): void
    {
        $recovery = $this->batchProcessor->implementErrorRecovery();
        
        $this->assertIsArray($recovery);
        $this->assertArrayHasKey('actions_taken', $recovery);
        $this->assertArrayHasKey('recovered_components', $recovery);
        $this->assertArrayHasKey('unrecoverable_components', $recovery);
        $this->assertArrayHasKey('recommendations', $recovery);
        
        $this->assertIsArray($recovery['actions_taken']);
        $this->assertIsInt($recovery['recovered_components']);
        $this->assertIsInt($recovery['unrecoverable_components']);
        $this->assertIsArray($recovery['recommendations']);
    }

    /**
     * 測試批次狀態結構
     */
    public function test_batch_state_structure(): void
    {
        // 使用反射來測試私有屬性
        $reflection = new \ReflectionClass($this->batchProcessor);
        $property = $reflection->getProperty('batchState');
        $property->setAccessible(true);
        $batchState = $property->getValue($this->batchProcessor);
        
        $this->assertIsArray($batchState);
        $this->assertArrayHasKey('status', $batchState);
        $this->assertArrayHasKey('current_batch', $batchState);
        $this->assertArrayHasKey('total_batches', $batchState);
        $this->assertArrayHasKey('processed_components', $batchState);
        $this->assertArrayHasKey('total_components', $batchState);
        $this->assertArrayHasKey('successful_batches', $batchState);
        $this->assertArrayHasKey('failed_batches', $batchState);
        $this->assertArrayHasKey('start_time', $batchState);
        $this->assertArrayHasKey('end_time', $batchState);
        $this->assertArrayHasKey('estimated_completion', $batchState);
        $this->assertArrayHasKey('current_priority_level', $batchState);
        $this->assertArrayHasKey('batch_results', $batchState);
        $this->assertArrayHasKey('errors', $batchState);
        $this->assertArrayHasKey('retry_queue', $batchState);
        
        // 檢查初始值
        $this->assertEquals('idle', $batchState['status']);
        $this->assertEquals(0, $batchState['current_batch']);
        $this->assertEquals(0, $batchState['total_batches']);
        $this->assertIsArray($batchState['batch_results']);
        $this->assertIsArray($batchState['errors']);
        $this->assertIsArray($batchState['retry_queue']);
    }

    /**
     * 測試優先級分數範圍
     */
    public function test_priority_score_ranges(): void
    {
        // 使用反射來測試私有屬性
        $reflection = new \ReflectionClass($this->batchProcessor);
        $property = $reflection->getProperty('priorityConfig');
        $property->setAccessible(true);
        $priorityConfig = $property->getValue($this->batchProcessor);
        
        // 檢查分數範圍是否連續且不重疊
        $expectedRanges = [
            'very_high' => [8.0, 10.0],
            'high' => [6.0, 8.0],
            'medium' => [4.0, 6.0],
            'low' => [2.0, 4.0],
            'very_low' => [0.0, 2.0],
        ];
        
        foreach ($expectedRanges as $level => $range) {
            $this->assertEquals($range[0], $priorityConfig[$level]['min_score']);
            $this->assertEquals($range[1], $priorityConfig[$level]['max_score']);
        }
    }

    /**
     * 測試批次大小配置
     */
    public function test_batch_size_configuration(): void
    {
        // 使用反射來測試私有屬性
        $reflection = new \ReflectionClass($this->batchProcessor);
        $property = $reflection->getProperty('priorityConfig');
        $property->setAccessible(true);
        $priorityConfig = $property->getValue($this->batchProcessor);
        
        // 檢查批次大小是否合理（高優先級批次較小）
        $this->assertLessThanOrEqual($priorityConfig['high']['batch_size'], $priorityConfig['very_high']['batch_size']);
        $this->assertLessThanOrEqual($priorityConfig['medium']['batch_size'], $priorityConfig['high']['batch_size']);
        $this->assertLessThanOrEqual($priorityConfig['low']['batch_size'], $priorityConfig['medium']['batch_size']);
        $this->assertLessThanOrEqual($priorityConfig['very_low']['batch_size'], $priorityConfig['low']['batch_size']);
    }

    /**
     * 測試重試配置
     */
    public function test_retry_configuration(): void
    {
        // 使用反射來測試私有屬性
        $reflection = new \ReflectionClass($this->batchProcessor);
        $property = $reflection->getProperty('priorityConfig');
        $property->setAccessible(true);
        $priorityConfig = $property->getValue($this->batchProcessor);
        
        // 檢查重試次數和延遲是否合理
        foreach ($priorityConfig as $level => $config) {
            $this->assertGreaterThanOrEqual(1, $config['retry_attempts']);
            $this->assertGreaterThanOrEqual(30, $config['retry_delay']);
            $this->assertLessThanOrEqual(5, $config['max_parallel']);
        }
        
        // 高優先級應該有更多重試次數和更短延遲
        $this->assertGreaterThanOrEqual($priorityConfig['low']['retry_attempts'], $priorityConfig['very_high']['retry_attempts']);
        $this->assertLessThanOrEqual($priorityConfig['low']['retry_delay'], $priorityConfig['very_high']['retry_delay']);
    }
}