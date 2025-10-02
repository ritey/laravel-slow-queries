<?php

namespace Ritey\SlowQueries\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Ritey\SlowQueries\Mail\SlowQueriesReport;
use Ritey\SlowQueries\Support\Paths;

class EmailSlowQueriesReport extends Command
{
    protected $signature = 'slow-queries:email';
    protected $description = 'Email the slow query log and rotate/truncate the file';

    public function handle(): int
    {
        if (!config('slow-queries.enabled')) {
            $this->info('Slow queries disabled. Skipping.');

            return self::SUCCESS;
        }

        $to = config('slow-queries.email_to');
        if (!$to) {
            $this->warn('No slow-queries.email_to configured. Skipping.');

            return self::SUCCESS;
        }

        $logPath = Paths::logAbsolutePath();

        if (!File::exists($logPath) || 0 === File::size($logPath)) {
            $this->info('No slow queries to send.');

            return self::SUCCESS;
        }

        $sendingPath = $logPath.'.sending-'.now()->format('YmdHis').'-'.uniqid('', true);

        if (!@rename($logPath, $sendingPath)) {
            $this->error('Failed to rotate slow query log (rename).');

            return self::FAILURE;
        }

        // Recreate empty log so DB::listen can continue
        File::put($logPath, '');

        try {
            $size = File::size($sendingPath) ?? 0;
            Mail::to($to)->send(new SlowQueriesReport(
                attachmentPath: $sendingPath,
                attachmentSizeBytes: $size,
                subjectLine: config('slow-queries.email_subject')
            ));
            $this->info("Slow query report emailed to {$to} ({$size} bytes).");
        } catch (\Throwable $e) {
            $this->error('Failed to send slow query report: '.$e->getMessage());
            // Try to restore content so it isn't lost
            @file_put_contents($logPath, @file_get_contents($sendingPath), FILE_APPEND);
        } finally {
            @unlink($sendingPath);
        }

        return self::SUCCESS;
    }
}
