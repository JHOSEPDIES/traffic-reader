<?php

namespace Pepeiborra\TrafficReader\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Pepeiborra\TrafficReader\Events\SecurityThreatDetected;
use Pepeiborra\TrafficReader\Notifications\SecurityAlertNotification;

/**
 * ShouldQueue: se procesa en background para no agregar
 * latencia a la respuesta HTTP original.
 */
class SecurityThreatListener implements ShouldQueue
{
    public function handle(SecurityThreatDetected $event): void
    {
        $visit   = $event->visit;
        $threats = $event->threats;

        Log::channel('security')->warning('Security threat detected', [
            'ip'        => $visit['ip'],
            'url'       => $visit['url'],
            'method'    => $visit['method'],
            'threats'   => $threats,
            'ua'        => $visit['user_agent'],
            'timestamp' => $visit['timestamp'],
        ]);

        $recipients = array_filter(
            explode(',', config('traffic-reader.alert_emails', ''))
        );

        if (!empty($recipients)) {
            Notification::route('mail', $recipients)
                ->notify(new SecurityAlertNotification($visit, $threats));
        }
    }

    /**
     * Si el job falla (mail caído, etc.) no reintentar indefinidamente.
     */
    public function failed(SecurityThreatDetected $event, \Throwable $exception): void
    {
        Log::channel('security')->error(
            'SecurityThreatListener failed: ' . $exception->getMessage()
        );
    }
}
