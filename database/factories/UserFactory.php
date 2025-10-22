<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'verification_code' => null,
            'password' => bcrypt('12345678'), // password
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
                'verification_code' => $this->faker->numberBetween(100000, 999999),

            ];
        });
    }
}
