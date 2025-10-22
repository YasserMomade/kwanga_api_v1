B<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    class CreateTableMonthlyGoals extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::create('monthly_goals', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained('users')->nullable()->onDelete('cascade')->onUpdate('cascade');
                $table->uuid('annual_goals_id');
                $table->foreign('annual_goals_id')->references('id')->on('annual_goals')->onDelete('cascade')->onUpdate('cascade');
                $table->string('description', 250);
                $table->string('status', 50)->nullable();
                $table->string('month', 12);

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
            Schema::dropIfExists('monthly_goals');
        }
    }
