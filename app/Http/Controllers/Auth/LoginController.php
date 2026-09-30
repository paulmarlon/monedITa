<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'registro_universitario';
    }
    public function loginApi(Request $request)
    {
        // Validar los campos enviados desde Android
        $credentials = $request->validate([
            'registro_universitario' => ['required'],
            'password' => ['required'],
        ]);

        // Intentar autenticar usando el campo personalizado 'registro_universitario'
        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Si usas Sanctum o Passport para tokens, aquí generarías el token:
            // $token = $user->createToken('AndroidApp')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'user' => $user,
                // 'token' => $token // Descomenta si usas tokens
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Credenciales inválidas'
        ], 401);
    }
}
