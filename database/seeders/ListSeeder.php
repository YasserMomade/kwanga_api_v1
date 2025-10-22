<?php

namespace Database\Seeders;

use App\Models\ListModel;
use Illuminate\Database\Seeder;

class ListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ListModel::factory(30)->create();
    }
}
