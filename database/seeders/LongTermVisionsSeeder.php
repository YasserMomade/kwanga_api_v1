<?php

namespace Database\Seeders;

use App\Models\LongTermVision;
use Illuminate\Database\Seeder;

class LongTermVisionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        LongTermVision::factory(20)->create();
    }
}
