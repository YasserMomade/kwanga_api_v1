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
     * Lista todos os objetivos mensais do utilizador autenticado.
     */


    public function index(Request $request): JsonResponse
    {
        try {

            $userId = $this->getUserId($request);

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
            'description' => 'required|string',
            'month' => 'required|string|max:20',
        ]);


        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);

            $monthlyGoal = MonthlyGoal::create(
                [

                    'id' => $request->id,
                    'user_id' =>  $userId,
                    'annual_goals_id' => $request->annual_goals_id,
                    'description' => $request->description,
                    'month' => $request->month,
                    'created_at' => $request->created_at ?? now(),
                    'updated_at' => $request->updated_at ?? now()
                ]
            );

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

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);

            $monthlyGoal = MonthlyGoal::where('id', $id)
                ->where('user_id', $userId)
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
            'annual_goals_id' => 'sometimes|exists:annual_goals,id',
            'description' => 'sometimes|string',
            'month' => 'nullable|string|max:20',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $monthlyGoal = MonthlyGoal::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$monthlyGoal) {
                return response()->json([
                    'status' => false,
                    'message' => 'Objetivo mensal não encontrado.'
                ], 404);
            }

            $data = $request->only([
                'annual_goals_id',
                'description',
                'month'
            ]);

            $data['updated_at'] = $request->updated_at ?? now();

            $monthlyGoal->update($data);



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

    public function destroy(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

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
                'message' => 'Eliminado'
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
