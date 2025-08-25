<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * 語言回退處理器 Facade
 * 
 * @method static string translate(string $key, array $replace = [], ?string $locale = null)
 * @method static bool hasTranslation(string $key, ?string $locale = null)
 * @method static array getTranslationStatus(string $key)
 * @method static void setFallbackChain(array $chain)
 * @method static array getFallbackChain()
 * @method static void setDefaultLocale(string $locale)
 * @method static string getDefaultLocale()
 * @method static void setLogging(bool $enable)
 * @method static void clearCache(?string $locale = null, ?string $file = null)
 * @method static array getFallbackStatistics()
 * 
 * @see \App\Services\LanguageFallbackHandler
 */
class LanguageFallback extends Facade
{
    /**
     * 取得 facade 對應的服務容器綁定名稱
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'language.fallback';
    }
}