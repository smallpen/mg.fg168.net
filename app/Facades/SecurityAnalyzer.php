<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * 安全分析器 Facade
 * 
 * @method static array analyzeActivity(\App\Models\Activity $activity)
 * @method static int calculateRiskScore(\App\Models\Activity $activity)
 * @method static array detectAnomalies(\Illuminate\Database\Eloquent\Collection $activities)
 * @method static array identifyPatterns(string $userId, string $timeRange)
 * @method static array generateSecurityReport(string $timeRange)
 * @method static \Illuminate\Database\Eloquent\Collection checkSuspiciousIPs()
 * @method static array monitorFailedLogins()
 * @method static \App\Models\SecurityAlert|null generateSecurityAlert(\App\Models\Activity $activity, array $securityEvents)
 * 
 * @see \App\Services\SecurityAnalyzer
 */
class SecurityAnalyzer extends Facade
{
    /**
     * 獲取 facade 對應的服務名稱
     */
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\SecurityAnalyzer::class;
    }
}