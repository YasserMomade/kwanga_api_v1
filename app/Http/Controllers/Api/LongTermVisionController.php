<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LifeArea;
use App\Models\LongTermVision;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class LongTermVisionController extends Controller
{

    public function index()
    {

        try {

            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Usuário não autenticado.'
                ], 401);
            }


            $longTermVision = LongTermVision::where('user_id', $user->id)
                ->with(['lifeArea:id,designation,icon_path'])->get();

            return response()->json([
                'status' => true,
                'long Term Vision' => $longTermVision
            ], 200);
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

        DB::beginTransaction();

        try {

            $user = JWTAuth::parseToken()->authenticate();

            $longTermVision = LongTermVision::create([
                'user_id' =>  $user->id,
                'life_area_id' => $request->life_area_id,
                'description' => $request->description,
                'status' => $request->status,
                'deadline' => $request->deadline
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'massage' => 'Visão a Longo Prazo Criada com Secesso',
                'Long term vision' => $longTermVision
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
                'Message' => "Falha ao criar Visao a longo prazo, volte a tentar mais tarde",
                'error' => $e->getMessage()
            ], 401);
        }
    }


    public function show($id)
    {

        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Usuário não autenticado.'
                ], 401);
            }

            $longTermVision = LongTermVision::where('id', $id)->where('user_id', $user->id)
                ->with(['LifeArea:id,designation'])->first();


            if (!$longTermVision) {
                return response()->json([
                    'status' => false,
                    'message' => 'Visao a longo prazo não foi encontrada'
                ], 404);
            }
            return response()->json([
                'status' => true,
                'description' => $longTermVision->description,
                'deadline' => $longTermVision->deadline,
                'area of life' => $longTermVision->lifeArea->designation,

            ], 200);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json([
                'status' => false,
                'message' => 'TOken expirado ou invalido! Faca login novamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Ocorreu um erro inesperado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $userId = $user->id;

        try {
            DB::beginTransaction();

            $longTermVision = LongTermVision::where('id', $id)->where('user_id', $userId)->first();

            if (!$longTermVision) {
                return response()->json([
                    'status' => false,
                    'Message' => 'Visão a Longo Prazo não encontrada.'
                ], 404);
            }

            $longTermVision->update([
                'life_area_id' => $request->life_area_id,
                'description' => $request->description,
                'status' => $request->status,
                'deadline' => $request->deadline
            ]);


            DB::commit();

            //$longTermVision->load('lifeArea');

            return response()->json([
                'status' => true,
                'Message' => 'Visão a Longo Prazo atualizada com sucesso.',
                'Visão a Longo Prazo' => $longTermVision
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Message' => "Falha ao atualizar Visão a Longo Prazo, tente novamente mais tarde.",
                'Erro' => $e->getMessage()
            ], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token expirado ou inválido! Faça login novamente'
            ], 401);
        }
    }






    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $user = JWTAuth::parseToken()->authenticate();


            $longTermVision = longTermVision::where($id, $id)
                ->where('user_id', $user->id)->first();

            if (!$longTermVision) {
                return response()->json([
                    'status' => false,
                    'Message' => 'Visão a Longo Prazo não encontrada.'
                ], 404);
            }


            $longTermVision->delete();
            DB::commit();

            return response()->json([
                'status' => true,
                'Message' => 'Visão a Longo Prazo com sucesso.'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Message' => "Falha ao deletar a Visão a Longo Prazo.",
                'erro' => $e->getMessage()
            ], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token expirado ou inválido! Faça login novamente'
            ], 401);
        }
    }
}
