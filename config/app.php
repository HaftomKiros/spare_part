<?php

return [
    'name'     => env('APP_NAME', 'Abush Spare Part'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => (bool) env('APP_DEBUG', false),
    'url'      => env('APP_URL', 'http://localhost'),
    'timezone' => 'Africa/Addis_Ababa',
    'locale'   => 'en',
    'fallback_locale' => 'en',
    'faker_locale'    => 'en_US',
    'key'      => env('APP_KEY'),
    'cipher'   => 'AES-256-CBC',

    'providers' => \Illuminate\Support\ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
        App\Providers\ViewServiceProvider::class,
    ])->toArray(),

    'aliases' => \Illuminate\Support\Facades\Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
    ])->toArray(),
];
