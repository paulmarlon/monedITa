<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BonoBienvenida;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest');
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'registro_universitario' => ['required', 'string', 'max:255', 'unique:users'],
            'name' => ['required', 'string', 'max:255'],
            'alias' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
                // CAMBIO: con "@" delante, para no aceptar dominios tipo "xusalesiana.edu.bo"
                'ends_with:@usalesiana.edu.bo'
            ],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ]);
    }

    protected function create(array $data)
    {
        $defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($data['name']) . '&background=random&color=fff&size=128';

        // 1. Crear el usuario (la wallet con saldo 0 la crea el hook booted() de User)
        $user = User::create([
            'registro_universitario' => $data['registro_universitario'],
            'name' => $data['name'],
            'alias' => $data['alias'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'avatar' => $defaultAvatar,
        ]);

        // 2. Asignar automaticamente el rol de ESTUDIANTE
        $user->assignRole('ESTUDIANTE');

        // 3. NUEVO: 3 monedas de cortesia (registra la transaccion en el libro mayor)
        BonoBienvenida::otorgar($user);

        return $user;
    }
}
