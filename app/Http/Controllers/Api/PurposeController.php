<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purpose;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class PurposeController extends Controller
{


    public function index()
    {
        try {
            // Autenticar user via token
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Usuário não autenticado.'
                ], 401);
            }

            // Buscar propositos do User logado
            $purposes = Purpose::where('user_id', $user->id)
                ->with(['lifeArea:id,designation'])->get();

            return response()->json([
                'status' => true,
                'purposes' => $purposes
            ], 200);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token expirado.'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token inválido.'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token nao encontrado.'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Ocorreu um erro inesperado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function create(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:250',
            'life_area_id' => 'required'
        ]);

        DB::beginTransaction();

        try {

            $user = JWTAuth::parseToken()->authenticate();

            $purpose = Purpose::create([
                'user_id' =>  $user->id,
                'life_area_id' => $request->life_area_id,
                'description' => $request->description,

            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'massage' => 'Proposito Salvo com sucesso',
                'purpose' => $purpose
            ], 200);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token expirado.'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token inválido.'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token nao encontrado.'
            ], 400);
        } catch (Exception   $e) {
            DB::rollBack();
            return response()->json([
                'Message' => "Falha ao criar Proposito, volte a tentar mais tarde",
                'error' => $e->getMessage()
            ], 401);
        }
    }



    public function update(Request $request, $id)
    {
        $request->validate([
            'description' => 'required|string',
            'life_area_id' => 'required'
        ]);


        try {

            DB::beginTransaction();

            $user = JWTAuth::parseToken()->authenticate();
            // Buscar o proposito de vida pelo ID

            $purpose = Purpose::where('id', $id)->where('user_id', $user->id)->first();


            // Verifica se existe
            if (!$purpose) {
                return response()->json([
                    'status' => false,
                    'Message' => 'Proposito não encontrada.'
                ], 404);
            }

            $purpose->update([
                'description' => $request->description,
                'life_area_id' => $request->life_area_id,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'Message' => 'Proposito atualizada com sucesso.',
                'Purpose' => $purpose
            ], 200);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token expirado.'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token inválido.'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token nao encontrado.'
            ], 400);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Message' => "Falha ao Atualizar Proposito, volte a tentar mais tarde" . $e->getMessage()
            ], 500);
        }
    }


    public function destroy($id)
    {

        DB::beginTransaction();

        try {
            $user = JWTAuth::parseToken()->authenticate();

            $purpose = Purpose::where('id', $id)->where('user_id', $user->id)->first();

            if (!$purpose) {
                return response()->json([
                    'status' => false,
                    'message' => 'Proposito não encontrado.'
                ], 404);
            }

            $purpose->delete();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Proposito deletado com sucesso.'
            ], 200);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token expirado.'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token inválido.'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token nao encontrado.'
            ], 400);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Falha ao deletar Proposito, volte a tentar mais tarde.'
            ], 500);
        }
    }
}
