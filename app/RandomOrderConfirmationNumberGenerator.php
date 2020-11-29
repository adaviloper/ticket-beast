<?php

namespace App;

class RandomOrderConfirmationNumberGenerator implements OrderConfirmationNumberGenerator
{
    const LENGTH = 24;

    public function generate()
    {
        $pool = 'ABCDEFGHJKLMNPQRSTUBWXYZ23456789';

        return substr(str_shuffle(str_repeat($pool, self::LENGTH)), 0, self::LENGTH);
    }
}
