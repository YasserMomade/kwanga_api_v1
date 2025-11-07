<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LifeArea;
use App\Models\ListModel;
use App\Models\Purpose;
use App\Models\Task;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{

    public function totalUsers()
    {

        try {
            $total = User::count();

            return
                response()->json([
                    'status' => true,
                    'data' => [
                        'total_users' => $total,
                    ],
                ]);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function userVerificationStatus()
    {
        try {
            $verified = User::whereNotNull('email_verified_at')->count();
            $unverified = User::whereNull('email_verified_at')->count();

            return response()->json([
                'status' => true,
                'data' => [
                    'verified_users' => $verified,
                    'unverified_users' => $unverified,
                ],
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function totalItems()
    {
        try {
            $totals = [
                'tasks' => Task::count(),
                'purposes' => Purpose::count(),
                'lists' => ListModel::count(),
                'life_areas' => LifeArea::count(),
            ];

            return response()->json([
                'status' => true,
                'data' => [
                    'total' => $totals,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }


    public function itemsByDay()
    {
        try {
            $tasks = DB::table('tasks')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            $purposes = DB::table('purposes')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            $lists = DB::table('lists')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            $lifeAreas = DB::table('life_areas')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            return response()->json([
                'status' => true,
                'data' => [
                    'tasks' => $tasks,
                    'purposes' => $purposes,
                    'lists' => $lists,
                    'life_areas' => $lifeAreas,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }


    public function engagementByDay()
    {
        try {
            $tasks = DB::table('tasks')
                ->select(DB::raw('DATE(updated_at) as date'), DB::raw('COUNT(DISTINCT user_id) as engaged_users'))
                ->groupBy('date');

            $purposes = DB::table('purposes')
                ->select(DB::raw('DATE(updated_at) as date'), DB::raw('COUNT(DISTINCT user_id) as engaged_users'))
                ->groupBy('date');

            $lists = DB::table('lists')
                ->select(DB::raw('DATE(updated_at) as date'), DB::raw('COUNT(DISTINCT user_id) as engaged_users'))
                ->groupBy('date');

            $lifeAreas = DB::table('life_areas')
                ->select(DB::raw('DATE(updated_at) as date'), DB::raw('COUNT(DISTINCT user_id) as engaged_users'))
                ->groupBy('date');

            // Une todos os resultados (para o caso de um usur ter criado em diferentes tabelas no mesmo dia)
            $union = $tasks->unionAll($purposes)->unionAll($lists)->unionAll($lifeAreas);

            $dailyEngagement = DB::query()
                ->fromSub($union, 'activities')
                ->select('date', DB::raw('COUNT(DISTINCT engaged_users) as total_engaged_users'))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $dailyEngagement,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    private function getItemsByDayData()
    {
        return [
            'tasks' => DB::table('tasks')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get(),

            'purposes' => DB::table('purposes')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get(),

            'lists' => DB::table('lists')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get(),

            'life_areas' => DB::table('life_areas')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get(),
        ];
    }

    private function getEngagementByDayData()
    {
        $tasks = DB::table('tasks')
            ->select(DB::raw('DATE(updated_at) as date'), DB::raw('user_id'))
            ->groupBy('date', 'user_id');

        $purposes = DB::table('purposes')
            ->select(DB::raw('DATE(updated_at) as date'), DB::raw('user_id'))
            ->groupBy('date', 'user_id');

        $lists = DB::table('lists')
            ->select(DB::raw('DATE(updated_at) as date'), DB::raw('user_id'))
            ->groupBy('date', 'user_id');

        $lifeAreas = DB::table('life_areas')
            ->select(DB::raw('DATE(updated_at) as date'), DB::raw('user_id'))
            ->groupBy('date', 'user_id');

        $union = $tasks->unionAll($purposes)->unionAll($lists)->unionAll($lifeAreas);

        return DB::query()
            ->fromSub($union, 'activities')
            ->select('date', DB::raw('COUNT(DISTINCT user_id) as total_engaged_users'))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
    }

    // Retorna metricas basicas em formato CSV
    public function exportBasic()
    {
        $data = [
            ['metric', 'value'],
            ['Total de Usuários', User::count()],
            ['Usuários Verificados', User::whereNotNull('email_verified_at')->count()],
            ['Usuários Não Verificados', User::whereNull('email_verified_at')->count()],
            ['Tarefas', Task::count()],
            ['Propósitos', Purpose::count()],
            ['Listas', ListModel::count()],
            ['Áreas da Vida', LifeArea::count()],
            ['Engajamento Diário', json_encode($this->getEngagementByDayData())],
            ['Itens por Dia', json_encode($this->getItemsByDayData())],

        ];

        $csv = '';
        foreach ($data as $row) {
            $csv .= implode(',', $row) . "\n";
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'inline; filename="metrics.csv"');
    }







    private function errorResponse(Exception $e): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Erro interno, volte a tentar mais tarde.',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
