<?php

namespace Pepeiborra\TrafficReader\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly array $visit,
        private readonly array $threats,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if (config('traffic-reader.slack_webhook')) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $types = implode(', ', array_column($this->threats, 'type'));
        $ip    = $this->visit['ip'];
        $url   = $this->visit['url'];
        $time  = $this->visit['timestamp'];
        $app   = config('app.name');

        return (new MailMessage)
            ->subject("[SIEM] Amenaza detectada: {$types}")
            ->error()
            ->greeting("⚠ Alerta de seguridad — {$app}")
            ->line("**IP:** {$ip}")
            ->line("**URL:** {$url}")
            ->line("**Hora:** {$time}")
            ->line("**Amenazas:** {$types}")
            ->line('---')
            ->line('Detalle completo:')
            ->line('```' . json_encode($this->threats, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '```')
            ->line('Revisa el log en `storage/logs/security.log` y en '
                . '`storage/app/private/visits/threats_' . now()->format('Y-m-d') . '.txt`');
    }
}
