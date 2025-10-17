<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LifeArea;
use Exception;
use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;


class LifeAreaController extends Controller
{

    public function index()
    {

        $lifeAreas = LifeArea::where('id_default', true)->get();

        return response()->json([
            'status' => true,
            'life_Areas' => $lifeAreas
        ], 200);
    }


    public function getLifeAreasByUser()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $userId = $user->id;

        $lifeAreas = LifeArea::where('is_default', true)->orWhere('user_id', $userId)->get();

        return response()->json([
            'status' => true,
            'lifeAreas' => $lifeAreas
        ], 200);
    }

    public function showLifeAreas($id)
    {
        try {

            $user = JWTAuth::parseToken()->authenticate();
            $userId = $user->id;

            $lifeAreas = LifeArea::where(function ($query) use ($userId) {
                $query->where('is_default', true)->orWhere('user_id', $userId);
            })->where('id', $id)->first();


            if (!$lifeAreas) {
                return response()->json([
                    'status' => false,
                    'message' => 'Area da vida não foi encontrada'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'Life area' => $lifeAreas->only(['designation', 'icon_path'])
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erro interno. VOlte a tentar mais tarde' . $e->getMessage()

            ], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json([
                'status' => false,
                'message' => 'TOken expirado ou invalido! Faca login novamente'
            ], 401);
        }
    }


    public function createAdm(Request $request)
    {
        try {
            $request->validate([
                'designation' => 'required|string|max:55',
                'icon_path' => 'required|string'
            ]);

            $lifeArea = LifeArea::create([
                'user_id' => 0,
                'designation' => $request->designation,
                'icon_path' => $request->icon_path,
                'is_default' => true
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Área de vida criada com sucesso',
                'life_area' => $lifeArea
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Falha ao criar área de vida, tente novamente mais tarde',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json([
                'status' => false,
                'message' => 'TOken expirado ou invalido! Faca login novamente'
            ], 401);
        }
    }


    public function create(Request $request)
    {

        $request->validate([
            'designation' => 'required|string|max:55',
            'icon_path' => 'required|string'
        ]);

        DB::beginTransaction();

        try {

            $user = JWTAuth::parseToken()->authenticate();

            $lifeArea = LifeArea::create([
                'user_id' =>  $user->id,
                'designation' => $request->designation,
                'icon_path' => $request->icon_path,
                'is_default' => false
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'massage' => 'Área de vida criada com sucesso',
                'lifeArea' => $lifeArea
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Message' => "Falha ao criar Area da vida, volte a tentar mais tarde" . $e->getMessage()
            ], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json([
                'status' => false,
                'message' => 'TOken expirado ou invalido! Faca login novamente'
            ], 401);
        }
    }

    public function updateAreaLife(Request $request, $id)
    {
        $request->validate([
            'designation' => 'required|string|max:255',
            'icon_path' => 'required|string'
        ]);

        DB::beginTransaction();

        $user = JWTAuth::parseToken()->authenticate();
        $userId =  $user->id;

        try {
            $lifeArea = LifeArea::find($id);

            if (!$lifeArea) {
                return response()->json([
                    'status' => false,
                    'Message' => 'Área de vida não encontrada.'
                ], 404);
            }

            if ($lifeArea->user_id !== $userId || $lifeArea->is_default) {
                return response()->json([
                    'status' => false,
                    'Message' => 'Você não tem permissão para editar esta área de vida.'
                ], 403);
            }

            $lifeArea->update([
                'designation' => $request->designation,
                'icon_path' => $request->icon_path
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'Message' => 'Área de vida atualizada com sucesso.',
                'lifeArea' => $lifeArea
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Message' => "Falha ao atualizar área da vida, tente novamente mais tarde."
            ], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json([
                'status' => false,
                'message' => 'TOken expirado ou invalido! Faca login novamente'
            ], 401);
        }
    }

    public function deleteLifeArea($id)
    {
        DB::beginTransaction();

        try {

            $user = JWTAuth::parseToken()->authenticate();
            $userId = $user->id;

            $lifeArea = LifeArea::find($id);

            if (!$lifeArea) {
                return response()->json([
                    'status' => false,
                    'Message' => 'Área de vida não encontrada.'
                ], 404);
            }


            if ($lifeArea->user_id !== $userId || $lifeArea->is_default) {
                return response()->json([
                    'status' => false,
                    'Message' => 'Você não tem permissão para apagar esta área de vida.'
                ], 403);
            }


            $lifeArea->delete();
            DB::commit();

            return response()->json([
                'status' => true,
                'Message' => 'Área de vida deletada com sucesso.'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Message' => "Falha ao deletar a área da vida. Erro: "
            ], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json([
                'status' => false,
                'message' => 'TOken expirado ou invalido! Faca login novamente'
            ], 401);
        }
    }
}
