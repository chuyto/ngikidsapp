<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forzar el uso de HTTPS si APP_URL está configurado con HTTPS
         $appUrl = Config::get('app.url');

         URL::forceRootUrl($appUrl);

         if (str_starts_with($appUrl, 'https://')) {
             URL::forceScheme('https');
         }
    }
}
