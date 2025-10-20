<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonthlyGoal;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class MonthlyGoalController extends Controller
{

    /**
     * Lista todos os objetivos mensais do utilizador autenticado.
     */

    public function index(): JsonResponse
    {
        try {

            $userId = auth()->id();

            $monthlyGoal = MonthlyGoal::where('user_id', $userId)
                ->with(['annualGoal:id,description'])->get();

            return response()->json([
                'status' => true,
                'data' => $monthlyGoal
            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Cria um novo objetivo mensal.
     */

    public function store(Request $request)
    {

        $request->validate([
            'annual_goals_id' => 'required|exists:annual_goals,id',
            'description' => 'required|string|max:255',
            'month' => 'required|string|max:20',
            'status' => 'nullable|string|max:50'
        ]);


        DB::beginTransaction();

        try {

            $userId = auth()->id();

            $monthlyGoal = MonthlyGoal::create([
                'user_id' =>  $userId,
                'annual_goals_id' => $request->annual_goals_id,
                'description' => $request->description,
                'month' => $request->month,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Objectivo mensal Criado com Secesso',
                'data' => $monthlyGoal
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->errorResponse($e);
        }
    }

    /**
     * Mostra um objetivo mensal específico.
     */

    public function show($id): JsonResponse
    {
        try {
            $userId = auth()->id();

            $monthlyGoal = MonthlyGoal::where('id', $id)
                ->where('user_id', $userId)
                ->with(['annualGoal.longTermVision.lifeArea:id,designation'])
                ->first();

            if (!$monthlyGoal) {
                return response()->json([
                    'status' => false,
                    'message' => 'Objetivo mensal não encontrado'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $monthlyGoal
            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Atualiza um objetivo mensal existente.
     */

    public function update(Request $request, $id): JsonResponse
    {

        $request->validate([
            'annual_goals_id' => 'nullable|exists:annual_goals,id',
            'description' => 'nullable|string|max:255',
            'month' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:50'
        ]);

        DB::beginTransaction();

        try {
            $userId = auth()->id();

            $monthlyGoal = MonthlyGoal::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$monthlyGoal) {
                return response()->json([
                    'status' => false,
                    'message' => 'Objetivo mensal não encontrado.'
                ], 404);
            }

            $monthlyGoal->update([
                'annual_goals_id' => $request->annual_goals_id ?? $monthlyGoal->annual_goals_id,
                'description' => $request->description ?? $monthlyGoal->description,
                'month' => $request->month ?? $monthlyGoal->month,
                'status' => $request->status ?? $monthlyGoal->status,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Objetivo mensal atualizado com sucesso',
                'Monthly goal' => $monthlyGoal
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse($e);
        }
    }

    /**
     * Deleta um objetivo mensal.
     */

    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = auth()->id();

            $monthlyGoal = MonthlyGoal::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$monthlyGoal) {
                return response()->json([
                    'status' => false,
                    'message' => 'Objetivo mensal não encontrado'
                ], 404);
            }

            $monthlyGoal->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Objetivo mensal apagado com sucesso'
            ], 200);
        } catch (\Exception $e) {
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
