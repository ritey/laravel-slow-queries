<?php

namespace Vendor\SlowQueries;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Vendor\SlowQueries\Console\EmailSlowQueriesReport;
use Vendor\SlowQueries\Support\Paths;

class SlowQueriesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/slow-queries.php', 'slow-queries');
    }

    public function boot(): void
    {
        // Publish config & view
        $this->publishes([
            __DIR__.'/../config/slow-queries.php' => config_path('slow-queries.php'),
        ], 'slow-queries-config');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'slow-queries');

        // Register command
        if ($this->app->runningInConsole()) {
            $this->commands([EmailSlowQueriesReport::class]);
        }

        // Attach DB listener (guarded by config)
        if (config('slow-queries.enabled')) {
            $this->attachDbListener();
        }

        // Optional package scheduler
        if (config('slow-queries.use_package_scheduler')) {
            $this->app->afterResolving(Schedule::class, function (Schedule $schedule) {
                $schedule->command('slow-queries:email')
                    ->everyTenMinutes()
                    ->when(fn () => (bool) config('slow-queries.enabled'))
                ;
            });
        }
    }

    protected function attachDbListener(): void
    {
        $logPath = Paths::logAbsolutePath();
        $dir = dirname($logPath);

        if (!is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        DB::listen(function ($query) use ($logPath) {
            try {
                $timeMs = (int) ($query->time ?? 0);
                if ($timeMs < config('slow-queries.threshold_ms')) {
                    return;
                }

                $context = app()->runningInConsole()
                    ? 'CONSOLE='.implode(' ', array_map('escapeshellarg', $_SERVER['argv'] ?? []))
                    : sprintf('HTTP=%s %s', request()->method(), request()->fullUrl());

                $connection = $query->connectionName ?? 'default';
                $now = now()->toDateTimeString();
                $sql = $query->sql;
                $bindings = $query->bindings ?? [];
                $renderedBindings = json_encode($bindings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                $entry = <<<LOG
------------------------------
[{$now}] connection={$connection} time_ms={$timeMs}
context: {$context}
sql: {$sql}
bindings: {$renderedBindings}

LOG;

                $fh = fopen($logPath, 'ab');
                if ($fh) {
                    flock($fh, LOCK_EX);
                    fwrite($fh, $entry);
                    fflush($fh);
                    flock($fh, LOCK_UN);
                    fclose($fh);
                }
            } catch (\Throwable $e) {
                logger()->warning('laravel-slow-queries failed to write log', ['error' => $e->getMessage()]);
            }
        });
    }
}
