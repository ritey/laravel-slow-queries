<?php

return [
    'enabled' => env('SLOW_QUERIES_ENABLED', false),
    'threshold_ms' => (int) env('SLOW_QUERY_THRESHOLD_MS', 500),
    'log_path' => env('SLOW_QUERIES_LOG_PATH', 'logs/slow-queries.log'),
    'email_to' => env('SLOW_QUERIES_EMAIL_TO', null),
    'email_subject' => env('SLOW_QUERIES_EMAIL_SUBJECT', 'Slow Query Report'),

    // If true, the package wires its own scheduler (every 10 minutes).
    // If false, you can schedule the command yourself in the host app.
    'use_package_scheduler' => env('SLOW_QUERIES_USE_PKG_SCHEDULER', true),
];
