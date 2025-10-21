<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LongTermVision;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LongTermVisionController extends Controller
{

    /**
     * Listar todas as visoes a longo prazo do utilizador autenticado.
     */

    public function index(): JsonResponse
    {

        try {

            $userId = auth()->id();


            $longTermVision = LongTermVision::where('user_id', $userId)
                ->with(['lifeArea:id,designation,icon_path'])->get();

            return response()->json([
                'status' => true,
                'data' => $longTermVision
            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Cria uma nova visso a longo prazo.
     */

    public function store(Request $request): JsonResponse
    {

        $request->validate([
            'life_area_id' => 'required|exists:life_areas,id',
            'description' => 'required|string|max:255',
            'status' => 'nullable|string|max:50',
            'deadline' => 'nullable|date'
        ]);


        DB::beginTransaction();

        try {

            $userId = auth()->id();

            $longTermVision = LongTermVision::create([
                'user_id' =>  $userId,
                'life_area_id' => $request->life_area_id,
                'description' => $request->description,
                'status' => $request->status,
                'deadline' => $request->deadline
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Visão a Longo Prazo Criada com Secesso',
                'data' => $longTermVision
            ], 201);
        } catch (Exception   $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    /**
     * Mostra uma visso a longo prazo específica.
     */

    public function show($id): JsonResponse
    {

        try {

            $userId = auth()->id();

            $longTermVision = LongTermVision::where('id', $id)->where('user_id', $userId)
                ->with(['lifeArea:id,designation'])->first();


            if (!$longTermVision) {
                return response()->json([
                    'status' => false,
                    'message' => 'Visao a longo prazo não  encontrada'
                ], 404);
            }
            return response()->json([
                'status' => true,
                'data' => $longTermVision,

            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Atualiza uma visao a longo prazo existente.
     */

    public function update(Request $request, $id): JsonResponse

    {
        $request->validate([
            'life_area_id' => 'required|exists:life_areas,id',
            'description' => 'required|string|max:255',
            'status' => 'nullable|string|max:50',
            'deadline' => 'nullable|date'
        ]);

        DB::beginTransaction();

        try {

            $userId = auth()->id();

            $longTermVision = LongTermVision::where('id', $id)->where('user_id', $userId)->first();

            if (!$longTermVision) {
                return response()->json([
                    'status' => false,
                    'message' => 'Visão a Longo Prazo não encontrada.'
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
                'message' => 'Visão a Longo Prazo atualizada com sucesso.',
                'data' => $longTermVision
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    /**
     * Deletar uma visao a longo prazo.
     */

    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();

        try {

            $userId = auth()->id();

            $longTermVision = LongTermVision::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$longTermVision) {
                return response()->json([
                    'status' => false,
                    'message' => 'Visão a Longo Prazo não encontrada.'
                ], 404);
            }


            $longTermVision->delete();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Visão a Longo Prazo eliminada com sucesso.'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    /**
     * Resposta de erro padronizada.
     */

    private function errorResponse(Exception $e): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => "Erro interno, volte a tentar mais tarde.",
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
