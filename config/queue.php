<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 預設佇列連接名稱
    |--------------------------------------------------------------------------
    |
    | Laravel 的佇列 API 支援各種後端服務，透過統一的 API 提供便利的存取。
    | 這裡您可以定義預設的佇列連接，該連接應該用於所有佇列工作。
    |
    */

    'default' => env('QUEUE_CONNECTION', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | 佇列連接
    |--------------------------------------------------------------------------
    |
    | 這裡您可以配置應用程式的佇列連接設定，並定義每個可用連接的設定。
    | 請務必配置每個連接的預設佇列。
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | 批次處理
    |--------------------------------------------------------------------------
    |
    | 以下選項配置批次處理的資料庫和表格，用於儲存批次處理的中繼資料。
    | 這些選項可以更新以符合應用程式使用的任何資料庫連接和表格名稱。
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | 失敗的佇列工作
    |--------------------------------------------------------------------------
    |
    | 這些選項配置失敗佇列工作記錄的行為，讓您控制失敗工作的記錄方式
    | 和位置。Laravel 預設會將這些記錄儲存在資料庫中。
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

];