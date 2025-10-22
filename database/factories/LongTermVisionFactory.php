<?php

namespace Database\Factories;

use App\Models\LifeArea;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LongTermVisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [

            'description' => $this->faker->word,
            'user_id' => User::inRandomOrder()->first()->id,
            'life_area_id' => LifeArea::inRandomOrder()->first()->id,
            'deadline' => $this->faker->year
        ];
    }
}
