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
use Carbon\Carbon;
use Namshi\JOSE\JWT;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function createUser(UserRequest $request): JsonResponse
    {

        DB::beginTransaction();

        try {
            $verification_code = rand(100000, 999999);

            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'verification_code' => $verification_code
            ]);

            Mail::to($user->email)->send(new VerifyEmail($user));
            DB::commit();

            return response()->json([
                'Message' => "conta criada. Verifique o seu email para ativar"
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Message' => "Falha ao criar conta, volte a tentar mais tarde" . $e->getMessage()
            ], 401);
        }
    }

    public function verifyEmail(Request $request)
    {

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
                'mensagem' => 'Código inválido ou e-mail não encontrado.'
            ], 400);
        }

        $user->update([
            'email_verified_at' => Carbon::now(),
            'verification_code' => null
        ]);

        return response()->json([
            'status' => true,
            'mensagem' => 'E-mail verificado com sucesso!'
        ], 200);
    }

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
                'message' => 'Erro ao criar o token'
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
            'mensagem' => 'Login efetuado com sucesso',
            'token' => $token,
            'utilizador' => $user->only(['id', 'name', 'email'])
        ]);
    }

    public function me()
    {

        try {

            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'utilizador não foi encontrado'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'email' => $user->email
            ], 200);
        } catch (Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Ero interno, volte a tentar mais tarde.'
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json([
                'status' => false,
                'message' => 'TOken expirado ou invalido! Faca login novamente'
            ], 401);
        }
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['status' => true, 'mensagem' => 'Logout efectuado com sucesso!']);
        } catch (JWTException $e) {
            return response()->json(['status' => false, 'mensagem' => 'Erro ao fazer logout.' . $e->getMessage()], 500);
        }
    }
}
