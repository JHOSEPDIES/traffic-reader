<?php

use Illuminate\Support\Facades\Route;
use Pepeiborra\TrafficReader\Livewire\AuditDashboard;

$prefix     = config('traffic-reader.routes.prefix', 'traffic-reader');
$middleware = config('traffic-reader.routes.middleware', ['web', 'auth']);

Route::middleware($middleware)
    ->prefix($prefix)
    ->group(function () {
        Route::get('/visitas', AuditDashboard::class)
            ->name('traffic-reader.audit.visits');
    });
