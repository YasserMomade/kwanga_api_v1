<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->uuid('monthly_goal_id');
            $table->foreign('monthly_goal_id')->references('id')->on('monthly_goals')->onDelete('cascade')->onUpdate('cascade');
            $table->string('title');
            $table->text('purpose');
            $table->text('expected_result');
            $table->json('brainstorm_ideas')->nullable();
            $table->text('first_action')->nullable();

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
        Schema::dropIfExists('projects');
    }
}
