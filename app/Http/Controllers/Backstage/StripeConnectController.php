<?php

namespace App\Http\Controllers\Backstage;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StripeConnectController extends Controller
{
    public function authorizeRedirect()
    {
        $url = vsprintf('%s?%s', [
            'https://connect.stripe.com/oauth/v2/authorize',
            http_build_query([
                'client_id' => config('services.stripe.client_id'),
                'response_type' => 'code',
                'scope' => 'read_write',
            ]),
        ]);
        return redirect($url);
    }
}
