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

    public function index(Request $request): JsonResponse
    {

        try {

            $userId = $this->getUserId($request);


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
            'description' => 'required|string',
            'deadline' => 'nullable'
        ]);


        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);

            $longTermVision = LongTermVision::updateOrCreate(
                ['id' => $request->id],
                [
                    'user_id' =>  $userId,
                    'life_area_id' => $request->life_area_id,
                    'description' => $request->description,
                    'deadline' => $request->deadline,
                    'created_at' => $request->created_at ?? now(),
                    'updated_at' => $request->updated_at ?? now()
                ]
            );


            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Visão a longo praza salva com sucesso',
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

    public function show(Request $request, $id): JsonResponse
    {

        try {

            $userId = $this->getUserId($request);

            $longTermVision = LongTermVision::where('id', $id)->where('user_id', $userId)
                ->with(['lifeArea:id,designation'])->first();


            if (!$longTermVision) {
                return response()->json([
                    'status' => false,
                    'message' => ' Visão a longo prazo não  encontrada'
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
            'life_area_id' => 'sometimes|exists:life_areas,id',
            'description' => 'sometimes|string|max:255',
            'deadline' => 'nullable'
        ]);

        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);

            $longTermVision = LongTermVision::where('id', $id)->where('user_id', $userId)->first();

            if (!$longTermVision) {
                return response()->json([
                    'status' => false,
                    'message' => 'Visão a Longo Prazo não encontrada.'
                ], 404);
            }

            $data = $request->only([
                'life_area_id',
                'description',
                'deadline'
            ]);

            $data['updated_at'] = $request->updated_at ?? now();

            $longTermVision->update($data);


            DB::commit();

            //$longTermVision->load('lifeArea');

            return response()->json([
                'status' => true,
                'message' => 'Visão a longo prazo atualizada com sucesso.',
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

    public function destroy(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);

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
