<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LifeAreaRequest;
use App\Models\LifeArea;
use App\Models\UserLifeAreaOrder;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class LifeAreaController extends Controller
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
     * Listar todas as areas da vida padrao e do usuario autenticado
     */

    public function index(Request $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            // Todas as life areas que o user pode ver
            $lifeAreas = LifeArea::where('is_default', true)
                ->orWhere('user_id', $userId)
                ->get();

            // Se não houver nada
            if ($lifeAreas->isEmpty()) {
                DB::commit();
                return response()->json(['status' => true, 'data' => []], 200);
            }

            // Lock nas ordens do user (evita corrida de seed/reorder)
            UserLifeAreaOrder::where('user_id', $userId)
                ->lockForUpdate()
                ->get(['id']);

            $lifeAreaIds = $lifeAreas->pluck('id')->values()->all();

            // Quais já têm ordem
            $existing = UserLifeAreaOrder::where('user_id', $userId)
                ->whereIn('life_area_id', $lifeAreaIds)
                ->pluck('life_area_id')
                ->all();

            $missingIds = array_values(array_diff($lifeAreaIds, $existing));

            // Seed das que faltam (coloca no fim)
            if (!empty($missingIds)) {
                $max = UserLifeAreaOrder::where('user_id', $userId)->max('order_index');
                $max = is_null($max) ? -1 : (int)$max;

                $rows = [];
                foreach ($missingIds as $i => $lifeAreaId) {
                    $rows[] = [
                        'user_id'     => $userId,
                        'life_area_id' => $lifeAreaId,
                        'order_index' => $max + 1 + $i,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }

                UserLifeAreaOrder::insert($rows);
            }

            // Buscar já ordenado pela ordem do user
            $ordered = LifeArea::query()
                ->join('user_life_area_orders as uo', function ($join) use ($userId) {
                    $join->on('uo.life_area_id', '=', 'life_areas.id')
                        ->where('uo.user_id', '=', $userId);
                })
                ->where(function ($q) use ($userId) {
                    $q->where('life_areas.is_default', true)
                        ->orWhere('life_areas.user_id', $userId);
                })
                ->orderBy('uo.order_index')
                ->select('life_areas.*', 'uo.order_index as user_order_index')
                ->get();

            DB::commit();

            return response()->json([
                'status' => true,
                'data'   => $ordered
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }


    /**
     * Exibir uma area da vida especifica
     */

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);

            $userId = (string) $userId;

            $lifeArea = LifeArea::find($id);

            if (!$lifeArea) {
                return response()->json([
                    'status' => false,
                    'message' => 'Área da vida não encontrada.'
                ], 404);
            }


            $ownerId = (string) $lifeArea->user_id;

            if ($ownerId !== $userId && !$lifeArea->is_default) {
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

    public function store(LifeAreaRequest $request): JsonResponse
    {



        $request->validate([
            'designation' => 'required|string|max:55',
            'icon_path' => 'required|string'
        ]);

        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);

            $lifeArea = LifeArea::updateOrCreate(
                ['id' => $request->id],
                [
                    'user_id' =>  $userId,
                    'designation' => $request->designation,
                    'icon_path' => $request->icon_path,
                    'is_default' => false
                ]
            );

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Área da vida salva com sucesso',
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

            $userId = $this->getUserId($request);

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

    public function destroy(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);

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
                'message' => 'Área da vida eliminada com sucesso.'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }


    public function reorder(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'to_index' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $lifeArea = LifeArea::find($id);

            if (! $lifeArea) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Área da vida não encontrada.'
                ], 404);
            }

            // Visibilidade: default OU do user
            if (! $lifeArea->is_default && (int)$lifeArea->user_id !== (int)$userId) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Área da vida não encontrada.'
                ], 404);
            }

            // Lock em TODA a ordem do user (evita concorrência)
            UserLifeAreaOrder::where('user_id', $userId)
                ->lockForUpdate()
                ->get(['id']);

            // Garante que existe um registro de ordem para esse life_area_id
            $orderRow = UserLifeAreaOrder::where('user_id', $userId)
                ->where('life_area_id', $lifeArea->id)
                ->first();

            if (! $orderRow) {
                $max = UserLifeAreaOrder::where('user_id', $userId)->max('order_index');
                $max = is_null($max) ? -1 : (int)$max;

                $orderRow = UserLifeAreaOrder::create([
                    'user_id'      => $userId,
                    'life_area_id' => $lifeArea->id,
                    'order_index'  => $max + 1,
                ]);
            }

            $oldIndex = (int)$orderRow->order_index;
            $toIndex  = (int)$request->to_index;

            // Clamp: não passa do fim
            $max = UserLifeAreaOrder::where('user_id', $userId)
                ->where('life_area_id', '!=', $lifeArea->id)
                ->max('order_index');

            $max = is_null($max) ? -1 : (int)$max;
            if ($toIndex > $max + 1) {
                $toIndex = $max + 1;
            }

            if ($toIndex === $oldIndex) {
                DB::commit();
                return response()->json([
                    'status'  => true,
                    'message' => 'Sem alterações na ordem.',
                    'data'    => ['life_area_id' => $lifeArea->id, 'to_index' => $toIndex]
                ], 200);
            }

            // 1) tira o item movido do caminho (sentinela)
            $sentinel = -999999;
            $orderRow->order_index = $sentinel;
            $orderRow->save();

            // OFFSET grande para evitar colisões com unique durante update em massa
            $OFFSET = 1000000;

            if ($toIndex < $oldIndex) {
                // mover pra cima:
                // intervalo afetado: [toIndex .. oldIndex-1] precisa +1

                // Passo A: empurra intervalo para longe (+OFFSET)
                UserLifeAreaOrder::where('user_id', $userId)
                    ->where('order_index', '>=', $toIndex)
                    ->where('order_index', '<', $oldIndex)
                    ->update(['order_index' => DB::raw("order_index + $OFFSET")]);

                // Passo B: traz de volta com +1 (OFFSET - 1)
                UserLifeAreaOrder::where('user_id', $userId)
                    ->where('order_index', '>=', $toIndex + $OFFSET)
                    ->where('order_index', '<', $oldIndex + $OFFSET)
                    ->update(['order_index' => DB::raw("order_index - " . ($OFFSET - 1))]);
            } else {
                // mover pra baixo:
                // intervalo afetado: [oldIndex+1 .. toIndex] precisa -1

                // Passo A: empurra intervalo para longe (+OFFSET)
                UserLifeAreaOrder::where('user_id', $userId)
                    ->where('order_index', '>', $oldIndex)
                    ->where('order_index', '<=', $toIndex)
                    ->update(['order_index' => DB::raw("order_index + $OFFSET")]);

                // Passo B: traz de volta com -1 (OFFSET + 1)
                UserLifeAreaOrder::where('user_id', $userId)
                    ->where('order_index', '>', $oldIndex + $OFFSET)
                    ->where('order_index', '<=', $toIndex + $OFFSET)
                    ->update(['order_index' => DB::raw("order_index - " . ($OFFSET + 1))]);
            }

            // 3) coloca o item no destino
            $orderRow->order_index = $toIndex;
            $orderRow->save();

            DB::commit();

            // Retorna ordenado (opcional)
            $ordered = LifeArea::query()
                ->join('user_life_area_orders as uo', function ($join) use ($userId) {
                    $join->on('uo.life_area_id', '=', 'life_areas.id')
                        ->where('uo.user_id', '=', $userId);
                })
                ->where(function ($q) use ($userId) {
                    $q->where('life_areas.is_default', true)
                        ->orWhere('life_areas.user_id', $userId);
                })
                ->orderBy('uo.order_index')
                ->select('life_areas.*', 'uo.order_index as user_order_index')
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Ordem atualizada com sucesso.',
                'data'    => $ordered
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
