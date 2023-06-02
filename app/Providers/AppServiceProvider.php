<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Schema::defaultStringLength(255);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (request()->hasHeader('Accept-Language')) {
            $locale = request()->header('Accept-Language');
            if (app()->getLocale() != $locale) {
                $availableLocales = array_filter(scandir(base_path() . '/lang'), function ($item) {
                    return !in_array($item, ['.', '..']);
                });
                if (in_array($locale, $availableLocales)) {
                    app()->setLocale($locale);
                }
            }
        }
        Validator::extend('validate_log_column', function ($key, $value) {
            return in_array($value, Schema::getColumnListing('request_logs'));
        }, __('validation.enum'));
    }
}
