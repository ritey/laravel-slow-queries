<?php

namespace Ritey\SlowQueries\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SlowQueriesReport extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $attachmentPath,
        public int $attachmentSizeBytes,
        public ?string $subjectLine = null
    ) {}

    public function build()
    {
        $subject = $this->subjectLine ?: 'Slow Query Report';
        $filename = 'slow-queries-'.now()->format('Ymd-His').'.log';

        return $this->subject($subject)
            ->text('slow-queries::emails.slow_queries_report_plain')
            ->attach($this->attachmentPath, [
                'as' => $filename,
                'mime' => 'text/plain',
            ])
            ->with([
                'size_kb' => round($this->attachmentSizeBytes / 1024, 2),
                'generated_at' => now()->toDateTimeString(),
            ])
        ;
    }
}
