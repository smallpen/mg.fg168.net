<?php

namespace Tests\Unit\LivewireFormReset;

use Tests\TestCase;
use App\Services\LivewireFormReset\FixExecutor;
use App\Services\LivewireFormReset\LivewireComponentScanner;
use App\Services\LivewireFormReset\IssueIdentifier;
use App\Services\LivewireFormReset\ComponentClassifier;
use Illuminate\Support\Collection;

/**
 * FixExecutor 測試
 */
class FixExecutorTest extends TestCase
{
    protected FixExecutor $executor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->executor = new FixExecutor();
    }

    /**
     * 測試建立 FixExecutor 實例
     */
    public function test_can_create_fix_executor_instance(): void
    {
        $this->assertInstanceOf(FixExecutor::class, $this->executor);
    }

    /**
     * 測試取得修復結果追蹤
     */
    public function test_can_get_fix_result_tracking(): void
    {
        $tracking = $this->executor->getFixResultTracking();
        
        $this->assertIsArray($tracking);
        $this->assertArrayHasKey('execution_state', $tracking);
        $this->assertArrayHasKey('queue_status', $tracking);
        $this->assertArrayHasKey('performance_metrics', $tracking);
        $this->assertArrayHasKey('error_summary', $tracking);
    }

    /**
     * 測試產生修復報告
     */
    public function test_can_generate_fix_report(): void
    {
        $report = $this->executor->generateFixReport();
        
        $this->assertIsArray($report);
        $this->assertArrayHasKey('report_generated_at', $report);
        $this->assertArrayHasKey('execution_summary', $report);
        $this->assertArrayHasKey('detailed_results', $report);
        $this->assertArrayHasKey('performance_analysis', $report);
        $this->assertArrayHasKey('recommendations', $report);
        $this->assertArrayHasKey('next_steps', $report);
    }

    /**
     * 測試執行狀態初始化
     */
    public function test_execution_state_initialization(): void
    {
        $tracking = $this->executor->getFixResultTracking();
        $executionState = $tracking['execution_state'];
        
        $this->assertEquals('idle', $executionState['status']);
        $this->assertEquals(0, $executionState['total_components']);
        $this->assertEquals(0, $executionState['processed_components']);
        $this->assertEquals(0, $executionState['successful_fixes']);
        $this->assertEquals(0, $executionState['failed_fixes']);
        $this->assertIsArray($executionState['errors']);
        $this->assertIsArray($executionState['results']);
    }

    /**
     * 測試佇列配置
     */
    public function test_queue_configuration(): void
    {
        $tracking = $this->executor->getFixResultTracking();
        $queueStatus = $tracking['queue_status'];
        
        $this->assertArrayHasKey('queue_name', $queueStatus);
        $this->assertArrayHasKey('pending_jobs', $queueStatus);
        $this->assertArrayHasKey('failed_jobs', $queueStatus);
        $this->assertArrayHasKey('processed_jobs', $queueStatus);
    }

    /**
     * 測試效能指標
     */
    public function test_performance_metrics(): void
    {
        $tracking = $this->executor->getFixResultTracking();
        $performanceMetrics = $tracking['performance_metrics'];
        
        $this->assertArrayHasKey('total_execution_time', $performanceMetrics);
        $this->assertArrayHasKey('average_time_per_component', $performanceMetrics);
        $this->assertArrayHasKey('components_per_minute', $performanceMetrics);
        $this->assertArrayHasKey('success_rate', $performanceMetrics);
    }

    /**
     * 測試錯誤摘要
     */
    public function test_error_summary(): void
    {
        $tracking = $this->executor->getFixResultTracking();
        $errorSummary = $tracking['error_summary'];
        
        $this->assertArrayHasKey('total_errors', $errorSummary);
        $this->assertArrayHasKey('error_types', $errorSummary);
        $this->assertArrayHasKey('recent_errors', $errorSummary);
        
        $this->assertEquals(0, $errorSummary['total_errors']);
        $this->assertIsArray($errorSummary['error_types']);
        $this->assertIsArray($errorSummary['recent_errors']);
    }

    /**
     * 測試修復策略對應
     */
    public function test_fix_strategies_mapping(): void
    {
        // 使用反射來測試私有屬性
        $reflection = new \ReflectionClass($this->executor);
        $property = $reflection->getProperty('fixStrategies');
        $property->setAccessible(true);
        $fixStrategies = $property->getValue($this->executor);
        
        $this->assertIsArray($fixStrategies);
        $this->assertArrayHasKey('StandardFormResetFix', $fixStrategies);
        $this->assertArrayHasKey('ListFilterResetFix', $fixStrategies);
        $this->assertArrayHasKey('ModalFormResetFix', $fixStrategies);
        $this->assertArrayHasKey('MonitoringControlFix', $fixStrategies);
    }

    /**
     * 測試建立任務佇列（模擬）
     */
    public function test_can_queue_fix_tasks(): void
    {
        // 建立模擬元件集合
        $components = collect([
            [
                'class_name' => 'TestComponent1',
                'classification' => [
                    'priority_score' => 8.5,
                    'estimated_fix_time' => 30,
                ],
            ],
            [
                'class_name' => 'TestComponent2',
                'classification' => [
                    'priority_score' => 6.2,
                    'estimated_fix_time' => 45,
                ],
            ],
        ]);

        $result = $this->executor->queueFixTasks($components);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_queued', $result);
        $this->assertArrayHasKey('queue_name', $result);
        $this->assertArrayHasKey('jobs', $result);
        $this->assertArrayHasKey('estimated_total_time', $result);
        
        $this->assertEquals(2, $result['total_queued']);
        $this->assertEquals(75, $result['estimated_total_time']); // 30 + 45
    }
}