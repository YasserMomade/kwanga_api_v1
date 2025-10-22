<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ListModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'designation' => $this->faker->word,
            'user_id' => User::inRandomOrder()->first()->id,
            'type' => $this->faker->randomElement(['entry', 'action'])
        ];
    }
}
