<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

        // Un token activo por dispositivo
        $device = $data['device_name'] ?? 'android';
        $user->tokens()->where('name', $device)->delete();

        $token = $user->createToken($device, ['minijuego'])->plainTextToken;

        return response()->json([
            'success'      => true,
            'token'        => $token,
            'token_type'   => 'Bearer',
            'user'         => $this->userPayload($user),
            'saldo_actual' => (float) ($user->wallet?->saldo_actual ?? 0),
        ]);
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
