<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnnualGoal;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class AnnualGoalController extends Controller
{

    /**
     * Lista todos os objetivos anuais do utilizador autenticado.
     */

    public function index(): JsonResponse
    {

        $userId = auth()->id();

        try {

            $annualGoals = AnnualGoal::where('user_id', $userId)
                ->with(['longTermVision:id,description'])->get();

            return response()->json([
                'status' => true,
                'data' => $annualGoals
            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Cria um novo objetivo anual.
     */

    public function store(Request $request): JsonResponse
    {

        $request->validate([
            'long_term_vision_id' => 'required|exists:long_term_visions,id',
            'description' => 'required|string|max:255',
            'year' => 'required|integer',
            'status' => 'nullable|string|max:50'
        ]);

        DB::beginTransaction();

        try {

            $userId = auth()->id();

            $annualGoal = AnnualGoal::create([
                'user_id' =>  $userId,
                'long_term_vision_id' => $request->long_term_vision_id,
                'description' => $request->description,
                'year' => $request->year,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Objectivo anual Criado com Secesso',
                'date' => $annualGoal
            ], 201);
        } catch (Exception   $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    /**
     * Mostra um objetivo anual específico.
     */
    public function show($id): JsonResponse
    {
        try {
            $userId = auth()->id();

            $annualGoal = AnnualGoal::where('id', $id)
                ->where('user_id', $userId)
                ->with(['longTermVision.lifeArea:id,designation'])
                ->first();

            if (!$annualGoal) {
                return response()->json([
                    'status' => false,
                    'message' => 'Objetivo anual não encontrado.'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $annualGoal
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }


    /**
     * Atualiza um objetivo anual existente.
     */

    public function update(Request $request, $id): JsonResponse
    {
        $userId = auth()->id();

        DB::beginTransaction();

        try {

            $annualGoal = AnnualGoal::where('id', $id)->where('user_id', $userId)->first();

            if (!$annualGoal) {
                return response()->json([
                    'status' => false,
                    'message' => 'Objectivo anual não encontrada.'
                ], 404);
            }

            $annualGoal->update([
                'user_id' =>  $userId,
                'long_term_vision_id' => $request->long_term_vision_id,
                'description' => $request->description,
                'status' => $request->status,
                'year' => $request->year
            ]);


            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Objectivo anual atualizada com sucesso.',
                'data' => $annualGoal
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    /**
     * Deleta um objetivo anual.
     */

    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();

        $userId = auth()->id();

        try {

            $annualGoal = AnnualGoal::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$annualGoal) {
                return response()->json([
                    'status' => false,
                    'message' => 'Objectivo anual não encontrada.'
                ], 404);
            }


            $annualGoal->delete();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Objectivo anual eliminado com sucesso.'
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
