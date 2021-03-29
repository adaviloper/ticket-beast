<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

interface InvitationCodeGenerator
{
    public function generate();
}
