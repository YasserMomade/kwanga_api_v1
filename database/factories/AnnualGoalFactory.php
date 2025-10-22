<?php

namespace Database\Factories;

use App\Models\LongTermVision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnualGoalFactory extends Factory
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
            'long_term_vision_id' => LongTermVision::inRandomOrder()->first()->id,
            'year' => $this->faker->year
        ];
    }
}
