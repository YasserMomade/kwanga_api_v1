<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChallengeParticipantTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('challenge_participant_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('participant_id')->constrained('challenge_participants')->onDelete('cascade');
            $table->uuid('task_id');
            $table->boolean('completed')->default(false);
            $table->foreign('task_id')->references('id')->on('challenge_tasks')->onDelete('cascade');

            // Garantir que um participante so tenha uma entrada por tarefa
            $table->unique(['participant_id', 'task_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('challenge_participant_tasks');
    }
}
