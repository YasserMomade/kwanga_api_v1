<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonthlyGoal;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class MonthlyGoalController extends Controller
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

            $monthlyGoal = MonthlyGoal::where('user_id', $user->id)
                ->with(['AnnualGoals:id,description'])->get();

            return response()->json([
                'status' => true,
                'Monthly goals' => $monthlyGoal
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

            $monthlyGoal = MonthlyGoal::create([
                'user_id' =>  $user->id,
                'annual_goals_id' => $request->annual_goals_id,
                'description' => $request->description,
                'month' => $request->month,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'massage' => 'Objectivo mensal Criado com Secesso',
                'Monthly goals' => $monthlyGoal
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'Message' => "Falha ao criar objectivo anual, volte a tentar mais tarde",
                'error' => $e->getMessage()
            ], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token expirado ou inválido! Faça login novamente'
            ], 401);
        }
    }

    public function show($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $monthlyGoal = MonthlyGoal::where('id', $id)
                ->where('user_id', $user->id)
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
                'Monthly goal' => $monthlyGoal->description,
                'Annual goal' => $monthlyGoal->annualGoal->description,
                'Life Area' => $monthlyGoal->annualGoal->longTermVision->lifeArea->designation
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Ocorreu um erro inesperado.',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token expirado ou inválido! Faça login novamente'
            ], 401);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $user = JWTAuth::parseToken()->authenticate();

            $monthlyGoal = MonthlyGoal::where('id', $id)
                ->where('user_id', $user->id)
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

            return response()->json([
                'status' => false,
                'message' => 'Falha ao atualizar objetivo mensal, tente novamente mais tarde',
                'error' => $e->getMessage()
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

            $monthlyGoal = MonthlyGoal::where('id', $id)
                ->where('user_id', $user->id)
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

            return response()->json([
                'status' => false,
                'message' => 'Falha ao apagar objetivo mensal, tente novamente mais tarde',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token expirado ou inválido! Faça login novamente'
            ], 401);
        }
    }
}
