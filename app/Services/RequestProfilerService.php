<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RequestProfilerService
{
    protected static ?self $instance = null;
    protected array $timings = [];
    protected array $checkpoints = [];
    protected float $startTime;
    protected bool $enabled = false;
    protected array $queries = [];
    protected int $queryCount = 0;
    protected float $queryTime = 0;

    public function __construct()
    {
        $this->startTime = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $this->enabled = env('REQUEST_PROFILE', false);
    }

    public static function getInstance(): self
    {
        if (static::$instance === null) {
            static::$instance = new self();
        }
        return static::$instance;
    }

    public static function reset(): void
    {
        static::$instance = null;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): self
    {
        $this->enabled = true;
        return $this;
    }

    /**
     * Start tracking database queries
     */
    public function startQueryTracking(): self
    {
        if (!$this->enabled) return $this;

        DB::listen(function ($query) {
            $this->queryCount++;
            $this->queryTime += $query->time;

            // Store top 5 slowest queries
            $this->queries[] = [
                'sql' => $query->sql,
                'time' => $query->time,
                'bindings' => $query->bindings,
            ];

            // Keep only top 10 slowest
            usort($this->queries, fn($a, $b) => $b['time'] <=> $a['time']);
            $this->queries = array_slice($this->queries, 0, 10);
        });

        return $this;
    }

    /**
     * Add a checkpoint with timing
     */
    public function checkpoint(string $name, ?string $category = null): self
    {
        if (!$this->enabled) return $this;

        $now = microtime(true);
        $elapsed = round(($now - $this->startTime) * 1000, 2);

        $this->checkpoints[] = [
            'name' => $name,
            'category' => $category ?? 'general',
            'elapsed' => $elapsed,
            'timestamp' => $now,
        ];

        return $this;
    }

    /**
     * Start timing a section
     */
    public function start(string $name): self
    {
        if (!$this->enabled) return $this;

        $this->timings[$name] = [
            'start' => microtime(true),
            'end' => null,
            'duration' => null,
        ];

        return $this;
    }

    /**
     * End timing a section
     */
    public function end(string $name): self
    {
        if (!$this->enabled) return $this;

        if (isset($this->timings[$name])) {
            $this->timings[$name]['end'] = microtime(true);
            $this->timings[$name]['duration'] = round(
                ($this->timings[$name]['end'] - $this->timings[$name]['start']) * 1000,
                2
            );
        }

        return $this;
    }

    /**
     * Get timing for a section
     */
    public function getTiming(string $name): ?float
    {
        return $this->timings[$name]['duration'] ?? null;
    }

    /**
     * Get all data for logging
     */
    public function getData(): array
    {
        return [
            'total_time' => round((microtime(true) - $this->startTime) * 1000, 2),
            'timings' => $this->timings,
            'checkpoints' => $this->checkpoints,
            'queries' => [
                'count' => $this->queryCount,
                'total_time' => round($this->queryTime, 2),
                'slowest' => array_slice($this->queries, 0, 5),
            ],
        ];
    }

    /**
     * Log the complete timeline
     */
    public function logTimeline(string $route, string $method): void
    {
        if (!$this->enabled) return;

        $data = $this->getData();
        $totalTime = $data['total_time'];

        // Determine color based on total time
        $status = $totalTime > 500 ? 'SLOW' : ($totalTime > 300 ? 'WARN' : 'OK');

        $log = [
            'type' => 'REQUEST_TIMELINE',
            'status' => $status,
            'route' => $route,
            'method' => $method,
            'total_ms' => $totalTime,
            'sections' => [],
            'db_queries' => $data['queries']['count'],
            'db_time_ms' => $data['queries']['total_time'],
        ];

        // Add timing sections
        foreach ($this->timings as $name => $timing) {
            if ($timing['duration'] !== null) {
                $log['sections'][$name] = $timing['duration'];
            }
        }

        // Add checkpoints as timeline
        $log['timeline'] = array_map(function ($cp) {
            return [
                'name' => $cp['name'],
                'at_ms' => $cp['elapsed'],
            ];
        }, $this->checkpoints);

        // Add slowest queries
        if (!empty($data['queries']['slowest'])) {
            $log['slow_queries'] = array_map(function ($q) {
                return [
                    'time_ms' => $q['time'],
                    'sql' => substr($q['sql'], 0, 100) . (strlen($q['sql']) > 100 ? '...' : ''),
                ];
            }, array_slice($data['queries']['slowest'], 0, 3));
        }

        Log::channel('single')->info('[PROFILE]', $log);
    }

    /**
     * Generate formatted output for CLI
     */
    public function getFormattedOutput(string $route, string $method): string
    {
        $data = $this->getData();
        $totalTime = $data['total_time'];

        $output = "\n";
        $output .= "╔═══════════════════════════════════════════════════════════════════════╗\n";
        $output .= "║                    REQUEST LIFECYCLE TIMELINE                         ║\n";
        $output .= "╠═══════════════════════════════════════════════════════════════════════╣\n";
        $output .= sprintf("║ Route: %-63s ║\n", substr($route, 0, 63));
        $output .= sprintf("║ Method: %-62s ║\n", $method);
        $output .= "╠═══════════════════════════════════════════════════════════════════════╣\n";
        $output .= "║ TIMELINE                                                              ║\n";
        $output .= "╠═══════════════════════════════════════════════════════════════════════╣\n";

        // Show checkpoints as timeline
        $lastTime = 0;
        foreach ($this->checkpoints as $cp) {
            $delta = $cp['elapsed'] - $lastTime;
            $bar = str_repeat('█', min(30, (int)($delta / 10)));
            $output .= sprintf("║ %6.1f ms │ %-25s │ +%-6.1f ms │ %-10s ║\n",
                $cp['elapsed'],
                substr($cp['name'], 0, 25),
                $delta,
                substr($bar, 0, 10)
            );
            $lastTime = $cp['elapsed'];
        }

        $output .= "╠═══════════════════════════════════════════════════════════════════════╣\n";
        $output .= "║ SECTIONS                                                              ║\n";
        $output .= "╠═══════════════════════════════════════════════════════════════════════╣\n";

        // Show timing sections
        foreach ($this->timings as $name => $timing) {
            if ($timing['duration'] !== null) {
                $pct = $totalTime > 0 ? ($timing['duration'] / $totalTime) * 100 : 0;
                $bar = str_repeat('▓', min(20, (int)($pct / 5)));
                $output .= sprintf("║ %-30s %10.2f ms  %5.1f%% │%-10s ║\n",
                    $name,
                    $timing['duration'],
                    $pct,
                    $bar
                );
            }
        }

        $output .= "╠═══════════════════════════════════════════════════════════════════════╣\n";
        $output .= sprintf("║ DB Queries: %-5d    Total DB Time: %10.2f ms                    ║\n",
            $data['queries']['count'],
            $data['queries']['total_time']
        );

        // Show slowest queries
        if (!empty($data['queries']['slowest'])) {
            $output .= "╠═══════════════════════════════════════════════════════════════════════╣\n";
            $output .= "║ TOP 3 SLOWEST QUERIES                                                 ║\n";
            foreach (array_slice($data['queries']['slowest'], 0, 3) as $i => $q) {
                $sql = substr($q['sql'], 0, 55);
                $output .= sprintf("║ %d. [%6.1f ms] %-55s ║\n", $i + 1, $q['time'], $sql);
            }
        }

        $output .= "╠═══════════════════════════════════════════════════════════════════════╣\n";

        $color = $totalTime > 500 ? 'SLOW!' : ($totalTime > 300 ? 'WARN' : 'OK');
        $output .= sprintf("║ TOTAL: %10.2f ms    [%s]                                        ║\n",
            $totalTime, $color);
        $output .= "╚═══════════════════════════════════════════════════════════════════════╝\n";

        return $output;
    }
}
