<?php

namespace Ritey\SlowQueries\Support;

class Paths
{
    public static function logAbsolutePath(): string
    {
        $rel = config('slow-queries.log_path', 'logs/slow-queries.log');

        return storage_path($rel);
    }
}
