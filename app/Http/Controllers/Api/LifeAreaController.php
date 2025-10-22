<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LifeArea;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class LifeAreaController extends Controller
{

    /**
     * Listar todas as areas da vida padrao e do usuario autenticado
     */

    public function index(): JsonResponse
    {

        try {
            $userId = auth()->id();

            $lifeAreas = LifeArea::where('is_default', true)
                ->orWhere('user_id', $userId)
                ->get();

            return response()->json([
                'status' => true,
                'data' => $lifeAreas
            ], 200);
        } catch (Exception $e) {

            return $this->errorResponse($e);
        }
    }


    /**
     * Exibir uma area da vida especifica
     */

    public function show($id): JsonResponse
    {
        try {

            $userId = auth()->id();

            $lifeArea = LifeArea::find($id);

            if (!$lifeArea || ($lifeArea->user_id !== $userId && !$lifeArea->is_default)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Área da vida não encontrada.'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $lifeArea
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // public function createAdm(Request $request): JsonResponse
    // {
    //     try {
    //         $request->validate([
    //             'designation' => 'required|string|max:55',
    //             'icon_path' => 'required|string'
    //         ]);

    //         $lifeArea = LifeArea::create([
    //             'user_id' => 0,
    //             'designation' => $request->designation,
    //             'icon_path' => $request->icon_path,
    //             'is_default' => true
    //         ]);

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Área de vida criada com sucesso',
    //             'data' => $lifeArea
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Erro interno, Volte a tentar mais tarde',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }


    /**
     * Criar uma nova area da vida.
     */

    public function store(Request $request): JsonResponse
    {

        $request->validate([
            'designation' => 'required|string|max:55',
            'icon_path' => 'required|string'
        ]);

        DB::beginTransaction();

        try {

            $userId = auth()->id();

            $lifeArea = LifeArea::create([
                'user_id' =>  $userId,
                'designation' => $request->designation,
                'icon_path' => $request->icon_path,
                'is_default' => false
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Área de vida criada com sucesso',
                'data' => $lifeArea
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    /**
     * Atualizar uma area da vida criada por Utilizador.
     */

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'designation' => 'required|string|max:255',
            'icon_path' => 'required|string'
        ]);

        DB::beginTransaction();


        try {

            $userId = auth()->id();

            $lifeArea = LifeArea::find($id);

            if (!$lifeArea) {
                return response()->json([
                    'status' => false,
                    'message' => 'Área de vida não encontrada.'
                ], 404);
            }

            if ($lifeArea->user_id !== $userId || $lifeArea->is_default) {
                return response()->json([
                    'status' => false,
                    'message' => 'Você não tem permissão para editar esta área de vida.'
                ], 403);
            }

            $lifeArea->update([
                'designation' => $request->designation,
                'icon_path' => $request->icon_path
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Área de vida atualizada com sucesso.',
                'data' => $lifeArea
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    /**
     * Deletar uma area da vida.
     */

    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();

        try {

            $userId = auth()->id();

            $lifeArea = LifeArea::find($id);

            if (!$lifeArea) {
                return response()->json([
                    'status' => false,
                    'message' => 'Área de vida não encontrada.'
                ], 404);
            }


            if ($lifeArea->user_id !== $userId || $lifeArea->is_default) {
                return response()->json([
                    'status' => false,
                    'message' => 'Você não tem permissão para apagar esta área de vida.'
                ], 403);
            }


            $lifeArea->delete();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Área de vida deletada com sucesso.'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    /**
     * Resposta padronizada de erro.
     */

    private function errorResponse(Exception $e): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Erro interno, volte a tentar mais tarde.',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
