<?php

namespace Database\Factories;

use App\Models\AnnualGoal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonthlyGoalFactory extends Factory
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
            'annual_goals_id' => AnnualGoal::inRandomOrder()->first()->id,
            'month' => $this->faker->monthName
        ];
    }
}
