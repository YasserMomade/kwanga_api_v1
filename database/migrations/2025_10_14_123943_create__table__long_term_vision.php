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
        Schema::create('long_term_visions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->uuid('life_area_id')->nullable();
            $table->foreign('life_area_id')->references('id')->on('life_areas')->onDelete('cascade')->onUpdate('cascade');
            $table->string('description', 250);
            $table->string('status')->nullable();
            $table->year('deadline');
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
        // Schema::dropIfExists('_table__long_term_vision');
    }
}
