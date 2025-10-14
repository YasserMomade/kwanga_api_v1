<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableLongTermVision extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('long_term_vision', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->nullable()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('lifeArea_id')->constrained('life_Areas')->nullable()->onDelete('cascade')->onUpdate('cascade');
            $table->string('description', 250);
            $table->date('deadline');
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
        Schema::dropIfExists('_table__long_term_vision');
    }
}
