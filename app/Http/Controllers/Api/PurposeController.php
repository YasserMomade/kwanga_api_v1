<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purpose;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class PurposeController extends Controller
{


    /**
     * Listar todos os propositos do usuario autenticado.
     */

    public function index(): JsonResponse
    {
        try {

            $userId = auth()->id();

            $purposes = Purpose::where('user_id', $userId)
                ->with(['lifeArea:id,designation'])->get();

            return response()->json([
                'status' => true,
                'data' => $purposes
            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Exibir um proposito especifico.
     */

    public function show($id): JsonResponse
    {
        try {

            $userId = auth()->id();

            $purpose = Purpose::where('id', $id)->where('user_id', $userId)
                ->with(['lifeArea:id,designation'])->first();

            if (!$purpose) {

                return response()->json([
                    'status' => false,
                    'message' => 'Proposito não encontrada'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $purpose

            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }


    /**
     * Criar um novo proposito.
     */

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:250',
            'life_area_id' => 'required|exists:life_areas,id'
        ]);

        DB::beginTransaction();

        try {

            $userId = auth()->id();

            $purpose = Purpose::create([
                'user_id' =>  $userId,
                'life_area_id' => $request->life_area_id,
                'description' => $request->description,

            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Proposito Salvo com sucesso',
                'data' => $purpose
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }


    /**
     * Atualizar um  proposito.
     */

    public function update(Request $request, $id)
    {
        $request->validate([
            'description' => 'required|string|max:250',
            'life_area_id' => 'required|exists:life_areas,id'
        ]);


        try {

            DB::beginTransaction();

            $userId = auth()->id();

            $purpose = Purpose::where('id', $id)->where('user_id', $userId)->first();


            if (!$purpose) {
                return response()->json([
                    'status' => false,
                    'message' => 'Proposito não encontrada.'
                ], 404);
            }

            $purpose->update([
                'description' => $request->description,
                'life_area_id' => $request->life_area_id,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Proposito atualizada com sucesso.',
                'data' => $purpose
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    /**
     * Deletar um propósito.
     */

    public function destroy($id)
    {

        DB::beginTransaction();

        try {
            $userId = auth()->id();

            $purpose = Purpose::where('id', $id)->where('user_id', $userId)->first();

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
        } catch (Exception $e) {
            DB::rollBack();

            return $this->errorResponse($e);
        }
    }

    /**
     * Resposta padronizada de erro.
     */

    private function errorResponse(Exception $e): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Erro interno, volte a tentar mais tarde.',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
