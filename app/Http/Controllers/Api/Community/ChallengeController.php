<?php

namespace App\Http\Controllers\Api\Community;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\Community;
use App\Models\CommunityMember;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;
use function PHPSTORM_META\map;

class ChallengeController extends Controller
{
    private function getUserId(Request $request): int
    {
        if (auth()->check()) {
            $authId = auth()->id();

            if ($request->has('user_id') && (int)$request->user_id !== $authId) {
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

    private function errorResponse(Exception $e): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => 'Erro interno, volte a tentar mais tarde.',
            'error'   => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }


    public function index(Request $request, $communityId): JsonResponse
    {

        try {
            $userId = $this->getUserId($request);


            $isMember = CommunityMember::where('community_id', $communityId)
                ->where('user_id', $userId)
                ->exists();


            $challenges = Challenge::where('community_id', $communityId)
                ->withCount('participants')
                ->orderBy('start_at', 'asc')
                ->get();

            $now = now();

            $data = $challenges->map(function ($c) use ($now) {

                $item = $c->toArray();

                if ($c->status === 'closed') {
                    $item['time_status'] = 'closed';
                } elseif ($now->lt($c->start_at)) {
                    $item['time_status'] = 'upcoming';
                } elseif ($c->end_at && $now->gt($c->end_at)) {
                    $item['time_status'] = 'fineshed';
                } else {
                    $item['time_status'] = 'ongoing';
                }

                return $item;
            });

            return response()->json([
                'status' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // detalhes de um desafio
    public function show(Request $request, $communityId, $id): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);

            $isMember = CommunityMember::where('community_id', $communityId)
                ->where('user_id', $userId)
                ->exists();

            if (! $isMember) {
                return response()->json([
                    'status' => false,
                    'message' => 'Nao pertence a esta comunidade.'
                ], 403);
            }

            $challenge = Challenge::where('id', $id)
                ->where('community_id', $communityId)
                ->withCount('participants')
                ->first();

            if (! $challenge) {
                return response()->json([
                    'status' => false,
                    'message' => 'Desafio nao encontrado.'
                ], 404);
            }

            $now = now();

            if ($challenge->status === 'closed') {
                $timeStatus = 'closed';
            } elseif ($now->lt($challenge->start_at)) {
                $timeStatus = 'upcoming';
            } elseif ($challenge->end_at && $now->gt($challenge->end_at)) {
                $timeStatus = 'finished';
            } else {
                $timeStatus = 'ongoing';
            }

            $data = $challenge->toArray();
            $data['time_status'] = $timeStatus;

            return response()->json([
                'status' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }



    public function store(Request $request, $communityId): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'end_at' => 'required|date',
        ]);

        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);

            $community = Community::find($communityId);

            if (!$community || $community->status !==  'active') {
                return response()->json([
                    'status' => false,
                    'message' => 'Comunidade nao encontrada ou inativa.'
                ], 404);
            }

            $isAdmin = CommunityMember::where('community_id', $communityId)
                ->where('user_id', $userId)
                ->whereIn('role', ['creator', 'admin'])
                ->exists();

            if (!$isAdmin) {
                return response()->json([
                    'status' => false,
                    'message' => 'Nao tem permissao para criar desafios.'
                ], 403);
            }

            // verificar se ja existe um desafio ativo na comunidade
            $hasActiveChallenge = Challenge::where('community_id', $communityId)
                ->where('status', 'active')
                ->exists();

            if ($hasActiveChallenge) {
                return response()->json([
                    'status' => false,
                    'message' => 'Ja existe um desafio ativo nesta comunidade. Encerre-o antes de criar outro.'
                ], 400);
            }


            $challenge = Challenge::create([
                'community_id' => $communityId,
                'title' => $request->title,
                'description' => $request->description,
                'start_at' => $request->start_at,
                'end_at' => $request->end_at,
                'status' => 'active',
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Desafio criado com sucesso.',
                'data' => $challenge
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    public function update(Request $request, $communityId, $id): JsonResponse
    {

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'start_at' => 'sometimes|required|date',
            'end_at' => 'sometimes|date|after:start_at',
        ]);

        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $challenge = Challenge::where('id', $id)
                ->where('community_id', $communityId)
                ->first();

            if (! $challenge) {
                return response()->json([
                    'status' => false,
                    'message' => 'Desafio nao encontrado.'
                ], 404);
            }


            $isAdmin = CommunityMember::where('community_id', $communityId)
                ->where('user_id', $userId)
                ->whereIn('role', ['creator', 'admin'])
                ->exists();

            if (! $isAdmin) {
                return response()->json([
                    'status' => false,
                    'message' => 'Nao tem permissao para editar desafios.'
                ], 403);
            }
            $challenge->update($request->only([
                'title',
                'description',
                'start_at',
                'status',
                'end_at',
            ]));

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Desafio atualizado com sucesso.',
                'data' => $challenge->fresh()
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    // encerrar desafio
    public function close(Request $request, $communityId, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $challenge = Challenge::where('id', $id)
                ->where('community_id', $communityId)
                ->first();

            if (! $challenge) {
                return response()->json([
                    'status' => false,
                    'message' => 'Desafio nao encontrado.'
                ], 404);
            }

            $isAdmin = CommunityMember::where('community_id', $communityId)
                ->where('user_id', $userId)
                ->whereIn('role', ['creator', 'admin'])
                ->exists();

            if (! $isAdmin) {
                return response()->json([
                    'status' => false,
                    'message' => 'Nao tem permissao para encerrar desafios.'
                ], 403);
            }

            if ($challenge->status === 'closed') {
                return response()->json([
                    'status' => false,
                    'message' => 'O desafio ja se encontra encerrado.'
                ], 400);
            }

            $challenge->update([
                'status' => 'closed'
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Desafio encerrado com sucesso.'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e);
        }
    }
}
