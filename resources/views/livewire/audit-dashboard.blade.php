@php use Pepeiborra\TrafficReader\Services\VisitsLogReader; @endphp

<div>

    {{-- ── Estilos ─────────────────────────────────────────────────────────── --}}
    @once
        <style>
            /* ── Paleta SGPOA ── */
            .ad {
                --guinda: #9b2247;
                --guinda-dk: #7a1a38;
                --verde: #1e5b4f;
                --verde-dk: #164438;
                --sky: #0a5782;
                --lime: #4C8C2B;
                --amber: #cec6a4;
                --orange: #b5430e;
                --surface: #ffffff;
                --bg: #f5f4f0;
                --border: #e0deda;
                --text: #1a1a18;
                --muted: #6b6a65;
                --r: 8px;
            }

            .ad { padding: 1.5rem; background: var(--bg); min-height: 80vh; }

            /* ── Toolbar ── */
            .ad-toolbar { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.75rem; background:var(--verde); border-radius:var(--r); padding:1rem 1.25rem; margin-bottom:1.25rem; }
            .ad-toolbar h1 { font-size:16px; font-weight:700; color:#fff; margin:0; }
            .ad-toolbar p  { font-size:11px; color:rgba(255,255,255,.65); margin:2px 0 0; font-family:monospace; }
            .ad-controls   { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
            .ad-btn        { height:32px; padding:0 14px; border-radius:5px; font-size:12px; font-weight:600; cursor:pointer; border:none; transition:opacity .15s; }
            .ad-btn:hover  { opacity:.85; }
            .ad-btn-all    { background:var(--guinda); color:#fff; }
            .ad-btn-all.active { background:#fff; color:var(--guinda); }
            .ad-select     { height:32px; border-radius:5px; font-size:12px; padding:0 10px; border:1px solid rgba(255,255,255,.35); background:rgba(255,255,255,.15); color:#fff; cursor:pointer; }
            .ad-select option { color:var(--text); background:#fff; }
            .ad-badge      { display:inline-block; background:var(--guinda); color:#fff; font-size:10px; font-weight:700; padding:2px 8px; border-radius:3px; margin-left:8px; vertical-align:middle; text-transform:uppercase; }

            /* ── Tabs ── */
            .ad-tabs  { display:flex; gap:2px; border-bottom:2px solid var(--border); margin-bottom:1.25rem; }
            .ad-tab   { padding:7px 18px; font-size:13px; font-weight:600; color:var(--muted); border:none; background:none; cursor:pointer; border-radius:6px 6px 0 0; border-bottom:2px solid transparent; margin-bottom:-2px; transition:color .15s; }
            .ad-tab:hover { color:var(--verde); }
            .ad-tab.active { color:var(--verde); border-bottom-color:var(--verde); }
            .ad-tab .ad-tab-badge { background:var(--orange); color:#fff; font-size:10px; padding:1px 5px; border-radius:3px; margin-left:5px; }

            /* ── Métricas ── */
            .ad-metrics { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:10px; margin-bottom:1.25rem; }
            .ad-metric  { background:var(--surface); border:1px solid var(--border); border-top:3px solid var(--verde); border-radius:var(--r); padding:.9rem 1rem; text-align:center; }
            .ad-metric.c-guinda { border-top-color:var(--guinda); }
            .ad-metric.c-sky    { border-top-color:var(--sky); }
            .ad-metric.c-orange { border-top-color:var(--orange); }
            .ad-metric.c-lime   { border-top-color:var(--lime); }
            .ad-metric-label    { font-size:10px; color:var(--muted); text-transform:uppercase; letter-spacing:.07em; margin-bottom:5px; }
            .ad-metric-value    { font-size:28px; font-weight:700; color:var(--text); line-height:1; }
            .ad-metric.c-guinda .ad-metric-value { color:var(--guinda); }
            .ad-metric.c-sky    .ad-metric-value { color:var(--sky); }
            .ad-metric.c-orange .ad-metric-value { color:var(--orange); }
            .ad-metric.c-lime   .ad-metric-value { color:var(--lime); }
            .ad-metric-sub { font-size:11px; color:var(--muted); margin-top:3px; }

            /* ── Panels ── */
            .ad-panel       { background:var(--surface); border:1px solid var(--border); border-radius:var(--r); padding:1.1rem 1.25rem; margin-bottom:1.1rem; }
            .ad-panel-title { font-size:11px; font-weight:700; color:var(--verde); text-transform:uppercase; letter-spacing:.08em; border-bottom:1px solid var(--border); padding-bottom:8px; margin-bottom:12px; }
            .ad-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:1.1rem; margin-bottom:1.1rem; }
            @media (max-width:768px) { .ad-grid-2 { grid-template-columns:1fr; } }

            /* ── Barras ── */
            .ad-bars { list-style:none; padding:0; margin:0; }
            .ad-bar  { margin-bottom:9px; }
            .ad-bar-label { display:flex; justify-content:space-between; font-size:12px; margin-bottom:3px; }
            .ad-bar-label span:first-child { color:var(--text); }
            .ad-bar-label span:last-child  { color:var(--muted); font-weight:600; }
            .ad-bar-track { background:var(--bg); border-radius:3px; height:6px; overflow:hidden; }
            .ad-bar-fill  { height:100%; border-radius:3px; transition:width .4s ease; }

            /* ── Tablas ── */
            .ad-table    { width:100%; border-collapse:collapse; font-size:12px; }
            .ad-table th { text-align:left; font-size:10px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.07em; padding:0 8px 8px; border-bottom:2px solid var(--verde); }
            .ad-table td { padding:7px 8px; border-bottom:1px solid var(--border); vertical-align:top; word-break:break-all; }
            .ad-table tr:last-child td { border-bottom:none; }
            .ad-table tr:hover td { background:#fafaf8; }
            .ad-table .col-r   { text-align:right; color:var(--muted); white-space:nowrap; }
            .ad-table .col-idx { color:var(--muted); width:24px; }

            /* ── Tags ── */
            .ad-tag         { display:inline-block; font-size:10px; font-weight:700; padding:2px 6px; border-radius:3px; white-space:nowrap; }
            .ad-tag-attack  { background:#fde8e8; color:var(--orange); margin-left:5px; vertical-align:middle; }
            .ad-tag-rce, .ad-tag-sqli, .ad-tag-xss { background:#fde8e8; color:#c0392b; }
            .ad-tag-rate, .ad-tag-route, .ad-tag-brute { background:#fff0eb; color:var(--orange); }
            .ad-tag-scanner, .ad-tag-path { background:#fef9e7; color:#b7770d; }
            .ad-tag-default { background:#f0f0f0; color:var(--muted); }

            /* ── Alerta ── */
            .ad-alert { display:flex; align-items:flex-start; gap:10px; background:#fdf2f2; border:1px solid #e8a0a0; border-left:3px solid var(--orange); border-radius:var(--r); padding:.75rem 1rem; font-size:13px; margin-bottom:1.1rem; color:#5a1a1a; }

            /* ── Empty ── */
            .ad-empty { text-align:center; padding:3rem 1rem; color:var(--muted); }
            .ad-empty-icon { font-size:36px; color:#ccc; margin-bottom:10px; }
            .ad-empty h3 { font-size:15px; color:var(--text); margin-bottom:6px; }
        </style>
    @endonce

    <div class="ad">

        {{-- ── Toolbar ──────────────────────────────────────────────────────── --}}
        <div class="ad-toolbar">
            <div>
                <h1>
                    Dashboard de Visitas
                    @if($allMode)<span class="ad-badge">Todo el período</span>@endif
                </h1>
                <p>{{ $this->periodLabel }}</p>
            </div>
            <div class="ad-controls">
                <button class="ad-btn ad-btn-all {{ $allMode ? 'active' : '' }}"
                        wire:click="showAll">&#9776; Todo</button>
                <select class="ad-select" wire:change="selectDate($event.target.value)">
                    @foreach($this->availableDates as $date)
                        <option value="{{ $date }}" {{ !$allMode && $date === $selectedDate ? 'selected' : '' }}>
                            {{ $date }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ── Sin datos ─────────────────────────────────────────────────────── --}}
        @if(empty($this->records))
            <div class="ad-empty">
                <div class="ad-empty-icon">📂</div>
                <h3>Sin registros</h3>
                <p>No se encontraron visitas para <strong>{{ $this->periodLabel }}</strong>.</p>
            </div>

        @else

            {{-- ── Alerta SIEM ─────────────────────────────────────────────── --}}
            @if($this->stats['threats'] > 0)
                <div class="ad-alert">
                    <span style="font-size:18px;">⚠</span>
                    <div>
                        <strong>Actividad sospechosa detectada</strong>
                        &mdash; {{ $this->stats['threats'] }} peticiones con amenazas
                        @if(!empty($this->stats['threat_types']))
                            ({{ implode(', ', array_keys($this->stats['threat_types'])) }})
                        @endif
                    </div>
                </div>
            @endif

            {{-- ── Tabs ──────────────────────────────────────────────────────── --}}
            <div class="ad-tabs">
                <button class="ad-tab {{ $activeTab === 'overview' ? 'active' : '' }}"
                        wire:click="setTab('overview')">Resumen</button>
                <button class="ad-tab {{ $activeTab === 'hours' ? 'active' : '' }}"
                        wire:click="setTab('hours')">Por hora / día</button>
                <button class="ad-tab {{ $activeTab === 'threats' ? 'active' : '' }}"
                        wire:click="setTab('threats')">
                    Amenazas
                    @if($this->stats['threats'] > 0)
                        <span class="ad-tab-badge">{{ $this->stats['threats'] }}</span>
                    @endif
                </button>
            </div>

            {{-- ════════ TAB: RESUMEN ════════ --}}
            @if($activeTab === 'overview')

                <div class="ad-metrics">
                    <div class="ad-metric {{ $allMode ? 'c-guinda' : '' }}">
                        <div class="ad-metric-label">Total registros</div>
                        <div class="ad-metric-value">{{ number_format($this->stats['total']) }}</div>
                        <div class="ad-metric-sub">{{ $this->periodLabel }}</div>
                    </div>
                    <div class="ad-metric">
                        <div class="ad-metric-label">Humanos</div>
                        <div class="ad-metric-value">{{ number_format($this->stats['humans']) }}</div>
                        <div class="ad-metric-sub">{{ $this->stats['humans_pct'] }}% del total</div>
                    </div>
                    <div class="ad-metric c-sky">
                        <div class="ad-metric-label">Bots</div>
                        <div class="ad-metric-value">{{ number_format($this->stats['bots']) }}</div>
                        <div class="ad-metric-sub">{{ $this->stats['bots_pct'] }}% del total</div>
                    </div>
                    @if($allMode)
                        <div class="ad-metric c-guinda">
                            <div class="ad-metric-label">Promedio / día</div>
                            <div class="ad-metric-value">
                                {{ count($this->availableDates) > 0
                                    ? number_format($this->stats['humans'] / count($this->availableDates), 1)
                                    : 0 }}
                            </div>
                            <div class="ad-metric-sub">visitas humanas</div>
                        </div>
                    @endif
                    <div class="ad-metric">
                        <div class="ad-metric-label">Desktop</div>
                        <div class="ad-metric-value">{{ $this->stats['desktop'] }}</div>
                        <div class="ad-metric-sub">{{ $this->stats['desktop_pct'] }}%</div>
                    </div>
                    <div class="ad-metric">
                        <div class="ad-metric-label">Mobile</div>
                        <div class="ad-metric-value">{{ $this->stats['mobile'] }}</div>
                        <div class="ad-metric-sub">{{ $this->stats['mobile_pct'] }}%</div>
                    </div>
                    <div class="ad-metric">
                        <div class="ad-metric-label">Hora pico</div>
                        <div class="ad-metric-value">{{ $this->stats['peak_hour'] }}h</div>
                        <div class="ad-metric-sub">{{ $this->stats['peak_count'] }} visitas</div>
                    </div>
                    @if($this->stats['threats'] > 0)
                        <div class="ad-metric c-orange">
                            <div class="ad-metric-label">Amenazas</div>
                            <div class="ad-metric-value">{{ $this->stats['threats'] }}</div>
                            <div class="ad-metric-sub">peticiones</div>
                        </div>
                    @endif
                </div>

                <div class="ad-grid-2">
                    @php $refTotal = array_sum($this->stats['referer']); @endphp
                    <div class="ad-panel">
                        <div class="ad-panel-title">Fuente de tráfico</div>
                        <ul class="ad-bars">
                            @foreach($this->stats['referer'] as $label => $count)
                                @php $pct = $refTotal > 0 ? round($count / $refTotal * 100) : 0; @endphp
                                <li class="ad-bar">
                                    <div class="ad-bar-label"><span>{{ $label }}</span><span>{{ $count }} &nbsp;{{ $pct }}%</span></div>
                                    <div class="ad-bar-track"><div class="ad-bar-fill" style="width:{{ $pct }}%;background:var(--guinda);"></div></div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    @php $brTotal = array_sum($this->stats['browser']); @endphp
                    <div class="ad-panel">
                        <div class="ad-panel-title">Navegador</div>
                        <ul class="ad-bars">
                            @foreach($this->stats['browser'] as $label => $count)
                                @php $pct = $brTotal > 0 ? round($count / $brTotal * 100) : 0; @endphp
                                <li class="ad-bar">
                                    <div class="ad-bar-label"><span>{{ $label }}</span><span>{{ $count }} &nbsp;{{ $pct }}%</span></div>
                                    <div class="ad-bar-track"><div class="ad-bar-fill" style="width:{{ $pct }}%;background:var(--verde);"></div></div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="ad-grid-2">
                    @php $osTotal = array_sum($this->stats['os']); @endphp
                    <div class="ad-panel">
                        <div class="ad-panel-title">Sistema operativo</div>
                        <ul class="ad-bars">
                            @foreach($this->stats['os'] as $label => $count)
                                @php $pct = $osTotal > 0 ? round($count / $osTotal * 100) : 0; @endphp
                                <li class="ad-bar">
                                    <div class="ad-bar-label"><span>{{ $label }}</span><span>{{ $count }} &nbsp;{{ $pct }}%</span></div>
                                    <div class="ad-bar-track"><div class="ad-bar-fill" style="width:{{ $pct }}%;background:var(--sky);"></div></div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    @php $mTotal = array_sum($this->stats['method']); @endphp
                    <div class="ad-panel">
                        <div class="ad-panel-title">Método HTTP</div>
                        <ul class="ad-bars">
                            @foreach($this->stats['method'] as $label => $count)
                                @php $pct = $mTotal > 0 ? round($count / $mTotal * 100) : 0; @endphp
                                <li class="ad-bar">
                                    <div class="ad-bar-label"><span>{{ $label }}</span><span>{{ $count }} &nbsp;{{ $pct }}%</span></div>
                                    <div class="ad-bar-track"><div class="ad-bar-fill" style="width:{{ $pct }}%;background:var(--lime);"></div></div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="ad-panel">
                    <div class="ad-panel-title">Top 10 URLs</div>
                    <table class="ad-table">
                        <thead><tr><th class="col-idx">#</th><th>URL</th><th class="col-r">Visitas</th></tr></thead>
                        <tbody>
                        @foreach($this->stats['top_urls'] as $url => $count)
                            @php $isAttack = app(VisitsLogReader::class)->isAttackUrl($url); @endphp
                            <tr>
                                <td class="col-idx">{{ $loop->iteration }}</td>
                                <td>
                                    {{ Str::limit($url, 110) }}
                                    @if($isAttack)<span class="ad-tag ad-tag-attack">ATAQUE</span>@endif
                                </td>
                                <td class="col-r">{{ number_format($count) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            @endif {{-- /overview --}}


            {{-- ════════ TAB: POR HORA / DÍA ════════ --}}
            @if($activeTab === 'hours')

                <div class="ad-panel">
                    <div class="ad-panel-title">Visitas por hora {{ $allMode ? '(acumulado)' : '' }}</div>
                    <div style="position:relative;height:200px;"><canvas id="chartHour"></canvas></div>
                </div>

                @if($allMode && count($this->stats['by_day']) > 1)
                    <div class="ad-panel">
                        <div class="ad-panel-title">Visitas por día</div>
                        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
                            <span style="display:flex;align-items:center;gap:5px;font-size:11px;color:var(--muted);"><span style="width:9px;height:9px;border-radius:2px;background:var(--verde);display:inline-block;"></span>Humanos</span>
                            <span style="display:flex;align-items:center;gap:5px;font-size:11px;color:var(--muted);"><span style="width:9px;height:9px;border-radius:2px;background:#aaa;display:inline-block;"></span>Bots</span>
                            @if($this->stats['threats'] > 0)
                                <span style="display:flex;align-items:center;gap:5px;font-size:11px;color:var(--muted);"><span style="width:9px;height:9px;border-radius:2px;background:var(--orange);display:inline-block;"></span>Amenazas</span>
                            @endif
                        </div>
                        <div style="position:relative;height:220px;"><canvas id="chartDays"></canvas></div>
                    </div>
                @endif

                @php $maxH = max($this->stats['hours']) ?: 1; @endphp
                <div class="ad-panel">
                    <div class="ad-panel-title">Detalle por hora</div>
                    <table class="ad-table">
                        <thead><tr><th style="width:50px;">Hora</th><th>Distribución</th><th class="col-r">Visitas</th></tr></thead>
                        <tbody>
                        @foreach($this->stats['hours'] as $h => $count)
                            @if($count > 0)
                                <tr>
                                    <td><strong>{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}h</strong></td>
                                    <td>
                                        <div class="ad-bar-track">
                                            <div class="ad-bar-fill" style="width:{{ round($count / $maxH * 100) }}%;background:var(--verde);"></div>
                                        </div>
                                    </td>
                                    <td class="col-r">{{ $count }}</td>
                                </tr>
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                </div>

            @endif {{-- /hours --}}


            {{-- ════════ TAB: AMENAZAS ════════ --}}
            @if($activeTab === 'threats')

                @if($this->stats['threats'] === 0)
                    <div class="ad-empty">
                        <div class="ad-empty-icon">✅</div>
                        <h3>Sin amenazas detectadas</h3>
                        <p>No se registraron peticiones sospechosas en este período.</p>
                    </div>
                @else
                    @if(!empty($this->stats['threat_types']))
                        @php $ttTotal = array_sum($this->stats['threat_types']); @endphp
                        <div class="ad-panel">
                            <div class="ad-panel-title">Tipos de amenaza</div>
                            <ul class="ad-bars">
                                @foreach($this->stats['threat_types'] as $type => $count)
                                    @php $pct = $ttTotal > 0 ? round($count / $ttTotal * 100) : 0; @endphp
                                    @php $tagClass = 'ad-tag-' . strtolower(explode('_', $type)[0]); @endphp
                                    <li class="ad-bar">
                                        <div class="ad-bar-label">
                                            <span><span class="ad-tag {{ $tagClass }}">{{ $type }}</span></span>
                                            <span>{{ $count }}</span>
                                        </div>
                                        <div class="ad-bar-track"><div class="ad-bar-fill" style="width:{{ $pct }}%;background:var(--orange);"></div></div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="ad-panel">
                        <div class="ad-panel-title">
                            Log de amenazas
                            <span style="font-weight:400;color:var(--muted);font-size:11px;margin-left:6px;">(últimas {{ count($this->threats) }})</span>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="ad-table">
                                <thead><tr><th>Timestamp</th><th>IP</th><th>Tipo(s)</th><th>URL</th><th class="col-r">Status</th></tr></thead>
                                <tbody>
                                @forelse($this->threats as $threat)
                                    <tr>
                                        <td style="white-space:nowrap;color:var(--muted);font-size:11px;">{{ $threat['timestamp'] ?? '-' }}</td>
                                        <td style="white-space:nowrap;font-family:monospace;font-size:11px;">{{ $threat['ip'] ?? '-' }}</td>
                                        <td style="white-space:nowrap;">
                                            @foreach($threat['threats'] ?? [] as $t)
                                                @php $tc = 'ad-tag-' . strtolower(explode('_', $t['type'])[0]); @endphp
                                                <span class="ad-tag {{ $tc }}">{{ $t['type'] }}</span>
                                            @endforeach
                                        </td>
                                        <td>{{ Str::limit($threat['url'] ?? '-', 80) }}</td>
                                        <td class="col-r"><span style="font-family:monospace;">{{ $threat['status_code'] ?? '-' }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:1.5rem;">Sin registros en este período</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endif {{-- /threats --}}

        @endif {{-- /records --}}

    </div>{{-- /.ad --}}

</div>{{-- /livewire root --}}

{{-- ── Chart.js ──────────────────────────────────────────────────────────── --}}
@if($activeTab === 'hours')
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
        <script>
            (function () {
                var tc = '#6b6a65', gc = 'rgba(0,0,0,0.06)';
                var baseScales = {
                    xAxes: [{ticks:{fontColor:tc,fontSize:11,autoSkip:false,maxRotation:30},gridLines:{display:false}}],
                    yAxes: [{ticks:{fontColor:tc,fontSize:11},gridLines:{color:gc},beginAtZero:true}]
                };

                function init() {
                    var hCanvas = document.getElementById('chartHour');
                    if (hCanvas) {
                        if (hCanvas._chart) hCanvas._chart.destroy();
                        hCanvas._chart = new Chart(hCanvas, {
                            type: 'bar',
                            data: {
                                labels: ['00','01','02','03','04','05','06','07','08','09','10','11',
                                         '12','13','14','15','16','17','18','19','20','21','22','23'],
                                datasets: [{data:{!! $this->chartHours !!},backgroundColor:'#1e5b4f'}]
                            },
                            options: {responsive:true,maintainAspectRatio:false,legend:{display:false},scales:baseScales}
                        });
                    }

                    @if($allMode && count($this->stats['by_day']) > 1)
                    var dCanvas = document.getElementById('chartDays');
                    if (dCanvas) {
                        if (dCanvas._chart) dCanvas._chart.destroy();
                        var dayData = {!! $this->chartByDay !!};
                        var datasets = [
                            {label:'Humanos',data:dayData.humans,backgroundColor:'#1e5b4f'},
                            {label:'Bots',data:dayData.bots,backgroundColor:'#aaa'}
                        ];
                        @if($this->stats['threats'] > 0)
                        datasets.push({label:'Amenazas',data:dayData.threats,backgroundColor:'#b5430e'});
                        @endif
                        dCanvas._chart = new Chart(dCanvas, {
                            type: 'bar',
                            data: {labels:dayData.labels,datasets:datasets},
                            options: {responsive:true,maintainAspectRatio:false,legend:{display:false},scales:baseScales}
                        });
                    }
                    @endif
                }

                document.addEventListener('DOMContentLoaded', init);
                document.addEventListener('livewire:navigated', init);
                Livewire.hook('morph.updated', function () { setTimeout(init, 50); });
            }());
        </script>
    @endpush
@endif
