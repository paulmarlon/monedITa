<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BonoBienvenida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class AuthApiController extends Controller
{
    /**
     * POST /api/login
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'registro_universitario' => ['required', 'string', 'max:50'],
            'password'               => ['required', 'string', 'max:255'],
            'device_name'            => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::with('wallet')
            ->where('registro_universitario', $data['registro_universitario'])
            ->first();

        // Mismo mensaje si el usuario no existe o la clave es incorrecta
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        return response()->json([
            'success'      => true,
            'token'        => $this->emitirToken($user, $data['device_name'] ?? 'android'),
            'token_type'   => 'Bearer',
            'user'         => $this->userPayload($user),
            'saldo_actual' => (float) ($user->wallet?->saldo_actual ?? 0),
        ]);
    }

    /**
     * POST /api/register
     * Crea la cuenta (rol ESTUDIANTE), acredita el bono de bienvenida y devuelve el token.
     */
    public function register(Request $request): JsonResponse
    {
        $request->merge(['email' => Str::lower((string) $request->input('email'))]);

        $data = $request->validate([
            'registro_universitario' => ['required', 'string', 'max:255', 'regex:/^[0-9]+$/', 'unique:users,registro_universitario'],
            'name'                   => ['required', 'string', 'max:255'],
            'alias'                  => ['required', 'string', 'max:255', 'unique:users,alias'],
            // Con "@" delante: evita aceptar dominios como "xusalesiana.edu.bo"
            'email'                  => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'ends_with:@usalesiana.edu.bo'],
            'password'               => ['required', 'string', 'min:4', 'max:255', 'confirmed'],
            'device_name'            => ['nullable', 'string', 'max:100'],
        ]);

        try {
            [$user, $bono] = DB::transaction(function () use ($data) {
                $user = User::create([
                    'registro_universitario' => $data['registro_universitario'],
                    'name'                   => $data['name'],
                    'alias'                  => $data['alias'],
                    'email'                  => $data['email'],
                    'password'               => Hash::make($data['password']),
                    'avatar'                 => 'https://ui-avatars.com/api/?name=' . urlencode($data['name']) . '&background=random&color=fff&size=128',
                ]);

                // La wallet (saldo 0) la crea el hook booted() del modelo User
                $user->assignRole('ESTUDIANTE');

                return [$user, BonoBienvenida::otorgar($user)];
            });
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear la cuenta. Intenta nuevamente.',
            ], 500);
        }

        $user->load('wallet');

        return response()->json([
            'success'       => true,
            'message'       => 'Cuenta creada.',
            'token'         => $this->emitirToken($user, $data['device_name'] ?? 'android'),
            'token_type'    => 'Bearer',
            'user'          => $this->userPayload($user),
            'saldo_actual'  => (float) ($user->wallet?->saldo_actual ?? 0),
            'bono_otorgado' => $bono ? (float) BonoBienvenida::MONTO : 0.0,
        ], 201);
    }

    /**
     * GET /api/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('wallet');

        return response()->json([
            'success'      => true,
            'user'         => $this->userPayload($user),
            'saldo_actual' => (float) ($user->wallet?->saldo_actual ?? 0),
        ]);
    }

    /**
     * POST /api/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'Sesión cerrada.']);
    }

    /** Un token activo por dispositivo. */
    private function emitirToken(User $user, string $device): string
    {
        $user->tokens()->where('name', $device)->delete();

        return $user->createToken($device, ['minijuego'])->plainTextToken;
    }

    private function userPayload(User $user): array
    {
        return [
            'id'      => $user->id,
            'name'    => $user->name,
            'alias'   => $user->alias,
            'avatar'  => $user->avatar,
            'team_id' => $user->team_id,
        ];
    }
}
