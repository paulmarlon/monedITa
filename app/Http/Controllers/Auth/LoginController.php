<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function username()
    {
        return 'registro_universitario';
    }

    protected function authenticated(Request $request, $user)
    {
        $userId = (int) $user->id;
        $remember = (bool) $request->input('remember', false);

        Auth::logout();
        $request->session()->regenerate();

        $challengeId = TwoFactorController::issueChallenge($userId, $remember);

        $request->session()->put('2fa:web:challenge_id', $challengeId);
        $request->session()->put('2fa:web:user_id', $userId);
        $request->session()->put('2fa:web:remember', $remember);

        return redirect()->route('2fa.challenge');
    }
}
