<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LifeAreaFactory extends Factory
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
            'icon_path' => $this->faker->word,
            'user_id' => User::inRandomOrder()->first()->id,
            'is_default' => $this->faker->boolean(50),
        ];
    }
}
