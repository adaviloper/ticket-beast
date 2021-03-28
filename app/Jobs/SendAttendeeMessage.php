<?php

namespace App\Jobs;

use App\AttendeeMessage;
use App\Mail\AttendeeMessageEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

class SendAttendeeMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $attendeeMessage;

    public function __construct(AttendeeMessage $attendeeMessage)
    {
        $this->attendeeMessage = $attendeeMessage;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->attendeeMessage->withChunkedRecipients(20, function ($recipients) {
            $recipients->each(function ($recipient) {
                Mail::to($recipient)->queue(new AttendeeMessageEmail($this->attendeeMessage));
            });
        });
    }
}
