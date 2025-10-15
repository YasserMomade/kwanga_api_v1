<?php

use App\Http\Controllers\Api\AnnualGoalController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\LifeAreaController;
use App\Http\Controllers\Api\LongTermVisionController;
use App\Http\Controllers\Api\MonthlyGoalController;
use App\Http\Controllers\Api\PurposeController;
use App\Models\LongTermVision;
use Illuminate\Support\Facades\Route;



Route::prefix('v1')->group(function () {


    // Autenticacao

    Route::prefix('auth')->group(function () {

        Route::post('/register', [AuthController::class, 'createUser'])->name("auth.register");
        Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('auth.verifyEmail');
        Route::post('/login', [AuthController::class, 'login'])->name('auth.Login');
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.Logout');
    });


    Route::post('/adm/lifeArea', [LifeAreaController::class, 'createAdm']);
    Route::get('/lifeAreas/default', [LifeAreaController::class, 'index']);



    //Grupo de rotan que requerem autenticacao

    Route::middleware(['auth:api'])->group(function () {


        Route::prefix('user')->group(function () {

            Route::get('/user', [UserController::class, 'show'])->name("users.show");
        });

        // Aresa da vida

        Route::prefix('life-areas')->group(function () {

            Route::post('/', [LifeAreaController::class, 'create'])->name('lifeAreas.store');
            Route::get('/', [LifeAreaController::class, 'getLifeAreasByUser'])->name('lifeAreas.byUser');
            Route::put('/{id}', [LifeAreaController::class, 'updateAreaLife'])->name('lifeAreas.update');
            Route::delete('/{id}', [LifeAreaController::class, 'deleteLifeArea'])->name('lifeAreas.delete');
        });

        //Proposito

        Route::prefix('purpose')->group(function () {

            Route::post('/', [PurposeController::class, 'create'])->name('purposes.store');
            Route::get('/', [PurposeController::class, 'index'])->name('purposes.index');
            Route::put('/{id}', [PurposeController::class, 'update'])->name('purposes.update');
            Route::delete('/{id}', [PurposeController::class, 'destroy'])->name('purposes.delete');
        });

        //Visao a longo prazo

        Route::prefix('long-term-vision')->group(function () {

            Route::post('/', [LongTermVisionController::class, 'create'])->name('longTermVision.create');
            Route::get('/', [LongTermVisionController::class, 'index'])->name('longTermVision.index');
            Route::put('/{id}', [LongTermVisionController::class, 'update'])->name('longTermVision.update');
            Route::delete('/{id}', [LongTermVisionController::class, 'destroy'])->name('longTermVision.delete');
        });

        //objectivos anuais

        Route::prefix('annual-goals')->group(function () {

            Route::post('/', [AnnualGoalController::class, 'create'])->name('annualGoal.create');
            Route::get('/', [AnnualGoalController::class, 'index'])->name('annualGoal.index');
            Route::put('/{id}', [AnnualGoalController::class, 'update'])->name('annualGoal.update');
            Route::delete('/{id}', [AnnualGoalController::class, 'destroy'])->name('annualGoal.delete');
        });

        Route::prefix('monthly-goals')->group(function () {

            Route::post('/', [MonthlyGoalController::class, 'create'])->name('MonthlyGoal.create');
            Route::get('/', [MonthlyGoalController::class, 'index'])->name('MonthlyGoal.index');
            Route::put('/{id}', [MonthlyGoalController::class, 'update'])->name('MonthlyGoal.update');
            Route::delete('/{id}', [MonthlyGoalController::class, 'destroy'])->name('MonthlyGoal.delete');
        });
    });
});
