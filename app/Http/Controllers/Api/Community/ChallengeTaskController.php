<?php

namespace App\Http\Controllers\Api\Community;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeParticipant;
use App\Models\ChallengeParticipantTask;
use App\Models\ChallengeTask;
use App\Models\CommunityMember;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class ChallengeTaskController extends Controller
{
    private function getUserId(Request $request): int
    {
        if (auth()->check()) {
            $authId = auth()->id();

            if ($request->has('user_id') && (int) $request->user_id !== $authId) {
                abort(response()->json([
                    'status' => false,
                    'message' => 'O ID do utilizador enviado nao corresponde ao autenticado.'
                ], 403));
            }

            return $authId;
        }

        if ($request->has('user_id')) {
            return (int) $request->user_id;
        }

        abort(response()->json([
            'status' => false,
            'message' => 'Identificacao de utilizador necessaria.'
        ], 401));
    }

    private function errorResponse(Exception $e): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Erro interno, volte a tentar mais tarde.',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }

    private function ensureCommunityMember(int $userId, string $communityId): ?JsonResponse
    {
        $isMember = CommunityMember::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->exists();

        if (! $isMember) {
            return response()->json([
                'status' => false,
                'message' => 'Nao pertence a esta comunidade.'
            ], 403);
        }

        return null;
    }

    private function ensureAdminOrCreator(int $userId, string $communityId): ?JsonResponse
    {
        $isAdmin = CommunityMember::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->whereIn('role', ['creator', 'admin'])
            ->exists();

        if (! $isAdmin) {
            return response()->json([
                'status' => false,
                'message' => 'Nao tem permissao para gerir tarefas do desafio.'
            ], 403);
        }

        return null;
    }

    private function getChallengeOrFail(string $communityId, string $challengeId): ?Challenge
    {
        return Challenge::where('id', $challengeId)
            ->where('community_id', $communityId)
            ->first();
    }

    // listar tarefas do desafio
    public function index(Request $request, $communityId, $challengeId): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);

            if ($resp = $this->ensureCommunityMember($userId, $communityId)) {
                return $resp;
            }

            $challenge = $this->getChallengeOrFail($communityId, $challengeId);

            if (! $challenge) {
                return response()->json([
                    'status' => false,
                    'message' => 'Desafio nao encontrado.'
                ], 404);
            }

            $tasks = ChallengeTask::where('challenge_id', $challenge->id)
                ->orderBy('created_at')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $tasks
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // ver detalhe de uma tarefa
    public function show(Request $request, $communityId, $challengeId, $taskId): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);

            if ($resp = $this->ensureCommunityMember($userId, $communityId)) {
                return $resp;
            }

            $challenge = $this->getChallengeOrFail($communityId, $challengeId);

            if (! $challenge) {
                return response()->json([
                    'status' => false,
                    'message' => 'Desafio nao encontrado.'
                ], 404);
            }

            $task = ChallengeTask::where('id', $taskId)
                ->where('challenge_id', $challenge->id)
                ->first();

            if (! $task) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tarefa nao encontrada.'
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

    // criar tarefa do desafio (admin)
    public function store(Request $request, $communityId, $challengeId): JsonResponse
    {
        $request->validate([
            'description' => 'required|string',

        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            if ($resp = $this->ensureCommunityMember($userId, $communityId)) {
                return $resp;
            }

            if ($resp = $this->ensureAdminOrCreator($userId, $communityId)) {
                return $resp;
            }

            $challenge = $this->getChallengeOrFail($communityId, $challengeId);

            if (! $challenge) {
                return response()->json([
                    'status' => false,
                    'message' => 'Desafio nao encontrado.'
                ], 404);
            }

            if ($challenge->status !== 'active') {
                return response()->json([
                    'status' => false,
                    'message' => 'Nao e possivel adicionar tarefas a um desafio encerrado.'
                ], 400);
            }

            $task = ChallengeTask::create([
                'challenge_id' => $challenge->id,
                'description' => $request->description,
            ]);

            // Criar tarefas par todos os participantes

            $participants = ChallengeParticipant::where('challenge_id', $challengeId)->get();

            foreach ($participants as $participant) {
                ChallengeParticipantTask::create([
                    'participant_id' => $participant->id,
                    'task_id' => $task->id,
                    'completed' => false,
                ]);
            }


            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Tarefa salva com sucesso.',
                'data' => $task
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    // atualizar tarefa do desafio (admin/creator)
    public function update(Request $request, $communityId, $challengeId, $taskId): JsonResponse
    {
        $request->validate([
            'description' => 'sometimes|required|string',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            if ($resp = $this->ensureCommunityMember($userId, $communityId)) {
                return $resp;
            }

            if ($resp = $this->ensureAdminOrCreator($userId, $communityId)) {
                return $resp;
            }

            $challenge = $this->getChallengeOrFail($communityId, $challengeId);

            if (! $challenge) {
                return response()->json([
                    'status' => false,
                    'message' => 'Desafio nao encontrado.'
                ], 404);
            }

            $task = ChallengeTask::where('id', $taskId)
                ->where('challenge_id', $challenge->id)
                ->first();

            if (! $task) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tarefa nao encontrada.'
                ], 404);
            }

            $task->update($request->only([
                'description',
            ]));

            // // Atualizar nas tarefas dos participantes
            // $participantTasks = ChallengeParticipantTask::where('task_id', $task->id)->get();
            // foreach ($participantTasks as $participantTask) {
            //     $participantTask->task()->update([
            //         'description' => $task->description,
            //     ]);
            // }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Tarefa atualizada com sucesso.',
                'data' => $task->fresh()
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    // eliminar tarefa do desafio (admin/creator)
    public function destroy(Request $request, $communityId, $challengeId, $taskId): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            if ($resp = $this->ensureCommunityMember($userId, $communityId)) {
                return $resp;
            }

            if ($resp = $this->ensureAdminOrCreator($userId, $communityId)) {
                return $resp;
            }

            $challenge = $this->getChallengeOrFail($communityId, $challengeId);

            if (! $challenge) {
                return response()->json([
                    'status' => false,
                    'message' => 'Desafio nao encontrado.'
                ], 404);
            }

            $task = ChallengeTask::where('id', $taskId)
                ->where('challenge_id', $challenge->id)
                ->first();

            if (! $task) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tarefa nao encontrada.'
                ], 404);
            }

            // Excluir nas tarefas dos participantes
            ChallengeParticipantTask::where('task_id', $task->id)->delete();

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
}
