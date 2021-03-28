<?php

namespace Tests\Unit\Jobs;

use App\AttendeeMessage;
use App\Jobs\SendAttendeeMessage;
use App\Mail\AttendeeMessageEmail;
use App\Order;
use ConcertFactory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;
use OrderFactory;
use Queue;
use Tests\TestCase;

class SendAttendeeMessageTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_sends_the_message_to_all_concert_attendees(): void
    {
        Mail::fake();
        $concert = ConcertFactory::createPublished();
        $otherConcert = ConcertFactory::createPublished();
        $message = AttendeeMessage::create([
            'concert_id' => $concert->id,
            'subject' => 'My subject',
            'message' => 'My message',
        ]);
        $orderA = OrderFactory::createForConcert($concert, ['email' => 'a@example.com']);
        $orderB = OrderFactory::createForConcert($concert, ['email' => 'b@example.com']);
        $orderC = OrderFactory::createForConcert($concert, ['email' => 'c@example.com']);
        $otherOrder = OrderFactory::createForConcert($otherConcert, ['email' => 'd@example.com']);

        SendAttendeeMessage::dispatch($message);

        Mail::assertQueued(AttendeeMessageEmail::class, static function ($mail) use ($message) {
            return $mail->hasTo('a@example.com') && $mail->attendeeMessage->is($message);
        });
        Mail::assertQueued(AttendeeMessageEmail::class, static function ($mail) use ($message) {
            return $mail->hasTo('b@example.com') && $mail->attendeeMessage->is($message);
        });
        Mail::assertQueued(AttendeeMessageEmail::class, static function ($mail) use ($message) {
            return $mail->hasTo('c@example.com') && $mail->attendeeMessage->is($message);
        });
        Mail::assertNotQueued(AttendeeMessageEmail::class, static function ($mail) {
            return $mail->hasTo('d@example.com');
        });
    }
}
