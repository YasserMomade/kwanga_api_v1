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

class ChallengeParticipantController extends Controller
{
    private function getUserId(Request $request): int
    {
        if (auth()->check()) {
            $authId = auth()->id();

            if ($request->has('user_id') && (int)$request->user_id !== $authId) {
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

    // Verificar se o desafio esta ativo e o user pode participar
    private function ensureChallengeActive(Challenge $challenge): ?JsonResponse
    {
        $now = now();
        if ($challenge->status !== 'active' || $now->lt($challenge->start_at) || ($challenge->end_at && $now->gt($challenge->end_at))) {
            return response()->json([
                'status' => false,
                'message' => 'Desafio nao esta ativo ou terminou.'
            ], 400);
        }

        return null;
    }

    // Entrar no desafio
    public function join(Request $request, $communityId, $challengeId): JsonResponse
    {
        DB::beginTransaction();


        try {
            $userId = $this->getUserId($request);

            // Verificar se o user e membro da comunidade
            if ($resp = $this->ensureCommunityMember($userId, $communityId)) {
                return $resp;
            }

            // Buscar desafio e validar se esta ativo
            $challenge = Challenge::find($challengeId);

            if (!$challenge || $challenge->community_id !== $communityId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Desafio nao encontrado na comunidade.'
                ], 404);
            }

            // Validar se o desafio esta ativo
            if ($resp = $this->ensureChallengeActive($challenge)) {
                return $resp;
            }

            // Verificar se o user ja esta participando no desafio
            $existingParticipation = ChallengeParticipant::where('user_id', $userId)
                ->where('challenge_id', $challengeId)
                ->exists();

            if ($existingParticipation) {
                return response()->json([
                    'status' => false,
                    'message' => 'Ja participa deste desafio.'
                ], 400);
            }

            // Adicionar participacao do user
            $participant = ChallengeParticipant::create([
                'user_id' => $userId,
                'challenge_id' => $challengeId,
                'joined_at' => now(),
            ]);

            // Criar as tarefas para o participante
            $tasks = ChallengeTask::where('challenge_id', $challengeId)->get();

            foreach ($tasks as $task) {
                ChallengeParticipantTask::create([
                    'participant_id' => $participant->id,
                    'task_id' => $task->id,
                    'completed' => false,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Desafio iniciado com sucesso!',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    // Sair do desafio
    public function leave(Request $request, $communityId, $challengeId): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            if ($resp = $this->ensureCommunityMember($userId, $communityId)) {
                return $resp;
            }

            // Buscar desafio
            $challenge = Challenge::find($challengeId);

            if (!$challenge || $challenge->community_id !== $communityId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Desafio nao encontrado na comunidade.'
                ], 404);
            }

            // Verificar se o user esta participando
            $participant = ChallengeParticipant::where('user_id', $userId)
                ->where('challenge_id', $challengeId)
                ->first();

            if (!$participant) {
                return response()->json([
                    'status' => false,
                    'message' => 'Nao participa deste desafio.'
                ], 400);
            }

            // Verificar se o criador tenta sair
            if ($participant->role === 'creator') {
                return response()->json([
                    'status' => false,
                    'message' => 'O criador do desafio nao pode sair.'
                ], 400);
            }

            // Remover o user
            $participant->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Saiu do desafio com sucesso.',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }


    public function getChallengeProgress(Request $request, string $communityId, string $challengeId): JsonResponse
    {
        try {


            $challenge = Challenge::where('id', $challengeId)
                ->where('community_id', $communityId)
                ->first();

            if (!$challenge) {
                return response()->json([
                    'status' => false,
                    'message' => 'Desafio não encontrado nesta comunidade.'
                ], 404);
            }

            // Buscar participantes com user e tarefas
            $participants = ChallengeParticipant::with([
                'user:id,email',
                'tasks'
            ])
                ->where('challenge_id', $challengeId)
                ->get();

            // Calcular progresso por user
            $participantsProgress = $participants->map(function ($participant) {

                $totalTasks = $participant->tasks->count();
                $completedTasks = $participant->tasks->where('completed', true)->count();

                $progress = $totalTasks > 0
                    ? ($completedTasks / $totalTasks) * 100
                    : 0;

                return [
                    'user_id' => $participant->user_id,
                    'email' => $participant->user->email ?? null,
                    'completed_tasks' => $completedTasks,
                    'total_tasks' => $totalTasks,
                    'progress' => round($progress, 2),
                ];
            })->sortByDesc('progress')->values();

            return response()->json([
                'status' => true,
                'message' => 'Progresso do desafio obtido com sucesso.',
                'data' => [
                    'challenge_id' => $challenge->id,
                    'challenge_title' => $challenge->title,
                    'participants' => $participantsProgress,
                ],
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function getCommunityProgress(Request $request, string $communityId): JsonResponse
    {

        try {

            // Buscar desafios da comunidade
            $challenges = Challenge::where('community_id', $communityId)->get();

            $overallCompletedTasks = 0;
            $overallPossibleTasks = 0;

            $progressByChallenge = [];

            foreach ($challenges as $challenge) {

                // Participantes do desafio
                $participants = ChallengeParticipant::where('challenge_id', $challenge->id)->pluck('id');

                if ($participants->isEmpty()) {
                    $progressByChallenge[] = [
                        'challenge_id' => $challenge->id,
                        'challenge_title' => $challenge->title,
                        'community_progress' => 0
                    ];
                    continue;
                }

                // Total de tarefas do desafio
                $totalTasks = ChallengeTask::where('challenge_id', $challenge->id)->count();

                // Total possivel de tarefas (tarefas × participantes)
                $possibleTasks = $totalTasks * $participants->count();

                // Total de tarefas concluidas
                $completedTasks = ChallengeParticipantTask::whereIn('participant_id', $participants)
                    ->where('completed', true)
                    ->count();

                // Progresso do desafio
                $challengeProgress = $possibleTasks > 0
                    ? ($completedTasks / $possibleTasks) * 100
                    : 0;

                // Acumuladores gerais
                $overallCompletedTasks += $completedTasks;
                $overallPossibleTasks += $possibleTasks;

                $progressByChallenge[] = [
                    'challenge_id' => $challenge->id,
                    'challenge_title' => $challenge->title,
                    'community_progress' => round($challengeProgress, 2)
                ];
            }

            // Progresso geral da comunidade
            $overallProgress = $overallPossibleTasks > 0
                ? ($overallCompletedTasks / $overallPossibleTasks) * 100
                : 0;

            return response()->json([
                'status' => true,
                'message' => 'Progresso geral da comunidade obtido com sucesso.',
                'data' => [
                    'overall_progress' => round($overallProgress, 2) . '%',
                    'progress_by_challenge' => $progressByChallenge
                ],
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    //ver ranking

    public function getCommunityRanking(Request $request, $communityId): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Buscar todos os participantes da comunidade
            $participants = ChallengeParticipant::whereHas('challenge', function ($q) use ($communityId) {
                $q->where('community_id', $communityId);
            })
                ->with(['user', 'tasks'])
                ->get()
                ->map(function ($participant) {
                    $completedTasks = $participant->tasks->where('completed', true);
                    $countCompleted = $completedTasks->count();
                    $lastCompletedAt = $completedTasks->max('updated_at');

                    return [
                        'participant_id' => $participant->id,
                        // 'name' => $participant->user->name,
                        'email' => $participant->user->email,
                        'completed_tasks' => $countCompleted,
                        'last_completed_at' => $lastCompletedAt,
                    ];
                });

            // Agrupar por usuario
            $grouped = $participants->groupBy('email')->map(function ($group) {
                $completedTasksSum = $group->sum('completed_tasks');
                $lastCompletedAt = $group->pluck('last_completed_at')->filter()->max();
                $first = $group->first();
                return [
                    'participant_id' => $first['participant_id'],
                    //'name' => $first['name'],
                    'email' => $first['email'],
                    'completed_tasks' => $completedTasksSum,
                    'last_completed_at' => $lastCompletedAt,
                ];
            })->values();

            // Ordenar por tarefas concluidas desc e ultima conclusao asc
            $sorted = $grouped->sort(function ($a, $b) {
                if ($b['completed_tasks'] === $a['completed_tasks']) {
                    return strtotime($a['last_completed_at'] ?? '9999-12-31 23:59:59') <=> strtotime($b['last_completed_at'] ?? '9999-12-31 23:59:59');
                }
                return $b['completed_tasks'] <=> $a['completed_tasks'];
            })->values();

            // Adicionar classificação com "°"
            $ranked = $sorted->map(function ($item, $index) {
                $rank = $index + 1;
                $item['rank'] = "{$rank}°";
                unset($item['last_completed_at']); // não expor
                return $item;
            });

            return response()->json([
                'status' => true,
                'message' => 'Ranking da comunidade obtido com sucesso.',
                'data' => $ranked,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }



    // Marcar tarefa como concluida/ nao concluida
    public function toggleStatus(Request $request, $communityId, $challengeId, $taskId): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $participant = ChallengeParticipant::where('user_id', $userId)
                ->where('challenge_id', $challengeId)
                ->first();

            if (!$participant) {
                return response()->json([
                    'status' => false,
                    'message' => 'Você não é participante deste desafio.'
                ], 403);
            }

            // Verificar se a tarefa existe no desafio
            $task = ChallengeTask::where('challenge_id', $challengeId)
                ->where('id', $taskId)
                ->first();

            if (!$task) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tarefa não encontrada para este desafio.'
                ], 404);
            }

            // Verificar se a tarefa esta associada ao participante
            $taskParticipant = ChallengeParticipantTask::where('participant_id', $participant->id)
                ->where('task_id', $task->id)
                ->first();

            if (!$taskParticipant) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tarefa não associada a este participante.'
                ], 404);
            }

            // Alternar o status de conclusao
            $taskParticipant->completed = $taskParticipant->completed ? 0 : 1;
            $taskParticipant->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Status da tarefa alternado com sucesso.',
                'data' => $taskParticipant
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    //Pegar todas as tarefas de um utilizador relacionadas a desafios

    public function listUserTasks(Request $request): JsonResponse
    {
        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);


            // Buscar todas as tarefas atribuidas ao user
            $participantTasks = ChallengeParticipantTask::whereHas('participant', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
                ->with(['task', 'participant.challenge.community'])->get();


            $result = [];

            foreach ($participantTasks as $participantTask) {
                $task = $participantTask->task;
                $challenge = $participantTask->participant->challenge;
                $community = $challenge->community;

                $result[] = [
                    'task_id' => $task->id,
                    'task_title' => $task->description,
                    'completed' => $participantTask->completed,
                    'challenge_id' => $challenge->id,
                    'challenge_title' => $challenge->description,
                    'community_id' => $community->id,
                    'community_name' => $community->designation,
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'Tarefas do utilizador obtidas com sucesso.',
                'data' => $result,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }
}
