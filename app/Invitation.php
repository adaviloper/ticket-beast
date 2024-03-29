<?php

namespace App;

use App\Mail\InvitationEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class Invitation extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function findByCode($code)
    {
        return self::where('code', $code)->firstOrFail();
    }

    /** @test */
    public function hasBeenUsed(): bool
    {
        return $this->user_id !== null;
    }

    public function send()
    {
        Mail::to($this->email)->send(new InvitationEmail($this));
    }
}
