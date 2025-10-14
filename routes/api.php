<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\api\UserController;
use App\Http\Controllers\LifeAreaController;
use App\Http\Controllers\PurposeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/users', [UserController::class, 'index'])->name("getAllUsers");
Route::post('/users/sign_up', [AuthController::class, 'createUser'])->name("createUser");
Route::post('/verifyEmail', [AuthController::class, 'verifyEmail'])->name('verifyEmail');
Route::post('/users/login', [AuthController::class, 'login'])->name('userLogin');
Route::post('/users/logout', [AuthController::class, 'logout'])->name('userLogout');


Route::post('/users/lifeArea', [LifeAreaController::class, 'create']);
Route::post('/adm/lifeArea', [LifeAreaController::class, 'createAdm']);
Route::get('/lifeAreas/default', [LifeAreaController::class, 'index']);
Route::get('/LifeAreaByUser', [LifeAreaController::class, 'getLifeAreasByUser']);
Route::put('/updateLifeArea/{id}', [LifeAreaController::class, 'updateAreaLife']);




Route::middleware(['auth:api'])->group(function () {
    Route::get('/LifeAreaByUser/{id}', [LifeAreaController::class, 'getLifeAreasByUser']);
    Route::put('/updateLifeArea/{id}', [LifeAreaController::class, 'updateAreaLife']);
    Route::POST('/users/purpose', [PurposeController::class, 'createPurpose']);
    Route::get('/users/purpose', [PurposeController::class, 'index']);
});
