<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();

            // Relacionamento com users
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->uuid('list_id')->nullable();
            $table->foreign('list_id')->references('id')->on('lists')->onDelete('set null')->onUpdate('cascade');
            $table->uuid('project_id')->nullable();
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade')->onUpdate('cascade');

            $table->string('description');
            $table->integer('order_index')->nullable()->index();
            $table->dateTime('deadline')->nullable();
            $table->dateTime('time')->nullable();
            $table->json('frequency')->nullable();
            $table->boolean('completed')->default(false);
            $table->uuid('linked_action_id')->nullable()->index();
            $table->timestamps();
            $table->index(['user_id', 'list_id']);
            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
