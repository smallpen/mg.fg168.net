<?php

if (!function_exists('trans_fallback')) {
    /**
     * 使用回退機制翻譯文字
     * 
     * @param string $key 翻譯鍵
     * @param array $replace 替換參數
     * @param string|null $locale 指定語言
     * @return string 翻譯結果
     */
    function trans_fallback(string $key, array $replace = [], ?string $locale = null): string
    {
        return app(\App\Services\LanguageFallbackHandler::class)->translate($key, $replace, $locale);
    }
}

if (!function_exists('has_trans_fallback')) {
    /**
     * 檢查翻譯是否存在（支援回退機制）
     * 
     * @param string $key 翻譯鍵
     * @param string|null $locale 指定語言
     * @return bool 是否存在
     */
    function has_trans_fallback(string $key, ?string $locale = null): bool
    {
        return app(\App\Services\LanguageFallbackHandler::class)->hasTranslation($key, $locale);
    }
}

if (!function_exists('trans_status')) {
    /**
     * 取得翻譯在各語言中的狀態
     * 
     * @param string $key 翻譯鍵
     * @return array 各語言的存在狀態
     */
    function trans_status(string $key): array
    {
        return app(\App\Services\LanguageFallbackHandler::class)->getTranslationStatus($key);
    }
}

if (!function_exists('__f')) {
    /**
     * 使用回退機制翻譯文字的簡短別名
     * 
     * @param string $key 翻譯鍵
     * @param array $replace 替換參數
     * @param string|null $locale 指定語言
     * @return string 翻譯結果
     */
    function __f(string $key, array $replace = [], ?string $locale = null): string
    {
        return trans_fallback($key, $replace, $locale);
    }
}