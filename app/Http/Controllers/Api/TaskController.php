<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Models\ListModel;
use App\Models\Project;
use App\Models\Task;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    private function getUserId(Request $request): int
    {
        if (auth()->check()) {
            $authId = auth()->id();

            if ($request->has('user_id') && (int) $request->user_id !== $authId) {
                abort(response()->json([
                    'status'  => false,
                    'message' => 'O ID do utilizador enviado nao corresponde ao autenticado.'
                ], 403));
            }

            return $authId;
        }

        if ($request->has('user_id')) {
            return (int) $request->user_id;
        }

        abort(response()->json([
            'status'  => false,
            'message' => 'Identificacao de utilizador necessaria.'
        ], 401));
    }

    // Listar tarefas do utilizador

    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);

            $query = Task::where('user_id', $userId);

            // Filtrar por completed
            if ($request->has('completed')) {
                $val = $request->completed;
                $completed = is_string($val)
                    ? (int) filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (int) $val
                    : (int) $val;

                $query->where('completed', $completed ? 1 : 0);
            }

            // Filtrar por list_id
            if ($request->has('list_id')) {
                $query->where('list_id', $request->list_id);
            }

            // Filtrar por project_id
            if ($request->has('project_id')) {
                $query->where('project_id', $request->project_id);


                $query->orderByRaw('CASE WHEN order_index IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('order_index')
                    ->orderByDesc('created_at');
            } else {

                $query->orderByDesc('created_at');
            }

            // Filtrar por tipo de lista
            if ($request->has('type')) {
                $type = $request->type;

                $query->whereNotNull('list_id')
                    ->whereHas('list', function ($q) use ($type) {
                        $q->where('type', $type);
                    });
            }

            $tasks = $query->with('list:id,designation,type')->get();


            $data = $tasks->map(function ($t) {
                $arr = $t->toArray();
                $arr['list_type'] = $t->list ? $t->list->type : null;
                return $arr;
            });

            return response()->json([
                'status' => true,
                'data'   => $data
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function indexByProject(Request $request, string $projectId): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);


            $project = Project::where('id', $projectId)
                ->where('user_id', $userId)
                ->first();

            if (! $project) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Projeto nao encontrado .'
                ], 404);
            }

            $query = Task::where('user_id', $userId)
                ->where('project_id', $projectId);

            // Filtro completed 
            if ($request->has('completed')) {
                $val = $request->completed;
                $completed = is_string($val)
                    ? (int) filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (int) $val
                    : (int) $val;

                $query->where('completed', $completed ? 1 : 0);
            }

            // Ordenacao do projecto
            $query->orderByRaw('CASE WHEN order_index IS NULL THEN 1 ELSE 0 END')
                ->orderBy('order_index')
                ->orderByDesc('created_at');

            $tasks = $query->with('list:id,designation,type')->get();

            $data = $tasks->map(function ($t) {
                $arr = $t->toArray();
                $arr['list_type'] = $t->list ? $t->list->type : null;
                return $arr;
            });

            return response()->json([
                'status' => true,
                'data'   => $data
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function indexByList(Request $request, string $listId): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);


            $list = ListModel::where('id', $listId)
                ->where('user_id', $userId)
                ->first();

            if (! $list) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Lista nao encontrada.'
                ], 404);
            }

            $query = Task::where('user_id', $userId)
                ->where('list_id', $listId)
                ->orderByDesc('created_at');

            // Filtro completed
            if ($request->has('completed')) {
                $val = $request->completed;
                $completed = is_string($val)
                    ? (int) filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (int) $val
                    : (int) $val;

                $query->where('completed', $completed ? 1 : 0);
            }

            $tasks = $query->with('list:id,designation,type')->get();

            $data = $tasks->map(function ($t) {
                $arr = $t->toArray();
                $arr['list_type'] = $t->list ? $t->list->type : null;
                return $arr;
            });

            return response()->json([
                'status' => true,
                'data'   => $data
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }



    // Mostrar uma tarefa especifica
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);

            $task = Task::where('id', $id)
                ->where('user_id', $userId)
                ->with('list:id,designation,type')
                ->first();

            if (! $task) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tarefa nao encontrada.'
                ], 404);
            }

            $data = $task->toArray();
            $data['list_type'] = $task->list ? $task->list->type : null;

            return response()->json([
                'status' => true,
                'data'   => $data
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // Criar tarefa

    public function store(TaskRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $projectId = $request->project_id;
            $listId    = $request->list_id;

            if (! $projectId && ! $listId) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Informe project_id ou list_id para criar a tarefa.'
                ], 422);
            }

            if ($projectId) {
                $project = Project::where('id', $projectId)
                    ->where('user_id', $userId)
                    ->first();

                if (! $project) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Projeto nao encontrado .'
                    ], 404);
                }
            }

            $list = null;
            if ($listId) {
                $list = ListModel::where('id', $listId)
                    ->where('user_id', $userId)
                    ->first();

                if (! $list) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Lista nao encontrada.'
                    ], 404);
                }
            }

            $orderIndex = null;
            if ($request->has('order_index')) {
                if (! $projectId) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'order_index so pode ser usado em tarefas ligadas a projectos.'
                    ], 422);
                }
                $orderIndex = (int) $request->order_index;
            }

            // Campos opcionais
            $deadline  = $request->deadline;
            $time      = $request->time;
            $frequency = $request->frequency;

            if ($list && $list->type === 'entry') {
                $deadline  = null;
                $time      = null;
                $frequency = null;
            }

            $completed = (int) ($request->completed ?? 0);
            $completed = $completed ? 1 : 0;

            // Order index para projectos
            if ($projectId) {
                // Conjunto de tarefas do projeto bloqueadas
                Task::where('user_id', $userId)
                    ->where('project_id', $projectId)
                    ->lockForUpdate()
                    ->get();

                if (is_null($orderIndex)) {
                    $max = Task::where('user_id', $userId)
                        ->where('project_id', $projectId)
                        ->max('order_index');

                    $orderIndex = is_null($max) ? 0 : ((int) $max + 1);
                } else {
                    Task::where('user_id', $userId)
                        ->where('project_id', $projectId)
                        ->whereNotNull('order_index')
                        ->where('order_index', '>=', $orderIndex)
                        ->update(['order_index' => DB::raw('order_index + 1')]);
                }
            }

            $task = Task::updateOrCreate(
                ['id' => $request->id],
                [
                    'user_id' => $userId,
                    'list_id' => $listId,
                    'project_id'  => $projectId,
                    'description' => $request->description,
                    'order_index' => $projectId ? $orderIndex : null,
                    'deadline'  => $deadline,
                    'time'      => $time,
                    'frequency' => $frequency,
                    'completed' => $completed,
                    'linked_action_id' => $request->linked_action_id,
                ]
            );

            DB::commit();

            $task->load('list:id,designation,type');

            $data = $task->toArray();
            $data['list_type'] = $task->list ? $task->list->type : null;

            return response()->json([
                'status'  => true,
                'message' => 'Tarefa salva com sucesso',
                'data'    => $data,
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }


    // Atualizar tarefa

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'list_id'     => 'sometimes|nullable|string|exists:lists,id',
            'project_id'  => 'sometimes|nullable|string|exists:projects,id',
            'description' => 'sometimes|string|max:255',
            'deadline'    => 'sometimes|nullable|date',
            'time'        => 'sometimes|nullable|date',
            'frequency'   => 'sometimes|nullable|array',
            'frequency.*' => 'string',
            'completed'   => 'sometimes|integer|in:0,1',
            'order_index' => 'sometimes|nullable|integer',
            'linked_action_id' => 'sometimes|nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $task = Task::where('id', $id)
                ->where('user_id', $userId)
                ->with('list:id,designation,type')
                ->first();

            if (! $task) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tarefa nao encontrada.'
                ], 404);
            }

            // Atualizar project_id
            if ($request->has('project_id')) {
                $newProjectId = $request->project_id;

                if ($newProjectId) {
                    $project = Project::where('id', $newProjectId)
                        ->where('user_id', $userId)
                        ->first();

                    if (! $project) {
                        return response()->json([
                            'status'  => false,
                            'message' => 'Projeto nao encontrado .'
                        ], 404);
                    }
                }

                $task->project_id = $newProjectId;

                if (! $newProjectId) {
                    $task->order_index = null;
                }
            }

            // atualizar list_id
            if ($request->has('list_id')) {
                $newListId = $request->list_id;

                if ($newListId) {
                    $newList = ListModel::where('id', $newListId)
                        ->where('user_id', $userId)
                        ->first();

                    if (! $newList) {
                        return response()->json([
                            'status'  => false,
                            'message' => 'Lista nao encontrada.'
                        ], 404);
                    }

                    $task->list_id = $newList->id;

                    if ($newList->type === 'entry') {
                        $task->deadline  = null;
                        $task->time      = null;
                        $task->frequency = null;
                    }
                } else {
                    $task->list_id = null;
                }
            }


            $finalListId    = $task->list_id;
            $finalProjectId = $task->project_id;

            if (! $finalListId && ! $finalProjectId) {
                return response()->json([
                    'status'  => false,
                    'message' => 'A tarefa nao pode ficar sem lista e sem projecto ao mesmo tempo.'
                ], 422);
            }

            if ($request->has('description')) {
                $task->description = $request->description;
            }

            if ($request->has('completed')) {
                $task->completed = (int) $request->completed ? 1 : 0;
            }

            if ($request->has('linked_action_id')) {
                $task->linked_action_id = $request->linked_action_id;
            }


            $task->load('list:id,type');
            $listType = $task->list ? $task->list->type : null;

            if ($listType === 'entry') {
                $task->deadline  = null;
                $task->time      = null;
                $task->frequency = null;
            } else {
                if ($request->has('deadline')) {
                    $task->deadline = $request->deadline;
                }
                if ($request->has('time')) {
                    $task->time = $request->time;
                }
                if ($request->has('frequency')) {
                    $task->frequency = $request->frequency;
                }
            }

            // order_index
            if ($request->has('order_index')) {
                if (! $task->project_id) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'order_index so pode ser usado em tarefas ligadas a projectos.'
                    ], 422);
                }

                $projectId = $task->project_id;
                $newIndex  = is_null($request->order_index) ? null : (int) $request->order_index;

                //Conjunto de tarefas do projeto bloqueadas
                Task::where('user_id', $userId)
                    ->where('project_id', $projectId)
                    ->lockForUpdate()
                    ->get();

                if (is_null($newIndex)) {
                    $task->order_index = null;
                } else {
                    $oldIndex = is_null($task->order_index) ? null : (int) $task->order_index;

                    if (is_null($oldIndex)) {
                        Task::where('user_id', $userId)
                            ->where('project_id', $projectId)
                            ->whereNotNull('order_index')
                            ->where('order_index', '>=', $newIndex)
                            ->update(['order_index' => DB::raw('order_index + 1')]);

                        $task->order_index = $newIndex;
                    } else {
                        if ($newIndex > $oldIndex) {
                            Task::where('user_id', $userId)
                                ->where('project_id', $projectId)
                                ->whereNotNull('order_index')
                                ->where('order_index', '>', $oldIndex)
                                ->where('order_index', '<=', $newIndex)
                                ->update(['order_index' => DB::raw('order_index - 1')]);
                        } elseif ($newIndex < $oldIndex) {
                            Task::where('user_id', $userId)
                                ->where('project_id', $projectId)
                                ->whereNotNull('order_index')
                                ->where('order_index', '>=', $newIndex)
                                ->where('order_index', '<', $oldIndex)
                                ->update(['order_index' => DB::raw('order_index + 1')]);
                        }

                        $task->order_index = $newIndex;
                    }
                }
            }

            $task->save();

            DB::commit();

            $task->load('list:id,designation,type');

            $data = $task->toArray();
            $data['list_type'] = $task->list ? $task->list->type : null;

            return response()->json([
                'status'  => true,
                'message' => 'Tarefa atualizada com sucesso',
                'data'    => $data,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }



    public function moveTask(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'list_id' => 'required|string|exists:lists,id',
        ]);

        try {
            $userId = $this->getUserId($request);

            $task = Task::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (! $task) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tarefa nao encontrada.'
                ], 404);
            }

            // Regra importante:
            // Se a tarefa for de projecto (tem project_id), ela nao pode ser movida para listas
            // Ela so pode ser movida entre projectos (use moveToProject)
            if (! is_null($task->project_id)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Esta tarefa pertence a um projeto. Para mover, use o endpoint de mover para outro projeto.'
                ], 422);
            }

            $newList = ListModel::where('id', $request->list_id)
                ->where('user_id', $userId)
                ->first();

            if (! $newList) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Lista nao encontrada.'
                ], 404);
            }

            $task->list_id = $newList->id;

            // Se mover para entry, limpar campos opcionais
            if ($newList->type === 'entry') {
                $task->deadline  = null;
                $task->time      = null;
                $task->frequency = null;
            }

            $task->save();

            $task->load('list:id,designation,type');

            $data = $task->toArray();
            $data['list_type'] = $task->list ? $task->list->type : null;

            return response()->json([
                'status'  => true,
                'message' => 'Tarefa movida para a lista "' . $newList->designation . '".',
                'data'    => $data,
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function moveMultipleTasks(Request $request): JsonResponse
    {
        $request->validate([
            'ids'     => 'required|array|min:1',
            'ids.*'   => 'string',
            'list_id' => 'required|string|exists:lists,id',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $ids = $request->ids;

            $newList = ListModel::where('id', $request->list_id)
                ->where('user_id', $userId)
                ->first();

            if (! $newList) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Lista nao encontrada.'
                ], 404);
            }

            // Buscar tarefas do utilizador
            $tasks = Task::where('user_id', $userId)
                ->whereIn('id', $ids)
                ->get(['id', 'user_id', 'project_id', 'list_id', 'deadline', 'time', 'frequency']);

            if ($tasks->isEmpty()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Nenhuma das tarefas foi encontrada .'
                ], 404);
            }

            // Regra: tarefas de projecto nao podem ser movidas para listas
            $projectTasksCount = $tasks->whereNotNull('project_id')->count();
            if ($projectTasksCount > 0) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Algumas tarefas pertencem a um projeto e não podem ser movidas para listas. Use o endpoint de mover para projeto.',
                    'data'    => [
                        'blocked_count' => $projectTasksCount,
                        'blocked_ids'   => $tasks->whereNotNull('project_id')->pluck('id')->values(),
                    ]
                ], 422);
            }

            // Atualização em massa
            $updateData = ['list_id' => $newList->id];

            // Se mover para entry, limpar campos opcionais
            if ($newList->type === 'entry') {
                $updateData['deadline']  = null;
                $updateData['time']      = null;
                $updateData['frequency'] = null;
            }

            $movedCount = Task::where('user_id', $userId)
                ->whereIn('id', $tasks->pluck('id'))
                ->update($updateData);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => $movedCount . ' tarefas movidas para a lista "' . $newList->designation . '".',
                'data'    => [
                    'moved_count' => $movedCount,
                    'list' => [
                        'id'          => $newList->id,
                        'designation' => $newList->designation,
                        'type'        => $newList->type,
                    ],
                ]
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }


    public function moveToProject(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'new_project_id' => 'required|string|exists:projects,id',
            'order_index'    => 'nullable|integer',
            'user_id'        => 'nullable|integer',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            // Buscar task que queremos mover (tem de pertencer ao user)
            $task = Task::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (! $task) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tarefa nao encontrada.'
                ], 404);
            }

            // Regra: so mover entre projectos se a task ja for de projecto
            if (is_null($task->project_id)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Esta tarefa nao pertence a um projeto. Nao pode ser movida entre projetos.'
                ], 422);
            }

            // Projeto de destino tem de ser do mesmo user
            $newProject = Project::where('id', $request->new_project_id)
                ->where('user_id', $userId)
                ->first();

            if (! $newProject) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Projeto de destino nao encontrado .'
                ], 404);
            }

            // Definir order_index no projecto de destino
            if ($request->has('order_index')) {
                $orderIndex = (int) $request->order_index;
            } else {
                // Se nao vier, mete no fim (apenas tarefas do projecto de destino)
                $max = Task::where('user_id', $userId)
                    ->where('project_id', $newProject->id)
                    ->max('order_index');

                $orderIndex = is_null($max) ? 0 : ((int) $max + 1);
            }

            // Mover para o novo projecto
            $task->update([
                'project_id'  => $newProject->id,
                'order_index' => $orderIndex,
            ]);

            DB::commit();

            // Devolver com list_type se existir lista associada
            $task->load('list:id,designation,type');

            $data = $task->toArray();
            $data['list_type'] = $task->list ? $task->list->type : null;

            return response()->json([
                'status'  => true,
                'message' => 'Tarefa movida para o projecto: "' . $newProject->title . '".',
                'data'    => $data
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    public function moveMultipleToProject(Request $request): JsonResponse
    {
        $request->validate([
            'ids'            => 'required|array|min:1',
            'ids.*'          => 'string',
            'new_project_id' => 'required|string|exists:projects,id',
            'order_index'    => 'nullable|integer',
            'user_id'        => 'nullable|integer',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);


            $newProject = Project::where('id', $request->new_project_id)
                ->where('user_id', $userId)
                ->first();

            if (! $newProject) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Projeto de destino nao encontrado .'
                ], 404);
            }

            $ids = $request->ids;


            $tasks = Task::where('user_id', $userId)
                ->whereIn('id', $ids)
                ->get(['id', 'user_id', 'project_id', 'order_index']);

            if ($tasks->isEmpty()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Nenhuma das tarefas foi encontrada .'
                ], 404);
            }

            // Regra: so mover entre projectos 
            $notProjectTasks = $tasks->whereNull('project_id')->pluck('id')->values();
            if ($notProjectTasks->count() > 0) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Algumas tarefas nao pertencem a um projeto. Nao podem ser movidas entre projetos.',
                    'data'    => [
                        'blocked_count' => $notProjectTasks->count(),
                        'blocked_ids'   => $notProjectTasks,
                    ]
                ], 422);
            }

            // Definir order_index inicial
            if ($request->has('order_index')) {
                $startIndex = (int) $request->order_index;
            } else {
                // Se nao vier, mete no fim (apenas tarefas do projecto de destino)
                $max = Task::where('user_id', $userId)
                    ->where('project_id', $newProject->id)
                    ->max('order_index');

                $startIndex = is_null($max) ? 0 : ((int) $max + 1);
            }

            // Para manter consistência, ordena por order_index atual (opcional, mas ajuda)
            $tasks = $tasks->sortBy('order_index')->values();

            // Atualizar uma a uma para garantir order_index sequencial
            foreach ($tasks as $i => $task) {
                $task->update([
                    'project_id'  => $newProject->id,
                    'order_index' => $startIndex + $i,
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => $tasks->count() . ' tarefas movidas para o projecto: "' . $newProject->title . '".',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }



    // Alternar status concluida / nao concluida (0/1)
    public function alterStatus(Request $request, string $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $task = Task::where('id', $id)
                ->where('user_id', $userId)
                ->with('list:id,designation,type')
                ->first();

            if (! $task) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tarefa nao encontrada.'
                ], 404);
            }

            $task->completed = $task->completed ? 0 : 1;
            $task->save();

            DB::commit();

            $data = $task->toArray();
            $data['list_type'] = $task->list ? $task->list->type : null;

            return response()->json([
                'status'  => true,
                'message' => 'Status da tarefa atualizado com sucesso.',
                'data'    => $data
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    public function linkToActionList(Request $request, ?string $id = null): JsonResponse
    {
        $request->validate([
            'list_id'   => 'required|string|exists:lists,id',
            'task_ids'  => 'sometimes|array|min:1',
            'task_ids.*' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);


            $taskIds = $request->filled('task_ids')
                ? $request->input('task_ids')
                : array_filter([$id]);

            if (empty($taskIds)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Selecione uma tarefa.'
                ], 422);
            }


            $list = ListModel::where('id', $request->list_id)
                ->where('user_id', $userId)
                ->where('type', 'action')
                ->first();

            if (! $list) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Lista nao encontrada'
                ], 404);
            }

            $tasks = Task::whereIn('id', $taskIds)
                ->where('user_id', $userId)
                ->get();

            if ($tasks->count() !== count($taskIds)) {
                $foundIds = $tasks->pluck('id')->all();
                $missing  = array_values(array_diff($taskIds, $foundIds));

                return response()->json([
                    'status'  => false,
                    'message' => 'Uma ou mais tarefas nao foram encontradas.',
                    'missing_task_ids' => $missing,
                ], 404);
            }

            $notProjectTasks = $tasks->filter(fn($t) => is_null($t->project_id));

            if ($notProjectTasks->isNotEmpty()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Apenas tarefas de projeto podem ser ligadas a uma lista de acao.',
                    'invalid_task_ids' => $notProjectTasks->pluck('id')->values(),
                ], 422);
            }

            Task::whereIn('id', $taskIds)
                ->where('user_id', $userId)
                ->update(['list_id' => $list->id]);

            DB::commit();

            $updated = Task::whereIn('id', $taskIds)
                ->where('user_id', $userId)
                ->with('list:id,designation,type')
                ->get();

            return response()->json([
                'status'  => true,
                'message' => "Tarefas alocada na lista . $list->designation.",
                'data'    => $updated,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }



    // Eliminar uma unica tarefa
    public function destroy(Request $request, string $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $task = Task::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (! $task) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tarefa nao encontrada.'
                ], 404);
            }

            $task->delete();

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Eliminado.'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }
    public function listOnlyTasks(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);

            $query = Task::where('user_id', $userId)
                ->whereNotNull('list_id')
                ->with([
                    'list:id,designation,type',
                    'project:id,title'
                ])
                ->orderByDesc('created_at');

            // filtrar por list_id especifica
            if ($request->has('list_id')) {
                $query->where('list_id', $request->list_id);
            }

            //filtrar por completed
            if ($request->has('completed')) {
                $completed = filter_var($request->completed, FILTER_VALIDATE_BOOLEAN);
                $query->where('completed', $completed);
            }

            // filtrar por type (tipo da lista)
            if ($request->has('type')) {
                $type = $request->type;
                $query->whereHas('list', function ($q) use ($type) {
                    $q->where('type', $type);
                });
            }

            $tasks = $query->get();

            $data = $tasks->map(function ($t) {
                $arr = $t->toArray();


                $arr['list_type'] = $t->list ? $t->list->type : null;

                // Mensagem de associacao ao projecto (quando existir)
                $arr['associado_ao_projeto'] = $t->project
                    ? ('Associado ao projeto: ' . $t->project->title)
                    : null;

                return $arr;
            });

            return response()->json([
                'status' => true,
                'data'   => $data
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function projectOnlyTasks(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);

            $tasks = Task::where('user_id', $userId)
                ->whereNotNull('project_id')
                ->with([
                    'project:id,title'
                ])
                ->orderByRaw('CASE WHEN order_index IS NULL THEN 1 ELSE 0 END')
                ->orderBy('order_index')
                ->orderByDesc('created_at')
                ->get();

            $data = $tasks->map(function ($task) {
                $item = $task->toArray();

                // Indicacao de associacao
                if ($task->list) {
                    $item['associado_a_lista'] = $task->list->designation;
                } else {
                    $item['associado_a_lista'] = null;
                }

                return $item;
            });

            return response()->json([
                'status' => true,
                'data'   => $data
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }


    // Eliminar varias tarefas
    public function destroyMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'task_ids'   => 'required|array|min:1',
            'task_ids.*' => 'string',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $ids = $request->task_ids;

            // Apenas tarefas do utilizador autenticado
            $query = Task::where('user_id', $userId)
                ->whereIn('id', $ids);

            $foundCount = $query->count();

            if ($foundCount === 0) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Nenhuma das tarefas foi encontrada.'
                ], 404);
            }

            $query->delete();

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => $foundCount . ' tarefas eliminadas com sucesso.',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    public function reorderProjectTask(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'project_id' => 'required|string|exists:projects,id',
            'index'   => 'required|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            // Task do user
            $task = Task::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (! $task) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tarefa nao encontrada.'
                ], 404);
            }

            // Só tarefas de projecto
            if (is_null($task->project_id)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Apenas tarefas de projeto podem ser reordenadas.'
                ], 422);
            }

            // Projeto alvo precisa ser do user
            $project = Project::where('id', $request->project_id)
                ->where('user_id', $userId)
                ->first();

            if (! $project) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Projeto nao encontrado.'
                ], 404);
            }

            $fromProjectId = $task->project_id;
            $toProjectId   = $project->id;

            // Lock nas tarefas dos projectos envolvidos para evitar concorrência
            Task::where('user_id', $userId)
                ->whereIn('project_id', array_values(array_unique([$fromProjectId, $toProjectId])))
                ->whereNotNull('order_index')
                ->lockForUpdate()
                ->get(['id']);

            $oldIndex = is_null($task->order_index) ? null : (int) $task->order_index;
            $toIndex  = (int) $request->to_index;

            // Limita toIndex ao máximo permitido no destino (insere no fim se passar)
            $maxDest = Task::where('user_id', $userId)
                ->where('project_id', $toProjectId)
                ->whereNotNull('order_index')
                ->where('id', '!=', $task->id)
                ->max('order_index');

            $maxDest = is_null($maxDest) ? -1 : (int) $maxDest;

            if ($toIndex > $maxDest + 1) {
                $toIndex = $maxDest + 1;
            }

            // Se for o mesmo projeto e não mudou nada
            if ($fromProjectId === $toProjectId && ! is_null($oldIndex) && $toIndex === $oldIndex) {
                DB::commit();

                $tasks = Task::where('user_id', $userId)
                    ->where('project_id', $toProjectId)
                    ->orderByRaw('CASE WHEN order_index IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('order_index')
                    ->orderByDesc('created_at')
                    ->get();

                return response()->json([
                    'status'  => true,
                    'message' => 'Sem alterações na ordem.',
                    'data'    => [
                        'moved_task_id' => $task->id,
                        'project_id'    => $toProjectId,
                        'to_index'      => $toIndex,
                        'tasks'         => $tasks,
                    ],
                ], 200);
            }

            // ===========================
            // Caso 1: mesmo projeto
            // ===========================
            if ($fromProjectId === $toProjectId) {

                // Se por algum motivo a tarefa está sem order_index, tratamos como inserção
                if (is_null($oldIndex)) {
                    // abre espaço no destino
                    Task::where('user_id', $userId)
                        ->where('project_id', $toProjectId)
                        ->whereNotNull('order_index')
                        ->where('order_index', '>=', $toIndex)
                        ->update(['order_index' => DB::raw('order_index + 1')]);

                    $task->order_index = $toIndex;
                    $task->save();
                } else {

                    // 1) Tira a task do caminho para não violar unique(project_id, order_index)
                    $sentinel = -999999;
                    $task->order_index = $sentinel;
                    $task->save();

                    // 2) Ajusta intervalo
                    if ($toIndex < $oldIndex) {
                        // subiu: [toIndex .. oldIndex-1] descem +1
                        Task::where('user_id', $userId)
                            ->where('project_id', $toProjectId)
                            ->whereNotNull('order_index')
                            ->where('order_index', '>=', $toIndex)
                            ->where('order_index', '<', $oldIndex)
                            ->update(['order_index' => DB::raw('order_index + 1')]);
                    } else {
                        // desceu: [oldIndex+1 .. toIndex] sobem -1
                        Task::where('user_id', $userId)
                            ->where('project_id', $toProjectId)
                            ->whereNotNull('order_index')
                            ->where('order_index', '>', $oldIndex)
                            ->where('order_index', '<=', $toIndex)
                            ->update(['order_index' => DB::raw('order_index - 1')]);
                    }

                    // 3) Coloca a task no índice final
                    $task->order_index = $toIndex;
                    $task->save();
                }
            }
            // ===========================
            // Caso 2: move entre projetos (opcional)
            // ===========================
            else {
                // Se a task não tinha índice, tratamos como "nova" no destino
                // Se tinha, precisamos fechar buraco no projeto origem
                $sentinel = -999999;

                // 1) Tira a task do caminho (evita colisões no projeto origem/destino)
                $task->order_index = $sentinel;
                $task->save();

                // 2) Fecha buraco no projeto origem (tudo que estava depois do oldIndex sobe -1)
                if (! is_null($oldIndex)) {
                    Task::where('user_id', $userId)
                        ->where('project_id', $fromProjectId)
                        ->whereNotNull('order_index')
                        ->where('order_index', '>', $oldIndex)
                        ->update(['order_index' => DB::raw('order_index - 1')]);
                }

                // 3) Abre espaço no projeto destino (>= toIndex desce +1)
                Task::where('user_id', $userId)
                    ->where('project_id', $toProjectId)
                    ->whereNotNull('order_index')
                    ->where('order_index', '>=', $toIndex)
                    ->update(['order_index' => DB::raw('order_index + 1')]);

                // 4) Move para o destino e seta índice final
                $task->project_id  = $toProjectId;
                $task->order_index = $toIndex;
                $task->save();
            }

            DB::commit();

            // Devolve a lista reordenada do projeto destino (útil pro front sincronizar)
            $tasks = Task::where('user_id', $userId)
                ->where('project_id', $toProjectId)
                ->orderByRaw('CASE WHEN order_index IS NULL THEN 1 ELSE 0 END')
                ->orderBy('order_index')
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Ordem atualizada com sucesso.',
                'data'    => [
                    'moved_task_id' => $task->id,
                    'project_id'    => $toProjectId,
                    'to_index'      => $toIndex,
                    'tasks'         => $tasks,
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
            'message' => 'Erro inesperado, volte a tentar mais tarde.',
            'error'   => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
