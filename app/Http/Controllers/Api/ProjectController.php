<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\project;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                ->with(['actions' => function ($q) {
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
                ->with(['actions' => function ($q) {
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
            'brainstorm_ideas' => 'nullable|array',
            'brainstorm_ideas.*' => 'string',
            'first_action'     => 'nullable|string',
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
                    'brainstorm_ideas' => $request->brainstorm_ideas ?? [],
                    'first_action'     => $request->first_action,
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Projeto criado com sucesso.',
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
            'brainstorm_ideas' => 'nullable|array',
            'brainstorm_ideas.*' => 'string',
            'first_action'     => 'nullable|string',
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
                'brainstorm_ideas',
                'first_action',
            ]);
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
