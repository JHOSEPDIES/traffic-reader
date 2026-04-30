<?php

namespace Pepeiborra\TrafficReader\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Pepeiborra\TrafficReader\Events\SecurityThreatDetected;

class TrackVisitMiddleware
{
    private int $rateThreshold;
    private int $notFoundThreshold;
    private int $probeThreshold;
    private int $bruteForceThreshold;

    public function __construct()
    {
        $t = config('traffic-reader.thresholds', []);

        $this->rateThreshold = $t['rate_per_minute'] ?? 60;
        $this->notFoundThreshold = $t['not_found_per_hour'] ?? 20;
        $this->probeThreshold = $t['probe_per_hour'] ?? 3;
        $this->bruteForceThreshold = $t['brute_force_per_hour'] ?? 10;
    }

    private array $excludePaths = [
        '_debugbar',
        '_ignition',
        'livewire/update',
        'livewire/upload-file',
        'livewire/preview-file',
        'sanctum/csrf-cookie',
        'telescope',
        'horizon',
    ];

    private array $excludeExtensions = [
        'css', 'js', 'map',
        'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'bmp',
        'woff', 'woff2', 'ttf', 'eot', 'otf',
        'pdf', 'zip', 'gz',
    ];

    private array $rcePatterns = [
        'allow_url_include', 'auto_prepend_file', 'php://input',
        'php://filter', 'data://text', 'expect://',
        'invokefunction', 'call_user_func', 'call_user_func_array',
        '/etc/passwd', '/etc/shadow', '/proc/self',
        'eval(', 'base64_decode(', 'system(',
        'exec(', 'passthru(', 'shell_exec(',
        'popen(', 'proc_open(',
    ];

    private array $sqliPatterns = [
        "' or '1'='1", "' or 1=1", "'; drop table",
        "union select", "union all select", "information_schema",
        "load_file(", "into outfile", "xp_cmdshell",
        "waitfor delay", "sleep(", "benchmark(",
        "extractvalue(", "updatexml(", "0x",
    ];

    private array $xssPatterns = [
        '<script', 'javascript:', 'onerror=',
        'onload=', 'onclick=', 'onmouseover=',
        'alert(', 'confirm(', 'prompt(',
        'document.cookie', 'window.location', '<iframe',
        'src=data:', 'vbscript:', 'expression(',
    ];

    private array $sensitiveRoutes = [
        'admin', 'wp-admin', 'wp-login',
        'phpmyadmin', '.env', 'config',
        'backup', 'dump', '.git',
        'composer', 'artisan', 'storage',
        'xmlrpc', 'cpanel', 'webmail',
    ];

    private array $scannerUserAgents = [
        'sqlmap', 'nikto', 'nessus',
        'burpsuite', 'masscan', 'zgrab',
        'nuclei', 'hydra', 'acunetix',
        'dirbuster', 'gobuster', 'ffuf',
        'wfuzz', 'nmap', 'metasploit',
        'openvas', 'whatweb', 'skipfish',
        'w3af', 'zap',
    ];

    private array $criticalThreatTypes = [
        'RCE_ATTEMPT',
        'SQLI_ATTEMPT',
        'SCANNER_UA',
        'HIGH_RATE',
        'SENSITIVE_PROBE',
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        $this->record($request, $response->getStatusCode());

        return $response;
    }

    private function resolveClientIp(Request $request): string
    {
        $candidates = [
            $request->header('CF-Connecting-IP'),           // Cloudflare
            $request->header('X-Real-IP'),                  // Nginx directo
            $this->firstForwardedIp($request),              // X-Forwarded-For
            $request->server('REMOTE_ADDR'),                // fallback
        ];

        foreach ($candidates as $candidate) {
            if (empty($candidate)) {
                continue;
            }

            $ip = trim($candidate);

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return $request->server('REMOTE_ADDR') ?? 'UNKNOWN';
    }

    private function firstForwardedIp(Request $request): ?string
    {
        $header = $request->header('X-Forwarded-For');

        if (empty($header)) {
            return null;
        }

        $parts = explode(',', $header);

        return trim($parts[0]);
    }

    private function record(Request $request, int $statusCode): void
    {
        try {
            $path = ltrim($request->path(), '/');

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if ($ext && in_array($ext, $this->excludeExtensions, true)) {
                return;
            }

            foreach ($this->excludePaths as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    return;
                }
            }

            $userExclude = config('traffic-reader.exclude_paths', []);
            foreach ($userExclude as $prefix) {
                if (str_starts_with($path, ltrim($prefix, '/'))) {
                    return;
                }
            }

            $ua = $request->userAgent() ?? 'UNKNOWN';
            $now = now();

            $clientIp = $this->resolveClientIp($request);

            $threats = array_merge(
                $this->detectPatterns($request),
                $this->detectRateAnomalies($request, $statusCode, $clientIp),
            );

            $isThreat = !empty($threats);

            $data = [
                'timestamp' => $now->format('Y-m-d H:i:s'),
                'session_date' => $now->format('Y-m-d'),
                'session_hour' => $now->format('H'),
                'ip' => $clientIp,              // ← IP real del cliente
                'host' => $request->getHost(),
                'bot' => $this->isBot($ua) ? 'YES' : 'NO',
                'device' => $this->device($ua),
                'os' => $this->os($ua),
                'browser' => $this->browser($ua),
                'method' => $request->method(),
                'url' => $request->getRequestUri(),
                'referer' => $request->headers->get('referer') ?: 'DIRECT',
                'page' => basename($path) ?: 'index',
                'query_string' => $request->getQueryString() ?? '',
                'status_code' => $statusCode,
                'route_name' => optional($request->route())->getName(),
                'threat' => $isThreat ? 'YES' : 'NO',
                'threats' => $threats,
                'user_agent' => $ua,
            ];

            $line = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

            $disk = config('traffic-reader.storage.disk', 'local');
            $folder = config('traffic-reader.storage.folder', 'visits');

            Storage::disk($disk)->append(
                "{$folder}/visits_{$now->format('Y-m-d')}.txt",
                $line
            );

            if ($isThreat) {
                Storage::disk($disk)->append(
                    "{$folder}/threats_{$now->format('Y-m-d')}.txt",
                    $line
                );

                $criticalThreats = array_filter(
                    $threats,
                    fn($t) => in_array($t['type'], $this->criticalThreatTypes, true)
                );

                if (!empty($criticalThreats)) {
                    event(new SecurityThreatDetected($data, array_values($criticalThreats)));
                }
            }

        } catch (\Throwable) {
            // Nunca romper la respuesta por un error del tracker
        }
    }


    private function detectPatterns(Request $request): array
    {
        $threats = [];
        $url = rawurldecode($request->getRequestUri());
        $ua = $request->userAgent() ?? '';
        $method = $request->method();

        foreach ($this->rcePatterns as $p) {
            if (stripos($url, $p) !== false) {
                $threats[] = ['type' => 'RCE_ATTEMPT', 'pattern' => $p];
                break;
            }
        }

        foreach ($this->sqliPatterns as $p) {
            if (stripos($url, $p) !== false) {
                $threats[] = ['type' => 'SQLI_ATTEMPT', 'pattern' => $p];
                break;
            }
        }

        foreach ($this->xssPatterns as $p) {
            if (stripos($url, $p) !== false) {
                $threats[] = ['type' => 'XSS_ATTEMPT', 'pattern' => $p];
                break;
            }
        }

        if (preg_match('/\.\.\/|\.\.\\\\|%2e%2e%2f|%252e%252e|%c0%af/i', $url)) {
            $threats[] = ['type' => 'PATH_TRAVERSAL'];
        }

        foreach ($this->scannerUserAgents as $s) {
            if (stripos($ua, $s) !== false) {
                $threats[] = ['type' => 'SCANNER_UA', 'tool' => $s];
                break;
            }
        }

        if (in_array($method, ['TRACE', 'TRACK', 'CONNECT', 'PROPFIND', 'MOVE'], true)) {
            $threats[] = ['type' => 'UNUSUAL_METHOD', 'method' => $method];
        }

        if (strlen($ua) < 10) {
            $threats[] = ['type' => 'SUSPICIOUS_UA', 'ua_length' => strlen($ua)];
        }

        return $threats;
    }

    private function detectRateAnomalies(Request $request, int $statusCode, string $clientIp): array
    {
        $threats = [];
        $now = now();
        $window = $now->format('Y-m-d-H-i');
        $hour = $now->format('Y-m-d-H');

        $rateKey = "traffic-reader:rate:{$clientIp}:{$window}";
        $rateCount = Cache::increment($rateKey);
        if ($rateCount === 1) {
            Cache::expire($rateKey, 90);
        }

        if ($rateCount > $this->rateThreshold) {
            $threats[] = [
                'type' => 'HIGH_RATE',
                'rpm' => $rateCount,
                'threshold' => $this->rateThreshold,
            ];
        }

        if ($statusCode === 404) {
            $nfKey = "traffic-reader:404:{$clientIp}:{$hour}";
            $nfCount = Cache::increment($nfKey);
            if ($nfCount === 1) {
                Cache::expire($nfKey, 3600);
            }

            if ($nfCount > $this->notFoundThreshold) {
                $threats[] = [
                    'type' => 'ROUTE_SCAN',
                    'not_found' => $nfCount,
                    'threshold' => $this->notFoundThreshold,
                ];
            }
        }

        $path = $request->path();
        foreach ($this->sensitiveRoutes as $route) {
            if (stripos($path, $route) !== false) {
                $probeKey = "traffic-reader:probe:{$clientIp}:{$hour}";
                $probeCount = Cache::increment($probeKey);
                if ($probeCount === 1) {
                    Cache::expire($probeKey, 3600);
                }

                if ($probeCount >= $this->probeThreshold) {
                    $threats[] = [
                        'type' => 'SENSITIVE_PROBE',
                        'path' => $path,
                        'count' => $probeCount,
                        'threshold' => $this->probeThreshold,
                    ];
                }
                break;
            }
        }

        if (in_array($statusCode, [401, 403], true)) {
            $bfKey = "traffic-reader:bf:{$clientIp}:{$hour}";
            $bfCount = Cache::increment($bfKey);
            if ($bfCount === 1) {
                Cache::expire($bfKey, 3600);
            }

            if ($bfCount > $this->bruteForceThreshold) {
                $threats[] = [
                    'type' => 'BRUTE_FORCE',
                    'count' => $bfCount,
                    'status' => $statusCode,
                ];
            }
        }

        return $threats;
    }

    private function device(string $ua): string
    {
        if (preg_match('/tablet|ipad/i', $ua)) return 'TABLET';
        if (preg_match('/mobile|android|iphone/i', $ua)) return 'MOBILE';
        return 'DESKTOP';
    }

    private function os(string $ua): string
    {
        if (preg_match('/android/i', $ua)) return 'Android';
        if (preg_match('/iphone|ipad/i', $ua)) return 'iOS';
        if (preg_match('/windows nt 10/i', $ua)) return 'Windows 10';
        if (preg_match('/windows nt 11/i', $ua)) return 'Windows 11';
        if (preg_match('/windows nt 6\.3/i', $ua)) return 'Windows 8.1';
        if (preg_match('/windows nt 6\.1/i', $ua)) return 'Windows 7';
        if (preg_match('/macintosh|mac os x/i', $ua)) return 'MacOS';
        if (preg_match('/linux/i', $ua)) return 'Linux';
        return 'UNKNOWN';
    }

    private function browser(string $ua): string
    {
        if (preg_match('/edg(e|\/)/i', $ua)) return 'Edge';
        if (preg_match('/opr\//i', $ua)) return 'Opera';
        if (preg_match('/chrome/i', $ua)) return 'Chrome';
        if (preg_match('/firefox/i', $ua)) return 'Firefox';
        if (preg_match('/safari/i', $ua)) return 'Safari';
        if (preg_match('/msie|trident/i', $ua)) return 'Internet Explorer';
        return 'UNKNOWN';
    }

    private function isBot(string $ua): bool
    {
        return (bool)preg_match(
            '/bot|crawl|spider|slurp|facebookexternalhit|Amazonbot|GPTBot|ClaudeBot|bingbot|Googlebot|YandexBot|DuckDuckBot|Baiduspider/i',
            $ua
        );
    }
}