<?php

use App\Http\Controllers\Api\AnnualGoalController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Community\ChallengeController;
use App\Http\Controllers\Api\Community\ChallengeParticipantController;
use App\Http\Controllers\Api\Community\ChallengeParticipantTaskController;
use App\Http\Controllers\Api\Community\ChallengeTaskController;
use App\Http\Controllers\Api\Community\CommunityController;
use App\Http\Controllers\Api\Community\CommunityMemberController;
use App\Http\Controllers\Api\LifeAreaController;
use App\Http\Controllers\Api\ListController;
use App\Http\Controllers\Api\LongTermVisionController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\MonthlyGoalController;
use App\Http\Controllers\Api\ProjectActionController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\PurposeController;
use App\Http\Controllers\Api\TaskController;
use App\Models\ProjectAction;
use Illuminate\Support\Facades\Route;



Route::prefix('v1')->group(function () {


    /**
     * Rotas publicas de autenticacao
     */

    Route::prefix('auth')->group(function () {

        Route::post('/register', [AuthController::class, 'registerRequestOtp'])->name("auth.register");
        Route::post('/register/verify_otp', [AuthController::class, 'registerVerifyOtp'])->name("auth.register");
        //Route::post('/verify_email', [AuthController::class, 'verifyEmail'])->name('auth.verifyEmail');
        Route::post('/resend_code', [AuthController::class, 'resendVerificationCode']);
        Route::post('/login', [AuthController::class, 'loginRequestOtp'])->middleware('throttle:5,1')->name('auth.Login');
        Route::post('/login/verify_otp', [AuthController::class, 'loginVerifyOtp'])->middleware('throttle:5,1')->name('auth.Login');
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
            Route::put('/profile', [AuthController::class, 'updateProfile']);
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
            Route::delete('/destroy_multiple', [MonthlyGoalController::class, 'destroyMultiple']);
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

        //Tarefas nas listas

        Route::prefix('tasks')->group(function () {

            Route::post('/', [TaskController::class, 'store'])->name('task.store');
            Route::get('/', [TaskController::class, 'index'])->name('task.index');
            Route::get('/{id}', [TaskController::class, 'show'])->name('task.show');
            Route::put('/{id}', [TaskController::class, 'update'])->name('task.update');
            Route::patch('/{id}/alter', [TaskController::class, 'alterStatus'])->name('task.alterStatus');
            Route::delete('/{id}', [TaskController::class, 'destroy'])->name('task.destroy');
            Route::post('/delete_multiple', [TaskController::class, 'destroyMultiple'])->name('task.destroyMultiple');
            Route::patch('/{id}/move', [TaskController::class, 'moveTask'])->name('task.move');
            Route::patch('/move_multiple', [TaskController::class, 'moveMultipleTasks'])->name('task.move');
            Route::get('/{list_id}/tasks', [TaskController::class, 'indexByList']);
        });

        //Projetos

        Route::prefix('projects')->group(function () {

            Route::post('/', [ProjectController::class, 'store'])->name('projects.store');
            Route::get('/', [ProjectController::class, 'index'])->name('Projects.index');
            Route::get('/archived', [ProjectController::class, 'archived'])->name('Projects.index');
            Route::get('/{id}', [ProjectController::class, 'show'])->name('Projects.show');
            Route::put('/{id}', [ProjectController::class, 'update'])->name('Projects.update');
            Route::delete('/{id}', [ProjectController::class, 'destroy'])->name('Projects.delete');
            Route::post('/delete_multiple', [ProjectController::class, 'destroyMultiple']);
            Route::post('/archive_multiple', [ProjectController::class, 'archiveMultiple']);
            Route::post('{id}/archive', [ProjectController::class, 'archive'])->name('Projects.archive');
        });


        //Tarefas nos projetos

        Route::prefix('project_tasks')->group(function () {
            Route::get('/', [TaskController::class, 'projectOnlyTasks'])->name('projectAction.index');

            Route::post('/', [TaskController::class, 'store'])->name('projectAction.store');
            Route::get('/{id}', [TaskController::class, 'show'])->name('projectAction.show');
            Route::put('/{id}', [TaskController::class, 'update'])->name('projectAction.update');
            Route::delete('/{id}', [TaskController::class, 'destroy'])->name('projectAction.delete');
            Route::post('/{id}/alter', [TaskController::class, 'alterstatus'])->name('projectAction.delete');
            Route::post('/{id}/move', [TaskController::class, 'moveToProject'])->name('projectAction.move');
            Route::patch('/{id}/link_to_list', [TaskController::class, 'linkToActionList'])->name('projectAction.move');
            Route::post('/move_multiple_tasks', [TaskController::class, 'moveMultipleToProject'])->name('projectAction.delete');
            Route::get('/{project_id}/tasks', [TaskController::class, 'indexByProject']);
            Route::post('/delete_multiple', [TaskController::class, 'destroyMultiple'])->name('task.destroyMultiple');
        });

        //Comunidades

        Route::prefix('communities')->group(function () {

            Route::get('/', [CommunityController::class, 'index']);
            Route::get('/my', [CommunityController::class, 'mycommunities']);
            Route::get('/{id}', [CommunityController::class, 'show']);
            Route::post('/', [CommunityController::class, 'store']);
            Route::put('/{id}', [CommunityController::class, 'update']);
            Route::post('/{id}/close', [CommunityController::class, 'close']);

            Route::post('/{id}/join', [CommunityMemberController::class, 'join']);
            Route::get('/{id}/join_requests', [CommunityMemberController::class, 'listJoinRequests']);
            Route::post('/{id}/join_requests/{requestId}/approve', [CommunityMemberController::class, 'approve']);
            Route::post('/{id}/join_requests/{requestId}/reject',  [CommunityMemberController::class, 'reject']);
            Route::post('/{id}/leave', [CommunityMemberController::class, 'leave']);
            Route::post('/{id}/members/{memberId}/remove',   [CommunityMemberController::class, 'removeMember']);
            Route::post('/{id}/members/{memberId}/promote',  [CommunityMemberController::class, 'promoteMember']);
        });

        //Criacao de desafios

        Route::prefix('communities')->group(function () {
            Route::post('/{communityId}/challenges', [ChallengeController::class, 'store']);
            Route::get('/{communityId}/challenges', [ChallengeController::class, 'index']);
            Route::get('/{communityId}/challenges/{id}', [ChallengeController::class, 'show']);
            Route::put('/{communityId}/challenges/{id}', [ChallengeController::class, 'update']);
            Route::patch('/{communityId}/challenges/{id}', [ChallengeController::class, 'close']);


            // Participar de desafios + ranking e proogresso

            Route::post('/{communityId}/challenges/{id}/join', [ChallengeParticipantController::class, 'join']);
            Route::Delete('/{communityId}/challenges/{id}/leave', [ChallengeParticipantController::class, 'leave']);
            Route::post('/{communityId}/challenges/{id}/join', [ChallengeParticipantController::class, 'join']);
            Route::get('/{communityId}/ranking', [ChallengeParticipantController::class, 'getCommunityRanking']);
            Route::get('/{communityId}/challenges/{id}/progress', [ChallengeParticipantController::class, 'getChallengeProgress']);
            Route::get('/{communityId}/progress', [ChallengeParticipantController::class, 'getCommunityProgress']);
            Route::put('/{communityId}/challenges/{id}/{taskId}/alter', [ChallengeParticipantController::class, 'toggleStatus']);
            Route::get('/users/tasks', [ChallengeParticipantController::class, 'listUserTasks']);
        });

        //Criacao de tarefas para desafios

        Route::prefix('community_tasks')->group(function () {
            Route::post('/{communityId}/challenges/{challengeId}', [ChallengeTaskController::class, 'store']);
            Route::get('/{communityId}/challenges/{challengeId}', [ChallengeTaskController::class, 'index']);
            Route::get('/{communityId}/challenges/{id}', [ChallengeTaskController::class, 'show']);
            Route::put('/{communityId}/challenges/{challengeId}/{taskId}', [ChallengeTaskController::class, 'update']);
            Route::delete('/{communityId}/challenges/{challengeId}/{taskId}', [ChallengeTaskController::class, 'destroy']);
        });
    });
});
