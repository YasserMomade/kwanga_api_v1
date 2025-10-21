<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListModel;
use App\Models\Task;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{



    /**
     * Listar todas tarefas/concluidas ou nao concluidas /de acordo com a lista do usuario
     */

    public function index(Request $request): JsonResponse
    {

        $userId = auth()->id();

        try {

            $query = Task::where('user_id', $userId);

            //Listas concluidas ou nao concluidas

            if ($request->has('completed')) {
                $completed = filter_var($request->completed, FILTER_VALIDATE_BOOLEAN);
                $query->where('completed', $completed);
            }

            // Listar tarefas de acordo com a lista

            if ($request->has('list_id')) {
                $query->where('list_id', $request->list_id);
            }

            $tasks = $query->orderByDesc('created_at')->get();

            return response()->json([
                'status' => true,
                'data' => $tasks
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Mostrar uma tarefa específica.
     */
    public function show($id): JsonResponse
    {
        try {
            $userId = auth()->id();

            $task = Task::where('id', $id)
                ->where('user_id', $userId)
                ->with(['list:id,designation,type'])
                ->first();

            if (!$task) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tarefa não encontrada.'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $task
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }


    /**
     * Criar tarafas
     */

    public function store(Request $request)
    {

        $request->validate([
            'list_id' => 'required|exists:lists,id',
            'designation' => 'required|string|max:255',
            'completed' => 'boolean',
            'has_due_date' => 'boolean',
            'due_date' => 'nullable|date',
            'has_reminder' => 'boolean',
            'reminder_datetime' => 'nullable|date',
            'has_frequency' => 'boolean',
            'frequency_days' => 'nullable|array',
        ]);


        DB::beginTransaction();

        $userId = auth()->id();

        try {

            $list = ListModel::where('id', $request->list_id)
                ->where('user_id', $userId)
                ->first();

            if (!$list) {
                return response()->json([
                    'status' => false,
                    'message' => 'Lista não encontrada '
                ], 404);
            }

            /**
             * Regras de acordo com o tipo de lista
             */

            if ($list->type === 'entry') {
                // Para listas de entrada so a designacao e obrigatoria
                $taskData = [
                    'user_id' => $userId,
                    'list_id' => $list->id,
                    'designation' => $request->designation,
                    'completed' => false,
                ];
            } else {
                if ($request->has_due_date && !$request->due_date) {
                    return response()->json(['status' => false, 'message' => 'A data de conclusão é obrigatória.'], 422);
                }
                if ($request->has_reminder && !$request->reminder_datetime) {
                    return response()->json(['status' => false, 'message' => 'A data e hora do lembrete são obrigatórias.'], 422);
                }
                if ($request->has_frequency && !$request->frequency_days) {
                    return response()->json(['status' => false, 'message' => 'Os dias de frequência são obrigatórios.'], 422);
                }

                $taskData = [
                    'user_id' => $userId,
                    'list_id' => $list->id,
                    'designation' => $request->designation,
                    'completed' => $request->completed ?? false,
                    'has_due_date' => $request->has_due_date ?? false,
                    'due_date' => $request->due_date,
                    'has_reminder' => $request->has_reminder ?? false,
                    'reminder_datetime' => $request->reminder_datetime,
                    'has_frequency' => $request->has_frequency ?? false,
                    'frequency_days' => $request->frequency_days,
                ];
            }

            $task = Task::create($taskData);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Tarefa criada com sucesso",
                'data' => $task,
            ], 201);
        } catch (Exception $e) {
            DB::rollback();
            return $this->errorResponse($e);
        }
    }

    /**
     * Atualiza uma tarefa existente.
     * Pode mover de uma lista de entrada para uma de ação.
     */

    public function update(Request $request, $id)
    {

        $request->validate([
            'designation' => 'sometimes|string|max:255',
            'completed' => 'sometimes|boolean',
            'list_id' => 'sometimes|exists:lists,id',
            'has_due_date' => 'boolean',
            'due_date' => 'nullable|date',
            'has_reminder' => 'boolean',
            'reminder_datetime' => 'nullable|date',
            'has_frequency' => 'boolean',
            'frequency_days' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $userId = auth()->id();

            $task = Task::where('id', $id)->where('user_id', $userId)->firstOrFail();

            /**
             * Parte para mudar uma tarefa de lista
             */

            if ($request->has('list_id') && $request->list_id != $task->list_id) {

                $newList = ListModel::where('id', $request->list_id)->where('user_id', $userId)->firstOrFail();

                $task->lisr_id = $newList->id;

                $task->fill($request->only([
                    'designation',
                    'completed',
                    'has_due_date',
                    'due_date',
                    'has_reminder',
                    'reminder_datetime',
                    'has_frequency',
                    'frequency_days',
                ]));


                $task->save();

                return response()->json([
                    'status' => true,
                    'message' => "Tarefa atualizada com sucesso",
                    'data' => $task,
                ], 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    /**
     * Alternar o status concluida / nao concluida.
     */
    public function alterStatus($id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = auth()->id();

            $task = Task::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$task) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tarefa não encontrada.'
                ], 404);
            }

            $task->completed = !$task->completed;
            $task->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Status da tarefa atualizado com sucesso.',
                'data' => $task
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    /**
     * Eliminar uma unica tarefa.
     */
    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = auth()->id();

            $task = Task::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$task) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tarefa não encontrada.'
                ], 404);
            }

            $task->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Tarefa eliminada com sucesso.'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    /**
     * Eliminar varias tarefas de uma vez
     */
    public function destroyMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'task_ids' => 'required|array|min:1',
            'task_ids.*' => 'integer|exists:tasks,id',
        ]);

        DB::beginTransaction();

        try {
            $userId = auth()->id();

            $deleted = Task::whereIn('id', $request->task_ids)
                ->where('user_id', $userId)
                ->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => $deleted > 0
                    ? 'Tarefas eliminadas com sucesso.'
                    : 'Nenhuma tarefa encontrada para eliminar.',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }


    private function errorResponse(Exception $e)
    {

        return response()->json([
            'status' => false,
            'message' => "Erro inesperado, volte a tentar mais tarde,",
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
