<?php

namespace App\Http\Controllers\Backstage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConcertsController extends Controller
{
    public function create()
    {
        return view('backstage.concerts.create');
    }
}
