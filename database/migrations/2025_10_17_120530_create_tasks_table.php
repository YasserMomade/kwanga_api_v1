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
            $table->id();
            $table->string('designation');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('list_id')->constrained('lists')->onDelete('cascade')->onUpdate('cascade');
            $table->boolean('completed')->default(false);
            $table->boolean('has_due_date')->default(false);
            $table->date('due_date')->nullable();
            $table->boolean('has_reminder')->default(false);
            $table->dateTime('remeinder_datetime')->nullable();
            $table->boolean('has_frequency')->default(false);
            $table->json('frequency_days')->nullable();
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
        Schema::dropIfExists('tasks');
    }
}
