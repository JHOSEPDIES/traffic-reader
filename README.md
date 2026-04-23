# traffic-reader

Lightweight SIEM (Security Information and Event Management) middleware for Laravel 10/11/12+. Tracks visits, detects threats in real time, logs them to disk, and sends optional email/Slack alerts — all in one Composer package.

## Features

- 🔍 **Visit tracking** — IP, device, OS, browser, referrer, URL, status code
- 🛡️ **Threat detection** — RCE, SQLi, XSS, path traversal, scanner UA, brute force, rate abuse
- 📊 **Livewire dashboard** — statistics, hourly charts, threat log (requires Livewire 3)
- 📧 **Alert notifications** — email + optional Slack for critical threats
- ⚙️ **Fully configurable** — thresholds, storage, excluded paths, layout

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ^8.1 |
| Laravel | 10 / 11 / 12 |
| Livewire *(optional)* | ^3.0 |
| laravel/slack-notification-channel *(optional)* | ^3.0 |

---

## Installation

```bash
composer require pepeiborra/traffic-reader
```

The package auto-discovers the service provider via `extra.laravel` in `composer.json`.

### Publish assets

```bash
# Configuración
php artisan vendor:publish --tag=traffic-reader-config

# Vistas (para personalizar)
php artisan vendor:publish --tag=traffic-reader-views

# Traducciones (opcional)
php artisan vendor:publish --tag=traffic-reader-lang
```

---

## Quick start

### 1. Agregar el middleware

**Laravel 10** — en `app/Http/Kernel.php`:

```php
use Pepeiborra\TrafficReader\Middleware\TrackVisitMiddleware;

protected $middlewareGroups = [
    'web' => [
        // ... otros middleware
        TrackVisitMiddleware::class,
    ],
];
```

**Laravel 11/12** — en `bootstrap/app.php`:

```php
use Pepeiborra\TrafficReader\Middleware\TrackVisitMiddleware;

->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('web', TrackVisitMiddleware::class);
    // Agregar a otros grupos según necesites
})
```

### 2. Variables de entorno

```dotenv
# Emails separados por coma para alertas críticas
TRAFFIC_READER_ALERT_EMAILS=admin@example.com,seguridad@example.com

# Webhook de Slack (opcional)
TRAFFIC_READER_SLACK_WEBHOOK=https://hooks.slack.com/services/XXX/YYY/ZZZ

# Umbrales (opcional, estos son los defaults)
TRAFFIC_READER_RATE_THRESHOLD=60
TRAFFIC_READER_404_THRESHOLD=20
TRAFFIC_READER_PROBE_THRESHOLD=3
TRAFFIC_READER_BF_THRESHOLD=10
```

### 3. Dashboard Livewire

Registra la ruta manualmente (recomendado, con tu propia autorización):

```php
use Pepeiborra\TrafficReader\Livewire\AuditDashboard;

Route::get('/admin/visitas', AuditDashboard::class)
    ->name('audit.visits')
    ->middleware(['auth', 'can:developer']);
```

O deja que el paquete registre la ruta automáticamente:

```dotenv
TRAFFIC_READER_ROUTES_ENABLED=true
TRAFFIC_READER_ROUTES_PREFIX=traffic-reader   # URL: /traffic-reader/visitas
```

Y configura los middlewares en `config/traffic-reader.php`:

```php
'routes' => [
    'enabled'    => true,
    'prefix'     => 'traffic-reader',
    'middleware' => ['web', 'auth', 'can:developer'],
],
```

---

## Configuración completa

```php
// config/traffic-reader.php (tras publicar)

return [
    'alert_emails' => env('TRAFFIC_READER_ALERT_EMAILS', ''),
    'slack_webhook' => env('TRAFFIC_READER_SLACK_WEBHOOK', ''),

    'storage' => [
        'disk'   => 'local',          // cualquier disk de filesystems.php
        'folder' => 'visits',         // dentro del disk
    ],

    'log_retention_days' => 30,

    'thresholds' => [
        'rate_per_minute'      => 60,
        'not_found_per_hour'   => 20,
        'probe_per_hour'       => 3,
        'brute_force_per_hour' => 10,
    ],

    // Rutas a ignorar (prefijos sin slash inicial)
    'exclude_paths' => [
        'api/health',
        'admin/ping',
    ],

    'routes' => [
        'enabled'    => false,
        'prefix'     => 'traffic-reader',
        'middleware' => ['web', 'auth'],
    ],

    'dashboard' => [
        'title'  => 'Dashboard de Visitas',
        'layout' => 'components.layouts.app',   // tu layout Blade
    ],
];
```

---

## Personalizar vistas

Tras publicar las vistas con `--tag=traffic-reader-views`, edita:

```
resources/views/vendor/traffic-reader/livewire/audit-dashboard.blade.php
```

El componente detecta automáticamente si existe la vista publicada y la usa en lugar de la del paquete.

---

## Usar el lector de logs directamente

```php
use Pepeiborra\TrafficReader\Services\VisitsLogReader;

$reader = app(VisitsLogReader::class);

$dates   = $reader->availableDates();
$records = $reader->records('2025-04-23');
$stats   = $reader->stats($records);
$threats = $reader->threats('2025-04-23');
```

---

## Estructura del paquete

```
traffic-reader/
├── config/
│   └── traffic-reader.php
├── resources/
│   └── views/livewire/
│       └── audit-dashboard.blade.php
├── routes/
│   └── web.php
├── src/
│   ├── TrafficReaderServiceProvider.php
│   ├── Events/
│   │   └── SecurityThreatDetected.php
│   ├── Listeners/
│   │   └── SecurityThreatListener.php
│   ├── Livewire/
│   │   └── AuditDashboard.php
│   ├── Middleware/
│   │   └── TrackVisitMiddleware.php
│   ├── Notifications/
│   │   └── SecurityAlertNotification.php
│   └── Services/
│       └── VisitsLogReader.php
└── composer.json
```

---

## Tipos de amenaza detectados

| Tipo | Descripción |
|---|---|
| `RCE_ATTEMPT` | Patrones de Remote Code Execution en la URL |
| `SQLI_ATTEMPT` | Patrones de SQL Injection |
| `XSS_ATTEMPT` | Patrones de Cross-Site Scripting |
| `PATH_TRAVERSAL` | Traversal de directorios (`../`, `%2e%2e`) |
| `SCANNER_UA` | User-Agent de herramienta de escaneo (sqlmap, nikto…) |
| `UNUSUAL_METHOD` | Métodos HTTP inusuales (TRACE, CONNECT…) |
| `SUSPICIOUS_UA` | User-Agent vacío o muy corto |
| `HIGH_RATE` | Más de N requests/minuto desde la misma IP |
| `ROUTE_SCAN` | Más de N 404s/hora desde la misma IP |
| `SENSITIVE_PROBE` | Acceso repetido a rutas sensibles (.env, wp-admin…) |
| `BRUTE_FORCE` | Más de N 401/403 por hora desde la misma IP |

---

## License

MIT
