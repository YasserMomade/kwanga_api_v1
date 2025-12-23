<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {

            $table->string('phone', 20)->unique()->after('id');
            $table->string('email', 60)->nullable()->after('phone');
            $table->string('first_name', 60)->nullable()->after('email');
            $table->string('last_name', 60)->nullable()->after('first_name');
            $table->enum('gender', ['M', 'F'])->nullable()->after('last_name');
            $table->string('province', 60)->nullable()->after('gender');
            $table->date('date_of_birth')->nullable()->after('province');
            $table->timestamp('phone_verified_at')->nullable()->after('phone')->after('date_of_birth');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
