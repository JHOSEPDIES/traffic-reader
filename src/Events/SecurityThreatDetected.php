<?php

namespace Pepeiborra\TrafficReader\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SecurityThreatDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        /** Payload completo de la visita (mismo formato que el log) */
        public readonly array $visit,
        /** Solo las amenazas críticas detectadas */
        public readonly array $threats,
    ) {}
}
