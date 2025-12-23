<?php

namespace App\Http\Controllers\Api\Community;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\CommunityJoinRequest;
use App\Models\CommunityMember;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class CommunityMemberController extends Controller
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

    private function ensureIsAdminOrOwner(Community $community, int $userId): ?JsonResponse
    {
        if ((int) $community->owner_id === $userId) {
            return null;
        }

        $membership = CommunityMember::where('community_id', $community->id)
            ->where('user_id', $userId)
            ->first();

        if (! $membership || ! in_array($membership->role, ['creator', 'admin'])) {
            return response()->json([
                'status'  => false,
                'message' => 'Nao tem permissao para gerir esta comunidade.',
            ], 403);
        }

        return null;
    }

    public function join(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $community = Community::find($id);

            if (! $community || $community->status !== 'active') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Comunidade nao encontrada ou inativa.',
                ], 404);
            }

            $existingMember = CommunityMember::where('community_id', $community->id)
                ->where('user_id', $userId)
                ->first();

            if ($existingMember) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Ja e membro desta comunidade.',
                ], 400);
            }

            if ($community->visibility === 'public') {

                CommunityMember::create([
                    'community_id' => $community->id,
                    'user_id'      => $userId,
                    'role'         => 'member',
                    'joined_at'    => now(),
                ]);

                DB::commit();

                return response()->json([
                    'status'  => true,
                    'message' => 'Entrou na comunidade com sucesso.',
                ]);
            }

            $existingRequest = CommunityJoinRequest::where('community_id', $community->id)
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->first();

            if ($existingRequest) {
                DB::rollBack();

                return response()->json([
                    'status'  => false,
                    'message' => 'Ja possui um pedido pendente para esta comunidade.',
                ], 400);
            }

            CommunityJoinRequest::updateOrCreate(
                [
                    'community_id' => $community->id,
                    'user_id'      => $userId,
                ],
                [
                    'status'     => 'pending',
                    'handled_by' => null,
                    'handled_at' => null,
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Pedido de entrada enviado com sucesso. Aguarde aprovacao.',
            ]);
        } catch (Exception $e) {

            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    public function listJoinRequests(Request $request, $id): JsonResponse
    {
        try {

            $userId = $this->getUserId($request);

            $community = Community::find($id);

            if (! $community) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Comunidade nao encontrada.',
                ], 404);
            }

            if ($resp = $this->ensureIsAdminOrOwner($community, $userId)) {
                return $resp;
            }

            $requests = CommunityJoinRequest::where('community_id', $community->id)
                ->where('status', 'pending')
                ->with('user')
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'status' => true,
                'data'   => $requests,
            ]);
        } catch (Exception $e) {

            return $this->errorResponse($e);
        }
    }

    public function approve(Request $request, $id, $requestId): JsonResponse
    {
        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);

            $community = Community::find($id);

            if (! $community) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Comunidade nao encontrada.',
                ], 404);
            }

            if ($resp = $this->ensureIsAdminOrOwner($community, $userId)) {
                return $resp;
            }

            $joinRequest = CommunityJoinRequest::where('id', $requestId)
                ->where('community_id', $community->id)
                ->where('status', 'pending')
                ->first();

            if (! $joinRequest) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Pedido nao encontrado ou ja processado.',
                ], 404);
            }

            $exists = CommunityMember::where('community_id', $community->id)
                ->where('user_id', $joinRequest->user_id)
                ->exists();

            if (! $exists) {
                CommunityMember::create([
                    'community_id' => $community->id,
                    'user_id'      => $joinRequest->user_id,
                    'role'         => 'member',
                    'joined_at'    => now(),
                ]);
            }

            $joinRequest->update([
                'status'     => 'approved',
                'handled_by' => $userId,
                'handled_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Membro aprovado com sucesso.',
            ]);
        } catch (Exception $e) {

            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    public function reject(Request $request, $id, $requestId): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $community = Community::find($id);

            if (! $community) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Comunidade nao encontrada.',
                ], 404);
            }

            if ($resp = $this->ensureIsAdminOrOwner($community, $userId)) {
                return $resp;
            }

            $joinRequest = CommunityJoinRequest::where('id', $requestId)
                ->where('community_id', $community->id)
                ->where('status', 'pending')
                ->first();

            if (! $joinRequest) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Pedido nao encontrado ou ja processado.',
                ], 404);
            }

            $joinRequest->update([
                'status'     => 'rejected',
                'handled_by' => $userId,
                'handled_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Pedido recusado.',
            ]);
        } catch (Exception $e) {

            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    public function leave(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $community = Community::find($id);

            if (! $community) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Comunidade nao encontrada.',
                ], 404);
            }

            $membership = CommunityMember::where('community_id', $community->id)
                ->where('user_id', $userId)
                ->first();

            if (! $membership) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Nao e membro desta comunidade.',
                ], 400);
            }

            if ($membership->role === 'creator') {
                return response()->json([
                    'status'  => false,
                    'message' => 'O criador da comunidade nao pode sair.',
                ], 400);
            }

            $membership->delete();

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Saiu da comunidade com sucesso.',
            ]);
        } catch (Exception $e) {

            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    public function removeMember(Request $request, $id, $memberId): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $community = Community::find($id);

            if (! $community) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Comunidade nao encontrada.',
                ], 404);
            }

            if ($resp = $this->ensureIsAdminOrOwner($community, $userId)) {
                return $resp;
            }

            $membership = CommunityMember::where('community_id', $community->id)
                ->where('user_id', $memberId)
                ->first();

            if (! $membership) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Membro nao encontrado nesta comunidade.',
                ], 404);
            }

            if ($membership->role === 'creator') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Nao e possivel remover o criador da comunidade.',
                ], 400);
            }

            $membership->delete();

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Membro removido com sucesso.',
            ]);
        } catch (Exception $e) {

            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    public function promoteMember(Request $request, $id, $memberId): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $this->getUserId($request);

            $community = Community::find($id);

            if (! $community) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Comunidade nao encontrada.',
                ], 404);
            }

            if ((int) $community->owner_id !== $userId) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Apenas o criador da comunidade pode promover membros.',
                ], 403);
            }

            $membership = CommunityMember::where('community_id', $community->id)
                ->where('user_id', $memberId)
                ->first();

            if (! $membership) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Membro nao encontrado.',
                ], 404);
            }

            if ($membership->role === 'creator') {
                return response()->json([
                    'status'  => false,
                    'message' => 'O criador ja tem todas as permissoes.',
                ], 400);
            }

            $membership->update([
                'role' => 'admin',
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Permissoes atualizadas.',
            ]);
        } catch (Exception $e) {

            DB::rollBack();
            return $this->errorResponse($e);
        }
    }
}
