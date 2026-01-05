<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserLifeAreaOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_life_area_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->uuid('life_area_id'); // ajuste se o teu id for diferente (uuid/string)
            $table->integer('order_index');
            $table->timestamps();

            $table->unique(['user_id', 'life_area_id'], 'ulao_user_lifearea_unique');
            $table->unique(['user_id', 'order_index'], 'ulao_user_order_unique');

            $table->index(['user_id', 'order_index'], 'ulao_user_order_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_life_area_orders');
    }
}
