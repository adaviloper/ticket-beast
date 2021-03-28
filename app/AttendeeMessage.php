<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class AttendeeMessage
 * @package App
 *
 * @property Concert $concert
 */
class AttendeeMessage extends Model
{
    protected $guarded = [];

    public function concert(): BelongsTo
    {
        return $this->belongsTo(Concert::class);
    }

    public function orders()
    {
        return $this->concert->orders();
    }

    public function withChunkedRecipients($chunkSize, $callback): void
    {
        $this->orders()->chunk($chunkSize, static function ($orders) use ($callback) {
            $callback($orders->pluck('email'));
        });
    }
}
