<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Invitation;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function register()
    {
        $invitation = Invitation::findByCode(request('invitation_code'));
        abort_if($invitation->hasBeenUsed(), 404);

        request()->validate([
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required'],
        ]);

        $user = User::create([
            'email' => request('email'),
            'password' => Hash::make(request('password')),
        ]);

        $invitation->update([
            'user_id' => $user->id,
        ]);

        Auth::login($user);

        return redirect()->route('backstage.concerts.index');
    }
}
