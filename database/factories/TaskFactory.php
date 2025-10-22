<?php

namespace Database\Factories;

use App\Models\ListModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id, // cria usuário aleatório
            'list_id' => ListModel::inRandomOrder()->first()->id, // cria lista aleatória
            'designation' => $this->faker->sentence(3), // título da tarefa
            'completed' => $this->faker->boolean(30), // 30% de chance de estar concluída
            'has_due_date' => $this->faker->boolean(70), // 70% de chance de ter prazo
            'due_date' => function (array $attributes) {
                return $attributes['has_due_date']
                    ? $this->faker->dateTimeBetween('now', '+1 month')
                    : null;
            },
            'has_reminder' => $this->faker->boolean(50), // 50% de chance de ter lembrete
            'reminder_datetime' => function (array $attributes) {
                return $attributes['has_reminder'] && $attributes['has_due_date']
                    ? $this->faker->dateTimeBetween('now', $attributes['due_date'])
                    : null;
            },
            'has_frequency' => $this->faker->boolean(40), // 40% de chance de frequência
            'frequency_days' => function (array $attributes) {
                return $attributes['has_frequency']
                    ? $this->faker->randomElements([1, 2, 3, 4, 5, 6, 7], $this->faker->numberBetween(1, 7))
                    : [];
            },
        ];
    }
}
