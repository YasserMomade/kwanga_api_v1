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
     * Lista todos os objetivos anuais do utilizador autenticado.
     */

    public function index(Request $request): JsonResponse
    {


        try {

            $userId = $this->getUserId($request);

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
            'description' => 'required|string',
            'year' => 'required|integer',
        ]);

        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);

            $annualGoal = AnnualGoal::create(
                [

                    'id' => $request->id,
                    'user_id' =>  $userId,
                    'long_term_vision_id' => $request->long_term_vision_id,
                    'description' => $request->description,
                    'year' => $request->year,
                    'created_at' => $request->created_at ?? now(),
                    'updated_at' => $request->updated_at ?? now()
                ]
            );

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Objectivo anual salvo com sucesso',
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
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);

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

        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);

            $annualGoal = AnnualGoal::where('id', $id)->where('user_id', $userId)->first();

            if (!$annualGoal) {
                return response()->json([
                    'status' => false,
                    'message' => 'Objectivo anual não encontrada.'
                ], 404);
            }

            $data = $request->only([
                'long_term_vision_id',
                'description',
                'year'
            ]);

            $data['updated_at'] = $request->updated_at ?? now();

            $annualGoal->update($data);


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

    public function destroy(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();


        try {

            $userId = $this->getUserId($request);

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
