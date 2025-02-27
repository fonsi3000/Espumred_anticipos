<?php

namespace App\Providers;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        FilamentShield::configurePermissionIdentifierUsing(function ($resource) {
            // Usa la clase completa del resource como base para el identificador
            return Str::of($resource)
                ->afterLast('\\')  // Toma solo el nombre de la clase
                ->kebab()          // Convierte a kebab-case
                ->toString();      // Convierte a string
        });
    }
}
