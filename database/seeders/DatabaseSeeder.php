<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        //UserSeeder::class
        //LifeAreaSeeder::class

        $this->call([
            //LongTermVisionsSeeder::class,
            // PurposeSeeder::class,
            //AnnualGoalSeeder::class,
            //MonthlyGoalSeeder::class,
            //ListSeeder::class,
            TaskSeeder::class
        ]);
    }
}
