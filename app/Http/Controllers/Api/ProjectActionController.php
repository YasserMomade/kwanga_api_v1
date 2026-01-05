<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectAction;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Task;
use App\Models\ListModel;

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
     * Criar / atualizar uma acao de projeto.
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
                    'created_at' => $request->created_at ?? now(),
                    'updated_at' => $request->updated_at ?? now()
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Tarefa salva com sucesso.',
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

            /** @var ProjectAction|null $action */
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

            // Dados a atualizar na ação
            $data = $request->only([
                'description',
                'order_index',
            ]);

            if ($request->has('is_done')) {
                $data['is_done'] = $request->boolean('is_done');
            }

            $data['updated_at'] = $request->updated_at ?? now();

            $action->update($data);

            // --- Sincronizar com Task associada, se existir ---
            $taskUpdate = [];

            if (array_key_exists('description', $data)) {
                $taskUpdate['designation'] = $action->description;
            }

            if (array_key_exists('is_done', $data)) {
                $taskUpdate['completed'] = $action->is_done;
            }

            if (! empty($taskUpdate)) {
                // Atualiza todas as tasks ligadas por project_action_id
                Task::where('linked_action_id', $action->id)->update($taskUpdate);
            }

            DB::commit();

            //recarrega a relacao
            $action->load('task');

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


    public function toggleDone(Request $request, string $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            /** @var ProjectAction|null $action */
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

            // Alternar status da acao do projeto
            $action->is_done = ! $action->is_done;
            $action->save();

            // Sincronizar diretamente nas tasks ligadas a esta tarefa
            Task::where('linked_action_id', $action->id)
                ->update(['completed' => $action->is_done]);

            // 3)  recarrega a relacao para devolver com a task atualizada
            $action->load('task');

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => $action->is_done
                    ? 'Ação marcada como concluída.'
                    : 'Ação marcada como não concluída.',
                'data'    => $action,
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
     * 
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


    /**
     * Copiar uma accao de projeto para uma lista de accao 
     * e manter ligacao bidirecional.
     */


    public function linkToActionList(Request $request, string $id): JsonResponse
    {

        $request->validate([
            'list_id' => 'nullable|string|exists:lists,id'
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

            // Verficar se o type da lista e action
            if ($request->filled('list_id')) {
                $list = ListModel::where('id', $request->list_id)
                    ->where('user_id', $userId)
                    ->where('type', 'action')
                    ->first();

                if (! $list) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Lista de destino não encontrada ou não é do tipo action.'
                    ], 404);
                }
            } else {
                // Se nao vier list_id, pega a primeira lista type=action do utilizador
                $list = ListModel::where('user_id', $userId)
                    ->where('type', 'action')
                    ->first();
            }

            //Criar ou atualizar a tarefa ligada a esta ProjectAction

            if ($action->task) {
                // Já existia task ligada entao so atualiza e muda de lista se preciso
                $task = $action->task;
                $task->list_id     = $list->id;
                $task->designation = $action->description;
                $task->completed   = $action->is_done;
                $task->save();
            } else {
                // Ainda nao existe task ligada entao cria
                $task = Task::updateOrcreate(
                    ['id' => $request->id],
                    [
                        'user_id'          => $userId,
                        'list_id'          => $list->id,
                        'designation'      => $action->description,
                        'completed'        => $action->is_done ?? false,
                        'linked_action_id' => $action->id,
                        'has_due_date'     => false,
                        'has_reminder'     => false,
                        'has_frequency'    => false,
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Tarefa associada a lista ',
                'data'    => [
                    'project_action' => $action->fresh('task'),
                    'task'           => $task,
                ],
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
