<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('communities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->uuid('life_area_id');
            $table->foreign('life_area_id')->references('id')->on('life_areas')->onDelete('restrict');
            $table->string('designation');
            $table->text('description');
            $table->text('objective');
            $table->string('whatsapp_link')->nullable();
            $table->string('status')->default('active');
            $table->enum('visibility', ['public', 'private'])
                ->default('public');
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
        Schema::dropIfExists('communities');
    }
}
