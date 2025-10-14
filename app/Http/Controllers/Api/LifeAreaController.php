<?php

namespace App\Http\Controllers;

use App\Models\LifeArea;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;


class LifeAreaController extends Controller
{

    public function index()
    {

        $lifeAreas = LifeArea::where('id_default', true)->get();

        return response()->json([
            'status' => true,
            'lifeAreas' => $lifeAreas
        ], 200);
    }


    public function getLifeAreasByUser()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $userId = $user->id;

        $lifeAreas = LifeArea::where('is_default', true)->orWhere('user_id', $userId)->get();

        return response()->json([
            'status' => true,
            'lifeAreas' => $lifeAreas
        ], 200);
    }


    public function createAdm(Request $request)
    {

        $request->validate([
            'designation' => 'required|string|max:55',
            'icon_path' => 'required|string'
        ]);


        $lifeArea = LifeArea::create([
            'user_id' =>  0,
            'designation' => $request->designation,
            'icon_path' => $request->icon_path,
            'is_default' => true
        ]);

        return response()->json([
            'status' => true,
            'massage' => 'Área de vida criada com sucesso',
            'lifeArea' => $lifeArea
        ], 200);
    }


    public function create(Request $request)
    {

        $request->validate([
            'designation' => 'required|string|max:55',
            'icon_path' => 'required|string'
        ]);

        DB::beginTransaction();

        try {

            $user = JWTAuth::parseToken()->authenticate();

            $lifeArea = LifeArea::create([
                'user_id' =>  $user->id,
                'designation' => $request->designation,
                'icon_path' => $request->icon_path,
                'is_default' => false
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'massage' => 'Área de vida criada com sucesso',
                'lifeArea' => $lifeArea
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Message' => "Falha ao criar Area da vida, volte a tentar mais tarde" . $e->getMessage()
            ], 401);
        }
    }

    public function updateAreaLife(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        DB::beginTransaction();

        $userId = auth()->id();

        try {

            // Buscar a área de vida pelo ID
            $lifeArea = LifeArea::find($id);

            // Verifica se existe
            if (!$lifeArea) {
                return response()->json([
                    'status' => false,
                    'Message' => 'Área de vida não encontrada.'
                ], 404);
            }

            //Verifica se a area da vida pode ser editada ou seja nao e default
            if ($lifeArea->user_id !== $userId || $lifeArea->is_default) {
                return response()->json([
                    'status' => false,
                    'Message' => 'Você não tem permissão para editar esta área de vida.'
                ], 403);
            }


            $lifeArea->update([
                'name' => $request->name,
                'Message' => $request->description
            ]);

            return response()->json([
                'status' => true,
                'Message' => 'Área de vida atualizada com sucesso.',
                'lifeArea' => $lifeArea
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Message' => "Falha ao Atualizar area da  vida, volte a tentar mais tarde" . $e->getMessage()
            ], 401);
        }
    }



    /**
     * Display the specified resource.
     *
     * @param  \App\Models\LifeArea  $lifeArea
     * @return \Illuminate\Http\Response
     */
    public function show(LifeArea $lifeArea)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\LifeArea  $lifeArea
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, LifeArea $lifeArea)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\LifeArea  $lifeArea
     * @return \Illuminate\Http\Response
     */
    public function destroy(LifeArea $lifeArea)
    {
        //
    }
}
