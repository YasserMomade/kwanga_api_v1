<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginVerifyOtpRequest;
use App\Http\Requests\RegisterVerifyOtpRequest;
use App\Http\Requests\RequestOtpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\UserRequest;
use App\Mail\VerifyEmail;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SmsService;
use App\Support\phone;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    /**
     * Registar novo utilizador
     */

    public function register(UserRequest $request): JsonResponse
    {

        DB::beginTransaction();

        try {
            $verification_code = random_int(100000, 999999);

            if (User::where('email', $request->email)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este e-mail já está em uso.'
                ], 409);
            }

            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'verification_code' => $verification_code
            ]);

            DB::commit();

            Mail::to($user->email)->send(new VerifyEmail($user));

            return response()->json([
                'status' => true,
                'message' => "conta criada com sucesso. Verifique o seu email para ativar"
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => "Erro interno, volte a tentar mais tarde",
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Reenviar codigo de verificacao de Email
     */
    public function resendVerificationCode(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);


            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'E-mail não encontrado.'
                ], 404);
            }


            if ($user->email_verified_at) {
                return response()->json([
                    'status' => false,
                    'message' => 'Este e-mail já foi verificado.'
                ], 400);
            }

            if ($user->updated_at->diffInSeconds(now()) < 120) {
                return response()->json([
                    'status' => false,
                    'message' => 'Aguarde um pouco antes de reenviar outro código.'
                ], 429);
            }



            $newCode = random_int(100000, 999999);


            $user->update([
                'verification_code' => $newCode
            ]);

            // Reenviar e-mail
            Mail::to($user->email)->send(new VerifyEmail($user));

            return response()->json([
                'status' => true,
                'message' => 'Novo código de verificação enviado para o seu e-mail.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erro ao reenviar o código. Tente novamente mais tarde.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    /**
     * Verificar codigo de e-mail
     */

    public function verifyEmail(Request $request): JsonResponse
    {

        try {

            $request->validate([
                'email' => 'required|email',
                'code'  => 'required|numeric',
            ]);


            $user = User::where('email', $request->email)
                ->where('verification_code', $request->code)
                ->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Código inválido ou e-mail não encontrado.'
                ], 404);
            }

            $user->update([
                'email_verified_at' => now(),
                'verification_code' => null
            ]);

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'status' => true,
                'message' => 'E-mail verificado com sucesso!',
                'token' => $token,
                'data' => [
                    'user' => $user->only(['id', 'email'])
                ]
            ], 200);
        } catch (Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Ero interno, volte a tentar mais tarde.',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Fazer login e gerar token JWT
     */

    public function login(Request $request): JsonResponse
    {

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Credenciais inválidas'
                ], 401);
            }
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erro ao criar o token',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }

        $user = JWTAuth::user();

        if (!$user->email_verified_at) {
            return response()->json([
                'status' => false,
                'message' => 'E-mail não verificado.'
            ], 403);
        }

        return response()->json([
            'status' => true,
            'message' => 'Login efetuado com sucesso',
            'token' => $token,
            'data' => [
                'user'  => $user->only(['id', 'email'])
            ]
        ], 200);
    }

    /**
     * Retornar dados do utilizador autenticado
     */

    public function me(): JsonResponse
    {

        try {

            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'utilizador não encontrado'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $user
            ], 200);
        } catch (Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Ero interno, tente novamente mais tarde.',
                'error' =>  config('app.debug') ? $e->getMessage() : null
            ]);
        }
    }


    /**
     * Fazer logout e invalidar token JWT
     */

    public function logout(Request $request): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'status' => true,
                'message' => 'Logout efectuado com sucesso!'
            ], 200);
        } catch (JWTException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Erro ao efectuar logout.',
                'error' =>  config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    public function registerRequestOtp(
        RequestOtpRequest $request,
        OtpService $otpService,
        SmsService $sms
    ) {
        $purpose = 'register';
        $phone   = Phone::normalizeMoz($request->phone);

        DB::beginTransaction();

        try {
            $existingUser = User::where('phone', $phone)->first();

            if ($existingUser && $existingUser->phone_verified_at) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Este número já tem uma conta registada. Faça login.'
                ], 409);
            }

            $user = User::firstOrCreate([
                'phone' => $phone
            ]);

            $otpService->ensureCanResend(
                $otpService->getExisting($user, $purpose)
            );

            // Gerar OTP
            $generate = $otpService->generata($user, $purpose);

            DB::commit();

            // Enviar SMS fora da transação
            $sms->send(
                $user->phone,
                "Código de verificação: {$generate['code']} (válido por {$otpService->minutes} min)"
            );

            // return response()->json([
            //     'status'  => true,
            //     'message' => 'Conta criada com sucesso, faça a verificação.'
            // ], 200);

            return response()->json([
                'status' => true,
                'phone' => $user->phone,
                'otp_message' => "Conta criada com sucesso, codigo otp: {$generate['code']} (válido por {$otpService->minutes} min) "
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Erro interno, volte a tentar mais tarde',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    public function registerVerifyOtp(RegisterVerifyOtpRequest $request, OtpService $otpService)
    {
        $phone = Phone::normalizeMoz($request->phone);
        $purpose = 'register';

        $user = User::where('phone', $phone)->first();
        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'Utilizador não encontrado.'
            ], 404);
        }

        $ok = $otpService->verify($user, $purpose, $request->code);
        if (!$ok) {
            return response()->json([
                'status' => false,
                'message' => 'código invalido.'
            ], 400);
        }

        if (! $user->phone_verified_at) {
            $user->update(['phone_verified_at' => now()]);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status' => true,
            'message' => 'Conta verificada com sucesso.',
            'token' => $token,
            'data' => [
                'user' => $user->only(['id', 'phone', 'email', 'phone_verified_at'])
            ]
        ], 200);
    }




    public function loginRequestOtp(RequestOtpRequest $request, OtpService $otpService, SmsService $sms)
    {

        $phone = Phone::normalizeMoz($request->phone);
        $purpose = 'login';

        $user = User::where('phone', $phone)->first();
        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'Utilizador não encontrado.'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $otpService->ensureCanResend($otpService->getExisting($user, $purpose));
            $generate = $otpService->generata($user, $purpose);

            DB::commit();

            $sms->send($user->phone, "Código de login: {$generate['code']} (válido por {$otpService->minutes} min)");


            // return response()->json([
            //     'status' => true,
            //     'message' => 'OTP enviado para login.'
            // ], 200);

            return response()->json([
                'status' => true,
                'phone' => $user->phone,
                'otp_message' => "Código de login: {$generate['code']} (válido por {$otpService->minutes} min)"
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Erro interno, volte a tentar mais tarde',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    public function loginVerifyOtp(LoginVerifyOtpRequest $request, OtpService $otpService)
    {

        $phone = Phone::normalizeMoz($request->phone);
        $purpose = 'login';

        $user = User::where('phone', $phone)->first();
        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'Utilizador não encontrado.'
            ], 404);
        }

        $ok = $otpService->verify($user, $purpose, $request->code);
        if (! $ok) {
            return response()->json([
                'status' => false,
                'message' => 'código invalido.'
            ], 400);
        }

        if (! $user->phone_verified_at) {
            $user->update(['phone_verified_at' => now()]);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status' => true,
            'message' => 'Login efetuado com sucesso.',
            'token' => $token,
            'data' => [
                'user' => $user->only(['id', 'phone', 'email'])
            ]
        ], 200);
    }

    public function registerResendOtp(RequestOtpRequest $request, OtpService $otpService, SmsService $sms)
    {

        $phone = Phone::normalizeMoz($request->phone);
        $purpose = 'register';

        DB::beginTransaction();

        try {

            $existingUser = User::where('phone', $phone)->first();


            if ($existingUser && $existingUser->phone_verified_at) {
                return response()->json([
                    'status' => false,
                    'message' => 'Este número já tem uma conta registada. Faça login.'
                ], 409);
            }

            $user = User::firstOrCreate(['phone' => $phone]);

            $otpService->ensureCanResend($otpService->getExisting($user, $purpose));

            $generate = $otpService->generata($user, $purpose);

            DB::commit();

            $sms->send($user->phone, "Código de verificação: {$generate['code']} (válido por {$otpService->minutes} min)");

            return response()->json([
                'status' => true,
                'message' => 'OTP reenviado com sucesso.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Erro interno, volte a tentar mais tarde',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function loginResendOtp(RequestOtpRequest $request, OtpService $otpService, SmsService $sms)
    {

        $phone = Phone::normalizeMoz($request->phone);
        $purpose = 'login';

        $user = User::where('phone', $phone)->first();
        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'Utilizador não encontrado.'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $otpService->ensureCanResend($otpService->getExisting($user, $purpose));
            $generate = $otpService->generata($user, $purpose);

            DB::commit();

            $sms->send($user->phone, "Código de login: {$generate['code']} (válido por {$otpService->minutes} min)");

            return response()->json([
                'status' => true,
                'message' => 'OTP reenviado com sucesso.
                .'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Erro interno, volte a tentar mais tarde',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    private function getUserId(Request $request): int
    {
        if (auth()->check()) {
            $authId = auth()->id();

            if ($request->has('user_id') && (int)$request->user_id !== $authId) {
                abort(response()->json([
                    'status' => false,
                    'message' => 'O ID do utilizador enviado não corresponde ao autenticado.'
                ], 403));
            }

            return $authId;
        }

        if ($request->has('user_id')) {
            return (int)$request->user_id;
        }

        abort(response()->json([
            'status' => false,
            'message' => 'Identificação de utilizador necessária.'
        ], 401));
    }

    private function errorResponce(Exception $e): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => "Erro interno, volte a tentar mais tarde.",
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $user = User::where('id', $userId)->first();

            if (! $user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Utilizador não encontrado.'
                ], 404);
            }

            $user->update([
                'first_name'    => $request->first_name,
                'last_name'     => $request->last_name,
                'province'      => $request->province,
                'gender'        => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'email'         => $request->email,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Perfil actualizado com sucesso.',
                'data' => $user->only([
                    'id',
                    'phone',
                    'email',
                    'first_name',
                    'last_name',
                    'province',
                    'gender',
                    'date_of_birth',
                    'phone_verified_at'
                ])
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponce($e);
        }
    }
}
