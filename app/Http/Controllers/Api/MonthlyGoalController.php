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
        }
    }
}
