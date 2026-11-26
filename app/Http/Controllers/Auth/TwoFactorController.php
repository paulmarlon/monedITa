<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    private const TTL_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    public static function issueChallenge(int $userId, bool $remember = false): string
    {
        $challengeId = (string) Str::uuid();
        $code = (string) random_int(100000, 999999);

        $payload = [
            'user_id' => $userId,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'remember' => $remember,
        ];

        Cache::put(self::cacheKey($challengeId), $payload, now()->addMinutes(self::TTL_MINUTES));

        $user = User::find($userId);
        if ($user && !empty($user->email)) {
            Mail::raw(
                "Tu código de verificación 2FA para tapITa es: {$code}\n\nEste código vence en " . self::TTL_MINUTES . " minutos.",
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Código de verificación 2FA - tapITa');
                }
            );
        }

        return $challengeId;
    }

    public function show(Request $request)
    {
        $challengeId = $request->session()->get('2fa:web:challenge_id');
        if (!$challengeId || !Cache::has(self::cacheKey($challengeId))) {
            return redirect()->route('login')
                ->withErrors(['registro_universitario' => 'Tu reto de verificación expiró. Inicia sesión nuevamente.']);
        }

        $user = User::find($request->session()->get('2fa:web:user_id'));

        return view('auth.2fa', [
            'emailMasked' => $user?->email ? self::maskEmail($user->email) : null,
            'ttlMinutes' => self::TTL_MINUTES,
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $challengeId = $request->session()->get('2fa:web:challenge_id');
        if (!$challengeId) {
            return redirect()->route('login');
        }

        $key = self::cacheKey($challengeId);
        $data = Cache::get($key);

        if (!$data) {
            $this->clearSessionChallenge($request);
            return redirect()->route('login')
                ->withErrors(['registro_universitario' => 'El código 2FA ha expirado. Inicia sesión otra vez.']);
        }

        if ($data['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($key);
            $this->clearSessionChallenge($request);
            return redirect()->route('login')
                ->withErrors(['registro_universitario' => 'Superaste el máximo de intentos. Inicia sesión nuevamente.']);
        }

        if (!Hash::check($request->input('code'), $data['code_hash'])) {
            $data['attempts']++;
            Cache::put($key, $data, now()->addMinutes(self::TTL_MINUTES));

            return back()->withErrors([
                'code' => 'Código incorrecto. Intentos restantes: ' . (self::MAX_ATTEMPTS - $data['attempts']),
            ]);
        }

        // Código válido: limpiamos reto y abrimos sesión real
        Cache::forget($key);
        $userId = (int) $data['user_id'];
        $remember = (bool) ($request->session()->get('2fa:web:remember', $data['remember'] ?? false));

        $this->clearSessionChallenge($request);
        $request->session()->regenerate();

        Auth::loginUsingId($userId, $remember);

        return redirect()->intended(route('home'));
    }

    public function resend(Request $request)
    {
        $userId = $request->session()->get('2fa:web:user_id');
        $oldChallengeId = $request->session()->get('2fa:web:challenge_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        if ($oldChallengeId) {
            Cache::forget(self::cacheKey($oldChallengeId));
        }

        $remember = (bool) $request->input('remember', false);
        $newChallengeId = self::issueChallenge((int) $userId, $remember);
        $request->session()->put('2fa:web:challenge_id', $newChallengeId);

        return back()->with('status', 'Se envió un nuevo código de 6 dígitos a tu correo.');
    }

    public function cancel(Request $request)
    {
        $oldChallengeId = $request->session()->get('2fa:web:challenge_id');
        if ($oldChallengeId) {
            Cache::forget(self::cacheKey($oldChallengeId));
        }

        $this->clearSessionChallenge($request);

        return redirect()->route('login');
    }

    private function clearSessionChallenge(Request $request): void
    {
        $request->session()->forget([
            '2fa:web:challenge_id',
            '2fa:web:user_id',
            '2fa:web:remember',
        ]);
    }

    private static function cacheKey(string $challengeId): string
    {
        return "2fa:web:challenge:{$challengeId}";
    }

    private static function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $prefix = mb_substr($local, 0, 2);
        return $prefix . str_repeat('*', max(2, mb_strlen($local) - 2)) . '@' . $domain;
    }
}
