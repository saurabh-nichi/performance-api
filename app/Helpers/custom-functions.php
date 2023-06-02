<?php

namespace App\Helpers;

if (!function_exists('translate')) {
    /**
     * Translate function to compensate for octane config caching issue
     * @param string $key
     * @param array $replace
     * @return \Illuminate\Contracts\Translation\Translator|string|array|null
     */
    function translate(string $key, array $replace = [])
    {
        $locale = request()->header('Accept-Language');
        if (!$locale) {
            $locale = app()->getLocale();
        }
        return trans($key, $replace, $locale);
    }
}
