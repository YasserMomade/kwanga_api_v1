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
                $table->id();
                $table->foreignId('annualGoals_id')->constrained('annual_goals')->nullable()->onDelete('cascade')->onUpdate('cascade');
                $table->foreignId('user_id')->constrained('users')->nullable()->onDelete('cascade')->onUpdate('cascade');
                $table->string('description', 250);
                $table->string('status', 50);
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
