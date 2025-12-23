<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Facade\FlareClient\Flare;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class OtpService
{

    public int $minutes = 5;
    public int $cooldownSeconds = 120;
    public int $maxAttempts = 5;


    public function generata(User $user, string $purpose = 'login'): array
    {

        $code = (string) random_int(100000, 999999);

        $otp = OtpCode::updateOrCreate(
            ['user_id' => $user->id, 'purpose' => $purpose],
            [
                'code' => Hash::make($code),
                //'code' => $code,
                'expires_at' => now()->addMinutes($this->minutes),
                'last_sent_at' => now(),
                'attempts' => 0,
                'verified_at' => null,
            ]
        );

        return ['code' => $code, 'otp' => $otp];
    }

    public function ensureCanResend(?OtpCode $otp): void
    {

        if (! $otp) {
            return;
        }

        if ($otp->last_sent_at && $otp->last_sent_at->diffInSeconds(now()) < $this->cooldownSeconds) {
            abort(response()->json([
                'status' => false,
                'message' => 'Aguarde um pouco antes de reenviar outro código.'
            ], 429));
        }
    }

    public function verify(User $user, string $purpose, string $code): bool
    {

        $otp = OtpCode::where('user_id', $user->id)
            ->where('purpose', $purpose)->first();

        if (!$otp) return false;
        if ($otp->verified_at) return false;
        if (now()->greaterThan($otp->expires_at)) return false;
        if ($otp->attempts >= $this->maxAttempts) return false;

        $otp->increment('attempts');

        // Deixar para criptografar
        if (! Hash::check($code, $otp->code)) return false;

        //  if ($code !== $otp->code) return false;

        $otp->update(['verified_at' => now()]);
        return true;
    }

    public function getExisting(User $user, string $purpose): ?OtpCode
    {

        return OtpCode::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->first();
    }
}
