<?php

namespace Database\Seeders;

use App\Models\AnnualGoal;
use Illuminate\Database\Seeder;

class AnnualGoalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AnnualGoal::factory(18)->create();
    }
}
