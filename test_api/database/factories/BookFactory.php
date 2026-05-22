<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title'   => $this->faker->sentence(3),
            'author'  => $this->faker->name(),
            'summary' => $this->faker->paragraph(2),
            'isbn'    => $this->faker->numerify('#############'),
        ];
    }
}
