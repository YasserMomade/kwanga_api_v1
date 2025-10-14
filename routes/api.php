<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\LifeAreaController;
use App\Http\Controllers\Api\PurposeController;

use Illuminate\Support\Facades\Route;


Route::get('/user', [UserController::class, 'index'])->name("getAllUsers");
Route::post('/user/sign_up', [AuthController::class, 'createUser'])->name("createUser");
Route::post('/user/verifyEmail', [AuthController::class, 'verifyEmail'])->name('verifyEmail');
Route::post('/user/login', [AuthController::class, 'login'])->name('userLogin');
Route::post('/user/logout', [AuthController::class, 'logout'])->name('userLogout');


Route::post('/adm/lifeArea', [LifeAreaController::class, 'createAdm']);
Route::get('/lifeAreas/default', [LifeAreaController::class, 'index']);



Route::middleware(['auth:api'])->group(function () {
    //Area de Vida
    Route::post('/user/lifeArea', [LifeAreaController::class, 'create']);
    Route::get('/user/LifeArea', [LifeAreaController::class, 'getLifeAreasByUser']);
    Route::put('/user/updateLifeArea/{id}', [LifeAreaController::class, 'updateAreaLife']);
    Route::delete('/user/deleteLifeArea/{id}', [LifeAreaController::class, 'deleteLifeArea']);

    //Proposito
    Route::post('/user/purpose', [PurposeController::class, 'store']);
    Route::get('/user/purpose', [PurposeController::class, 'index']);
    Route::put('/user/purpose/{id}', [PurposeController::class, 'update']);
    Route::delete('/user/purpose/{id}', [PurposeController::class, 'destroy']);
});
