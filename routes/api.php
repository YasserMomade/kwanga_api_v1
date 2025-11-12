<?php

use App\Http\Controllers\Api\AnnualGoalController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LifeAreaController;
use App\Http\Controllers\Api\ListController;
use App\Http\Controllers\Api\LongTermVisionController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\MonthlyGoalController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\PurposeController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;



Route::prefix('v1')->group(function () {


    /**
     * Rotas publicas de autenticacao
     */

    Route::prefix('auth')->group(function () {

        Route::post('/register', [AuthController::class, 'register'])->name("auth.register");
        Route::post('/verify_email', [AuthController::class, 'verifyEmail'])->name('auth.verifyEmail');
        Route::post('/resend_code', [AuthController::class, 'resendVerificationCode']);
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('auth.Login');
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.Logout');
    });



    /**
     * Metricas do sistema
     */

    Route::prefix('metrics')->group(function () {

        Route::get('/users', [MetricsController::class, 'totalUsers'])->name("metrics.totalUsers");
        Route::get('/users/verification', [MetricsController::class, 'userVerificationStatus'])->name("metrics.userVerificationStatus");
        Route::get('/items', [MetricsController::class, 'totalItems'])->name("metrics.totalItems");
        Route::get('/items/daily', [MetricsController::class, 'itemsByDay']);
        Route::get('/engagement/daily', [MetricsController::class, 'engagementByDay']);
        Route::get('/export', [MetricsController::class, 'exportBasic']);
    });


    /**
     * ROtas protegida porautenticacao
     */


    //Grupo de rotas que requerem autenticacao

    Route::middleware(['auth:api'])->group(function () {


        Route::prefix('users')->group(function () {

            Route::get('/user', [AuthController::class, 'me'])->name("users.me");
        });

        // Aresa da vida

        Route::prefix('life_areas')->group(function () {

            Route::post('/', [LifeAreaController::class, 'store'])->name('lifeAreas.store');
            Route::get('/', [LifeAreaController::class, 'index'])->name('lifeAreas.byUser');
            Route::get('/{id}', [LifeAreaController::class, 'show'])->name('lifeAreasDetails.byUser');
            Route::put('/{id}', [LifeAreaController::class, 'update'])->name('lifeAreas.update');
            Route::delete('/{id}', [LifeAreaController::class, 'destroy'])->name('lifeAreas.delete');
        });

        //Proposito

        Route::prefix('purposes')->group(function () {

            Route::post('/', [PurposeController::class, 'store'])->name('purposes.store');
            Route::get('/', [PurposeController::class, 'index'])->name('purposes.index');
            Route::get('/{id}', [PurposeController::class, 'show'])->name('purposes.show');
            Route::put('/{id}', [PurposeController::class, 'update'])->name('purposes.update');
            Route::delete('/{id}', [PurposeController::class, 'destroy'])->name('purposes.delete');
        });

        //Visao a longo prazo

        Route::prefix('long_term_visions')->group(function () {

            Route::post('/', [LongTermVisionController::class, 'store'])->name('longTermVisions.store');
            Route::get('/', [LongTermVisionController::class, 'index'])->name('longTermVisions.index');
            Route::get('/{id}', [LongTermVisionController::class, 'show'])->name('longTermVisions.show');
            Route::put('/{id}', [LongTermVisionController::class, 'update'])->name('longTermVisions.update');
            Route::delete('/{id}', [LongTermVisionController::class, 'destroy'])->name('longTermVisions.delete');
        });

        //objectivos anuais

        Route::prefix('annual_goals')->group(function () {

            Route::post('/', [AnnualGoalController::class, 'store'])->name('annualGoals.store');
            Route::get('/', [AnnualGoalController::class, 'index'])->name('annualGoals.index');
            Route::get('/{id}', [AnnualGoalController::class, 'show'])->name('annualGoals.show');
            Route::put('/{id}', [AnnualGoalController::class, 'update'])->name('annualGoals.update');
            Route::delete('/{id}', [AnnualGoalController::class, 'destroy'])->name('annualGoals.delete');
        });

        //Objectivos mensais

        Route::prefix('monthly_goals')->group(function () {

            Route::post('/', [MonthlyGoalController::class, 'store'])->name('MonthlyGoals.store');
            Route::get('/', [MonthlyGoalController::class, 'index'])->name('MonthlyGoals.index');
            Route::get('/{id}', [MonthlyGoalController::class, 'show'])->name('MonthlyGoals.show');
            Route::put('/{id}', [MonthlyGoalController::class, 'update'])->name('MonthlyGoals.update');
            Route::delete('/{id}', [MonthlyGoalController::class, 'destroy'])->name('MonthlyGoals.delete');
        });


        //listas

        Route::prefix('lists')->group(function () {

            Route::post('/', [ListController::class, 'store'])->name('lists.store');
            Route::get('/', [ListController::class, 'index'])->name('lists.index');
            Route::get('/{id}', [ListController::class, 'show'])->name('lists.show');
            Route::put('/{id}', [ListController::class, 'update'])->name('lists.update');
            Route::delete('/{id}', [ListController::class, 'destroy'])->name('lists.destroy');
            Route::post('/destroy_multiple', [ListController::class, 'destroyMultiple'])->name('lists.destroyMultiple');
        });

        //Tarefas

        Route::prefix('tasks')->group(function () {

            Route::post('/', [TaskController::class, 'store'])->name('task.store');
            Route::get('/', [TaskController::class, 'index'])->name('task.index');
            Route::get('/{id}', [TaskController::class, 'show'])->name('task.show');
            Route::put('/{id}', [TaskController::class, 'update'])->name('task.update');
            Route::patch('/{id}/alter', [TaskController::class, 'alterStatus'])->name('task.alterStatus');
            Route::delete('/{id}', [TaskController::class, 'destroy'])->name('task.destroy');
            Route::post('/delete_multiple', [TaskController::class, 'destroyMultiple'])->name('task.destroyMultiple');
            Route::patch('/{id}/move', [TaskController::class, 'moveTask'])->name('task.move');
        });

        //Projetos

        Route::prefix('projects')->group(function () {

            Route::post('/', [ProjectController::class, 'create'])->name('Projects.create');
            Route::get('/', [ProjectController::class, 'index'])->name('Projects.index');
            Route::get('/{id}', [ProjectController::class, 'show'])->name('Projects.show');
            Route::put('/{id}', [ProjectController::class, 'update'])->name('Projects.update');
            Route::delete('/{id}', [ProjectController::class, 'destroy'])->name('Projects.delete');
        });
    });
});
