<?php

namespace App\Http\Controllers\Api\Community;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\LifeArea;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\JsonResponse;

class CommunityController extends Controller
{
    private function getUserId(Request $request): int
    {
        if (auth()->check()) {
            $authId = auth()->id();

            if ($request->has('user_id') && (int)$request->user_id !== $authId) {
                abort(response()->json([
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

    private function errorResponse(Exception $e): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Erro interno, volte a tentar mais tarde.',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }

    // Aplicar filtros e pesquisa para consultar comunidades
    private function applyCommunityFilters($query, Request $request)
    {
        // Filtrar por id de area de vida
        if ($request->filled('life_area_id')) {
            $query->where('life_area_id', $request->life_area_id);
        }

        //  Filtrar por objectivo usando correspondencia parcial
        if ($request->filled('objective')) {
            $query->where('objective', 'like', '%' . $request->objective . '%');
        }

        // Filtrar por designacao da aresa da vida
        if ($request->filled('category')) {
            $category = $request->category;

            $query->whereHas('lifeArea', function ($q) use ($category) {
                $q->where('designation', 'like', '%' . $category . '%');
            });
        }

        // Pesquisa por nome ou palavra-chave na comunidade e na area de vida
        if ($request->filled('q')) {
            $term = $request->q;

            $query->where(function ($q) use ($term) {
                $q->where('designation', 'like', '%' . $term . '%')
                    ->orWhere('description', 'like', '%' . $term . '%')
                    ->orWhere('objective', 'like', '%' . $term . '%')
                    ->orWhereHas('lifeArea', function ($qq) use ($term) {
                        $qq->where('designation', 'like', '%' . $term . '%');
                    });
            });
        }

        return $query;
    }

    // Verifcar se area da vida e permitida
    private function ensureLifeAreaAllowed(string $lifeAreaId, int $userId): ?JsonResponse
    {
        $lifeArea = LifeArea::where('id', $lifeAreaId)
            ->where(function ($q) use ($userId) {
                $q->where('is_default', true)
                    ->orWhere('user_id', $userId);
            })
            ->first();

        if (! $lifeArea) {
            return response()->json([
                'status'  => false,
                'message' => 'Area da vida nao encontrada ou sem permissao.',
            ], 403);
        }

        return null;
    }

    public function index(Request $request): JsonResponse
    {
        try {

            $query = Community::query()
                ->where('status', 'active')
                ->withCount('members')
                ->with(['lifeArea:id,designation'])
                ->orderBy('created_at', 'desc');

            $query = $this->applyCommunityFilters($query, $request);

            $communities = $query->get();

            $communities->transform(function ($community) {
                $community->life_area_designation = $community->lifeArea
                    ? $community->lifeArea->designation
                    : null;
                return $community;
            });

            return response()->json([
                'status' => true,
                'data' => $communities,
            ]);
        } catch (Exception $e) {

            return $this->errorResponse($e);
        }
    }

    public function mycommunities(Request $request): JsonResponse
    {
        try {

            $userId = $this->getUserId($request);

            $query = Community::query()
                ->where('status', 'active')
                ->whereHas('members', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->withCount('members')
                ->with(['lifeArea:id,designation'])
                ->orderBy('created_at', 'desc');

            $query = $this->applyCommunityFilters($query, $request);

            $communities = $query->get();

            if (!$communities) {
                return response()->json([
                    'status' => false,
                    'message' => 'Voce nao faz parte de nenhuma comunidade',

                ]);
            }

            $communities->transform(function ($community) {
                $community->life_area_designation = $community->lifeArea
                    ? $community->lifeArea->designation : null;
                return $community;
            });

            return response()->json([
                'status' => true,
                'data' => $communities,
            ]);
        } catch (Exception $e) {

            return $this->errorResponse($e);
        }
    }


    public function show(Request $request, $id): JsonResponse
    {
        try {

            $community = Community::withCount('members')
                ->with(['owner', 'members', 'lifeArea:id,designation'])
                ->find($id);

            if (! $community) {
                return response()->json([
                    'status' => false,
                    'message' => 'comunidade não encontrada.'
                ], 404);
            }

            $community->life_area_designation = $community->lifeArea
                ? $community->lifeArea->designation : null;

            return response()->json([
                'status' => true,
                'data' => $community,
            ]);
        } catch (Exception $e) {

            return $this->errorResponse($e);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
                'life_area_id' => ['required', 'uuid', 'exists:life_areas,id'],
                'designation' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[A-Za-z0-9\s\-\._]+$/',
                ],
                'description' => ['required', 'string'],
                'objective' => ['required', 'string'],
                'visibility' => ['required', Rule::in(['public', 'private'])],
            ],
            [
                'life_area_id.required' => 'A area da vida e obrigatoria.',
                'life_area_id.uuid' => 'A area da vida e invalida.',
                'life_area_id.exists' => 'A area da vida nao foi encontrada.',
                'designation.required' => 'O nome da comunidade e obrigatorio.',
                'designation.regex' => 'O nome da comunidade contem caracteres invalidos.',
                'description.required' => 'A descricao da comunidade e obrigatoria.',
                'objective.required' => 'O objetivo da comunidade e obrigatorio.',
                'visibility.required' => 'O tipo de comunidade e obrigatorio.',
                'visibility.in' => 'O tipo de comunidade deve ser public ou private.',
            ]
        );

        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);

            if ($resp = $this->ensureLifeAreaAllowed($validated['life_area_id'], $userId)) {
                DB::rollBack();
                return $resp;
            }

            $community = Community::create([
                'owner_id' => $userId,
                'life_area_id' => $validated['life_area_id'],
                'designation' => $validated['designation'],
                'description' => $validated['description'],
                'objective' => $validated['objective'],
                'visibility' => $validated['visibility'],
            ]);

            CommunityMember::create([
                'community_id' => $community->id,
                'user_id' => $userId,
                'role' => 'creator',
                'joined_at' => now(),
            ]);

            $community = Community::withCount('members')
                ->with(['lifeArea:id,designation'])
                ->find($community->id);

            $community->life_area_designation = $community->lifeArea
                ? $community->lifeArea->designation : null;

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Comunidade criada com sucesso.',
                'data' => $community,
            ], 201);
        } catch (Exception $e) {

            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate(
            [
                'life_area_id' => ['sometimes', 'required', 'uuid', 'exists:life_areas,id'],
                'designation' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[A-Za-z0-9\s\-\._]+$/',
                ],
                'description' => ['sometimes', 'required', 'string'],
                'objective' => ['sometimes', 'required', 'string'],
                'visibility' => ['sometimes', 'required', Rule::in(['public', 'private'])],
                'whatsapp_link' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:255',
                    'regex:/^https?:\/\/(chat\.whatsapp\.com|wa\.me)\/.+$/',
                ],
            ],
            [
                'life_area_id.required' => 'A area da vida e obrigatoria.',
                'life_area_id.uuid' => 'A area da vida e invalida.',
                'life_area_id.exists' => 'A area da vida nao foi encontrada.',
                'designation.required' => 'O nome da comunidade e obrigatorio.',
                'designation.regex' => 'O nome da comunidade contem caracteres invalidos.',
                'description.required' => 'A descricao da comunidade e obrigatoria.',
                'objective.required' => 'O objetivo da comunidade e obrigatorio.',
                'visibility.in' => 'O tipo de comunidade deve ser public ou private.',
                'whatsapp_link.regex' => 'O link de WhatsApp informado nao e valido.',
            ]
        );

        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);

            $community = Community::find($id);

            if (! $community) {
                return response()->json([
                    'status' => false,
                    'message' => 'comunidade não encontrada.'
                ], 404);
            }

            if ($community->owner_id !== $userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Nao tem permissao para editar esta comunidade.',
                ], 403);
            }

            if (isset($validated['life_area_id'])) {
                if ($resp = $this->ensureLifeAreaAllowed($validated['life_area_id'], $userId)) {
                    DB::rollBack();
                    return $resp;
                }
            }

            $community->update($validated);

            $community = Community::withCount('members')
                ->with(['lifeArea:id,designation'])
                ->find($community->id);

            $community->life_area_designation = $community->lifeArea
                ? $community->lifeArea->designation : null;

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Comunidade atualizada com sucesso.',
                'data' => $community,
            ]);
        } catch (Exception $e) {

            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    public function close(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);

            $community = Community::find($id);

            if (! $community) {
                return response()->json([
                    'status' => false,
                    'message' => 'comunidade não encontrada.'
                ], 404);
            }

            if ($community->owner_id !== $userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Nao tem permissao para encerrar esta comunidade.',
                ], 403);
            }

            if ($community->status === 'closed') {
                return response()->json([
                    'status' => false,
                    'message' => 'A comunidade ja se encontra encerrada.',
                ], 400);
            }

            $community->update([
                'status' => 'closed',
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Comunidade encerrada com sucesso.',
            ]);
        } catch (Exception $e) {

            DB::rollBack();
            return $this->errorResponse($e);
        }
    }

    public function open(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {

            $userId = $this->getUserId($request);

            $community = Community::find($id);

            if (! $community) {
                return response()->json([
                    'status' => false,
                    'message' => 'comunidade não encontrada.'
                ], 404);
            }

            if ($community->owner_id !== $userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Nao tem permissao para reabrir esta comunidade.',
                ], 403);
            }

            if ($community->status === 'closed') {
                return response()->json([
                    'status' => false,
                    'message' => 'A comunidade ja se encontra activa.',
                ], 400);
            }

            $community->update([
                'status' => 'closed',
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Comunidade activada com sucesso.',
            ]);
        } catch (Exception $e) {

            DB::rollBack();
            return $this->errorResponse($e);
        }
    }
}
