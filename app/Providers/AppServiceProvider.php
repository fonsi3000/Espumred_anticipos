<?php

namespace App\Providers;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        // Monitoreo de consultas SQL en modo debug
        if (config('app.debug')) {
            DB::listen(function ($query) {
                if ($query->time > 100) { // Solo log consultas que tomen más de 100ms
                    Log::info('Consulta SQL lenta: ' . $query->sql, [
                        'bindings' => $query->bindings,
                        'time' => $query->time . 'ms'
                    ]);
                }
            });
        }

        // Monitoreo de Livewire si está instalado
        if (class_exists('\Livewire\Livewire')) {
            \Livewire\Livewire::listen('component.hydrate', function ($component) {
                Log::debug('Inicio de hidratación del componente: ' . get_class($component), [
                    'tiempo_inicio' => microtime(true),
                    'memoria_inicio' => memory_get_usage() / 1024 / 1024 . ' MB'
                ]);
            });

            \Livewire\Livewire::listen('component.dehydrate', function ($component) {
                Log::debug('Fin de deshidratación del componente: ' . get_class($component), [
                    'tiempo_fin' => microtime(true),
                    'memoria_fin' => memory_get_usage() / 1024 / 1024 . ' MB'
                ]);
            });
        }
    }
}
