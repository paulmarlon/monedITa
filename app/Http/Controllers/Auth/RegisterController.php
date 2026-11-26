<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Ciclo;
use App\Models\CicloUsuarioRole;
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
                'ends_with:@usalesiana.edu.bo',
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
            'name'                   => $data['name'],
            'alias'                  => $data['alias'],
            'email'                  => $data['email'],
            'password'               => Hash::make($data['password']),
            'avatar'                 => $defaultAvatar,
        ]);

        // 2. Asignar rol base ESTUDIANTE y registrar en el ciclo vigente
        $user->assignRole(CicloUsuarioRole::ROLE_ESTUDIANTE);

        if ($cicloActivo = Ciclo::vigente()) {
            CicloUsuarioRole::updateOrCreate(
                [
                    'ciclo_id' => $cicloActivo->id,
                    'user_id'  => $user->id,
                ],
                [
                    'role_name'    => CicloUsuarioRole::ROLE_ESTUDIANTE,
                    'team_id'      => $user->team_id,
                    'asignado_por' => $user->id,
                ]
            );
        }

        // 3. 3 monedas de cortesía (registra la transacción en el libro mayor)
        BonoBienvenida::otorgar($user);

        return $user;
    }
}
