<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableAnnualGoals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('annual_goals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->nullable()->onDelete('cascade')->onUpdate('cascade');
            $table->uuid('long_term_vision_id');
            $table->foreign('long_term_vision_id')->references('id')->on('long_term_visions')->onDelete('cascade')->onUpdate('cascade');
            $table->string('description');
            $table->year('year');
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
        Schema::dropIfExists('table_annual_goals');
    }
}
