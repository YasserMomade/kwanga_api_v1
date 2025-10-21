<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListModel;
use App\Models\Task;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{


    public function index(Request $request)
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
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function store(Request $request)
    {

        $userId = auth()->id();

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

        try {

            $list = ListModel::where('id', $request->list_id)
                ->where('user_id', $userId)
                ->firstOrFail();


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




    private function errorResponse(Exception $e)
    {

        return response()->json([
            'status' => false,
            'message' => "Erro inesperado, volte a tentar mais tarde,",
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
