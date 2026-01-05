<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\project;
use App\Models\Project as ModelsProject;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Util\Json;
use SebastianBergmann\CodeCoverage\Report\Xml\Project as XmlProject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProjectController extends Controller
{


    public function getUserId(Request $request)
    {

        if (auth()->check()) {

            $authId = auth()->id();

            if ($request->has('user_id') && (int)$request->user_id !== $authId) {

                abort(Response()->json([
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
     * Listar todos os projetos do utilizador autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);

            $projects = Project::where('user_id', $userId)
                ->where('is_archived', false)
                ->with(['tasks' => function ($q) {
                    $q->orderBy('order_index');
                }])
                ->get();

            return response()->json([
                'status' => true,
                'data'   => $projects
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function archived(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);

            $projects = Project::where('user_id', $userId)
                ->where('is_archived', true)
                ->with(['tasks' => function ($q) {
                    $q->orderBy('order_index');
                }])
                ->get();

            return response()->json([
                'status' => true,
                'data'   => $projects
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }



    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);

            $project = Project::where('id', $id)
                ->where('user_id', $userId)
                ->with(['tasks' => function ($q) {
                    $q->orderBy('order_index');
                }])
                ->first();

            if (! $project) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Projeto não encontrado.'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data'   => $project
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id'               => 'required|string',
            'monthly_goal_id'  => 'required|exists:monthly_goals,id',
            'title'            => 'required|string|max:255',
            'purpose'          => 'required|string',
            'expected_result'  => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $project = Project::updateOrCreate(
                ['id' => $request->id],
                [
                    'user_id'          => $userId,
                    'monthly_goal_id'  => $request->monthly_goal_id,
                    'title'            => $request->title,
                    'purpose'          => $request->purpose,
                    'expected_result'  => $request->expected_result,
                    'is_archived' => false,
                    'created_at' => $request->created_at ?? now(),
                    'updated_at' => $request->updated_at ?? now()
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Projeto Salvo com sucesso.',
                'data'    => $project
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    /**
     * Atualizar um projeto existente.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'monthly_goal_id'  => 'sometimes|required|string',
            'title'            => 'sometimes|required|string|max:255',
            'purpose'          => 'sometimes|required|string',
            'expected_result'  => 'sometimes|required|string',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $project = Project::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (! $project) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Projeto não encontrado.'
                ], 404);
            }

            $data = $request->only([
                'monthly_goal_id',
                'title',
                'purpose',
                'expected_result',
            ]);

            $data['updated_at'] = $request->updated_at ?? now();

            $project->update($data);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Projeto atualizado com sucesso.',
                'data'    => $project
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }


    public function archive(Request $request, string $id): JsonResponse
    {

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $project = Project::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$project) {
                return response()->json([
                    'status' => false,
                    'message' => 'Projeto não encontrado.',
                ], 404);
            }
            $project->is_archived = !$project->is_archived;
            $project->save();

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => $project->is_archived ? 'Arquivado' : 'Projeto desarquivado',
                'data'    => $project
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }


    public function destroy(Request $request, string $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $project = Project::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (! $project) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Projeto não encontrado.'
                ], 404);
            }

            $project->delete();

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Projeto eliminado.'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    /**
     * Apagar multiplos projetos de uma vez.
     */
    public function destroyMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'string'
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $ids = $request->ids;

            // Apenas projetos do utilizador autenticado
            $query = Project::where('user_id', $userId)
                ->whereIn('id', $ids);

            $foundCount = $query->count();

            if ($foundCount === 0) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Nenhum dos projetos foi encontrado .'
                ], 404);
            }

            $query->delete();

            DB::commit();

            return response()->json([
                'status'        => true,
                'message'       => " $foundCount  Projetos eliminados com sucesso.",
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }



    public function archiveMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'string'
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);
            $ids = $request->ids;

            $projects = Project::where('user_id', $userId)
                ->whereIn('id', $ids)
                ->get();

            $foundCount = $projects->count();

            if ($foundCount === 0) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Nenhum dos projetos foi encontrado .'
                ], 404);
            }

            // Alterna estado de cada um
            foreach ($projects as $p) {
                $p->is_archived = !$p->is_archived;
                $p->save();
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => $projects->first()->is_archived
                    ? "$foundCount Projetos arquivados"
                    : "$foundCount Projetos desarquivados",
                'data'    => $projects
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
            'status'  => false,
            'message' => 'Erro interno, volte a tentar mais tarde.',
            'error'   => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
