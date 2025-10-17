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

    public function create(Request $request)
    {

        DB::beginTransaction();

        try {

            $user = JWTAuth::parseToken()->authenticate();

            $project = project::create([
                'user_id' =>  $user->id,
                'monthly_goals_id' => $request->monthly_goals_id,
                'designation' => $request->designation,
                'purpose' => $request->purpose,
                'expected_result' => $request->expected_result,
                'priority' => $request->priority,
                'first_step' => $request->first_step

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
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json([
                'status' => false,
                'message' => 'TOken expirado ou invalido! Faca login novamente'
            ], 401);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $user = JWTAuth::parseToken()->authenticate();


            $project = project::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$project) {
                return response()->json([
                    'status' => false,
                    'message' => 'Projeto não encontrado'
                ], 404);
            }


            $project->update([
                'monthly_goals_id' => $request->monthly_goals_id ?? $project->monthly_goals_id,
                'designation' => $request->designation ?? $project->designation,
                'purpose' => $request->purpose ?? $project->purpose,
                'expected_result' => $request->expected_result ?? $project->expected_result,
                'priority' => $request->priority ?? $project->priority,
                'first_step' => $request->first_step ?? $project->first_step
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Projeto atualizado com sucesso',
                'Project' => $project
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Falha ao atualizar projeto, tente novamente mais tarde',
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

            $project = project::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$project) {
                return response()->json([
                    'status' => false,
                    'message' => 'Projeto não encontrado'
                ], 404);
            }

            $project->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Projeto apagado com sucesso'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Falha ao apagar projeto, tente novamente mais tarde',
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

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Usuário não autenticado.'
                ], 401);
            }

            $project = project::where('id', $id)
                ->where('user_id', $user->id)
                ->with(['MonthlyGoals:id,month,description'])
                ->first();

            if (!$project) {
                return response()->json([
                    'status' => false,
                    'message' => 'Projeto não encontrado.'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'Project' => $project->designation,
                'purpose' => $project->purpose,
                'expected result' => $project->expected_result,
                'Monthy goals' => $project->MonthlyGoals->description,
                'Month' => $project->MonthlyGoals->month,
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




    // public function show($id)
    // {

    //     $project = Project::with('tasks')->findOrFail($id);

    //     return response()->json([
    //         'status' => true,
    //         'Project' => $project
    //     ]);
    // }

}
