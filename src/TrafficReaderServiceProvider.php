<?php

namespace Pepeiborra\TrafficReader;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Pepeiborra\TrafficReader\Events\SecurityThreatDetected;
use Pepeiborra\TrafficReader\Listeners\SecurityThreatListener;
use Pepeiborra\TrafficReader\Services\VisitsLogReader;

class TrafficReaderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/traffic-reader.php', 'traffic-reader');

        $this->app->singleton(VisitsLogReader::class);
    }

    public function boot(): void
    {
        // ── Publicar config ──────────────────────────────────────────────────
        $this->publishes([
            __DIR__ . '/../config/traffic-reader.php' => config_path('traffic-reader.php'),
        ], 'traffic-reader-config');

        // ── Publicar vistas ──────────────────────────────────────────────────
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/traffic-reader'),
        ], 'traffic-reader-views');

        // ── Publicar traducciones ────────────────────────────────────────────
        $this->publishes([
            __DIR__ . '/../resources/lang' => lang_path('vendor/traffic-reader'),
        ], 'traffic-reader-lang');

        // ── Cargar vistas del paquete ────────────────────────────────────────
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'traffic-reader');

        // ── Cargar traducciones ──────────────────────────────────────────────
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'traffic-reader');

        // ── Cargar rutas opcionales ──────────────────────────────────────────
        if (config('traffic-reader.routes.enabled', false)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        // ── Registrar canal de log de seguridad ─────────────────────────────
        $this->configureSecurityLogChannel();

        // ── Registrar listener del evento ────────────────────────────────────
        Event::listen(
            SecurityThreatDetected::class,
            SecurityThreatListener::class,
        );

        // ── Registrar componente Livewire (si está instalado) ────────────────
        $this->bootLivewireComponents();
    }

    private function configureSecurityLogChannel(): void
    {
        // Solo agrega el canal si no existe ya en la config de logging
        $channels = config('logging.channels', []);

        if (!isset($channels['security'])) {
            config([
                'logging.channels.security' => [
                    'driver' => 'daily',
                    'path'   => storage_path('logs/security.log'),
                    'level'  => 'warning',
                    'days'   => config('traffic-reader.log_retention_days', 30),
                ],
            ]);
        }
    }

    private function bootLivewireComponents(): void
    {
        if (!class_exists(\Livewire\Livewire::class)) {
            return;
        }

        \Livewire\Livewire::component(
            'traffic-reader-audit-dashboard',
            \Pepeiborra\TrafficReader\Livewire\AuditDashboard::class
        );
    }
}
