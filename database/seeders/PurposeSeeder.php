<?php

namespace Database\Seeders;

use App\Models\Purpose;
use Illuminate\Database\Seeder;

class PurposeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Purpose::factory(30)->create();
    }
}
