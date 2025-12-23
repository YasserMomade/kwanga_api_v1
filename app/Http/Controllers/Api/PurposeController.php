<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurposeRequest;
use App\Models\Purpose;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class PurposeController extends Controller
{

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


    /**
     * Listar todos os propositos do usuario autenticado.
     */

    public function index(Request $request): JsonResponse
    {
        try {

            $userId = $this->getUserId($request);

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

    public function show(Request $request, $id): JsonResponse
    {
        try {

            $userId = $this->getUserId($request);

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
            'id' => 'required|string',
            'description' => 'required|string|max:250',
            'life_area_id' => 'required|exists:life_areas,id'
        ]);

        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);

            $purpose = Purpose::updateOrCreate(
                ['id' => $request->id],
                [
                    'user_id' =>  $userId,
                    'life_area_id' => $request->life_area_id,
                    'description' => $request->description,

                ]
            );

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

    public function update(PurposeRequest $request, $id)
    {

        DB::beginTransaction();

        try {



            $userId = $this->getUserId($request);

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
                'message' => 'Proposito atualizado com sucesso.',
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

    public function destroy(Request $request, $id)
    {

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

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
                'message' => 'Proposito eliminado com sucesso.'
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
