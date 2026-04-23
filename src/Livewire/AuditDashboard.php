<?php

namespace Pepeiborra\TrafficReader\Livewire;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Pepeiborra\TrafficReader\Services\VisitsLogReader;

class AuditDashboard extends Component
{
    public string $selectedDate = '';
    public bool   $allMode      = false;
    public string $activeTab    = 'overview'; // overview | hours | threats

    // ─── Mount ───────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $dates = $this->reader()->availableDates();
        $this->selectedDate = $dates[0] ?? now()->format('Y-m-d');
    }

    // ─── Acciones ─────────────────────────────────────────────────────────────

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->allMode      = false;
    }

    public function showAll(): void
    {
        $this->allMode = true;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // ─── Propiedades computadas ───────────────────────────────────────────────

    #[Computed]
    public function availableDates(): array
    {
        return $this->reader()->availableDates();
    }

    #[Computed]
    public function records(): array
    {
        if ($this->allMode) {
            return $this->reader()->allRecords($this->availableDates);
        }

        return $this->reader()->records($this->selectedDate);
    }

    #[Computed]
    public function stats(): array
    {
        return $this->reader()->stats($this->records);
    }

    #[Computed]
    public function threats(): array
    {
        if ($this->allMode) {
            $all = [];
            foreach ($this->availableDates as $date) {
                foreach ($this->reader()->threats($date) as $t) {
                    $all[] = $t;
                }
            }
            usort($all, fn($a, $b) => strcmp(
                $b['timestamp'] ?? '',
                $a['timestamp'] ?? ''
            ));
            return array_slice($all, 0, 50);
        }

        return $this->reader()->threats($this->selectedDate);
    }

    #[Computed]
    public function periodLabel(): string
    {
        if ($this->allMode) {
            return 'Todo el período (' . count($this->availableDates) . ' días)';
        }

        try {
            return \Carbon\Carbon::parse($this->selectedDate)
                ->translatedFormat('d M Y');
        } catch (\Throwable) {
            return $this->selectedDate;
        }
    }

    // ─── Datos para Chart.js ──────────────────────────────────────────────────

    #[Computed]
    public function chartHours(): string
    {
        return json_encode(array_values($this->stats['hours']));
    }

    #[Computed]
    public function chartByDay(): string
    {
        $byDay = $this->stats['by_day'];

        return json_encode([
            'labels'  => array_keys($byDay),
            'humans'  => array_column(array_values($byDay), 'humans'),
            'bots'    => array_column(array_values($byDay), 'bots'),
            'threats' => array_column(array_values($byDay), 'threats'),
        ]);
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render(): Factory|View|\Illuminate\View\View
    {
        // Permite override: resources/views/vendor/traffic-reader/livewire/audit-dashboard.blade.php
        $view = view()->exists('vendor.traffic-reader.livewire.audit-dashboard')
            ? 'vendor.traffic-reader.livewire.audit-dashboard'
            : 'traffic-reader::livewire.audit-dashboard';

        return view($view)->layout(
            config('traffic-reader.dashboard.layout', 'components.layouts.app')
        )->title(
            config('traffic-reader.dashboard.title', 'Dashboard de Visitas')
        );
    }

    // ─── Privado ─────────────────────────────────────────────────────────────

    private function reader(): VisitsLogReader
    {
        return app(VisitsLogReader::class);
    }
}
