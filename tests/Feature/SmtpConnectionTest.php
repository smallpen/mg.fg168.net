<?php

namespace Tests\Feature;

use App\Services\ConfigurationService;
use Tests\TestCase;

/**
 * SMTP 連線測試
 */
class SmtpConnectionTest extends TestCase
{
    /** @test */
    public function it_can_test_smtp_connection_with_invalid_config()
    {
        $configService = app(ConfigurationService::class);
        
        // 測試無效的 SMTP 配置
        $result = $configService->testConnection('smtp', [
            'host' => 'invalid.smtp.server',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'test@example.com',
            'password' => 'invalid_password',
            'test_email' => 'test@example.com',
        ]);
        
        // 無效配置應該返回 false
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_handle_missing_smtp_config()
    {
        $configService = app(ConfigurationService::class);
        
        // 測試缺少必要配置的情況
        $result = $configService->testConnection('smtp', []);
        
        // 缺少配置應該返回 false
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_test_google_oauth_connection()
    {
        $configService = app(ConfigurationService::class);
        
        // 測試無效的 Google OAuth 配置
        $result = $configService->testConnection('google_oauth', [
            'client_id' => 'invalid_client_id',
            'client_secret' => 'invalid_client_secret',
        ]);
        
        // 無效配置應該返回 false
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_test_aws_s3_connection_without_sdk()
    {
        $configService = app(ConfigurationService::class);
        
        // 測試 AWS S3 連線（沒有 SDK 的情況）
        $result = $configService->testConnection('aws_s3', [
            'access_key' => 'invalid_access_key',
            'secret_key' => 'invalid_secret_key',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
        ]);
        
        // 沒有 SDK 或無效配置應該返回 false
        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_false_for_unknown_connection_type()
    {
        $configService = app(ConfigurationService::class);
        
        // 測試未知的連線類型
        $result = $configService->testConnection('unknown_type', []);
        
        $this->assertFalse($result);
    }
}