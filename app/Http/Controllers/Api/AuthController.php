<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\UserRequest;
use App\Mail\VerifyEmail;
use App\Models\User;
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
                'error' => $e->getMessage()
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

            return response()->json([
                'status' => true,
                'message' => 'E-mail verificado com sucesso!'
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
                'error' => $e->getMessage()
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
            'data' => [
                'token' => $token,
                'user'  => $user->only(['id', 'name', 'email'])
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
                'error' =>  $e->getMessage()
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
                'error' =>  $e->getMessage()
            ], 500);
        }
    }
}
