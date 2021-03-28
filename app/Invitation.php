<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
}
