<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Models\ListModel;
use Exception;
use Facade\FlareClient\Http\Response;
use GuzzleHttp\Psr7\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListController extends Controller
{

    /**
     * Listar todas as litas  do usuario autenticado
     */

    public function index(Request $request): JsonResponse
    {

        try {
            $user_id = auth()->id();

            $query = ListModel::where('user_id', $user_id);

            if ($request->has('type')) {

                $query->where('type', $request->type);
            }

            $lists = $query->get();

            return response()->json([
                'status' => true,
                'data' => $lists
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponce($e);
        }
    }

    /**
     * Criar uma nova lita
     */

    public function store(ListRequest $request): JsonResponse
    {
        $userId = auth()->id();

        DB::beginTransaction();
        try {

            $list = ListModel::create([
                'user_id' => $userId,
                'designation' => $request->designation,
                'type' => $request->type,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Lista criada com sucesso",
                'data' => $list
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponce($e);
        }
    }

    public function show($id): JsonResponse
    {

        $userId = auth()->id();

        try {
            $list = ListModel::where('id', $id)->where('user_id', $userId)->first();


            if (!$list) {
                return response()->json([
                    'status' => false,
                    'message' => 'lista não  encontrada'
                ], 404);
            }

            return Response()->json([
                'status' => true,
                'data' => $list
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponce($e);
        }
    }

    /**
     * Atualizar uma lista
     */


    public function update(ListRequest $request, $id): JsonResponse
    {

        $userId = auth()->id();

        DB::beginTransaction();


        try {

            $list = ListModel::where('user_id', $userId)->findOrFail($id);


            if (!$list) {
                return response()->json([
                    'status' => false,
                    'message' => 'Lista não encontrada'
                ], 404);
            }

            $list->update([
                'designation' => $request->designation,
                'type' => $request->type
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Atualizado com sucesso',
                'data' => $list
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponce($e);
        }
    }


    /**
     * Eliminar uma lista
     */



    public function destroy($id)
    {

        DB::beginTransaction();

        $userId = auth()->id();

        try {

            $list = ListModel::where('id', $id)->where('user_id', $userId)->first();

            if (!$list) {
                return response()->json([
                    'status' => false,
                    'message' => 'Lista não encontrada'
                ], 404);
            }

            $list->delete();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Eliminado'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponce($e);
        }
    }

    /**
     * Eliminar mais de uma lista
     */


    public function destroyMultiple(Request $request)
    {

        DB::beginTransaction();

        $userId = auth()->id();

        try {

            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer'
            ]);


            $lists = ListModel::whereIn('id', $request->ids)
                ->where('user_id', $userId)
                ->get();

            if ($lists->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Nenhuma lista encontrada para eliminar'
                ], 404);
            }

            // Apaga todas as listas encontradas
            ListModel::whereIn('id', $lists->pluck('id'))->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Listas eliminadas com sucesso'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponce($e);
        }
    }



    /**
     * Resposta padronizada de erro.
     */


    private function errorResponce(Exception $e): JsonResponse
    {

        return response()->json([
            'status' => false,
            'message' => "Erro interno, volte a tentar mais tarde.",
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
