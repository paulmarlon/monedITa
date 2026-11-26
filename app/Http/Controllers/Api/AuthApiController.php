<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DispositivoConfiable;
use App\Models\User;
use App\Services\BonoBienvenida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class AuthApiController extends Controller
{
    private const TWO_FACTOR_TTL_MINUTES = 10;
    private const TWO_FACTOR_MAX_ATTEMPTS = 5;

    /**
     * POST /api/login
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'registro_universitario' => ['required', 'string', 'max:50'],
            'password'               => ['required', 'string', 'max:255'],
            'device_id'              => ['required', 'string', 'max:120'],
            'device_name'            => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::with('wallet')
            ->where('registro_universitario', $data['registro_universitario'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $cleanDeviceId = trim($data['device_id']);
        $cleanDeviceName = isset($data['device_name']) ? trim($data['device_name']) : null;

        $dispositivo = DispositivoConfiable::where('user_id', $user->id)
            ->where('device_id', $cleanDeviceId)
            ->first();

        if (! $dispositivo || is_null($dispositivo->verified_at)) {
            DispositivoConfiable::updateOrCreate(
                [
                    'user_id'   => $user->id,
                    'device_id' => $cleanDeviceId,
                ],
                [
                    'device_name' => $cleanDeviceName,
                ]
            );

            $challengeId = $this->iniciarDesafioTwoFactor($user, $cleanDeviceId, $cleanDeviceName);
            $this->registrarAuditoria($request, $user->id, '2FA_SOLICITADO');

            return response()->json([
                'success'      => true,
                'requires_2fa' => true,
                'challenge_id' => $challengeId,
                'email_masked' => $this->enmascararCorreo($user->email),
                'expires_in'   => self::TWO_FACTOR_TTL_MINUTES * 60,
                'message'      => 'Dispositivo nuevo detectado. Ingresa el código enviado a tu correo.',
            ]);
        }

        $dispositivo->update([
            'device_name'  => $cleanDeviceName ?: $dispositivo->device_name,
            'last_used_at' => now(),
        ]);

        $deviceLabel = $this->formatearDispositivo($cleanDeviceId, $cleanDeviceName);
        $token = $this->emitirTokenUnico($request, $user, $deviceLabel);

        return response()->json([
            'success'      => true,
            'requires_2fa' => false,
            'token'        => $token,
            'token_type'   => 'Bearer',
            'device'       => $deviceLabel,
            'user'         => $this->userPayload($user),
            'saldo_actual' => (float) ($user->wallet?->saldo_actual ?? 0),
        ]);
    }

    /**
     * POST /api/2fa/verify
     */
    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'challenge_id' => ['required', 'string', 'max:100'],
            'code'         => ['required', 'string', 'size:6'],
            'device_id'    => ['required', 'string', 'max:120'],
            'device_name'  => ['nullable', 'string', 'max:100'],
        ]);

        $cacheKey = $this->twoFactorCacheKey($data['challenge_id']);
        $challenge = Cache::get($cacheKey);

        if (! is_array($challenge)) {
            return response()->json([
                'success' => false,
                'message' => 'El código expiró o no es válido. Inicia sesión nuevamente.',
            ], 422);
        }

        $cleanDeviceId = trim($data['device_id']);

        if (($challenge['device_id'] ?? '') !== $cleanDeviceId) {
            return response()->json([
                'success' => false,
                'message' => 'El código no corresponde a este dispositivo.',
            ], 403);
        }

        $attempts = (int) ($challenge['attempts'] ?? 0);
        if ($attempts >= self::TWO_FACTOR_MAX_ATTEMPTS) {
            Cache::forget($cacheKey);

            return response()->json([
                'success' => false,
                'message' => 'Demasiados intentos fallidos. Solicita un nuevo código.',
            ], 429);
        }

        if (! Hash::check(trim($data['code']), (string) $challenge['code_hash'])) {
            $challenge['attempts'] = $attempts + 1;
            Cache::put($cacheKey, $challenge, now()->addMinutes(self::TWO_FACTOR_TTL_MINUTES));

            return response()->json([
                'success' => false,
                'message' => 'Código incorrecto.',
            ], 422);
        }

        $user = User::with('wallet')->find($challenge['user_id'] ?? 0);
        if (! $user) {
            Cache::forget($cacheKey);

            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        Cache::forget($cacheKey);

        $cleanDeviceName = isset($data['device_name'])
            ? trim($data['device_name'])
            : ($challenge['device_name'] ?? null);

        DispositivoConfiable::updateOrCreate(
            [
                'user_id'   => $user->id,
                'device_id' => $cleanDeviceId,
            ],
            [
                'device_name'  => $cleanDeviceName,
                'verified_at'  => now(),
                'last_used_at' => now(),
            ]
        );

        $this->registrarAuditoria($request, $user->id, '2FA_VERIFICADO');

        $deviceLabel = $this->formatearDispositivo($cleanDeviceId, $cleanDeviceName);
        $token = $this->emitirTokenUnico($request, $user, $deviceLabel);

        return response()->json([
            'success'      => true,
            'requires_2fa' => false,
            'token'        => $token,
            'token_type'   => 'Bearer',
            'device'       => $deviceLabel,
            'user'         => $this->userPayload($user),
            'saldo_actual' => (float) ($user->wallet?->saldo_actual ?? 0),
        ]);
    }

    /**
     * POST /api/2fa/resend
     */
    public function resendTwoFactor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'challenge_id' => ['required', 'string', 'max:100'],
            'device_id'    => ['required', 'string', 'max:120'],
        ]);

        $oldKey = $this->twoFactorCacheKey($data['challenge_id']);
        $challenge = Cache::get($oldKey);

        if (! is_array($challenge) || ($challenge['device_id'] ?? '') !== trim($data['device_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud expiró. Vuelve a iniciar sesión.',
            ], 422);
        }

        $user = User::find($challenge['user_id'] ?? 0);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        Cache::forget($oldKey);

        $newChallengeId = $this->iniciarDesafioTwoFactor(
            $user,
            $challenge['device_id'],
            $challenge['device_name'] ?? null
        );

        return response()->json([
            'success'      => true,
            'requires_2fa' => true,
            'challenge_id' => $newChallengeId,
            'expires_in'   => self::TWO_FACTOR_TTL_MINUTES * 60,
            'message'      => 'Se envió un nuevo código a tu correo.',
        ]);
    }

    /**
     * POST /api/register
     */
    public function register(Request $request): JsonResponse
    {
        $request->merge(['email' => Str::lower((string) $request->input('email'))]);

        $data = $request->validate([
            'registro_universitario' => ['required', 'string', 'max:255', 'regex:/^[0-9]+$/', 'unique:users,registro_universitario'],
            'name'                   => ['required', 'string', 'max:255'],
            'alias'                  => ['required', 'string', 'max:255', 'unique:users,alias'],
            'email'                  => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'ends_with:@usalesiana.edu.bo'],
            'password'               => ['required', 'string', 'min:4', 'max:255', 'confirmed'],
            'device_id'              => ['required', 'string', 'max:120'],
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

                $user->assignRole('ESTUDIANTE');

                DispositivoConfiable::create([
                    'user_id'      => $user->id,
                    'device_id'    => trim($data['device_id']),
                    'device_name'  => isset($data['device_name']) ? trim($data['device_name']) : null,
                    'verified_at'  => now(),
                    'last_used_at' => now(),
                ]);

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
        $deviceLabel = $this->formatearDispositivo($data['device_id'], $data['device_name'] ?? null);
        $token = $this->emitirTokenUnico($request, $user, $deviceLabel);

        return response()->json([
            'success'       => true,
            'requires_2fa'  => false,
            'message'       => 'Cuenta creada.',
            'token'         => $token,
            'token_type'    => 'Bearer',
            'device'        => $deviceLabel,
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
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        if ($user) {
            $this->registrarAuditoria($request, $user->id, 'LOGOUT');
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada.',
        ]);
    }

    private function iniciarDesafioTwoFactor(User $user, string $deviceId, ?string $deviceName): string
    {
        $challengeId = (string) Str::uuid();
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put(
            $this->twoFactorCacheKey($challengeId),
            [
                'user_id'     => $user->id,
                'device_id'   => $deviceId,
                'device_name' => $deviceName,
                'code_hash'   => Hash::make($code),
                'attempts'    => 0,
            ],
            now()->addMinutes(self::TWO_FACTOR_TTL_MINUTES)
        );

        $dispositivoTexto = $deviceName ?: $deviceId;

        Mail::raw(
            "Tu código de verificación para iniciar sesión en {$dispositivoTexto} es: {$code}. Expira en " . self::TWO_FACTOR_TTL_MINUTES . " minutos.",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Código de verificación de dispositivo - monedITa');
            }
        );

        return $challengeId;
    }

    private function twoFactorCacheKey(string $challengeId): string
    {
        return "2fa_device_challenge:{$challengeId}";
    }

    private function enmascararCorreo(string $email): string
    {
        [$usuario, $dominio] = array_pad(explode('@', $email, 2), 2, '');
        if (strlen($usuario) <= 2) {
            return '***@' . $dominio;
        }

        return substr($usuario, 0, 2) . str_repeat('*', max(strlen($usuario) - 2, 3)) . '@' . $dominio;
    }

    private function formatearDispositivo(string $deviceId, ?string $deviceName): string
    {
        $cleanId = trim($deviceId);
        $cleanName = $deviceName ? trim($deviceName) : null;

        return $cleanName ? "{$cleanName} [{$cleanId}]" : $cleanId;
    }

    private function emitirTokenUnico(Request $request, User $user, string $deviceLabel): string
    {
        return DB::transaction(function () use ($request, $user, $deviceLabel) {
            $tokensAnteriores = $user->tokens()->get();

            $huboCambioDispositivo = $tokensAnteriores->contains(
                fn($t) => (string) $t->name !== $deviceLabel
            );

            if ($tokensAnteriores->isNotEmpty()) {
                $user->tokens()->delete();

                if ($huboCambioDispositivo) {
                    $this->registrarAuditoria($request, $user->id, 'EXPULSADO');
                }
            }

            $plainTextToken = $user->createToken($deviceLabel, ['*'])->plainTextToken;
            $this->registrarAuditoria($request, $user->id, 'LOGIN');

            return $plainTextToken;
        });
    }

    private function registrarAuditoria(Request $request, int $userId, string $evento): void
    {
        DB::table('auditoria_accesos')->insert([
            'user_id'    => $userId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'evento'     => $evento,
            'created_at' => now(),
        ]);
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
