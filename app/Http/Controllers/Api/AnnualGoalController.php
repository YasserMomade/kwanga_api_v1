<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnnualGoal;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class AnnualGoalController extends Controller
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

            $annualGoal = AnnualGoal::where('user_id', $user->id)
                ->with(['longTermVision:id,description'])->get();

            return response()->json([
                'status' => true,
                'annual goals' => $annualGoal
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

            $annualGoal = AnnualGoal::create([
                'user_id' =>  $user->id,
                'longTermVision_id' => $request->longTermVision_id,
                'description' => $request->description,
                'year' => $request->year,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'massage' => 'Objectivo anual Criado com Secesso',
                'Annual goals' => $annualGoal
            ], 200);
        } catch (Exception   $e) {
            DB::rollBack();


            return response()->json([

                'user_id' =>  $user->id,
                'longTermVision_id' => $request->longTermVision_id,
                'description' => $request->description,
                'status' => $request->status,
                'year' => $request->year
            ]);
            return response()->json([
                'Message' => "Falha ao criar objectivo anual, volte a tentar mais tarde",
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $userId = $user->id;

        try {
            DB::beginTransaction();

            $annualGoal = AnnualGoal::where('id', $id)->where('user_id', $userId)->first();

            if (!$annualGoal) {
                return response()->json([
                    'status' => false,
                    'Message' => 'Objectivo anual não encontrada.'
                ], 404);
            }

            $annualGoal->update([
                'user_id' =>  $user->id,
                'longTermVision_id' => $request->longTermVision_id,
                'description' => $request->description,
                'status' => $request->status,
                'year' => $request->year
            ]);


            DB::commit();

            //$longTermVision->load('lifeArea');

            return response()->json([
                'status' => true,
                'Message' => 'Objectivo anual atualizada com sucesso.',
                'Objectivo anual' => $annualGoal
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Message' => "Falha ao atualizar Objectivo anual, tente novamente mais tarde.",
                'Erro' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $user = JWTAuth::parseToken()->authenticate();


            $annualGoal = AnnualGoal::find($id);

            if (!$annualGoal) {
                return response()->json([
                    'status' => false,
                    'Message' => 'Objectivo anual não encontrada.'
                ], 404);
            }


            $annualGoal->delete();
            DB::commit();

            return response()->json([
                'status' => true,
                'Message' => 'Objectivo anual eliminado com sucesso.'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Message' => "Falha ao deletar o Objectivo anual.",
                'erro' => $e->getMessage()
            ], 500);
        }
    }
}
