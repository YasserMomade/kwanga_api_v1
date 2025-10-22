<?php

namespace Database\Seeders;

use App\Models\LifeArea;
use Illuminate\Database\Seeder;

class LifeAreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        LifeArea::factory(50)->create();
    }
}
