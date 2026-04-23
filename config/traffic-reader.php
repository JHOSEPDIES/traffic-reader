<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Emails de alerta
    |--------------------------------------------------------------------------
    | Lista separada por comas de correos que recibirán alertas críticas.
    | Ej: TRAFFIC_READER_ALERT_EMAILS=admin@conanp.gob.mx,seguridad@conanp.gob.mx
    */
    'alert_emails' => env('TRAFFIC_READER_ALERT_EMAILS', ''),

    /*
    |--------------------------------------------------------------------------
    | Webhook de Slack (opcional)
    |--------------------------------------------------------------------------
    | URL del Incoming Webhook de Slack.
    | Requiere: laravel/slack-notification-channel
    */
    'slack_webhook' => env('TRAFFIC_READER_SLACK_WEBHOOK', ''),

    /*
    |--------------------------------------------------------------------------
    | Almacenamiento de logs
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'disk'   => env('TRAFFIC_READER_STORAGE_DISK',   'local'),
        'folder' => env('TRAFFIC_READER_STORAGE_FOLDER', 'visits'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retención de logs de seguridad
    |--------------------------------------------------------------------------
    */
    'log_retention_days' => env('TRAFFIC_READER_LOG_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Umbrales de detección
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        'rate_per_minute'      => env('TRAFFIC_READER_RATE_THRESHOLD', 60),
        'not_found_per_hour'   => env('TRAFFIC_READER_404_THRESHOLD', 20),
        'probe_per_hour'       => env('TRAFFIC_READER_PROBE_THRESHOLD', 3),
        'brute_force_per_hour' => env('TRAFFIC_READER_BF_THRESHOLD', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rutas a excluir del tracking (además de las internas del paquete)
    |--------------------------------------------------------------------------
    | Prefijos de ruta (sin slash inicial). Ej: ['admin/health', 'api/ping']
    */
    'exclude_paths' => [],

    /*
    |--------------------------------------------------------------------------
    | Rutas del dashboard (opcional)
    |--------------------------------------------------------------------------
    | Si habilitas las rutas del paquete, se registrará automáticamente:
    |   GET /traffic-reader/visitas  →  AuditDashboard
    |
    | Puedes protegerla con los middlewares que necesites.
    */
    'routes' => [
        'enabled'    => env('TRAFFIC_READER_ROUTES_ENABLED', false),
        'prefix'     => env('TRAFFIC_READER_ROUTES_PREFIX', 'traffic-reader'),
        'middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'title'  => 'Dashboard de Visitas',
        'layout' => 'components.layouts.app',
    ],

];
