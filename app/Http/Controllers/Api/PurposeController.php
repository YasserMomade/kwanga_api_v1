<?php

namespace App\Http\Controllers;

use App\Models\Purpose;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class PurposeController extends Controller
{



    public function index()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $userId = $user->id;

        $purposes = Purpose::where('user_id', $userId)->get();

        return response()->json([
            'status' => true,
            'purposes' => $purposes
        ], 200);
    }

    public function createPurpose(Request $request)
    {


        $request->validate([
            'description' => 'required|string|max:250',
            'areaLife_id' => 'required'
        ]);


        try {

            $user = JWTAuth::parseToken()->authenticate();

            $purpose = Purpose::create([
                'user_id' =>  $user->id,
                'lifeArea_id' => $request->areaLife_id,
                'description' => $request->description,

            ]);

            return response()->json([
                'status' => true,
                'massage' => 'Proposito Salvo com sucesso',
                'lifeArea' => $purpose
            ], 200);
        } catch (Exception   $e) {
            DB::rollBack();
            return response()->json([
                'Message' => "Falha ao criar Proposito, volte a tentar mais tarde"
            ], 401);
        }
    }
}
