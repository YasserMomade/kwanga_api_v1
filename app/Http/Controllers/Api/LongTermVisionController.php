<?php

namespace App\Http\Controllers;

use App\Models\LongTermVision;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class LongTermVisionController extends Controller
{

    public function createPurpose(Request $request)
    {


        $request->validate([
            'description' => 'required|string|max:250',
            'areaLife_id' => 'required'
        ]);


        try {

            $user = JWTAuth::parseToken()->authenticate();

            $longTermVision = LongTermVision::create([
                'user_id' =>  $user->id,
                'lifeArea_id' => $request->areaLife_id,
                'description' => $request->description,
                'deadline' => $request->deadline,
                'status' => $request->description,

            ]);

            return response()->json([
                'status' => true,
                'massage' => 'Visao a longo prazo criada com sucesso',
                'lifeArea' => $longTermVision
            ], 200);
        } catch (Exception   $e) {
            DB::rollBack();
            return response()->json([
                'Message' => "Falha ao criar visao a longo prazo, volte a tentar mais tarde"
            ], 401);
        }
    }
}
