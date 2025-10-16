<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\project;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProjectController extends Controller
{
    
    public function index(){

         try {

            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Usuário não autenticado.'
                ], 401);
            }

            $project = project::where('user_id', $user->id)
                ->with(['MonthlyGoals:id,month,description'])->get();

            return response()->json([
                'status' => true,
                'Projects' => $project
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Ocorreu um erro inesperado.',
                'error' => $e->getMessage()
            ], 500);
        }

    }

    public function create(Request $request){

DB::beginTransaction();

        try {

            $user = JWTAuth::parseToken()->authenticate();

            $project = project::create([
                'user_id' =>  $user->id,
                'monthly_goals_id' => $request->monthly_goals_id,
                'designation' => $request->designation,
                'purpose' => $request->purpose,
                'expected_result' => $request->expected_result,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'massage' => 'Projeto Criado com Secesso',
                'Project' => $project
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'Message' => "Falha ao criar Projeto, volte a tentar mais tarde",
                'error' => $e->getMessage()
            ], 500);
        }

    }

}
