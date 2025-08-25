<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ActivityExportService;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

/**
 * 活動記錄匯出服務測試
 */
class ActivityExportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityExportService $exportService;
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->exportService = app(ActivityExportService::class);
        
        // 建立測試使用者
        $this->testUser = User::factory()->create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
        ]);
        
        // 設定假的儲存磁碟
        Storage::fake('local');
    }

    /**
     * 測試直接匯出 CSV 格式
     */
    public function test_direct_export_csv(): void
    {
        // 建立測試活動記錄
        $activities = Activity::factory()->count(5)->create([
            'user_id' => $this->testUser->id,
            'type' => 'test_action',
            'description' => '測試操作',
            'module' => 'test',
            'result' => 'success',
            'risk_level' => 1,
        ]);

        $config = [
            'format' => 'csv',
            'filters' => [],
            'options' => [
                'include_user_details' => true,
                'include_properties' => false,
                'include_related_data' => false,
                'batch_size' => 1000,
            ],
            'user_id' => $this->testUser->id,
        ];

        $result = $this->exportService->exportDirect($config);

        // 驗證結果
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('file_path', $result);
        $this->assertArrayHasKey('download_url', $result);
        $this->assertArrayHasKey('record_count', $result);
        $this->assertEquals(5, $result['record_count']);

        // 驗證檔案存在
        Storage::assertExists($result['file_path']);

        // 驗證 CSV 內容
        $csvContent = Storage::get($result['file_path']);
        $this->assertStringContainsString('測試操作', $csvContent);
        $this->assertStringContainsString('測試使用者', $csvContent);
    }

    /**
     * 測試直接匯出 JSON 格式
     */
    public function test_direct_export_json(): void
    {
        // 建立測試活動記錄
        Activity::factory()->count(3)->create([
            'user_id' => $this->testUser->id,
            'type' => 'test_action',
            'description' => '測試操作',
            'properties' => ['test_key' => 'test_value'],
        ]);

        $config = [
            'format' => 'json',
            'filters' => [],
            'options' => [
                'include_user_details' => true,
                'include_properties' => true,
                'include_related_data' => false,
                'batch_size' => 1000,
            ],
            'user_id' => $this->testUser->id,
        ];

        $result = $this->exportService->exportDirect($config);

        // 驗證結果
        $this->assertEquals(3, $result['record_count']);

        // 驗證 JSON 內容
        $jsonContent = Storage::get($result['file_path']);
        $data = json_decode($jsonContent, true);
        
        $this->assertArrayHasKey('export_info', $data);
        $this->assertArrayHasKey('activities', $data);
        $this->assertEquals(3, $data['export_info']['total_records']);
        $this->assertCount(3, $data['activities']);
        
        // 驗證活動資料結構
        $activity = $data['activities'][0];
        $this->assertArrayHasKey('id', $activity);
        $this->assertArrayHasKey('type', $activity);
        $this->assertArrayHasKey('description', $activity);
        $this->assertArrayHasKey('user', $activity);
        $this->assertArrayHasKey('properties', $activity);
    }

    /**
     * 測試批量匯出工作建立
     */
    public function test_batch_export_job_creation(): void
    {
        $config = [
            'format' => 'csv',
            'filters' => [],
            'options' => [
                'include_user_details' => true,
                'include_properties' => false,
                'include_related_data' => false,
                'batch_size' => 100,
            ],
            'user_id' => $this->testUser->id,
        ];

        $jobId = $this->exportService->exportBatch($config);

        // 驗證工作 ID 格式
        $this->assertIsString($jobId);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $jobId);

        // 驗證配置已快取
        $cachedConfig = Cache::get("activity_export.job_{$jobId}");
        $this->assertNotNull($cachedConfig);
        $this->assertEquals($config, $cachedConfig);
    }

    /**
     * 測試匯出進度更新
     */
    public function test_export_progress_update(): void
    {
        $jobId = 'test-job-id';
        $progress = 50;
        $status = '處理中...';

        $this->exportService->updateExportProgress($jobId, $progress, $status);

        // 驗證進度已快取
        $cachedProgress = Cache::get("activity_export.progress_{$jobId}");
        $this->assertNotNull($cachedProgress);
        $this->assertEquals($jobId, $cachedProgress['job_id']);
        $this->assertEquals($progress, $cachedProgress['progress']);
        $this->assertEquals($status, $cachedProgress['status']);
    }

    /**
     * 測試匯出完成標記
     */
    public function test_mark_export_completed(): void
    {
        $jobId = 'test-job-id';
        $result = [
            'filename' => 'test.csv',
            'download_url' => 'http://example.com/test.csv',
            'record_count' => 100,
            'file_size' => 1024,
        ];

        $this->exportService->markExportCompleted($jobId, $result);

        // 驗證完成狀態已快取
        $cachedProgress = Cache::get("activity_export.progress_{$jobId}");
        $this->assertNotNull($cachedProgress);
        $this->assertEquals(100, $cachedProgress['progress']);
        $this->assertEquals('完成', $cachedProgress['status']);
        $this->assertTrue($cachedProgress['completed']);
        $this->assertEquals($result['download_url'], $cachedProgress['download_url']);
    }

    /**
     * 測試匯出失敗標記
     */
    public function test_mark_export_failed(): void
    {
        $jobId = 'test-job-id';
        $error = '匯出過程中發生錯誤';

        $this->exportService->markExportFailed($jobId, $error);

        // 驗證失敗狀態已快取
        $cachedProgress = Cache::get("activity_export.progress_{$jobId}");
        $this->assertNotNull($cachedProgress);
        $this->assertEquals(0, $cachedProgress['progress']);
        $this->assertEquals('失敗', $cachedProgress['status']);
        $this->assertEquals($error, $cachedProgress['error']);
    }

    /**
     * 測試使用者匯出歷史記錄
     */
    public function test_user_export_history(): void
    {
        $userId = $this->testUser->id;
        
        // 模擬匯出歷史
        $historyData = [
            [
                'filename' => 'export1.csv',
                'format' => 'csv',
                'record_count' => 100,
                'completed_at' => now()->subHours(2),
            ],
            [
                'filename' => 'export2.json',
                'format' => 'json',
                'record_count' => 200,
                'completed_at' => now()->subHour(),
            ],
        ];

        // 設定快取
        Cache::put("activity_export.history_{$userId}", $historyData, now()->addDays(30));

        $history = $this->exportService->getUserExportHistory($userId, 5);

        $this->assertCount(2, $history);
        $this->assertEquals('export2.json', $history[0]['filename']); // 應該按時間排序
        $this->assertEquals('export1.csv', $history[1]['filename']);
    }

    /**
     * 測試清除使用者匯出歷史
     */
    public function test_clear_user_export_history(): void
    {
        $userId = $this->testUser->id;
        
        // 設定一些歷史資料
        Cache::put("activity_export.history_{$userId}", ['test' => 'data'], now()->addDays(30));
        
        // 驗證資料存在
        $this->assertNotNull(Cache::get("activity_export.history_{$userId}"));
        
        // 清除歷史
        $this->exportService->clearUserExportHistory($userId);
        
        // 驗證資料已清除
        $this->assertNull(Cache::get("activity_export.history_{$userId}"));
    }

    /**
     * 測試取消匯出工作
     */
    public function test_cancel_export(): void
    {
        $jobId = 'test-job-id';
        
        // 設定一些測試資料
        Cache::put("activity_export.job_{$jobId}", ['test' => 'config'], now()->addHours(24));
        Cache::put("activity_export.progress_{$jobId}", ['test' => 'progress'], now()->addHours(24));
        
        // 取消匯出
        $result = $this->exportService->cancelExport($jobId);
        
        $this->assertTrue($result);
        
        // 驗證快取已清除
        $this->assertNull(Cache::get("activity_export.job_{$jobId}"));
        $this->assertNull(Cache::get("activity_export.progress_{$jobId}"));
    }

    /**
     * 測試篩選條件應用
     */
    public function test_export_with_filters(): void
    {
        // 建立不同類型的活動記錄
        Activity::factory()->create([
            'user_id' => $this->testUser->id,
            'type' => 'login',
            'result' => 'success',
        ]);
        
        Activity::factory()->create([
            'user_id' => $this->testUser->id,
            'type' => 'logout',
            'result' => 'success',
        ]);
        
        Activity::factory()->create([
            'user_id' => $this->testUser->id,
            'type' => 'login',
            'result' => 'failed',
        ]);

        // 測試類型篩選
        $config = [
            'format' => 'json',
            'filters' => [
                'type' => 'login',
            ],
            'options' => [
                'include_user_details' => false,
                'include_properties' => false,
                'include_related_data' => false,
                'batch_size' => 1000,
            ],
            'user_id' => $this->testUser->id,
        ];

        $result = $this->exportService->exportDirect($config);
        
        // 應該只有 2 筆 login 記錄
        $this->assertEquals(2, $result['record_count']);
        
        // 驗證內容
        $jsonContent = Storage::get($result['file_path']);
        $data = json_decode($jsonContent, true);
        
        foreach ($data['activities'] as $activity) {
            $this->assertEquals('login', $activity['type']);
        }
    }

    /**
     * 測試不支援的匯出格式
     */
    public function test_unsupported_export_format(): void
    {
        $config = [
            'format' => 'unsupported',
            'filters' => [],
            'options' => [
                'include_user_details' => false,
                'include_properties' => false,
                'include_related_data' => false,
                'batch_size' => 1000,
            ],
            'user_id' => $this->testUser->id,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('不支援的匯出格式: unsupported');

        $this->exportService->exportDirect($config);
    }
}