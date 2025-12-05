<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectAction;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Scalar\String_;

class ProjectActionController extends Controller
{
    private function getUserId(Request $request): int
    {
        if (auth()->check()) {
            $authId = auth()->id();

            if ($request->has('user_id') && (int) $request->user_id !== $authId) {
                abort(response()->json([
                    'status'  => false,
                    'message' => 'O ID do utilizador enviado não corresponde ao autenticado.'
                ], 403));
            }

            return $authId;
        }

        if ($request->has('user_id')) {
            return (int) $request->user_id;
        }

        abort(response()->json([
            'status'  => false,
            'message' => 'Identificação de utilizador necessária.'
        ], 401));
    }

    /**
     * Listar acoes de projeto.
     * Se vier project_id no request, filtra por projeto.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);

            $query = ProjectAction::query()
                ->whereHas('project', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->orderBy('order_index');

            if ($request->has('project_id')) {
                $query->where('project_id', $request->project_id);
            }

            $actions = $query->get();

            return response()->json([
                'status' => true,
                'data'   => $actions
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Exibir uma acao especifica.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);

            $action = ProjectAction::where('id', $id)
                ->whereHas('project', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->first();

            if (! $action) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Ação não encontrada.'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data'   => $action
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Criar / atualizar (via id) uma acao de projeto.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id'          => 'required|string',
            'project_id'  => 'required|string|exists:projects,id',
            'description' => 'required|string',
            'order_index' => 'nullable|integer',
            'is_done'     => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            // garante que o projeto pertence ao user
            $project = Project::where('id', $request->project_id)
                ->where('user_id', $userId)
                ->first();

            if (! $project) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Projeto não encontrado para este utilizador.'
                ], 404);
            }

            $action = ProjectAction::updateOrCreate(
                ['id' => $request->id],
                [
                    'project_id'  => $request->project_id,
                    'description' => $request->description,
                    'order_index' => $request->input('order_index', 0),
                    'is_done'     => $request->boolean('is_done', false),
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Ação guardada com sucesso.',
                'data'    => $action
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    /**
     * Atualizar uma ação específica.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'description' => 'sometimes|required|string',
            'order_index' => 'nullable|integer',
            'is_done'     => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $action = ProjectAction::where('id', $id)
                ->whereHas('project', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->first();

            if (! $action) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Ação não encontrada.'
                ], 404);
            }

            $data = $request->only([
                'description',
                'order_index',
            ]);

            if ($request->has('is_done')) {
                $data['is_done'] = $request->boolean('is_done');
            }


            $action->update($data);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Ação atualizada com sucesso.',
                'data'    => $action
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }


    public function isDone(Request $request, String $id)
    {

        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);

            $action = ProjectAction::where('id', $id)
                ->whereHas('project', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->first();

            if (! $action) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Ação não encontrada.'
                ], 404);
            }


            $action->is_done = true;
            $action->save();
            DB::commit();


            return response()->json([
                'status'  => true,
                'message' => 'Ação marcada como concluida.',
                'data'    => $action
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }


    public function toggleDone(Request $request, String $id)
    {

        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);


            $action = ProjectAction::where('id', $id)
                ->whereHas('project', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->first();

            if (! $action) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Ação não encontrada.'
                ], 404);
            }

            $action->is_done  = !$action->is_done;

            $action->save();
            DB::commit();
            return response()->json([
                'status'  => true,
                'message' => $action->is_done ? 'Ação marcada comoconcluida.' : 'Ação marcada como não concluida.',
                'data'    => $action
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }




    /**
     * Mover uma ação.
     */

    public function move(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'new_project_id' => 'required|string|exists:projects,id',
            'order_index'    => 'nullable|integer',
            'user_id'        => 'nullable|integer',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            // Ação que queremos mover (tem de pertencer a um projeto do user)
            $action = ProjectAction::where('id', $id)
                ->whereHas('project', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->first();

            if (! $action) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Ação não encontrada.'
                ], 404);
            }

            // Projeto de destino tambem tem de ser do mesmo user
            $newProject = Project::where('id', $request->new_project_id)
                ->where('user_id', $userId)
                ->first();

            if (! $newProject) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Projeto de destino não encontrado para este utilizador.'
                ], 404);
            }

            // Definir order_index na lista do projeto de destino
            if ($request->has('order_index')) {
                $orderIndex = (int) $request->order_index;
            } else {
                // se não vier, mete no fim
                $max = ProjectAction::where('project_id', $newProject->id)->max('order_index');
                $orderIndex = is_null($max) ? 0 : $max + 1;
            }

            $action->update([
                'project_id'  => $newProject->id,
                'order_index' => $orderIndex,
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Ação movida com sucesso.',
                'data'    => $action
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }



    /**
     * Mover multiplas acoes de uma vez.
     *
     * Espera algo assim no body:
     * {
     *   "ids": ["acao-1", "acao-2"],
     *   "new_project_id": "proj-123",
     *   "order_index": 5 // opcional, índice inicial
     * }
     */
    public function moveMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'ids'           => 'required|array|min:1',
            'ids.*'         => 'string',
            'new_project_id' => 'required|string|exists:projects,id',
            'order_index'   => 'nullable|integer',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);
            $ids    = $request->ids;


            $newProject = Project::where('id', $request->new_project_id)
                ->where('user_id', $userId)
                ->first();

            if (! $newProject) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Projeto de destino não encontrado'
                ], 404);
            }

            // Buscar acoes que pertençam a este utilizador (via projeto)
            $actions = ProjectAction::whereIn('id', $ids)
                ->whereHas('project', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->orderBy('order_index')
                ->get();

            if ($actions->isEmpty()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Nenhuma das ações foi encontrada'
                ], 404);
            }

            // Definir order_index inicial
            if ($request->has('order_index')) {
                $currentIndex = (int) $request->order_index;
            } else {
                $max = ProjectAction::where('project_id', $newProject->id)->max('order_index');
                $currentIndex = is_null($max) ? 0 : $max + 1;
            }

            $updatedActions = [];

            foreach ($actions as $action) {
                $action->project_id  = $newProject->id;
                $action->order_index = $currentIndex++;
                $action->save();
                $updatedActions[] = $action;
            }

            DB::commit();

            return response()->json([
                'status'        => true,
                'message'       => count($updatedActions) . 'Ações movidas com sucesso.',
                'target_project' => $newProject->title,
                'ids'           => $ids,
                'data'          => $updatedActions,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }



    /**
     * Remover uma aco.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $action = ProjectAction::where('id', $id)
                ->whereHas('project', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->first();

            if (! $action) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Ação não encontrada.'
                ], 404);
            }

            $action->delete();

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Ação eliminada.'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    public function destroyMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'string',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);
            $ids    = $request->ids;

            $query = ProjectAction::whereIn('id', $ids)
                ->whereHas('project', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });

            $foundCount = $query->count();

            if ($foundCount === 0) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Nenhuma das ações foi encontrada.'
                ], 404);
            }

            $query->delete();

            DB::commit();

            return response()->json([
                'status'        => true,
                'message'       => "$foundCount Ações eliminadas com sucesso."
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    private function errorResponse(Exception $e): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => 'Erro interno, volte a tentar mais tarde.',
            'error'   => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
